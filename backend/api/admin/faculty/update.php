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
if (!$user_data || !in_array($user_data['role'], ['admin', 'dean'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get faculty ID from URL parameter
$faculty_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$faculty_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faculty ID is required']);
    exit;
}

// Get PUT data
$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

try {
    $db->beginTransaction();

    // Get user_id from faculty record
    $user_query = "SELECT user_id FROM faculty WHERE id = :faculty_id OR faculty_number = :faculty_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':faculty_id', $faculty_id);
    $user_stmt->execute();

    if ($user_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Faculty member not found']);
        exit;
    }

    $user_row = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $user_row['user_id'];

    // Check if email is being updated and if it already exists
    if (isset($data->email)) {
        $check_email = "SELECT id FROM users WHERE email = :email AND id != :user_id";
        $check_stmt = $db->prepare($check_email);
        $check_stmt->bindParam(':email', $data->email);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }
    }

    // Update users table
    $user_update = "UPDATE users SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    middle_name = :middle_name,
                    email = :email
                    WHERE id = :user_id";
    
    $user_stmt = $db->prepare($user_update);
    $user_stmt->bindParam(':first_name', $data->first_name);
    $user_stmt->bindParam(':last_name', $data->last_name);
    $middle_name = $data->middle_name ?? '';
    $user_stmt->bindParam(':middle_name', $middle_name);
    $user_stmt->bindParam(':email', $data->email);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();

    // Update faculty table
    $faculty_update = "UPDATE faculty SET 
                       department = :department,
                       designation = :designation,
                       specialization = :specialization,
                       contact_number = :contact_number,
                       employment_status = :employment_status,
                       date_hired = :date_hired
                       WHERE id = :faculty_id";
    
    $faculty_stmt = $db->prepare($faculty_update);
    $faculty_stmt->bindParam(':department', $data->department);
    $faculty_stmt->bindParam(':designation', $data->designation);
    $specialization = $data->specialization ?? '';
    $faculty_stmt->bindParam(':specialization', $specialization);
    $faculty_stmt->bindParam(':contact_number', $data->contact_number);
    $faculty_stmt->bindParam(':employment_status', $data->employment_status);
    $faculty_stmt->bindParam(':date_hired', $data->date_hired);
    $faculty_stmt->bindParam(':faculty_id', $faculty_id);
    $faculty_stmt->execute();

    // Update status if provided (admin only)
    if (isset($data->status) && $user_data['role'] === 'admin') {
        $status = ($data->status === 'Active') ? 1 : 0;
        $status_update = "UPDATE users SET is_active = :status WHERE id = :user_id";
        $status_stmt = $db->prepare($status_update);
        $status_stmt->bindParam(':status', $status);
        $status_stmt->bindParam(':user_id', $user_id);
        $status_stmt->execute();
    }

    $db->commit();

    // Fetch updated data
    $fetch_query = "SELECT f.*, u.first_name, u.last_name, u.middle_name, u.email, u.is_active
                    FROM faculty f
                    JOIN users u ON f.user_id = u.id
                    WHERE f.id = :faculty_id";
    
    $fetch_stmt = $db->prepare($fetch_query);
    $fetch_stmt->bindParam(':faculty_id', $faculty_id);
    $fetch_stmt->execute();
    $updated = $fetch_stmt->fetch(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Faculty member updated successfully',
        'data' => [
            'id' => $updated['id'],
            'faculty_number' => $updated['faculty_number'],
            'first_name' => $updated['first_name'],
            'last_name' => $updated['last_name'],
            'email' => $updated['email'],
            'department' => $updated['department'],
            'designation' => $updated['designation'],
            'employment_status' => $updated['employment_status'],
            'status' => $updated['is_active'] ? 'Active' : 'Inactive'
        ]
    ]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update faculty: ' . $e->getMessage()
    ]);
}
?>