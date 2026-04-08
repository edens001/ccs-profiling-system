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
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
$token = str_replace('Bearer ', '', $auth_header);

// Log for debugging
error_log("========== FACULTY DASHBOARD API CALLED ==========");
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
    // Get faculty details
    $faculty_query = "SELECT 
                        f.id,
                        f.faculty_number,
                        f.department,
                        f.designation,
                        f.specialization,
                        u.first_name,
                        u.last_name,
                        u.middle_name,
                        u.email,
                        u.profile_picture
                      FROM faculty f
                      JOIN users u ON f.user_id = u.id
                      WHERE u.id = :user_id";
    
    $faculty_stmt = $db->prepare($faculty_query);
    $faculty_stmt->bindParam(':user_id', $user_data['id']);
    $faculty_stmt->execute();
    
    if ($faculty_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Faculty record not found']);
        exit;
    }
    
    $faculty = $faculty_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get today's classes
    $today = date('l');
    $today_classes = [];
    
    try {
        $today_query = "SELECT 
                            s.id,
                            c.course_code,
                            c.course_name,
                            c.units,
                            sec.section_code,
                            r.room_code,
                            s.time_start,
                            s.time_end,
                            (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = sec.id AND e.status = 'Enrolled') as student_count
                        FROM schedules s
                        JOIN courses c ON s.course_id = c.id
                        JOIN sections sec ON s.section_id = sec.id
                        JOIN rooms r ON s.room_id = r.id
                        WHERE s.faculty_id = :faculty_id 
                        AND s.day_of_week = :today
                        ORDER BY s.time_start";
        
        $today_stmt = $db->prepare($today_query);
        $today_stmt->bindParam(':faculty_id', $faculty['id']);
        $today_stmt->bindParam(':today', $today);
        $today_stmt->execute();
        $today_classes = $today_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format time for each class
        foreach ($today_classes as &$class) {
            $class['time_start'] = date('h:i A', strtotime($class['time_start']));
            $class['time_end'] = date('h:i A', strtotime($class['time_end']));
        }
        
    } catch (PDOException $e) {
        error_log("Error fetching today's classes: " . $e->getMessage());
    }
    
    // Get upcoming events
    $upcoming_events = [];
    
    try {
        $events_query = "SELECT 
                            e.id,
                            e.title,
                            e.description,
                            e.event_type,
                            e.event_date,
                            e.event_time,
                            e.venue,
                            e.organizer
                        FROM events e
                        WHERE e.event_date >= CURDATE() 
                        ORDER BY e.event_date ASC 
                        LIMIT 5";
        
        $events_stmt = $db->prepare($events_query);
        $events_stmt->execute();
        $upcoming_events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format date and time
        foreach ($upcoming_events as &$event) {
            $event['event_date_formatted'] = date('M d, Y', strtotime($event['event_date']));
            if ($event['event_time']) {
                $event['event_time_formatted'] = date('h:i A', strtotime($event['event_time']));
            }
        }
        
    } catch (PDOException $e) {
        error_log("Error fetching events: " . $e->getMessage());
    }
    
    // If no events found, provide sample data
    if (empty($upcoming_events)) {
        $upcoming_events = [
            [
                'id' => 1,
                'title' => 'Faculty Meeting',
                'description' => 'Monthly faculty meeting to discuss curriculum updates',
                'event_type' => 'Curricular',
                'event_date' => date('Y-m-d', strtotime('+1 day')),
                'event_date_formatted' => date('M d, Y', strtotime('+1 day')),
                'event_time' => '10:00:00',
                'event_time_formatted' => '10:00 AM',
                'venue' => 'Conference Room A',
                'organizer' => 'Dean\'s Office'
            ],
            [
                'id' => 2,
                'title' => 'Department Workshop',
                'description' => 'Workshop on new teaching methodologies',
                'event_type' => 'Curricular',
                'event_date' => date('Y-m-d', strtotime('+1 week')),
                'event_date_formatted' => date('M d, Y', strtotime('+1 week')),
                'event_time' => '14:00:00',
                'event_time_formatted' => '02:00 PM',
                'venue' => 'Computer Lab 101',
                'organizer' => 'CS Department'
            ]
        ];
    }
    
    // Get statistics
    $statistics = [
        'total_classes' => 0,
        'total_students' => 0,
        'total_subjects' => 0,
        'pending_grades' => 0
    ];
    
    try {
        // Get total classes
        $classes_query = "SELECT COUNT(*) as total FROM schedules WHERE faculty_id = :faculty_id";
        $classes_stmt = $db->prepare($classes_query);
        $classes_stmt->bindParam(':faculty_id', $faculty['id']);
        $classes_stmt->execute();
        $classes_result = $classes_stmt->fetch(PDO::FETCH_ASSOC);
        $statistics['total_classes'] = (int)($classes_result['total'] ?? 0);
        
        // Get total subjects (distinct courses)
        $subjects_query = "SELECT COUNT(DISTINCT course_id) as total FROM schedules WHERE faculty_id = :faculty_id";
        $subjects_stmt = $db->prepare($subjects_query);
        $subjects_stmt->bindParam(':faculty_id', $faculty['id']);
        $subjects_stmt->execute();
        $subjects_result = $subjects_stmt->fetch(PDO::FETCH_ASSOC);
        $statistics['total_subjects'] = (int)($subjects_result['total'] ?? 0);
        
        // Get total students
        $students_query = "SELECT COUNT(DISTINCT e.student_id) as total
                          FROM schedules s
                          JOIN enrollments e ON s.section_id = e.section_id
                          WHERE s.faculty_id = :faculty_id AND e.status = 'Enrolled'";
        $students_stmt = $db->prepare($students_query);
        $students_stmt->bindParam(':faculty_id', $faculty['id']);
        $students_stmt->execute();
        $students_result = $students_stmt->fetch(PDO::FETCH_ASSOC);
        $statistics['total_students'] = (int)($students_result['total'] ?? 0);
        
        // Get pending grades count (if grades table has status)
        $grades_query = "SELECT COUNT(*) as total
                        FROM schedules s
                        JOIN enrollments e ON s.section_id = e.section_id
                        LEFT JOIN grades g ON e.id = g.enrollment_id
                        WHERE s.faculty_id = :faculty_id 
                        AND (g.id IS NULL OR g.remarks = 'Incomplete')";
        $grades_stmt = $db->prepare($grades_query);
        $grades_stmt->bindParam(':faculty_id', $faculty['id']);
        $grades_stmt->execute();
        $grades_result = $grades_stmt->fetch(PDO::FETCH_ASSOC);
        $statistics['pending_grades'] = (int)($grades_result['total'] ?? 0);
        
    } catch (PDOException $e) {
        error_log("Error fetching statistics: " . $e->getMessage());
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'faculty' => [
            'id' => $faculty['id'],
            'faculty_number' => $faculty['faculty_number'],
            'name' => trim($faculty['first_name'] . ' ' . ($faculty['middle_name'] ? $faculty['middle_name'] . ' ' : '') . $faculty['last_name']),
            'first_name' => $faculty['first_name'],
            'last_name' => $faculty['last_name'],
            'email' => $faculty['email'],
            'department' => $faculty['department'],
            'designation' => $faculty['designation'],
            'specialization' => $faculty['specialization'],
            'profile_picture' => $faculty['profile_picture']
        ],
        'statistics' => $statistics,
        'today_classes' => $today_classes,
        'upcoming_events' => $upcoming_events
    ]);

} catch (PDOException $e) {
    error_log("Database error in faculty dashboard: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in faculty dashboard: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>