<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
$token = str_replace('Bearer ', '', $auth_header);

$user_data = $jwt->validate($token);
if (!$user_data) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

// Allow admin, dean, dept_chair to view courses
if (!in_array($user_data['role'], ['admin', 'dean', 'dept_chair', 'secretary'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Course ID is required']);
        exit;
    }
    
    try {
        $query = "SELECT c.*, 
                         u.first_name as created_by_first,
                         u.last_name as created_by_last,
                         (SELECT COUNT(*) FROM syllabus WHERE course_id = c.id) as syllabus_count,
                         (SELECT COUNT(*) FROM sections WHERE course_id = c.id) as section_count
                  FROM courses c
                  LEFT JOIN users u ON c.created_by = u.id
                  WHERE c.id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($course) {
            // Format the response
            $response = [
                'success' => true,
                'data' => [
                    'id' => $course['id'],
                    'course_code' => $course['course_code'],
                    'course_name' => $course['course_name'],
                    'department' => $course['department'],
                    'units' => $course['units'],
                    'description' => $course['description'],
                    'is_active' => (bool)$course['is_active'],
                    'syllabus_count' => (int)$course['syllabus_count'],
                    'section_count' => (int)$course['section_count'],
                    'created_at' => $course['created_at'],
                    'updated_at' => $course['updated_at'],
                    'created_by' => $course['created_by_first'] ? 
                                   trim($course['created_by_first'] . ' ' . $course['created_by_last']) : 
                                   'System'
                ]
            ];
            
            echo json_encode($response);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Course not found']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in get.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>