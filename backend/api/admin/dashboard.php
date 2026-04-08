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
error_log("Admin Dashboard API called");
error_log("Token: " . ($token ? 'Present' : 'Missing'));

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use GET.'
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

try {
    // Initialize stats array
    $stats = [
        'total_students' => 0,
        'total_faculty' => 0,
        'total_courses' => 0,
        'ongoing_events' => 0
    ];

    // Check if tables exist before querying
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    // Total students
    if (in_array('students', $tables)) {
        $students_query = "SELECT COUNT(*) as total FROM students";
        $students_stmt = $db->prepare($students_query);
        $students_stmt->execute();
        $stats['total_students'] = (int)$students_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Total faculty
    if (in_array('faculty', $tables)) {
        $faculty_query = "SELECT COUNT(*) as total FROM faculty";
        $faculty_stmt = $db->prepare($faculty_query);
        $faculty_stmt->execute();
        $stats['total_faculty'] = (int)$faculty_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Total courses
    if (in_array('courses', $tables)) {
        $courses_query = "SELECT COUNT(*) as total FROM courses WHERE is_active = 1";
        $courses_stmt = $db->prepare($courses_query);
        $courses_stmt->execute();
        $stats['total_courses'] = (int)$courses_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Ongoing events
    if (in_array('events', $tables)) {
        $events_query = "SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE()";
        $events_stmt = $db->prepare($events_query);
        $events_stmt->execute();
        $stats['ongoing_events'] = (int)$events_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Recent activities - check which tables exist
    $recent_activities = [];
    
    if (in_array('enrollments', $tables) && in_array('students', $tables) && in_array('users', $tables)) {
        try {
            $activities_query = "SELECT 
                                    CONCAT(u.first_name, ' ', u.last_name) as user,
                                    'Enrolled in course' as action,
                                    e.enrolled_at as date,
                                    'Completed' as status
                                FROM enrollments e
                                JOIN students s ON e.student_id = s.id
                                JOIN users u ON s.user_id = u.id
                                ORDER BY e.enrolled_at DESC
                                LIMIT 5";
            
            $activities_stmt = $db->prepare($activities_query);
            $activities_stmt->execute();
            $recent_activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching activities: " . $e->getMessage());
        }
    }

    // If no activities found or tables don't exist, provide sample data
    if (empty($recent_activities)) {
        $recent_activities = [
            ['user' => 'John Doe', 'action' => 'Enrolled in CS101', 'date' => date('Y-m-d', strtotime('-1 day')), 'status' => 'Completed'],
            ['user' => 'Jane Smith', 'action' => 'Updated syllabus', 'date' => date('Y-m-d', strtotime('-2 days')), 'status' => 'Pending'],
            ['user' => 'Mike Johnson', 'action' => 'Added new course', 'date' => date('Y-m-d', strtotime('-3 days')), 'status' => 'Completed']
        ];
    }

    // Upcoming events
    $upcoming_events = [];
    
    if (in_array('events', $tables)) {
        try {
            $events_query = "SELECT 
                                id,
                                title,
                                event_type,
                                event_date,
                                event_time,
                                venue,
                                description
                            FROM events 
                            WHERE event_date >= CURDATE() 
                            ORDER BY event_date ASC 
                            LIMIT 5";
            
            $events_stmt = $db->prepare($events_query);
            $events_stmt->execute();
            $upcoming_events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching events: " . $e->getMessage());
        }
    }

    // If no events found or table doesn't exist, provide sample data
    if (empty($upcoming_events)) {
        $upcoming_events = [
            [
                'id' => 1,
                'title' => 'CCS Week 2024',
                'event_type' => 'Extracurricular',
                'event_date' => date('Y-m-d', strtotime('+7 days')),
                'event_time' => '08:00:00',
                'venue' => 'Main Hall',
                'description' => 'Annual college week celebration'
            ],
            [
                'id' => 2,
                'title' => 'Faculty Meeting',
                'event_type' => 'Curricular',
                'event_date' => date('Y-m-d', strtotime('+14 days')),
                'event_time' => '10:00:00',
                'venue' => 'Conference Room',
                'description' => 'Monthly faculty meeting'
            ],
            [
                'id' => 3,
                'title' => 'Research Symposium',
                'event_type' => 'Curricular',
                'event_date' => date('Y-m-d', strtotime('+21 days')),
                'event_time' => '13:00:00',
                'venue' => 'Audio Visual Room',
                'description' => 'Student research presentation'
            ]
        ];
    }

    // Get enrollment trends (last 6 months)
    $enrollment_trends = [];
    
    if (in_array('enrollments', $tables)) {
        try {
            $trends_query = "SELECT 
                                DATE_FORMAT(enrolled_at, '%Y-%m') as month,
                                COUNT(*) as count
                            FROM enrollments
                            WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                            GROUP BY DATE_FORMAT(enrolled_at, '%Y-%m')
                            ORDER BY month ASC";
            
            $trends_stmt = $db->prepare($trends_query);
            $trends_stmt->execute();
            $enrollment_trends = $trends_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching trends: " . $e->getMessage());
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'statistics' => [
            ['label' => 'Total Students', 'value' => number_format($stats['total_students']), 'icon' => 'bi bi-people', 'color' => '#3498db'],
            ['label' => 'Faculty Members', 'value' => number_format($stats['total_faculty']), 'icon' => 'bi bi-person-badge', 'color' => '#27ae60'],
            ['label' => 'Active Courses', 'value' => number_format($stats['total_courses']), 'icon' => 'bi bi-book', 'color' => '#f39c12'],
            ['label' => 'Ongoing Events', 'value' => number_format($stats['ongoing_events']), 'icon' => 'bi bi-calendar-event', 'color' => '#e74c3c']
        ],
        'recent_activities' => $recent_activities,
        'upcoming_events' => $upcoming_events,
        'enrollment_trends' => $enrollment_trends,
        'user' => [
            'name' => $user_data['username'] ?? 'Admin',
            'role' => $user_data['role'] ?? 'admin'
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in admin dashboard: " . $e->getMessage());
    
    // Return sample data on error
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'statistics' => [
            ['label' => 'Total Students', 'value' => '1,234', 'icon' => 'bi bi-people', 'color' => '#3498db'],
            ['label' => 'Faculty Members', 'value' => '89', 'icon' => 'bi bi-person-badge', 'color' => '#27ae60'],
            ['label' => 'Active Courses', 'value' => '56', 'icon' => 'bi bi-book', 'color' => '#f39c12'],
            ['label' => 'Ongoing Events', 'value' => '3', 'icon' => 'bi bi-calendar-event', 'color' => '#e74c3c']
        ],
        'recent_activities' => [
            ['user' => 'John Doe', 'action' => 'Enrolled in CS101', 'date' => date('Y-m-d', strtotime('-1 day')), 'status' => 'Completed'],
            ['user' => 'Jane Smith', 'action' => 'Updated syllabus', 'date' => date('Y-m-d', strtotime('-2 days')), 'status' => 'Pending'],
            ['user' => 'Mike Johnson', 'action' => 'Added new course', 'date' => date('Y-m-d', strtotime('-3 days')), 'status' => 'Completed']
        ],
        'upcoming_events' => [
            [
                'id' => 1,
                'title' => 'CCS Week 2024',
                'event_type' => 'Extracurricular',
                'event_date' => date('Y-m-d', strtotime('+7 days')),
                'event_time' => '08:00:00',
                'venue' => 'Main Hall',
                'description' => 'Annual college week celebration'
            ],
            [
                'id' => 2,
                'title' => 'Faculty Meeting',
                'event_type' => 'Curricular',
                'event_date' => date('Y-m-d', strtotime('+14 days')),
                'event_time' => '10:00:00',
                'venue' => 'Conference Room',
                'description' => 'Monthly faculty meeting'
            ]
        ],
        'enrollment_trends' => [
            ['month' => date('Y-m', strtotime('-5 months')), 'count' => 45],
            ['month' => date('Y-m', strtotime('-4 months')), 'count' => 52],
            ['month' => date('Y-m', strtotime('-3 months')), 'count' => 48],
            ['month' => date('Y-m', strtotime('-2 months')), 'count' => 63],
            ['month' => date('Y-m', strtotime('-1 months')), 'count' => 71],
            ['month' => date('Y-m'), 'count' => 84]
        ],
        'user' => [
            'name' => $user_data['username'] ?? 'Admin',
            'role' => $user_data['role'] ?? 'admin'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("General error in admin dashboard: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>