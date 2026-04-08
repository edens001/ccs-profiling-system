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

// Get curriculum ID
$curriculum_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$curriculum_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Curriculum ID is required']);
    exit;
}

try {
    // Check if curriculum exists and has courses
    $check_query = "SELECT c.id, 
                           (SELECT COUNT(*) FROM curriculum_courses WHERE curriculum_id = c.id) as course_count
                    FROM curriculum c 
                    WHERE c.id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $curriculum_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Curriculum not found']);
        exit;
    }

    $curriculum = $check_stmt->fetch(PDO::FETCH_ASSOC);

    // Delete curriculum (cascade will delete curriculum_courses)
    $query = "DELETE FROM curriculum WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $curriculum_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Curriculum deleted successfully',
            'courses_removed' => (int)$curriculum['course_count']
        ]);
    } else {
        throw new Exception('Failed to delete curriculum');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete curriculum: ' . $e->getMessage()
    ]);
}
?>