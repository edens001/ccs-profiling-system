<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

// TAMA NA ITO - 2 beses lang pataas (auth/ -> student/ -> api/)
require_once '../../config/database.php';
require_once '../../config/cors.php';
require_once '../../config/jwt.php';

// For debugging - check if files exist
$database_path = realpath(__DIR__ . '/../../config/database.php');
$jwt_path = realpath(__DIR__ . '/../../config/jwt.php');

error_log("Login.php - Database path: " . ($database_path ? $database_path : 'NOT FOUND'));
error_log("Login.php - JWT path: " . ($jwt_path ? $jwt_path : 'NOT FOUND'));

if (!$database_path || !$jwt_path) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Configuration files not found. Please check server setup.'
    ]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get POST data
$input = file_get_contents("php://input");
$data = json_decode($input);

error_log("========== STUDENT LOGIN ATTEMPT ==========");
error_log("Raw input: " . $input);
error_log("Current directory: " . __DIR__);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use POST.'
    ]);
    exit;
}

// Validate required fields
if (empty($data->username) || empty($data->password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Student number/email and password are required'
    ]);
    exit;
}

try {
    // Check database connection
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Check if tables exist
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    error_log("Available tables: " . implode(", ", $tables));
    
    if (!in_array('users', $tables) || !in_array('students', $tables)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database tables not found. Please run the SQL setup.'
        ]);
        exit;
    }
    
    // First, check if the input is a student number or email
    $is_email = filter_var($data->username, FILTER_VALIDATE_EMAIL);
    error_log("Login type: " . ($is_email ? "Email" : "Student Number"));
    
    // Query to get user with student details
    if ($is_email) {
        // Login with email
        $query = "SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.password,
                    u.first_name,
                    u.last_name,
                    u.middle_name,
                    u.role,
                    u.is_active,
                    s.id as student_id,
                    s.student_number,
                    s.course,
                    s.year_level,
                    s.section,
                    s.status as enrollment_status
                  FROM users u
                  INNER JOIN students s ON u.id = s.user_id
                  WHERE u.email = :username AND u.role = 'student'";
    } else {
        // Login with student number
        $query = "SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.password,
                    u.first_name,
                    u.last_name,
                    u.middle_name,
                    u.role,
                    u.is_active,
                    s.id as student_id,
                    s.student_number,
                    s.course,
                    s.year_level,
                    s.section,
                    s.status as enrollment_status
                  FROM users u
                  INNER JOIN students s ON u.id = s.user_id
                  WHERE s.student_number = :username AND u.role = 'student'";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $data->username);
    $stmt->execute();
    
    $rowCount = $stmt->rowCount();
    error_log("Query row count: " . $rowCount);
    
    if ($rowCount === 0) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid student number/email or password'
        ]);
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("User found: ID=" . $user['id'] . ", Status=" . $user['enrollment_status']);
    
    // Check if account is active in users table
    if (!$user['is_active']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Your account has been deactivated. Please contact the administrator.'
        ]);
        exit;
    }
    
    // Check enrollment status
    if ($user['enrollment_status'] !== 'Enrolled') {
        $status_message = '';
        switch($user['enrollment_status']) {
            case 'Pending':
                $status_message = 'Your registration is pending approval from the administrator.';
                break;
            case 'Rejected':
                $status_message = 'Your registration has been rejected. Please contact the administrator.';
                break;
            case 'Inactive':
                $status_message = 'Your account is inactive. Please contact the administrator.';
                break;
            default:
                $status_message = 'Your account is not yet enrolled. Please wait for admin approval.';
        }
        
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $status_message,
            'status' => $user['enrollment_status']
        ]);
        exit;
    }
    
    // Verify password
    error_log("Verifying password...");
    if (!password_verify($data->password, $user['password'])) {
        error_log("Password verification failed");
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid student number/email or password'
        ]);
        exit;
    }
    
    error_log("Password verification successful");
    
    // Update last login timestamp
    try {
        $update_query = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':id', $user['id']);
        $update_stmt->execute();
        error_log("Last login updated");
    } catch (PDOException $e) {
        error_log("Failed to update last_login: " . $e->getMessage());
    }
    
    // Generate JWT token
    $token_data = [
        'id' => $user['id'],
        'student_id' => $user['student_id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'student_number' => $user['student_number']
    ];
    
    $token = $jwt->generate($token_data);
    error_log("JWT token generated");
    
    // Successful login response
    $response = [
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'student_id' => $user['student_id'],
            'student_number' => $user['student_number'],
            'username' => $user['username'],
            'email' => $user['email'],
            'name' => trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']),
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'middle_name' => $user['middle_name'],
            'role' => $user['role'],
            'course' => $user['course'],
            'year_level' => $user['year_level'],
            'section' => $user['section'],
            'status' => $user['enrollment_status']
        ]
    ];
    
    error_log("Login successful for user: " . $user['username']);
    error_log("Response: " . json_encode($response));
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("DATABASE ERROR in student login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
} catch (Exception $e) {
    error_log("GENERAL ERROR in student login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>