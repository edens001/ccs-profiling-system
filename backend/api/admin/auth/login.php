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

// Log for debugging
error_log("Admin login attempt: " . $input);

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
        'message' => 'Username and password are required'
    ]);
    exit;
}

try {
    // Check if username is email or username
    $is_email = filter_var($data->username, FILTER_VALIDATE_EMAIL);
    
    // Query to get user with admin details
    if ($is_email) {
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
                    a.id as admin_id,
                    a.admin_level,
                    a.department
                  FROM users u
                  LEFT JOIN admins a ON u.id = a.user_id
                  WHERE u.email = :username 
                  AND u.role IN ('admin', 'dean', 'dept_chair', 'secretary')";
    } else {
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
                    a.id as admin_id,
                    a.admin_level,
                    a.department
                  FROM users u
                  LEFT JOIN admins a ON u.id = a.user_id
                  WHERE u.username = :username 
                  AND u.role IN ('admin', 'dean', 'dept_chair', 'secretary')";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $data->username);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
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
            'message' => 'Invalid username or password'
        ]);
        exit;
    }
    
    // Generate JWT token
    $token_data = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'admin_level' => $user['admin_level']
    ];
    
    $token = $jwt->generate($token_data);
    
    // Get admin level display name
    $admin_level_display = '';
    switch($user['admin_level']) {
        case 'Dean':
            $admin_level_display = 'Dean';
            break;
        case 'Department Chair':
            $admin_level_display = 'Department Chair';
            break;
        case 'Secretary':
            $admin_level_display = 'Secretary';
            break;
        default:
            $admin_level_display = 'Administrator';
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'name' => trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']),
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
            'admin_level' => $user['admin_level'],
            'admin_level_display' => $admin_level_display,
            'department' => $user['department']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in admin login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
} catch (Exception $e) {
    error_log("General error in admin login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>