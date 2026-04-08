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
    // GET all faculty with pagination and filters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $department = isset($_GET['department']) ? $_GET['department'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $offset = ($page - 1) * $limit;

    try {
        // Build query with filters
        $count_query = "SELECT COUNT(*) as total FROM faculty f 
                       JOIN users u ON f.user_id = u.id 
                       WHERE 1=1";
        $query = "SELECT f.*, u.first_name, u.last_name, u.middle_name, u.email, u.username,
                  u.profile_picture, u.is_active, u.created_at
                  FROM faculty f 
                  JOIN users u ON f.user_id = u.id 
                  WHERE 1=1";
        
        $params = [];

        if (!empty($search)) {
            $query .= " AND (f.faculty_number LIKE :search 
                        OR u.first_name LIKE :search 
                        OR u.last_name LIKE :search
                        OR u.email LIKE :search)";
            $count_query .= " AND (f.faculty_number LIKE :search 
                            OR u.first_name LIKE :search 
                            OR u.last_name LIKE :search
                            OR u.email LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($department)) {
            $query .= " AND f.department = :department";
            $count_query .= " AND f.department = :department";
            $params[':department'] = $department;
        }

        if (!empty($status)) {
            $active = $status === 'Active' ? 1 : 0;
            $query .= " AND u.is_active = :status";
            $count_query .= " AND u.is_active = :status";
            $params[':status'] = $active;
        }

        // Get total count
        $count_stmt = $db->prepare($count_query);
        foreach ($params as $key => &$val) {
            $count_stmt->bindParam($key, $val);
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get paginated results
        $query .= " ORDER BY f.id DESC LIMIT :offset, :limit";
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the response
        $formatted_faculty = [];
        foreach ($faculty as $member) {
            $formatted_faculty[] = [
                'id' => $member['id'],
                'user_id' => $member['user_id'],
                'faculty_number' => $member['faculty_number'],
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
                'middle_name' => $member['middle_name'],
                'full_name' => trim($member['first_name'] . ' ' . $member['middle_name'] . ' ' . $member['last_name']),
                'email' => $member['email'],
                'department' => $member['department'],
                'designation' => $member['designation'],
                'specialization' => $member['specialization'],
                'contact_number' => $member['contact_number'],
                'employment_status' => $member['employment_status'],
                'date_hired' => $member['date_hired'],
                'profile_picture' => $member['profile_picture'],
                'status' => $member['is_active'] ? 'Active' : 'Inactive',
                'created_at' => $member['created_at']
            ];
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $formatted_faculty,
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
    // CREATE new faculty
    $data = json_decode(file_get_contents("php://input"));

    // Validate required fields
    $required = ['first_name', 'last_name', 'email', 'department', 'designation', 'contact_number', 'employment_status', 'date_hired'];
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

    // Validate email format
    if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]);
        exit;
    }

    // Validate contact number
    if (!preg_match('/^09\d{9}$/', $data->contact_number)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid contact number. Must be 11 digits starting with 09'
        ]);
        exit;
    }

    try {
        $db->beginTransaction();

        // Check if email already exists
        $check_email = "SELECT id FROM users WHERE email = :email";
        $check_stmt = $db->prepare($check_email);
        $check_stmt->bindParam(':email', $data->email);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }

        // Generate username
        $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data->first_name . '.' . $data->last_name));
        $username = $base_username;
        $counter = 1;

        while (true) {
            $check_username = "SELECT id FROM users WHERE username = :username";
            $check_username_stmt = $db->prepare($check_username);
            $check_username_stmt->bindParam(':username', $username);
            $check_username_stmt->execute();

            if ($check_username_stmt->rowCount() == 0) {
                break;
            }
            $username = $base_username . $counter;
            $counter++;
        }

        // Generate faculty number
        $year = date('Y');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $faculty_number = 'FAC-' . $year . '-' . $random;

        // Check if faculty number exists
        while (true) {
            $check_fn = "SELECT id FROM faculty WHERE faculty_number = :fn";
            $check_fn_stmt = $db->prepare($check_fn);
            $check_fn_stmt->bindParam(':fn', $faculty_number);
            $check_fn_stmt->execute();

            if ($check_fn_stmt->rowCount() == 0) {
                break;
            }
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $faculty_number = 'FAC-' . $year . '-' . $random;
        }

        // Default password
        $default_password = password_hash('password123', PASSWORD_DEFAULT);

        // Insert into users table
        $user_query = "INSERT INTO users (username, email, password, first_name, last_name, middle_name, role, is_active, created_at) 
                       VALUES (:username, :email, :password, :first_name, :last_name, :middle_name, 'faculty', 1, NOW())";
        
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindParam(':username', $username);
        $user_stmt->bindParam(':email', $data->email);
        $user_stmt->bindParam(':password', $default_password);
        $user_stmt->bindParam(':first_name', $data->first_name);
        $user_stmt->bindParam(':last_name', $data->last_name);
        $middle_name = $data->middle_name ?? '';
        $user_stmt->bindParam(':middle_name', $middle_name);
        $user_stmt->execute();

        $user_id = $db->lastInsertId();

        // Check if education_background column exists
        $check_column = "SHOW COLUMNS FROM faculty LIKE 'education_background'";
        $check_column_stmt = $db->prepare($check_column);
        $check_column_stmt->execute();
        $column_exists = $check_column_stmt->rowCount() > 0;

        // Prepare education data if it exists
        $education_json = null;
        if (isset($data->education) && !empty($data->education)) {
            // If education is already a JSON string, use it as is
            if (is_string($data->education)) {
                $education_json = $data->education;
            } else {
                // If it's an object, encode it
                $education_json = json_encode($data->education);
            }
        }

        // Insert into faculty table with or without education_background
        if ($column_exists) {
            $faculty_query = "INSERT INTO faculty (user_id, faculty_number, department, designation, specialization, 
                              education_background, contact_number, employment_status, date_hired) 
                              VALUES (:user_id, :faculty_number, :department, :designation, :specialization, 
                              :education_background, :contact_number, :employment_status, :date_hired)";
        } else {
            $faculty_query = "INSERT INTO faculty (user_id, faculty_number, department, designation, specialization, 
                              contact_number, employment_status, date_hired) 
                              VALUES (:user_id, :faculty_number, :department, :designation, :specialization, 
                              :contact_number, :employment_status, :date_hired)";
        }
        
        $faculty_stmt = $db->prepare($faculty_query);
        $faculty_stmt->bindParam(':user_id', $user_id);
        $faculty_stmt->bindParam(':faculty_number', $faculty_number);
        $faculty_stmt->bindParam(':department', $data->department);
        $faculty_stmt->bindParam(':designation', $data->designation);
        $specialization = $data->specialization ?? '';
        $faculty_stmt->bindParam(':specialization', $specialization);
        
        if ($column_exists) {
            $faculty_stmt->bindParam(':education_background', $education_json);
        }
        
        $faculty_stmt->bindParam(':contact_number', $data->contact_number);
        $faculty_stmt->bindParam(':employment_status', $data->employment_status);
        $faculty_stmt->bindParam(':date_hired', $data->date_hired);
        $faculty_stmt->execute();

        $db->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Faculty member created successfully',
            'data' => [
                'id' => $user_id,
                'faculty_id' => $faculty_number,
                'faculty_number' => $faculty_number,
                'username' => $username,
                'name' => $data->first_name . ' ' . $data->last_name,
                'email' => $data->email
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create faculty: ' . $e->getMessage()
        ]);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>