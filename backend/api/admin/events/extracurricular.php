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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // GET all extracurricular events with pagination and filters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $offset = ($page - 1) * $limit;

    try {
        // Build query with filters
        $count_query = "SELECT COUNT(*) as total FROM events WHERE event_type = 'Extracurricular'";
        $query = "SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) as organizer_name
                  FROM events e
                  LEFT JOIN users u ON e.created_by = u.id
                  WHERE e.event_type = 'Extracurricular'";
        
        $params = [];

        if (!empty($search)) {
            $query .= " AND (e.title LIKE :search OR e.description LIKE :search OR e.venue LIKE :search)";
            $count_query .= " AND (title LIKE :search OR description LIKE :search OR venue LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($status)) {
            if ($status === 'upcoming') {
                $query .= " AND e.event_date >= CURDATE()";
                $count_query .= " AND event_date >= CURDATE()";
            } elseif ($status === 'past') {
                $query .= " AND e.event_date < CURDATE()";
                $count_query .= " AND event_date < CURDATE()";
            } elseif ($status === 'today') {
                $query .= " AND e.event_date = CURDATE()";
                $count_query .= " AND event_date = CURDATE()";
            }
        }

        // Get total count
        $count_stmt = $db->prepare($count_query);
        foreach ($params as $key => &$val) {
            $count_stmt->bindParam($key, $val);
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get paginated results
        $query .= " ORDER BY e.event_date DESC, e.event_time DESC LIMIT :offset, :limit";
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the response
        $formatted_events = [];
        foreach ($events as $event) {
            $formatted_events[] = [
                'id' => $event['id'],
                'title' => $event['title'],
                'description' => $event['description'],
                'event_type' => $event['event_type'],
                'event_date' => $event['event_date'],
                'event_time' => $event['event_time'],
                'venue' => $event['venue'],
                'organizer' => $event['organizer'],
                'organizer_name' => $event['organizer_name'],
                'status' => getEventStatus($event['event_date']),
                'created_at' => $event['created_at']
            ];
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $formatted_events,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }

} elseif ($method === 'POST') {
    // CREATE new extracurricular event
    $data = json_decode(file_get_contents("php://input"));

    // Validate required fields
    $required = ['title', 'event_date', 'venue'];
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

    try {
        $db->beginTransaction();

        // Insert into events table
        $query = "INSERT INTO events (title, description, event_type, event_date, event_time, venue, organizer, created_by, created_at) 
                  VALUES (:title, :description, 'Extracurricular', :event_date, :event_time, :venue, :organizer, :created_by, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $data->title);
        $stmt->bindParam(':description', $data->description);
        $stmt->bindParam(':event_date', $data->event_date);
        $stmt->bindParam(':event_time', $data->event_time);
        $stmt->bindParam(':venue', $data->venue);
        $stmt->bindParam(':organizer', $data->organizer);
        $stmt->bindParam(':created_by', $user_data['id']);
        $stmt->execute();

        $event_id = $db->lastInsertId();

        $db->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Extracurricular event created successfully',
            'data' => [
                'id' => $event_id,
                'title' => $data->title,
                'event_date' => $data->event_date
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create event: ' . $e->getMessage()
        ]);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
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