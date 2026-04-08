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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // GET all syllabi with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    $offset = ($page - 1) * $limit;

    try {
        // Build query
        $count_query = "SELECT COUNT(*) as total FROM syllabus WHERE 1=1";
        $query = "SELECT s.*, 
                         c.course_code, c.course_name,
                         CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                         u.email as instructor_email
                  FROM syllabus s
                  LEFT JOIN courses c ON s.course_id = c.id
                  LEFT JOIN faculty f ON s.faculty_id = f.id
                  LEFT JOIN users u ON f.user_id = u.id
                  WHERE 1=1";
        
        $params = [];

        if (!empty($search)) {
            $query .= " AND (s.title LIKE :search OR c.course_code LIKE :search OR c.course_name LIKE :search)";
            $count_query .= " AND (title LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if ($course_id > 0) {
            $query .= " AND s.course_id = :course_id";
            $count_query .= " AND course_id = :course_id";
            $params[':course_id'] = $course_id;
        }

        // Get total count
        $count_stmt = $db->prepare($count_query);
        foreach ($params as $key => &$val) {
            $count_stmt->bindParam($key, $val);
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get paginated results
        $query .= " ORDER BY s.created_at DESC LIMIT :offset, :limit";
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $syllabi = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $syllabi,
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
    // CREATE new syllabus
    $data = json_decode(file_get_contents("php://input"));

    // Validate required fields
    $required = ['course_id', 'title'];
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

        // Check if course exists
        $check_course = "SELECT id FROM courses WHERE id = :id";
        $check_stmt = $db->prepare($check_course);
        $check_stmt->bindParam(':id', $data->course_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Course not found']);
            exit;
        }

        // Insert syllabus
        $query = "INSERT INTO syllabus (course_id, faculty_id, title, description, objectives, learning_outcomes, grading_system, policies, reference_materials, file_path, created_at, updated_at) 
                  VALUES (:course_id, :faculty_id, :title, :description, :objectives, :learning_outcomes, :grading_system, :policies, :reference_materials, :file_path, NOW(), NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':course_id', $data->course_id);
        $stmt->bindParam(':faculty_id', $data->faculty_id);
        $stmt->bindParam(':title', $data->title);
        $stmt->bindParam(':description', $data->description);
        $stmt->bindParam(':objectives', $data->objectives);
        $stmt->bindParam(':learning_outcomes', $data->learning_outcomes);
        $stmt->bindParam(':grading_system', $data->grading_system);
        $stmt->bindParam(':policies', $data->policies);
        $stmt->bindParam(':reference_materials', $data->reference_materials);
        $stmt->bindParam(':file_path', $data->file_path);
        $stmt->execute();

        $syllabus_id = $db->lastInsertId();

        $db->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Syllabus created successfully',
            'data' => [
                'id' => $syllabus_id,
                'title' => $data->title
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create syllabus: ' . $e->getMessage()
        ]);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>