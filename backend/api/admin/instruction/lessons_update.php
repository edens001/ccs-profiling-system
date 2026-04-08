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

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

// Get PUT data
$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

try {
    // Check if lesson exists
    $check_query = "SELECT id FROM lessons WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $lesson_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lesson not found']);
        exit;
    }

    // Update lesson
    $query = "UPDATE lessons SET 
              syllabus_id = COALESCE(:syllabus_id, syllabus_id),
              week_number = COALESCE(:week_number, week_number),
              topic = COALESCE(:topic, topic),
              objectives = COALESCE(:objectives, objectives),
              activities = COALESCE(:activities, activities),
              resources = COALESCE(:resources, resources)
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':syllabus_id', $data->syllabus_id);
    $stmt->bindParam(':week_number', $data->week_number);
    $stmt->bindParam(':topic', $data->topic);
    $stmt->bindParam(':objectives', $data->objectives);
    $stmt->bindParam(':activities', $data->activities);
    $stmt->bindParam(':resources', $data->resources);
    $stmt->bindParam(':id', $lesson_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Lesson updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update lesson');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update lesson: ' . $e->getMessage()
    ]);
}
?>