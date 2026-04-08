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
if (!$user_data || !in_array($user_data['role'], ['admin', 'dean'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get syllabus ID
$syllabus_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$syllabus_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Syllabus ID is required']);
    exit;
}

try {
    // Check if syllabus exists and has lessons
    $check_query = "SELECT s.id, 
                           (SELECT COUNT(*) FROM lessons WHERE syllabus_id = s.id) as lesson_count
                    FROM syllabus s 
                    WHERE s.id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $syllabus_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Syllabus not found']);
        exit;
    }

    $syllabus = $check_stmt->fetch(PDO::FETCH_ASSOC);

    // If has lessons, soft delete? Or cascade delete
    // For now, we'll allow deletion (lessons will be deleted via foreign key cascade)

    // Delete syllabus
    $query = "DELETE FROM syllabus WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $syllabus_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Syllabus deleted successfully',
            'lessons_deleted' => (int)$syllabus['lesson_count']
        ]);
    } else {
        throw new Exception('Failed to delete syllabus');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete syllabus: ' . $e->getMessage()
    ]);
}
?>