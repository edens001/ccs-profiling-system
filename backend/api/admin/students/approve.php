<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';
require_once '../../config/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get token from header
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Validate token
$user_data = $jwt->validate($token);
if (!$user_data || !in_array($user_data['role'], ['admin', 'dean', 'dept_chair', 'secretary'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents("php://input"));

if (empty($data->student_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

try {
    // Check if student exists and is pending
    $checkQuery = "SELECT 
                        s.id, 
                        s.status, 
                        s.student_number,
                        s.year_level,
                        s.section,
                        s.course
                  FROM students s 
                  WHERE s.id = :student_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':student_id', $data->student_id);
    $checkStmt->execute();
    $student = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    if ($student['status'] !== 'Pending') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Student is already ' . $student['status']]);
        exit;
    }
    
    $db->beginTransaction();
    
    // 1. Approve student - update status to Enrolled
    $query = "UPDATE students SET status = 'Enrolled', enrolled_at = NOW() WHERE id = :student_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_id', $data->student_id);
    $stmt->execute();
    
    // 2. AUTO-ENROLLMENT: Find ALL schedules that match student's year level and section
    $scheduleQuery = "SELECT 
                        s.id as schedule_id,
                        sec.id as section_id
                      FROM schedules s
                      JOIN sections sec ON s.section_id = sec.id
                      WHERE sec.year_level = :year_level
                      AND sec.section_code = :section";
    
    $scheduleStmt = $db->prepare($scheduleQuery);
    $scheduleStmt->bindParam(':year_level', $student['year_level']);
    $scheduleStmt->bindParam(':section', $student['section']);
    $scheduleStmt->execute();
    
    $matching_schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Auto-enrollment: Found " . count($matching_schedules) . " schedules for Year " . $student['year_level'] . " Section " . $student['section']);
    
    // 3. Enroll student to matching schedules
    $enrolled_count = 0;
    foreach ($matching_schedules as $schedule) {
        // Check if already enrolled
        $checkEnroll = "SELECT id FROM enrollments WHERE student_id = :student_id AND schedule_id = :schedule_id";
        $checkEnrollStmt = $db->prepare($checkEnroll);
        $checkEnrollStmt->bindParam(':student_id', $data->student_id);
        $checkEnrollStmt->bindParam(':schedule_id', $schedule['schedule_id']);
        $checkEnrollStmt->execute();
        
        if ($checkEnrollStmt->rowCount() == 0) {
            $enrollQuery = "INSERT INTO enrollments (student_id, section_id, schedule_id, status) 
                            VALUES (:student_id, :section_id, :schedule_id, 'Enrolled')";
            $enrollStmt = $db->prepare($enrollQuery);
            $enrollStmt->bindParam(':student_id', $data->student_id);
            $enrollStmt->bindParam(':section_id', $schedule['section_id']);
            $enrollStmt->bindParam(':schedule_id', $schedule['schedule_id']);
            $enrollStmt->execute();
            $enrolled_count++;
            error_log("Enrolled to schedule ID: " . $schedule['schedule_id']);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Student approved and enrolled successfully',
        'data' => [
            'student_id' => $student['id'],
            'student_number' => $student['student_number'],
            'status' => 'Enrolled',
            'enrolled_subjects' => $enrolled_count
        ],
        'debug' => [
            'student_year' => $student['year_level'],
            'student_section' => $student['section'],
            'schedules_found' => count($matching_schedules),
            'enrolled_count' => $enrolled_count
        ]
    ]);
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Database error in approve.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("General error in approve.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>