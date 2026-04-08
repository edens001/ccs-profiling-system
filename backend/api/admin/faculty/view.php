<?php
require_once '../../config/database.php';
require_once '../../config/cors.php';
require_once '../../config/jwt.php';

header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get token from header
$headers = apache_request_headers();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Log for debugging
error_log("=== Faculty View API Called ===");
error_log("Token: " . substr($token, 0, 20) . "...");
error_log("Faculty ID: " . (isset($_GET['id']) ? $_GET['id'] : 'Not provided'));

// Validate token
$user_data = $jwt->validate($token);
if (!$user_data) {
    error_log("Token validation failed");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

if (!in_array($user_data['role'], ['admin', 'dean', 'dept_chair'])) {
    error_log("Unauthorized role: " . $user_data['role']);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_log("Invalid method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get faculty ID from URL parameter
$faculty_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$faculty_id) {
    error_log("Faculty ID not provided");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faculty ID is required']);
    exit;
}

try {
    // First, check if education_background column exists
    $check_column_query = "SHOW COLUMNS FROM faculty LIKE 'education_background'";
    $check_column_stmt = $db->prepare($check_column_query);
    $check_column_stmt->execute();
    $column_exists = $check_column_stmt->rowCount() > 0;
    
    error_log("education_background column exists: " . ($column_exists ? 'Yes' : 'No'));

    // Build query dynamically based on column existence
    if ($column_exists) {
        $query = "SELECT f.*, u.first_name, u.last_name, u.middle_name, u.email, u.username,
                         u.profile_picture, u.is_active, u.created_at,
                         f.education_background
                  FROM faculty f 
                  JOIN users u ON f.user_id = u.id 
                  WHERE f.id = :id OR f.faculty_number = :id";
    } else {
        $query = "SELECT f.*, u.first_name, u.last_name, u.middle_name, u.email, u.username,
                         u.profile_picture, u.is_active, u.created_at
                  FROM faculty f 
                  JOIN users u ON f.user_id = u.id 
                  WHERE f.id = :id OR f.faculty_number = :id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $faculty_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        error_log("Faculty not found with ID: " . $faculty_id);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Faculty member not found']);
        exit;
    }

    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Faculty found: ID " . $faculty['id'] . ", Name: " . $faculty['first_name'] . " " . $faculty['last_name']);

    // Parse education background if exists and column exists
    $education = null;
    if ($column_exists && !empty($faculty['education_background'])) {
        $education = json_decode($faculty['education_background'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error for education: " . json_last_error_msg());
            $education = null;
        }
    }

    // Get assigned courses/schedules
    $schedule_query = "SELECT s.id, c.course_code, c.course_name, c.units,
                              s.day_of_week, s.time_start, s.time_end,
                              r.room_code, r.building,
                              sec.section_code, sec.academic_year, sec.semester
                       FROM schedules s
                       JOIN courses c ON s.course_id = c.id
                       JOIN rooms r ON s.room_id = r.id
                       JOIN sections sec ON s.section_id = sec.id
                       WHERE s.faculty_id = :faculty_id
                       ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                                s.time_start";
    
    $schedule_stmt = $db->prepare($schedule_query);
    $schedule_stmt->bindParam(':faculty_id', $faculty['id']);
    $schedule_stmt->execute();
    $schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format schedules
    $formatted_schedules = [];
    foreach ($schedules as $schedule) {
        $formatted_schedules[] = [
            'id' => $schedule['id'],
            'course_code' => $schedule['course_code'],
            'course_name' => $schedule['course_name'],
            'units' => $schedule['units'],
            'day' => $schedule['day_of_week'],
            'time_start' => date('h:i A', strtotime($schedule['time_start'])),
            'time_end' => date('h:i A', strtotime($schedule['time_end'])),
            'room' => $schedule['room_code'] . ' (' . $schedule['building'] . ')',
            'section' => $schedule['section_code'],
            'academic_year' => $schedule['academic_year'],
            'semester' => $schedule['semester']
        ];
    }

    // Get total students handled
    $students_query = "SELECT COUNT(DISTINCT e.student_id) as total_students
                       FROM schedules s
                       JOIN enrollments e ON s.section_id = e.section_id
                       WHERE s.faculty_id = :faculty_id AND e.status = 'Enrolled'";
    
    $students_stmt = $db->prepare($students_query);
    $students_stmt->bindParam(':faculty_id', $faculty['id']);
    $students_stmt->execute();
    $students_result = $students_stmt->fetch(PDO::FETCH_ASSOC);
    $students_count = $students_result ? (int)$students_result['total_students'] : 0;

    // Get publications count (placeholder)
    $publications_count = 0;

    // Format response
    $response = [
        'success' => true,
        'data' => [
            'id' => (int)$faculty['id'],
            'user_id' => (int)$faculty['user_id'],
            'faculty_number' => $faculty['faculty_number'],
            'first_name' => $faculty['first_name'],
            'last_name' => $faculty['last_name'],
            'middle_name' => $faculty['middle_name'] ?? '',
            'full_name' => trim($faculty['first_name'] . ' ' . ($faculty['middle_name'] ?? '') . ' ' . $faculty['last_name']),
            'email' => $faculty['email'],
            'username' => $faculty['username'],
            'department' => $faculty['department'],
            'designation' => $faculty['designation'],
            'specialization' => $faculty['specialization'] ?? '',
            'contact_number' => $faculty['contact_number'],
            'employment_status' => $faculty['employment_status'],
            'date_hired' => $faculty['date_hired'],
            'profile_picture' => $faculty['profile_picture'],
            'status' => $faculty['is_active'] ? 'Active' : 'Inactive',
            'created_at' => $faculty['created_at'],
            'education' => $education,
            'schedules' => $formatted_schedules,
            'total_courses' => count($formatted_schedules),
            'total_students' => $students_count,
            'publications_count' => $publications_count
        ]
    ];

    error_log("Sending successful response");
    http_response_code(200);
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("PDO Error in faculty view: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'debug' => $e->getMessage() // Remove in production
    ]);
} catch (Exception $e) {
    error_log("General Error in faculty view: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'debug' => $e->getMessage() // Remove in production
    ]);
}
?>