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
    
    // Get filter parameters
    $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    $section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Base query
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
                sec.section_code,
                c.course_code,
                c.course_name
              FROM students s
              JOIN users u ON s.user_id = u.id
              JOIN enrollments e ON s.id = e.student_id
              JOIN sections sec ON e.section_id = sec.id
              JOIN schedules sch ON sec.id = sch.section_id
              JOIN courses c ON sch.course_id = c.id
              WHERE sch.faculty_id = :faculty_id
              AND e.status = 'Enrolled'";
    
    $params = [':faculty_id' => $faculty['id']];
    
    if ($course_id > 0) {
        $query .= " AND c.id = :course_id";
        $params[':course_id'] = $course_id;
    }
    
    if ($section_id > 0) {
        $query .= " AND sec.id = :section_id";
        $params[':section_id'] = $section_id;
    }
    
    if (!empty($search)) {
        $query .= " AND (s.student_number LIKE :search 
                    OR u.first_name LIKE :search 
                    OR u.last_name LIKE :search
                    OR u.email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $query .= " GROUP BY s.id ORDER BY u.last_name, u.first_name";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique courses and sections for filters
    $filters_query = "SELECT DISTINCT 
                        c.id as course_id,
                        c.course_code,
                        sec.id as section_id,
                        sec.section_code
                      FROM schedules sch
                      JOIN sections sec ON sch.section_id = sec.id
                      JOIN courses c ON sch.course_id = c.id
                      WHERE sch.faculty_id = :faculty_id
                      ORDER BY c.course_code, sec.section_code";
    
    $filters_stmt = $db->prepare($filters_query);
    $filters_stmt->bindParam(':faculty_id', $faculty['id']);
    $filters_stmt->execute();
    $filters = $filters_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $students,
        'filters' => $filters,
        'total' => count($students)
    ]);

} catch (PDOException $e) {
    error_log("Database error in faculty students: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>