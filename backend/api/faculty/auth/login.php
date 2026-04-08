<?php
require_once '../../config/database.php';
require_once '../../config/cors.php';
require_once '../../config/jwt.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get POST data
$input = file_get_contents("php://input");
$data = json_decode($input);

error_log("Faculty login attempt: " . $input);

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
        'message' => 'Faculty ID/Email and password are required'
    ]);
    exit;
}

try {
    // Check if input is email or faculty number
    $is_email = filter_var($data->username, FILTER_VALIDATE_EMAIL);
    
    // Query to get faculty with user details
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
                    f.id as faculty_id,
                    f.faculty_number,
                    f.department,
                    f.designation,
                    f.specialization,
                    f.employment_status
                  FROM users u
                  INNER JOIN faculty f ON u.id = f.user_id
                  WHERE u.email = :username AND u.role = 'faculty'";
    } else {
        // Login with faculty number
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
                    f.id as faculty_id,
                    f.faculty_number,
                    f.department,
                    f.designation,
                    f.specialization,
                    f.employment_status
                  FROM users u
                  INNER JOIN faculty f ON u.id = f.user_id
                  WHERE f.faculty_number = :username AND u.role = 'faculty'";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $data->username);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid faculty ID/email or password'
        ]);
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if account is active
    if (!$user['is_active']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Your account has been deactivated. Please contact the administrator.'
        ]);
        exit;
    }
    
    // Verify password
    if (!password_verify($data->password, $user['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid faculty ID/email or password'
        ]);
        exit;
    }
    
    // Generate JWT token
    $token_data = [
        'id' => $user['id'],
        'faculty_id' => $user['faculty_id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'faculty_number' => $user['faculty_number']
    ];
    
    $token = $jwt->generate($token_data);
    
    // Successful login response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'faculty_id' => $user['faculty_id'],
            'faculty_number' => $user['faculty_number'],
            'username' => $user['username'],
            'email' => $user['email'],
            'name' => trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']),
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'middle_name' => $user['middle_name'],
            'role' => $user['role'],
            'department' => $user['department'],
            'designation' => $user['designation'],
            'specialization' => $user['specialization'],
            'employment_status' => $user['employment_status']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in faculty login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
} catch (Exception $e) {
    error_log("General error in faculty login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>