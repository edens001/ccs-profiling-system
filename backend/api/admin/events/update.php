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
if (!$user_data || !in_array($user_data['role'], ['admin', 'dean', 'dept_chair', 'secretary'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get event ID from URL parameter
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$event_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
    exit;
}

// Get PUT data
$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$required = ['title', 'event_type', 'event_date', 'venue'];
$missing = [];
foreach ($required as $field) {
    if (empty($data->$field)) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields',
        'missing' => $missing
    ]);
    exit;
}

// Validate event type
if (!in_array($data->event_type, ['Curricular', 'Extracurricular'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid event type. Must be Curricular or Extracurricular'
    ]);
    exit;
}

try {
    // Check if event exists
    $check_query = "SELECT id FROM events WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $event_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit;
    }

    // Update event
    $query = "UPDATE events SET 
              title = :title,
              description = :description,
              event_type = :event_type,
              event_date = :event_date,
              event_time = :event_time,
              venue = :venue,
              organizer = :organizer
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $data->title);
    $stmt->bindParam(':description', $data->description);
    $stmt->bindParam(':event_type', $data->event_type);
    $stmt->bindParam(':event_date', $data->event_date);
    $stmt->bindParam(':event_time', $data->event_time);
    $stmt->bindParam(':venue', $data->venue);
    $stmt->bindParam(':organizer', $data->organizer);
    $stmt->bindParam(':id', $event_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => [
                'id' => $event_id,
                'title' => $data->title,
                'event_type' => $data->event_type
            ]
        ]);
    } else {
        throw new Exception('Failed to update event');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update event: ' . $e->getMessage()
    ]);
}
?>