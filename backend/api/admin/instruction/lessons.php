<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    header("Access-Control-Allow-Origin: http://localhost:8081");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400"); // 24 hours cache

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    } else {
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    }
    
    http_response_code(200);
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

// Now include other files
require_once '../../config/database.php';
require_once '../../config/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get token from header
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
$token = str_replace('Bearer ', '', $auth_header);

// Validate token
$user_data = $jwt->validate($token);
if (!$user_data) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

// Check if user has proper role
if (!in_array($user_data['role'], ['admin', 'dean', 'dept_chair', 'faculty'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Check if created_by column exists
function column_exists($db, $table, $column) {
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE :column");
        $stmt->execute([':column' => $column]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

$has_created_by = column_exists($db, 'lessons', 'created_by');

if ($method === 'GET') {
    // GET all lessons with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    $syllabus_id = isset($_GET['syllabus_id']) ? (int)$_GET['syllabus_id'] : 0;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : '';
    $offset = ($page - 1) * $limit;

    try {
        // Check if lessons table exists
        $tables_check = $db->query("SHOW TABLES LIKE 'lessons'");
        if ($tables_check->rowCount() === 0) {
            // Return empty array if table doesn't exist
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => 0,
                    'pages' => 0
                ]
            ]);
            exit;
        }
        
        // Build query based on whether created_by column exists
        $count_query = "SELECT COUNT(*) as total FROM lessons l WHERE 1=1";
        
        if ($has_created_by) {
            $query = "SELECT l.*, 
                         c.course_code, c.course_name,
                         s.title as syllabus_title,
                         CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                  FROM lessons l
                  LEFT JOIN syllabus s ON l.syllabus_id = s.id
                  LEFT JOIN courses c ON s.course_id = c.id
                  LEFT JOIN users u ON l.created_by = u.id
                  WHERE 1=1";
        } else {
            $query = "SELECT l.*, 
                         c.course_code, c.course_name,
                         s.title as syllabus_title
                  FROM lessons l
                  LEFT JOIN syllabus s ON l.syllabus_id = s.id
                  LEFT JOIN courses c ON s.course_id = c.id
                  WHERE 1=1";
        }
        
        $params = [];

        if ($course_id > 0) {
            $query .= " AND s.course_id = :course_id";
            $count_query .= " AND s.course_id = :course_id";
            $params[':course_id'] = $course_id;
        }

        if ($syllabus_id > 0) {
            $query .= " AND l.syllabus_id = :syllabus_id";
            $count_query .= " AND l.syllabus_id = :syllabus_id";
            $params[':syllabus_id'] = $syllabus_id;
        }

        // Get total count
        $count_stmt = $db->prepare($count_query);
        foreach ($params as $key => $val) {
            $count_stmt->bindValue($key, $val);
        }
        $count_stmt->execute();
        $total_row = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $total = $total_row ? $total_row['total'] : 0;

        // Get paginated results
        if ($sort === 'recent') {
            $query .= " ORDER BY l.created_at DESC LIMIT :offset, :limit";
        } else {
            $query .= " ORDER BY l.week_number ASC, l.created_at DESC LIMIT :offset, :limit";
        }
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $lessons,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => $limit > 0 ? ceil($total / $limit) : 0
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Database error in lessons.php GET: " . $e->getMessage());
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'pages' => 0
            ],
            'error' => $e->getMessage() // Remove in production
        ]);
    }

} elseif ($method === 'POST') {
    // CREATE new lesson
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    // Validate required fields
    $required = ['syllabus_id', 'week_number', 'topic'];
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data->$field) || empty($data->$field)) {
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

        // Check if syllabus exists
        $check_syllabus = "SELECT id FROM syllabus WHERE id = :id";
        $check_stmt = $db->prepare($check_syllabus);
        $check_stmt->bindParam(':id', $data->syllabus_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() === 0) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Syllabus not found']);
            exit;
        }

        // Insert lesson - handle presence of created_by column
        if ($has_created_by) {
            $query = "INSERT INTO lessons (syllabus_id, week_number, topic, objectives, activities, resources, created_by, created_at) 
                      VALUES (:syllabus_id, :week_number, :topic, :objectives, :activities, :resources, :created_by, NOW())";
        } else {
            $query = "INSERT INTO lessons (syllabus_id, week_number, topic, objectives, activities, resources, created_at) 
                      VALUES (:syllabus_id, :week_number, :topic, :objectives, :activities, :resources, NOW())";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':syllabus_id', $data->syllabus_id);
        $stmt->bindParam(':week_number', $data->week_number);
        $stmt->bindParam(':topic', $data->topic);
        $stmt->bindParam(':objectives', $data->objectives);
        $stmt->bindParam(':activities', $data->activities);
        $stmt->bindParam(':resources', $data->resources);
        
        if ($has_created_by) {
            $stmt->bindParam(':created_by', $user_data['id']);
        }
        
        $stmt->execute();

        $lesson_id = $db->lastInsertId();

        $db->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Lesson created successfully',
            'data' => [
                'id' => $lesson_id,
                'topic' => $data->topic,
                'week_number' => $data->week_number
            ]
        ]);

    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Database error in lessons.php POST: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log("General error in lessons.php POST: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create lesson: ' . $e->getMessage()
        ]);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>