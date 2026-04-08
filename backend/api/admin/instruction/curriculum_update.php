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
if (!$user_data || !in_array($user_data['role'], ['admin', 'dean', 'dept_chair'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

// Get PUT data
$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

try {
    // Check if curriculum exists
    $check_query = "SELECT id FROM curriculum WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $curriculum_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Curriculum not found']);
        exit;
    }

    // Check if new curriculum code already exists (excluding current)
    if (!empty($data->curriculum_code)) {
        $check_code = "SELECT id FROM curriculum WHERE curriculum_code = :code AND id != :id";
        $check_code_stmt = $db->prepare($check_code);
        $check_code_stmt->bindParam(':code', $data->curriculum_code);
        $check_code_stmt->bindParam(':id', $curriculum_id);
        $check_code_stmt->execute();

        if ($check_code_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Curriculum code already exists']);
            exit;
        }
    }

    // Update curriculum
    $query = "UPDATE curriculum SET 
              program = COALESCE(:program, program),
              curriculum_code = COALESCE(:curriculum_code, curriculum_code),
              effective_year = COALESCE(:effective_year, effective_year),
              total_units = COALESCE(:total_units, total_units),
              description = COALESCE(:description, description),
              status = COALESCE(:status, status)
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':program', $data->program);
    $stmt->bindParam(':curriculum_code', $data->curriculum_code);
    $stmt->bindParam(':effective_year', $data->effective_year);
    $stmt->bindParam(':total_units', $data->total_units);
    $stmt->bindParam(':description', $data->description);
    $stmt->bindParam(':status', $data->status);
    $stmt->bindParam(':id', $curriculum_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Curriculum updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update curriculum');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update curriculum: ' . $e->getMessage()
    ]);
}
?>