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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

try {
    // Get event details
    $query = "SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) as creator_name,
                     u.username as creator_username
              FROM events e
              LEFT JOIN users u ON e.created_by = u.id
              WHERE e.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $event_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit;
    }

    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    // Format response
    $response = [
        'success' => true,
        'data' => [
            'id' => $event['id'],
            'title' => $event['title'],
            'description' => $event['description'],
            'event_type' => $event['event_type'],
            'event_date' => $event['event_date'],
            'event_time' => $event['event_time'],
            'venue' => $event['venue'],
            'organizer' => $event['organizer'],
            'creator_name' => $event['creator_name'],
            'creator_username' => $event['creator_username'],
            'status' => getEventStatus($event['event_date']),
            'created_at' => $event['created_at']
        ]
    ];

    http_response_code(200);
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Helper function to determine event status
function getEventStatus($event_date) {
    $today = date('Y-m-d');
    if ($event_date < $today) {
        return 'Past';
    } elseif ($event_date == $today) {
        return 'Today';
    } else {
        return 'Upcoming';
    }
}
?>