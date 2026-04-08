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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // GET all curriculum with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $program = isset($_GET['program']) ? $_GET['program'] : '';
    $offset = ($page - 1) * $limit;

    try {
        // Build query
        $count_query = "SELECT COUNT(*) as total FROM curriculum WHERE 1=1";
        $query = "SELECT c.*, 
                         CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                  FROM curriculum c
                  LEFT JOIN users u ON c.created_by = u.id
                  WHERE 1=1";
        
        $params = [];

        if (!empty($search)) {
            $query .= " AND (c.program LIKE :search OR c.curriculum_code LIKE :search)";
            $count_query .= " AND (program LIKE :search OR curriculum_code LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($program)) {
            $query .= " AND c.program = :program";
            $count_query .= " AND program = :program";
            $params[':program'] = $program;
        }

        // Get total count
        $count_stmt = $db->prepare($count_query);
        foreach ($params as $key => &$val) {
            $count_stmt->bindParam($key, $val);
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get paginated results
        $query .= " ORDER BY c.created_at DESC LIMIT :offset, :limit";
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $curriculum = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $curriculum,
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
    // CREATE new curriculum
    $data = json_decode(file_get_contents("php://input"));

    // Validate required fields
    $required = ['program', 'curriculum_code', 'effective_year', 'total_units'];
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

        // Check if curriculum code already exists
        $check_query = "SELECT id FROM curriculum WHERE curriculum_code = :code";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':code', $data->curriculum_code);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Curriculum code already exists']);
            exit;
        }

        // Insert curriculum
        $query = "INSERT INTO curriculum (program, curriculum_code, effective_year, total_units, description, status, created_by, created_at) 
                  VALUES (:program, :curriculum_code, :effective_year, :total_units, :description, :status, :created_by, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':program', $data->program);
        $stmt->bindParam(':curriculum_code', $data->curriculum_code);
        $stmt->bindParam(':effective_year', $data->effective_year);
        $stmt->bindParam(':total_units', $data->total_units);
        $stmt->bindParam(':description', $data->description);
        $status = $data->status ?? 'Draft';
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':created_by', $user_data['id']);
        $stmt->execute();

        $curriculum_id = $db->lastInsertId();

        $db->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Curriculum created successfully',
            'data' => [
                'id' => $curriculum_id,
                'curriculum_code' => $data->curriculum_code,
                'program' => $data->program
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create curriculum: ' . $e->getMessage()
        ]);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>