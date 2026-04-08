<?php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../config/jwt.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get token from header
$headers = apache_request_headers();
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
    
    // Get all classes with student counts
    $classes_query = "SELECT 
                        s.id,
                        c.course_code,
                        c.course_name,
                        c.units,
                        sec.section_code,
                        sec.academic_year,
                        sec.semester,
                        r.room_code,
                        r.building,
                        s.day_of_week,
                        s.time_start,
                        s.time_end,
                        (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = sec.id AND e.status = 'Enrolled') as enrolled_students,
                        (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = sec.id) as total_students,
                        (SELECT COUNT(*) FROM grades g WHERE g.schedule_id = s.id) as graded_count
                    FROM schedules s
                    JOIN courses c ON s.course_id = c.id
                    JOIN sections sec ON s.section_id = sec.id
                    JOIN rooms r ON s.room_id = r.id
                    WHERE s.faculty_id = :faculty_id
                    ORDER BY sec.academic_year DESC, sec.semester DESC, s.day_of_week, s.time_start";
    
    $classes_stmt = $db->prepare($classes_query);
    $classes_stmt->bindParam(':faculty_id', $faculty['id']);
    $classes_stmt->execute();
    
    $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $classes,
        'total' => count($classes)
    ]);

} catch (PDOException $e) {
    error_log("Database error in faculty classes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>