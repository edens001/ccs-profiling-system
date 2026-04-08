<?php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../config/jwt.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get token from header
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate token
$user_data = $jwt->validate($token);
if (!$user_data || $user_data['role'] !== 'faculty') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get faculty ID
    $faculty_query = "SELECT id FROM faculty WHERE user_id = :user_id";
    $faculty_stmt = $db->prepare($faculty_query);
    $faculty_stmt->bindParam(':user_id', $user_data['id']);
    $faculty_stmt->execute();
    
    if ($faculty_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Faculty not found']);
        exit;
    }
    
    $faculty = $faculty_stmt->fetch(PDO::FETCH_ASSOC);
    $faculty_id = $faculty['id'];
    
    // Simplified query to get students
    $query = "SELECT 
                s.id,
                s.student_number,
                u.first_name,
                u.last_name,
                u.middle_name,
                u.email,
                s.course,
                s.year_level,
                s.section,
                s.contact_number,
                c.course_code,
                c.course_name
              FROM schedules sch
              JOIN enrollments e ON sch.id = e.schedule_id
              JOIN students s ON e.student_id = s.id
              JOIN users u ON s.user_id = u.id
              JOIN courses c ON sch.course_id = c.id
              WHERE sch.faculty_id = :faculty_id
              AND e.status = 'Enrolled'
              AND s.status = 'Enrolled'
              ORDER BY u.last_name, u.first_name";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':faculty_id', $faculty_id);
    $stmt->execute();
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique courses for filters
    $courses_query = "SELECT DISTINCT c.id as course_id, c.course_code
                      FROM schedules sch
                      JOIN courses c ON sch.course_id = c.id
                      WHERE sch.faculty_id = :faculty_id";
    
    $courses_stmt = $db->prepare($courses_query);
    $courses_stmt->bindParam(':faculty_id', $faculty_id);
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique sections for filters
    $sections_query = "SELECT DISTINCT sec.id as section_id, sec.section_code
                       FROM schedules sch
                       JOIN sections sec ON sch.section_id = sec.id
                       WHERE sch.faculty_id = :faculty_id";
    
    $sections_stmt = $db->prepare($sections_query);
    $sections_stmt->bindParam(':faculty_id', $faculty_id);
    $sections_stmt->execute();
    $sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $students,
        'filters' => [
            'courses' => $courses,
            'sections' => $sections
        ],
        'total' => count($students)
    ]);

} catch (PDOException $e) {
    error_log("Database error in faculty students: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
?>