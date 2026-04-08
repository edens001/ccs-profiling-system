<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
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

$method = $_SERVER['REQUEST_METHOD'];

// Handle different HTTP methods
switch ($method) {
    case 'GET':
        handleGet($db, $user_data);
        break;
    case 'POST':
        handlePost($db, $user_data);
        break;
    case 'PUT':
        handlePut($db, $user_data);
        break;
    case 'DELETE':
        handleDelete($db, $user_data);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function handleGet($db, $user_data) {
    // Allow admin, dean, dept_chair, secretary to view courses
    if (!in_array($user_data['role'], ['admin', 'dean', 'dept_chair', 'secretary'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        return;
    }
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    
    try {
        $query = "SELECT c.*, 
                         (SELECT COUNT(*) FROM syllabus WHERE course_id = c.id) as syllabus_count,
                         (SELECT COUNT(*) FROM sections WHERE course_id = c.id) as section_count
                  FROM courses c
                  ORDER BY c.course_code ASC 
                  LIMIT :limit";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $formatted_courses = array_map(function($course) {
            return [
                'id' => $course['id'],
                'course_code' => $course['course_code'],
                'course_name' => $course['course_name'],
                'department' => $course['department'],
                'units' => $course['units'],
                'description' => $course['description'],
                'is_active' => (bool)$course['is_active'],
                'syllabus_count' => (int)$course['syllabus_count'],
                'section_count' => (int)$course['section_count'],
                'created_at' => $course['created_at']
            ];
        }, $courses);
        
        $count_query = "SELECT COUNT(*) as total FROM courses";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $formatted_courses,
            'total' => (int)$total
        ]);

    } catch (PDOException $e) {
        error_log("Database error in courses GET: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function handlePost($db, $user_data) {
    // Only admin and dean can add courses
    if (!in_array($user_data['role'], ['admin', 'dean'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        return;
    }
    
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    $required = ['course_code', 'course_name', 'department', 'units'];
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
        // Check if course code already exists
        $check_query = "SELECT id FROM courses WHERE course_code = :course_code";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':course_code', $data->course_code);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Course code already exists']);
            return;
        }
        
        $query = "INSERT INTO courses (
            course_code, 
            course_name, 
            department, 
            units, 
            description, 
            is_active,
            created_by,
            created_at
        ) VALUES (
            :course_code, 
            :course_name, 
            :department, 
            :units, 
            :description, 
            :is_active,
            :created_by,
            NOW()
        )";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':course_code', $data->course_code);
        $stmt->bindParam(':course_name', $data->course_name);
        $stmt->bindParam(':department', $data->department);
        $stmt->bindParam(':units', $data->units);
        $description = $data->description ?? '';
        $stmt->bindParam(':description', $description);
        $is_active = isset($data->is_active) ? (int)$data->is_active : 1;
        $stmt->bindParam(':is_active', $is_active);
        $stmt->bindParam(':created_by', $user_data['id']);
        $stmt->execute();
        
        $course_id = $db->lastInsertId();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Course created successfully',
            'data' => [
                'id' => $course_id,
                'course_code' => $data->course_code,
                'course_name' => $data->course_name
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in courses POST: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

// Add handlePut and handleDelete functions here if needed