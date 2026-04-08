<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Public endpoint - no token required for courses list
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    
    try {
        // Simple query to get all courses
        $query = "SELECT id, course_code, course_name FROM courses ORDER BY course_name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check if may laman
        if (count($courses) > 0) {
            echo json_encode([
                'success' => true,
                'data' => $courses
            ]);
        } else {
            // Kung walang laman, magbigay ng default courses
            $default_courses = [
                ['id' => 1, 'course_code' => 'BSCS', 'course_name' => 'BS Computer Science'],
                ['id' => 2, 'course_code' => 'BSIT', 'course_name' => 'BS Information Technology'],
                ['id' => 3, 'course_code' => 'BSIS', 'course_name' => 'BS Information Systems']
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $default_courses
            ]);
        }

    } catch (PDOException $e) {
        // Log error pero magbigay parin ng default courses
        error_log("Courses list error: " . $e->getMessage());
        
        $default_courses = [
            ['id' => 1, 'course_code' => 'BSCS', 'course_name' => 'BS Computer Science'],
            ['id' => 2, 'course_code' => 'BSIT', 'course_name' => 'BS Information Technology'],
            ['id' => 3, 'course_code' => 'BSIS', 'course_name' => 'BS Information Systems']
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $default_courses
        ]);
    }

} else {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Method not allowed'
    ]);
}
?>