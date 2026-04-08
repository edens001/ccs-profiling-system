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

if ($method === 'POST') {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
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
        exit;
    }
    
    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]);
        exit;
    }
    
    // Validate contact number
    if (!preg_match('/^09\d{9}$/', $data['contact_number'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid contact number. Must be 11 digits starting with 09'
        ]);
        exit;
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
            exit;
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
        
        // Insert student record
        $student_query = "INSERT INTO students (
            user_id, student_number, course, year_level, section, contact_number, 
            address, birth_date, gender, guardian_name, guardian_contact, status, enrolled_at
        ) VALUES (
            :user_id, :student_number, :course, :year_level, :section, :contact_number,
            :address, :birth_date, :gender, :guardian_name, :guardian_contact, 'Enrolled', NOW()
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
            'message' => 'Student added successfully',
            'data' => [
                'student_number' => $student_number,
                'username' => $username,
                'user_id' => $user_id
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
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>