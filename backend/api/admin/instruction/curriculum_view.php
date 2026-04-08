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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get curriculum ID
$curriculum_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$curriculum_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Curriculum ID is required']);
    exit;
}

try {
    // Get curriculum details
    $query = "SELECT c.*, 
                     CONCAT(u.first_name, ' ', u.last_name) as created_by_name
              FROM curriculum c
              LEFT JOIN users u ON c.created_by = u.id
              WHERE c.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $curriculum_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Curriculum not found']);
        exit;
    }

    $curriculum = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get courses in this curriculum grouped by year and semester
    $courses_query = "SELECT cc.*, c.course_code, c.course_name, c.units, c.description
                      FROM curriculum_courses cc
                      JOIN courses c ON cc.course_id = c.id
                      WHERE cc.curriculum_id = :curriculum_id
                      ORDER BY cc.year_level, cc.semester, cc.course_code";
    
    $courses_stmt = $db->prepare($courses_query);
    $courses_stmt->bindParam(':curriculum_id', $curriculum_id);
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group courses by year and semester
    $grouped_courses = [];
    foreach ($courses as $course) {
        $year = $course['year_level'];
        $sem = $course['semester'];
        if (!isset($grouped_courses[$year])) {
            $grouped_courses[$year] = [];
        }
        if (!isset($grouped_courses[$year][$sem])) {
            $grouped_courses[$year][$sem] = [];
        }
        $grouped_courses[$year][$sem][] = $course;
    }

    $response = [
        'success' => true,
        'data' => [
            'id' => $curriculum['id'],
            'program' => $curriculum['program'],
            'curriculum_code' => $curriculum['curriculum_code'],
            'effective_year' => $curriculum['effective_year'],
            'total_units' => $curriculum['total_units'],
            'description' => $curriculum['description'],
            'status' => $curriculum['status'],
            'created_by_name' => $curriculum['created_by_name'],
            'created_at' => $curriculum['created_at'],
            'updated_at' => $curriculum['updated_at'],
            'courses' => $courses,
            'grouped_courses' => $grouped_courses,
            'total_courses' => count($courses)
        ]
    ];

    http_response_code(200);
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>