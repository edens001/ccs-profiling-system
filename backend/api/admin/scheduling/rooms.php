<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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

if (!in_array($user_data['role'], ['admin', 'dean', 'dept_chair'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($db);
        break;
    case 'POST':
        handlePost($db, $user_data);
        break;
    case 'PUT':
        handlePut($db);
        break;
    case 'DELETE':
        handleDelete($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function handleGet($db) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    try {
        if ($id > 0) {
            // Get single room
            $query = "SELECT * FROM rooms WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($room) {
                echo json_encode(['success' => true, 'data' => $room]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Room not found']);
            }
        } else {
            // Get all rooms
            $query = "SELECT *, 
                             (SELECT COUNT(*) FROM schedules WHERE room_id = rooms.id) as schedule_count
                      FROM rooms 
                      ORDER BY building ASC, room_code ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $rooms]);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function handlePost($db, $user_data) {
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    $required = ['room_code', 'building', 'capacity'];
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
        return;
    }
    
    try {
        // Check if room code already exists
        $check_query = "SELECT id FROM rooms WHERE room_code = :room_code";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':room_code', $data->room_code);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Room code already exists']);
            return;
        }
        
        $query = "INSERT INTO rooms (room_code, building, capacity, is_active, created_by, created_at) 
                  VALUES (:room_code, :building, :capacity, :is_active, :created_by, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':room_code', $data->room_code);
        $stmt->bindParam(':building', $data->building);
        $stmt->bindParam(':capacity', $data->capacity);
        $is_active = isset($data->is_active) ? $data->is_active : 1;
        $stmt->bindParam(':is_active', $is_active);
        $stmt->bindParam(':created_by', $user_data['id']);
        $stmt->execute();
        
        $room_id = $db->lastInsertId();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Room created successfully',
            'id' => $room_id
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function handlePut($db) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Room ID is required']);
        return;
    }
    
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    try {
        // Check if room exists
        $check_query = "SELECT id FROM rooms WHERE id = :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':id', $id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Room not found']);
            return;
        }
        
        // Check for duplicate room code (excluding current)
        if (!empty($data->room_code)) {
            $dup_query = "SELECT id FROM rooms WHERE room_code = :room_code AND id != :id";
            $dup_stmt = $db->prepare($dup_query);
            $dup_stmt->bindParam(':room_code', $data->room_code);
            $dup_stmt->bindParam(':id', $id);
            $dup_stmt->execute();
            
            if ($dup_stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Room code already exists']);
                return;
            }
        }
        
        $query = "UPDATE rooms SET 
                    room_code = :room_code,
                    building = :building,
                    capacity = :capacity,
                    is_active = :is_active,
                    updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':room_code', $data->room_code);
        $stmt->bindParam(':building', $data->building);
        $stmt->bindParam(':capacity', $data->capacity);
        $stmt->bindParam(':is_active', $data->is_active);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Room updated successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function handleDelete($db) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Room ID is required']);
        return;
    }
    
    try {
        // Check if room has schedules
        $check_schedules = "SELECT id FROM schedules WHERE room_id = :id LIMIT 1";
        $sched_stmt = $db->prepare($check_schedules);
        $sched_stmt->bindParam(':id', $id);
        $sched_stmt->execute();
        
        if ($sched_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot delete room with existing schedules']);
            return;
        }
        
        $query = "DELETE FROM rooms WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Room deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}
?>