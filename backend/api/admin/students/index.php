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

switch ($method) {
    case 'GET':
        handleGet($db);
        break;
    case 'POST':
        handlePost($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGet($db) {
    // GET all students with pagination and filters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $course = isset($_GET['course']) ? $_GET['course'] : '';
    $year = isset($_GET['year']) ? $_GET['year'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $offset = ($page - 1) * $limit;

    try {
        // Build query with filters
        $count_query = "SELECT COUNT(*) as total FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE 1=1";
        
        $query = "SELECT 
                    s.id,
                    s.user_id,
                    s.student_number,
                    s.course as course_name,
                    s.year_level,
                    s.section,
                    s.contact_number,
                    s.address,
                    s.birth_date,
                    s.gender,
                    s.guardian_name,
                    s.guardian_contact,
                    s.status,
                    s.enrolled_at,
                    u.first_name,
                    u.last_name,
                    u.middle_name,
                    u.email,
                    u.username,
                    u.profile_picture,
                    u.is_active,
                    u.created_at
                  FROM students s 
                  JOIN users u ON s.user_id = u.id 
                  WHERE 1=1";
        
        $params = [];

        if (!empty($search)) {
            $query .= " AND (s.student_number LIKE :search 
                        OR u.first_name LIKE :search 
                        OR u.last_name LIKE :search)";
            $count_query .= " AND (s.student_number LIKE :search 
                            OR u.first_name LIKE :search 
                            OR u.last_name LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($course)) {
            $query .= " AND s.course LIKE :course";
            $count_query .= " AND s.course LIKE :course";
            $params[':course'] = "%$course%";
        }

        if (!empty($year)) {
            $query .= " AND s.year_level = :year";
            $count_query .= " AND s.year_level = :year";
            $params[':year'] = $year;
        }

        if (!empty($status)) {
            $query .= " AND s.status = :status";
            $count_query .= " AND s.status = :status";
            $params[':status'] = $status;
        }

        // Get total count
        $count_stmt = $db->prepare($count_query);
        foreach ($params as $key => &$val) {
            $count_stmt->bindParam($key, $val);
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get paginated results
        $query .= " ORDER BY s.id DESC LIMIT :offset, :limit";
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the response
        $formatted_students = [];
        foreach ($students as $student) {
            $formatted_students[] = [
                'id' => $student['id'],
                'user_id' => $student['user_id'],
                'student_number' => $student['student_number'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'middle_name' => $student['middle_name'],
                'full_name' => trim($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']),
                'email' => $student['email'],
                'course' => $student['course_name'],
                'year_level' => $student['year_level'],
                'section' => $student['section'],
                'contact_number' => $student['contact_number'],
                'address' => $student['address'],
                'birth_date' => $student['birth_date'],
                'gender' => $student['gender'],
                'guardian_name' => $student['guardian_name'],
                'guardian_contact' => $student['guardian_contact'],
                'profile_picture' => $student['profile_picture'],
                'status' => $student['status'] ?? 'Pending',
                'enrolled_at' => $student['enrolled_at'],
                'is_active' => $student['is_active'],
                'created_at' => $student['created_at']
            ];
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $formatted_students,
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
}

function handlePost($db) {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    // Validate required fields
    $missing_fields = [];
    if (empty($data['first_name'])) $missing_fields[] = 'first_name';
    if (empty($data['last_name'])) $missing_fields[] = 'last_name';
    if (empty($data['email'])) $missing_fields[] = 'email';
    if (empty($data['contact_number'])) $missing_fields[] = 'contact_number';
    if (empty($data['address'])) $missing_fields[] = 'address';
    if (empty($data['birth_date'])) $missing_fields[] = 'birth_date';
    if (empty($data['gender'])) $missing_fields[] = 'gender';
    if (empty($data['course'])) $missing_fields[] = 'course';
    if (empty($data['year_level'])) $missing_fields[] = 'year_level';
    if (empty($data['section'])) $missing_fields[] = 'section';
    
    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields',
            'missing_fields' => $missing_fields
        ]);
        return;
    }
    
    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]);
        return;
    }
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Check if email already exists
        $check_email = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->execute([$data['email']]);
        if ($check_email->rowCount() > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Email already exists'
            ]);
            return;
        }
        
        // Generate username from first and last name
        $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data['first_name'] . '.' . $data['last_name']));
        $username = $base_username;
        $counter = 1;
        
        while (true) {
            $check_username = $db->prepare("SELECT id FROM users WHERE username = ?");
            $check_username->execute([$username]);
            if ($check_username->rowCount() == 0) {
                break;
            }
            $username = $base_username . $counter;
            $counter++;
        }
        
        // Generate student number
        $year = date('Y');
        $random = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $student_number = $year . '-' . $random;
        
        // Create user account with default password
        $default_password = 'password123';
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        
        $user_query = "INSERT INTO users (
            username, email, password, first_name, last_name, middle_name, role, is_active
        ) VALUES (
            :username, :email, :password, :first_name, :last_name, :middle_name, 'student', 1
        )";
        
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute([
            ':username' => $username,
            ':email' => $data['email'],
            ':password' => $hashed_password,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':middle_name' => $data['middle_name'] ?? ''
        ]);
        
        $user_id = $db->lastInsertId();
        
        // =============================================
        // UPDATED: Insert into students table with PENDING status
        // =============================================
        $student_query = "INSERT INTO students (
            user_id, student_number, course, year_level, section, contact_number, 
            address, birth_date, gender, guardian_name, guardian_contact, status
        ) VALUES (
            :user_id, :student_number, :course, :year_level, :section, :contact_number,
            :address, :birth_date, :gender, :guardian_name, :guardian_contact, 'Pending'
        )";
        
        $student_stmt = $db->prepare($student_query);
        $student_stmt->execute([
            ':user_id' => $user_id,
            ':student_number' => $student_number,
            ':course' => $data['course'],
            ':year_level' => $data['year_level'],
            ':section' => $data['section'],
            ':contact_number' => $data['contact_number'],
            ':address' => $data['address'],
            ':birth_date' => $data['birth_date'],
            ':gender' => $data['gender'],
            ':guardian_name' => $data['guardian_name'] ?? '',
            ':guardian_contact' => $data['guardian_contact'] ?? ''
        ]);
        
        $db->commit();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Student added successfully. Account is pending approval.',
            'data' => [
                'student_number' => $student_number,
                'username' => $username,
                'user_id' => $user_id,
                'status' => 'Pending'
            ]
        ]);
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred. Please try again.'
        ]);
    }
}
?>