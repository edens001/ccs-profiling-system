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
if (!$user_data || !in_array($user_data['role'], ['admin', 'dean', 'dept_chair', 'faculty'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get syllabus ID
$syllabus_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$syllabus_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Syllabus ID is required']);
    exit;
}

try {
    // Get syllabus details with course and instructor info
    $query = "SELECT s.*, 
                     c.course_code, c.course_name, c.units, c.department,
                     f.id as faculty_id,
                     CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                     u.email as instructor_email,
                     u.username as instructor_username
              FROM syllabus s
              LEFT JOIN courses c ON s.course_id = c.id
              LEFT JOIN faculty f ON s.faculty_id = f.id
              LEFT JOIN users u ON f.user_id = u.id
              WHERE s.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $syllabus_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Syllabus not found']);
        exit;
    }

    $syllabus = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get lessons for this syllabus
    $lessons_query = "SELECT * FROM lessons 
                      WHERE syllabus_id = :syllabus_id 
                      ORDER BY week_number ASC";
    $lessons_stmt = $db->prepare($lessons_query);
    $lessons_stmt->bindParam(':syllabus_id', $syllabus_id);
    $lessons_stmt->execute();
    $lessons = $lessons_stmt->fetchAll(PDO::FETCH_ASSOC);

    $syllabus['lessons'] = $lessons;
    $syllabus['total_weeks'] = count($lessons);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $syllabus
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>