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

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get student ID from URL parameter
$student_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$student_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
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

    // Get user_id from student record
    $user_query = "SELECT user_id FROM students WHERE id = :student_id OR student_number = :student_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':student_id', $student_id);
    $user_stmt->execute();

    if ($user_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found']);
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

    // Update students table
    $student_update = "UPDATE students SET 
                       course = :course,
                       year_level = :year_level,
                       section = :section,
                       contact_number = :contact_number,
                       address = :address,
                       birth_date = :birth_date,
                       gender = :gender,
                       guardian_name = :guardian_name,
                       guardian_contact = :guardian_contact
                       WHERE id = :student_id";
    
    $student_stmt = $db->prepare($student_update);
    $student_stmt->bindParam(':course', $data->course);
    $student_stmt->bindParam(':year_level', $data->year_level);
    $section = $data->section ?? 'A';
    $student_stmt->bindParam(':section', $section);
    $student_stmt->bindParam(':contact_number', $data->contact_number);
    $student_stmt->bindParam(':address', $data->address);
    $student_stmt->bindParam(':birth_date', $data->birth_date);
    $student_stmt->bindParam(':gender', $data->gender);
    $guardian_name = $data->guardian_name ?? '';
    $student_stmt->bindParam(':guardian_name', $guardian_name);
    $guardian_contact = $data->guardian_contact ?? '';
    $student_stmt->bindParam(':guardian_contact', $guardian_contact);
    $student_stmt->bindParam(':student_id', $student_id);
    $student_stmt->execute();

    // Update status if provided (admin only)
    if (isset($data->status) && in_array($user_data['role'], ['admin', 'dean'])) {
        $status = ($data->status === 'Active') ? 1 : 0;
        $status_update = "UPDATE users SET is_active = :status WHERE id = :user_id";
        $status_stmt = $db->prepare($status_update);
        $status_stmt->bindParam(':status', $status);
        $status_stmt->bindParam(':user_id', $user_id);
        $status_stmt->execute();
    }

    $db->commit();

    // Fetch updated data
    $fetch_query = "SELECT s.*, u.first_name, u.last_name, u.middle_name, u.email, u.is_active
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    WHERE s.id = :student_id";
    
    $fetch_stmt = $db->prepare($fetch_query);
    $fetch_stmt->bindParam(':student_id', $student_id);
    $fetch_stmt->execute();
    $updated = $fetch_stmt->fetch(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Student updated successfully',
        'data' => [
            'id' => $updated['id'],
            'student_number' => $updated['student_number'],
            'first_name' => $updated['first_name'],
            'last_name' => $updated['last_name'],
            'email' => $updated['email'],
            'course' => $updated['course'],
            'year_level' => $updated['year_level'],
            'section' => $updated['section'],
            'status' => $updated['is_active'] ? 'Active' : 'Inactive'
        ]
    ]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update student: ' . $e->getMessage()
    ]);
}
?>