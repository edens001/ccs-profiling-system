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
require_once '../config/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get token from header
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

error_log("========== FACULTY SCHEDULE API CALLED ==========");
error_log("Token: " . ($token ? 'Present' : 'Missing'));

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate token
$user_data = $jwt->validate($token);
if (!$user_data) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

if ($user_data['role'] !== 'faculty') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Faculty role required.']);
    exit;
}

try {
    // Get faculty ID
    $faculty_query = "SELECT id FROM faculty WHERE user_id = :user_id";
    $faculty_stmt = $db->prepare($faculty_query);
    $faculty_stmt->bindParam(':user_id', $user_data['id']);
    $faculty_stmt->execute();
    
    if ($faculty_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Faculty not found']);
        exit;
    }
    
    $faculty = $faculty_stmt->fetch(PDO::FETCH_ASSOC);
    $faculty_id = $faculty['id'];
    
    // Get filter parameters
    $semester = isset($_GET['semester']) ? $_GET['semester'] : '1';
    $academic_year = isset($_GET['year']) ? $_GET['year'] : '2025-2026';
    
    // Get faculty's schedule with year_level
    $schedule_query = "SELECT 
                            s.id,
                            s.day_of_week,
                            TIME_FORMAT(s.time_start, '%h:%i %p') as time_start,
                            TIME_FORMAT(s.time_end, '%h:%i %p') as time_end,
                            c.id as course_id,
                            c.course_code,
                            c.course_name,
                            c.units,
                            sec.section_code,
                            sec.year_level as section_year_level,
                            s.year_level as schedule_year_level,
                            r.room_code,
                            r.building,
                            (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = sec.id AND e.status = 'Enrolled') as student_count
                        FROM schedules s
                        JOIN courses c ON s.course_id = c.id
                        JOIN sections sec ON s.section_id = sec.id
                        JOIN rooms r ON s.room_id = r.id
                        WHERE s.faculty_id = :faculty_id
                        AND s.semester = :semester
                        AND s.academic_year = :academic_year
                        ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                                 s.time_start";
    
    $schedule_stmt = $db->prepare($schedule_query);
    $schedule_stmt->bindParam(':faculty_id', $faculty_id);
    $schedule_stmt->bindParam(':semester', $semester);
    $schedule_stmt->bindParam(':academic_year', $academic_year);
    $schedule_stmt->execute();
    
    $schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by day of week
    $days_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $grouped_schedules = [];
    
    foreach ($days_order as $day) {
        $grouped_schedules[$day] = [];
    }
    
    $total_units = 0;
    $unique_courses = [];
    
    foreach ($schedules as $schedule) {
        $day = $schedule['day_of_week'];
        $year_level = $schedule['schedule_year_level'] ?: $schedule['section_year_level'];
        
        $grouped_schedules[$day][] = [
            'id' => $schedule['id'],
            'course_code' => $schedule['course_code'],
            'course_name' => $schedule['course_name'],
            'units' => $schedule['units'],
            'section' => $schedule['section_code'],
            'year_level' => $year_level,
            'time_start' => $schedule['time_start'],
            'time_end' => $schedule['time_end'],
            'room' => $schedule['room_code'] . ' (' . $schedule['building'] . ')',
            'students' => $schedule['student_count']
        ];
        
        // Calculate total units (unique courses only)
        if (!in_array($schedule['course_id'], $unique_courses)) {
            $unique_courses[] = $schedule['course_id'];
            $total_units += $schedule['units'];
        }
    }
    
    // Remove empty days
    foreach ($grouped_schedules as $day => $classes) {
        if (empty($classes)) {
            unset($grouped_schedules[$day]);
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'faculty' => [
            'id' => $faculty_id
        ],
        'schedule' => $grouped_schedules,
        'total_classes' => count($schedules),
        'total_units' => $total_units,
        'current_period' => [
            'semester' => $semester,
            'academic_year' => $academic_year
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error in faculty schedule: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in faculty schedule: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>