<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

require_once '../../../config/database.php';
require_once '../../../config/jwt.php';

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

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get enrollments for a student
            $student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
            
            if (!$student_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Student ID required']);
                exit;
            }
            
            $query = "SELECT 
                        e.id,
                        e.status,
                        e.enrolled_at,
                        sch.id as schedule_id,
                        c.course_code,
                        c.course_name,
                        c.units,
                        sec.section_code,
                        sec.year_level,
                        r.room_code,
                        r.building,
                        CONCAT(u.first_name, ' ', u.last_name) as faculty_name,
                        sch.day_of_week,
                        sch.time_start,
                        sch.time_end,
                        sch.semester,
                        sch.academic_year
                      FROM enrollments e
                      JOIN schedules sch ON e.schedule_id = sch.id
                      JOIN courses c ON sch.course_id = c.id
                      JOIN sections sec ON sch.section_id = sec.id
                      JOIN rooms r ON sch.room_id = r.id
                      JOIN faculty f ON sch.faculty_id = f.id
                      JOIN users u ON f.user_id = u.id
                      WHERE e.student_id = :student_id
                      ORDER BY sch.semester DESC, sch.academic_year DESC, c.course_code";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $enrollments,
                'total' => count($enrollments)
            ]);
            break;
            
        case 'POST':
            // Create new enrollment
            $data = json_decode(file_get_contents("php://input"));
            
            if (empty($data->student_id) || empty($data->schedule_ids) || !is_array($data->schedule_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Student ID and schedule IDs required']);
                exit;
            }
            
            $db->beginTransaction();
            
            try {
                $success_count = 0;
                $errors = [];
                
                foreach ($data->schedule_ids as $schedule_id) {
                    // Check if already enrolled
                    $checkQuery = "SELECT id FROM enrollments WHERE student_id = :student_id AND schedule_id = :schedule_id";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->bindParam(':student_id', $data->student_id);
                    $checkStmt->bindParam(':schedule_id', $schedule_id);
                    $checkStmt->execute();
                    
                    if ($checkStmt->rowCount() > 0) {
                        $errors[] = "Schedule ID $schedule_id already enrolled";
                        continue;
                    }
                    
                    // Get section_id from schedule
                    $sectionQuery = "SELECT section_id FROM schedules WHERE id = :schedule_id";
                    $sectionStmt = $db->prepare($sectionQuery);
                    $sectionStmt->bindParam(':schedule_id', $schedule_id);
                    $sectionStmt->execute();
                    $section = $sectionStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$section) {
                        $errors[] = "Schedule ID $schedule_id not found";
                        continue;
                    }
                    
                    // Create enrollment
                    $insertQuery = "INSERT INTO enrollments (student_id, section_id, schedule_id, status) 
                                    VALUES (:student_id, :section_id, :schedule_id, 'Enrolled')";
                    $insertStmt = $db->prepare($insertQuery);
                    $insertStmt->bindParam(':student_id', $data->student_id);
                    $insertStmt->bindParam(':section_id', $section['section_id']);
                    $insertStmt->bindParam(':schedule_id', $schedule_id);
                    $insertStmt->execute();
                    
                    $success_count++;
                }
                
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => "Enrolled $success_count subjects",
                    'errors' => $errors,
                    'enrolled_count' => $success_count
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        case 'DELETE':
            // Drop enrollment
            $enrollment_id = isset($_GET['id']) ? $_GET['id'] : null;
            
            if (!$enrollment_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Enrollment ID required']);
                exit;
            }
            
            $query = "DELETE FROM enrollments WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $enrollment_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Enrollment dropped successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Database error in enrollments: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("General error in enrollments: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>