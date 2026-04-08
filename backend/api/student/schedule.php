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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate token
$user_data = $jwt->validate($token);
if (!$user_data || $user_data['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get student ID
    $student_query = "SELECT id, student_number, course, year_level, section, status 
                      FROM students WHERE user_id = :user_id";
    $student_stmt = $db->prepare($student_query);
    $student_stmt->bindParam(':user_id', $user_data['id']);
    $student_stmt->execute();
    
    if ($student_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if student is enrolled
    if ($student['status'] !== 'Enrolled') {
        echo json_encode([
            'success' => true,
            'student' => [
                'student_number' => $student['student_number'],
                'course' => $student['course'],
                'year_level' => $student['year_level'],
                'section' => $student['section']
            ],
            'schedule' => [],
            'statistics' => ['total_courses' => 0, 'total_units' => 0],
            'message' => 'Your account is pending approval'
        ]);
        exit;
    }
    
    // Get filter parameters (allow empty to show all)
    $semester = isset($_GET['semester']) ? $_GET['semester'] : '';
    $academic_year = isset($_GET['year']) ? $_GET['year'] : '';
    
    // Base query - get ALL enrollments for this student
    $schedule_query = "SELECT 
                        sch.id,
                        sch.day_of_week,
                        TIME_FORMAT(sch.time_start, '%h:%i %p') as time_start,
                        TIME_FORMAT(sch.time_end, '%h:%i %p') as time_end,
                        c.course_code,
                        c.course_name,
                        c.units,
                        r.room_code,
                        r.building,
                        CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                        u.email as instructor_email,
                        sec.section_code,
                        sec.year_level,
                        sch.semester,
                        sch.academic_year
                      FROM enrollments e
                      JOIN schedules sch ON e.schedule_id = sch.id
                      JOIN courses c ON sch.course_id = c.id
                      JOIN rooms r ON sch.room_id = r.id
                      JOIN faculty f ON sch.faculty_id = f.id
                      JOIN users u ON f.user_id = u.id
                      JOIN sections sec ON sch.section_id = sec.id
                      WHERE e.student_id = :student_id
                      AND e.status = 'Enrolled'";
    
    $params = [':student_id' => $student['id']];
    
    // Apply filters only if provided
    if (!empty($semester)) {
        $schedule_query .= " AND sch.semester = :semester";
        $params[':semester'] = $semester;
    }
    
    if (!empty($academic_year)) {
        $schedule_query .= " AND sch.academic_year = :academic_year";
        $params[':academic_year'] = $academic_year;
    }
    
    $schedule_query .= " ORDER BY FIELD(sch.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                                sch.time_start";
    
    $schedule_stmt = $db->prepare($schedule_query);
    foreach ($params as $key => &$val) {
        $schedule_stmt->bindParam($key, $val);
    }
    $schedule_stmt->execute();
    
    $schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by day
    $days_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $grouped = [];
    $total_units = 0;
    $unique_courses = [];
    
    foreach ($days_order as $day) {
        $grouped[$day] = [];
    }
    
    foreach ($schedules as $schedule) {
        $day = $schedule['day_of_week'];
        $grouped[$day][] = [
            'id' => $schedule['id'],
            'course_code' => $schedule['course_code'],
            'course_name' => $schedule['course_name'],
            'units' => $schedule['units'],
            'time_start' => $schedule['time_start'],
            'time_end' => $schedule['time_end'],
            'room' => $schedule['room_code'] . ' (' . $schedule['building'] . ')',
            'instructor' => $schedule['instructor_name'],
            'instructor_email' => $schedule['instructor_email'],
            'section' => $schedule['section_code'],
            'year_level' => $schedule['year_level'],
            'semester' => $schedule['semester'],
            'academic_year' => $schedule['academic_year']
        ];
        
        if (!in_array($schedule['course_code'], $unique_courses)) {
            $unique_courses[] = $schedule['course_code'];
            $total_units += $schedule['units'];
        }
    }
    
    // Remove empty days
    foreach ($grouped as $day => $classes) {
        if (empty($classes)) {
            unset($grouped[$day]);
        }
    }
    
    // Get available periods
    $periods_query = "SELECT DISTINCT sch.academic_year, sch.semester
                      FROM enrollments e
                      JOIN schedules sch ON e.schedule_id = sch.id
                      WHERE e.student_id = :student_id
                      ORDER BY sch.academic_year DESC, sch.semester DESC";
    
    $periods_stmt = $db->prepare($periods_query);
    $periods_stmt->bindParam(':student_id', $student['id']);
    $periods_stmt->execute();
    $available_periods = $periods_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'student' => [
            'student_number' => $student['student_number'],
            'course' => $student['course'],
            'year_level' => $student['year_level'],
            'section' => $student['section']
        ],
        'schedule' => $grouped,
        'statistics' => [
            'total_courses' => count($unique_courses),
            'total_units' => $total_units
        ],
        'available_periods' => $available_periods
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>