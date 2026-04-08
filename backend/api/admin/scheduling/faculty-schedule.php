<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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

$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
$token = str_replace('Bearer ', '', $auth_header);

$user_data = $jwt->validate($token);
if (!$user_data) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

if (!in_array($user_data['role'], ['admin', 'dean', 'dept_chair'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $faculty_id = isset($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : 0;
    $semester = isset($_GET['semester']) ? $_GET['semester'] : '1';
    $academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : date('Y') . '-' . (date('Y') + 1);
    
    if ($faculty_id === 0) {
        // Return faculty list only
        try {
            $query = "SELECT f.id, 
                             CONCAT(u.first_name, ' ', u.last_name) as name,
                             f.department,
                             f.designation
                      FROM faculty f
                      JOIN users u ON f.user_id = u.id
                      WHERE u.is_active = 1
                      ORDER BY u.last_name ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'faculty' => $faculty
            ]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    } else {
        // Return schedule for specific faculty
        try {
            $query = "SELECT 
                        s.id,
                        c.course_code,
                        c.course_name,
                        sec.section_code,
                        r.room_code,
                        s.day_of_week,
                        s.time_start,
                        s.time_end,
                        s.year_level,
                        s.semester,
                        s.academic_year
                      FROM schedules s
                      JOIN courses c ON s.course_id = c.id
                      JOIN sections sec ON s.section_id = sec.id
                      JOIN rooms r ON s.room_id = r.id
                      WHERE s.faculty_id = :faculty_id
                      AND s.semester = :semester
                      AND s.academic_year = :academic_year
                      ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                               s.time_start ASC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':faculty_id', $faculty_id);
            $stmt->bindParam(':semester', $semester);
            $stmt->bindParam(':academic_year', $academic_year);
            $stmt->execute();
            
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get faculty info
            $faculty_query = "SELECT 
                                CONCAT(u.first_name, ' ', u.last_name) as name,
                                f.department,
                                f.designation
                              FROM faculty f
                              JOIN users u ON f.user_id = u.id
                              WHERE f.id = :faculty_id";
            
            $faculty_stmt = $db->prepare($faculty_query);
            $faculty_stmt->bindParam(':faculty_id', $faculty_id);
            $faculty_stmt->execute();
            $faculty_info = $faculty_stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'faculty' => $faculty_info,
                'schedules' => $schedules,
                'total_hours' => count($schedules) * 3
            ]);
            
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    }
    
} elseif ($method === 'POST') {
    // Add new schedule
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }
    
    // Required fields including year_level
    $required = ['faculty_id', 'course_id', 'section_id', 'room_id', 'day_of_week', 'time_start', 'time_end', 'semester', 'academic_year', 'year_level'];
    $missing = [];
    foreach ($required as $field) {
        if (empty($data->$field)) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields',
            'missing' => $missing
        ]);
        exit;
    }
    
    try {
        // Check for schedule conflict
        $conflict_query = "SELECT id FROM schedules 
                           WHERE faculty_id = :faculty_id
                           AND day_of_week = :day_of_week
                           AND semester = :semester
                           AND academic_year = :academic_year
                           AND (
                               (time_start <= :time_start AND time_end > :time_start)
                               OR (time_start < :time_end AND time_end >= :time_end)
                               OR (time_start >= :time_start AND time_end <= :time_end)
                           )";
        
        $conflict_stmt = $db->prepare($conflict_query);
        $conflict_stmt->bindParam(':faculty_id', $data->faculty_id);
        $conflict_stmt->bindParam(':day_of_week', $data->day_of_week);
        $conflict_stmt->bindParam(':semester', $data->semester);
        $conflict_stmt->bindParam(':academic_year', $data->academic_year);
        $conflict_stmt->bindParam(':time_start', $data->time_start);
        $conflict_stmt->bindParam(':time_end', $data->time_end);
        $conflict_stmt->execute();
        
        if ($conflict_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Schedule conflict detected']);
            exit;
        }
        
        // Insert schedule with year_level
        $query = "INSERT INTO schedules (
                    faculty_id, course_id, section_id, room_id, 
                    day_of_week, time_start, time_end, semester, academic_year, year_level, created_by
                  ) VALUES (
                    :faculty_id, :course_id, :section_id, :room_id,
                    :day_of_week, :time_start, :time_end, :semester, :academic_year, :year_level, :created_by
                  )";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':faculty_id', $data->faculty_id);
        $stmt->bindParam(':course_id', $data->course_id);
        $stmt->bindParam(':section_id', $data->section_id);
        $stmt->bindParam(':room_id', $data->room_id);
        $stmt->bindParam(':day_of_week', $data->day_of_week);
        $stmt->bindParam(':time_start', $data->time_start);
        $stmt->bindParam(':time_end', $data->time_end);
        $stmt->bindParam(':semester', $data->semester);
        $stmt->bindParam(':academic_year', $data->academic_year);
        $stmt->bindParam(':year_level', $data->year_level);
        $stmt->bindParam(':created_by', $user_data['id']);
        $stmt->execute();
        
        $schedule_id = $db->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule added successfully',
            'id' => $schedule_id
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
    
} elseif ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Schedule ID is required']);
        exit;
    }
    
    try {
        $query = "DELETE FROM schedules WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>