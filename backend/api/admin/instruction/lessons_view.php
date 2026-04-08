<?php
require_once '../../config/database.php';
require_once '../../config/cors.php';
require_once '../../config/jwt.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get token from header
$headers = apache_request_headers();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Validate token
$user_data = $jwt->validate($token);
if (!$user_data || !in_array($user_data['role'], ['admin', 'dean', 'dept_chair', 'faculty'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get lesson ID
$lesson_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$lesson_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lesson ID is required']);
    exit;
}

try {
    // Get lesson details with syllabus and course info
    $query = "SELECT l.*, 
                     s.title as syllabus_title,
                     s.course_id,
                     c.course_code, c.course_name,
                     CONCAT(u.first_name, ' ', u.last_name) as created_by_name
              FROM lessons l
              LEFT JOIN syllabus s ON l.syllabus_id = s.id
              LEFT JOIN courses c ON s.course_id = c.id
              LEFT JOIN users u ON l.created_by = u.id
              WHERE l.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $lesson_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lesson not found']);
        exit;
    }

    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $lesson
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>