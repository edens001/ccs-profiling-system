<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';
require_once '../../config/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get token from header
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
$token = str_replace('Bearer ', '', $auth_header);

// Log for debugging
error_log("========== SYLLABUS UPDATE API CALLED ==========");
error_log("Token: " . ($token ? 'Present' : 'Missing'));

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use PUT.'
    ]);
    exit;
}

// Validate token
$user_data = $jwt->validate($token);

if (!$user_data) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid or expired token'
    ]);
    exit;
}

// Check if user has admin role
if (!in_array($user_data['role'], ['admin', 'dean', 'dept_chair', 'secretary'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Admin privileges required.'
    ]);
    exit;
}

// Get syllabus ID from URL parameter
$syllabus_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$syllabus_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Syllabus ID is required'
    ]);
    exit;
}

// Get PUT data
$input = file_get_contents("php://input");
$data = json_decode($input);

// Log received data
error_log("Updating syllabus ID: " . $syllabus_id);
error_log("Received data: " . $input);

// Validate required fields
if (empty($data->course_id) || empty($data->title)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Course ID and Title are required fields'
    ]);
    exit;
}

try {
    // Check if syllabus exists
    $check_query = "SELECT id FROM syllabus WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $syllabus_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Syllabus not found'
        ]);
        exit;
    }

    // Check if course exists
    $course_check = "SELECT id FROM courses WHERE id = :course_id";
    $course_stmt = $db->prepare($course_check);
    $course_stmt->bindParam(':course_id', $data->course_id);
    $course_stmt->execute();

    if ($course_stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Selected course does not exist'
        ]);
        exit;
    }

    // Check if faculty exists (if provided)
    if (!empty($data->faculty_id)) {
        $faculty_check = "SELECT id FROM faculty WHERE id = :faculty_id";
        $faculty_stmt = $db->prepare($faculty_check);
        $faculty_stmt->bindParam(':faculty_id', $data->faculty_id);
        $faculty_stmt->execute();

        if ($faculty_stmt->rowCount() === 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Selected faculty does not exist'
            ]);
            exit;
        }
    }

    // Update syllabus
    $query = "UPDATE syllabus SET 
                course_id = :course_id,
                faculty_id = :faculty_id,
                title = :title,
                description = :description,
                objectives = :objectives,
                learning_outcomes = :learning_outcomes,
                grading_system = :grading_system,
                policies = :policies,
                reference_materials = :reference_materials,
                file_path = :file_path,
                updated_at = NOW()
              WHERE id = :id";

    $stmt = $db->prepare($query);

    // Handle empty values
    $faculty_id = !empty($data->faculty_id) ? $data->faculty_id : null;
    $description = !empty($data->description) ? $data->description : null;
    $objectives = !empty($data->objectives) ? $data->objectives : null;
    $learning_outcomes = !empty($data->learning_outcomes) ? $data->learning_outcomes : null;
    $grading_system = !empty($data->grading_system) ? $data->grading_system : null;
    $policies = !empty($data->policies) ? $data->policies : null;
    $reference_materials = !empty($data->reference_materials) ? $data->reference_materials : null;
    $file_path = !empty($data->file_path) ? $data->file_path : null;

    // Bind parameters
    $stmt->bindParam(':course_id', $data->course_id);
    $stmt->bindParam(':faculty_id', $faculty_id);
    $stmt->bindParam(':title', $data->title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':objectives', $objectives);
    $stmt->bindParam(':learning_outcomes', $learning_outcomes);
    $stmt->bindParam(':grading_system', $grading_system);
    $stmt->bindParam(':policies', $policies);
    $stmt->bindParam(':reference_materials', $reference_materials);
    $stmt->bindParam(':file_path', $file_path);
    $stmt->bindParam(':id', $syllabus_id);

    if ($stmt->execute()) {
        // Get the updated syllabus data
        $select_query = "SELECT 
                            s.*,
                            c.course_code,
                            c.course_name,
                            c.units,
                            c.department,
                            CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                            u.email as instructor_email
                        FROM syllabus s
                        LEFT JOIN courses c ON s.course_id = c.id
                        LEFT JOIN faculty f ON s.faculty_id = f.id
                        LEFT JOIN users u ON f.user_id = u.id
                        WHERE s.id = :id";

        $select_stmt = $db->prepare($select_query);
        $select_stmt->bindParam(':id', $syllabus_id);
        $select_stmt->execute();
        $updated_syllabus = $select_stmt->fetch(PDO::FETCH_ASSOC);

        // Get lesson count
        $lesson_query = "SELECT COUNT(*) as total FROM lessons WHERE syllabus_id = :syllabus_id";
        $lesson_stmt = $db->prepare($lesson_query);
        $lesson_stmt->bindParam(':syllabus_id', $syllabus_id);
        $lesson_stmt->execute();
        $lesson_count = $lesson_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Add lesson count to response
        $updated_syllabus['total_lessons'] = (int)$lesson_count;

        // Log successful update
        error_log("Syllabus updated successfully. ID: " . $syllabus_id);

        echo json_encode([
            'success' => true,
            'message' => 'Syllabus updated successfully',
            'data' => $updated_syllabus
        ]);
    } else {
        throw new Exception('Failed to update syllabus');
    }

} catch (PDOException $e) {
    error_log("Database error in syllabus update: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
} catch (Exception $e) {
    error_log("General error in syllabus update: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>