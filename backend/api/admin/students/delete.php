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
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Admin or Dean only.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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

    // Check if student has enrollments
    $check_enrollments = "SELECT COUNT(*) as count FROM enrollments WHERE student_id = :student_id";
    $check_stmt = $db->prepare($check_enrollments);
    $check_stmt->bindParam(':student_id', $student_id);
    $check_stmt->execute();
    $enrollment_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($enrollment_count > 0) {
        // Soft delete - just deactivate the user
        $update_query = "UPDATE users SET is_active = 0 WHERE id = :user_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':user_id', $user_id);
        $update_stmt->execute();

        $db->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Student has been deactivated due to existing enrollments',
            'deactivated' => true
        ]);
    } else {
        // Hard delete - remove all records
        // Delete from students table (will cascade to enrollments/grades if any)
        $delete_student = "DELETE FROM students WHERE id = :student_id";
        $delete_stmt = $db->prepare($delete_student);
        $delete_stmt->bindParam(':student_id', $student_id);
        $delete_stmt->execute();

        // Delete from users table
        $delete_user = "DELETE FROM users WHERE id = :user_id";
        $delete_user_stmt = $db->prepare($delete_user);
        $delete_user_stmt->bindParam(':user_id', $user_id);
        $delete_user_stmt->execute();

        $db->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Student permanently deleted successfully',
            'deactivated' => false
        ]);
    }

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete student: ' . $e->getMessage()
    ]);
}
?>