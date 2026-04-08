<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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
error_log("========== SEARCH API CALLED ==========");
error_log("Token: " . ($token ? 'Present' : 'Missing'));

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

// Check if user has access (admin, dean, dept_chair, secretary, faculty)
if (!in_array($user_data['role'], ['admin', 'dean', 'dept_chair', 'secretary', 'faculty'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Insufficient privileges.'
    ]);
    exit;
}

// Get search query from request
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_query = isset($_POST['q']) ? trim($_POST['q']) : $search_query;
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

if (empty($search_query)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Search query is required'
    ]);
    exit;
}

try {
    $results = [];
    $total_results = 0;
    
    // Search in students
    if ($type_filter === 'all' || $type_filter === 'student') {
        $student_query = "SELECT 
                            s.id,
                            s.student_number,
                            s.course,
                            s.year_level,
                            s.section,
                            u.first_name,
                            u.last_name,
                            u.email,
                            u.profile_picture,
                            s.status as enrollment_status,
                            CONCAT(u.first_name, ' ', u.last_name) as full_name,
                            'student' as result_type
                          FROM students s
                          JOIN users u ON s.user_id = u.id
                          WHERE u.first_name LIKE :search 
                             OR u.last_name LIKE :search 
                             OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search
                             OR s.student_number LIKE :search
                             OR u.email LIKE :search
                             OR s.course LIKE :search
                          ORDER BY u.last_name ASC
                          LIMIT :limit OFFSET :offset";
        
        $search_term = "%{$search_query}%";
        $stmt = $db->prepare($student_query);
        $stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for students
        $count_query = "SELECT COUNT(*) as total 
                        FROM students s
                        JOIN users u ON s.user_id = u.id
                        WHERE u.first_name LIKE :search 
                           OR u.last_name LIKE :search 
                           OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search
                           OR s.student_number LIKE :search
                           OR u.email LIKE :search
                           OR s.course LIKE :search";
        
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $count_stmt->execute();
        $total_students = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $total_results += $total_students;
        $results = array_merge($results, $students);
    }
    
    // Search in faculty
    if ($type_filter === 'all' || $type_filter === 'faculty') {
        $faculty_query = "SELECT 
                            f.id,
                            f.faculty_number,
                            f.department,
                            f.designation,
                            f.specialization,
                            u.first_name,
                            u.last_name,
                            u.email,
                            u.profile_picture,
                            CONCAT(u.first_name, ' ', u.last_name) as full_name,
                            'faculty' as result_type
                          FROM faculty f
                          JOIN users u ON f.user_id = u.id
                          WHERE u.first_name LIKE :search 
                             OR u.last_name LIKE :search 
                             OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search
                             OR f.faculty_number LIKE :search
                             OR u.email LIKE :search
                             OR f.department LIKE :search
                             OR f.designation LIKE :search
                          ORDER BY u.last_name ASC
                          LIMIT :limit OFFSET :offset";
        
        $search_term = "%{$search_query}%";
        $stmt = $db->prepare($faculty_query);
        $stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for faculty
        $count_query = "SELECT COUNT(*) as total 
                        FROM faculty f
                        JOIN users u ON f.user_id = u.id
                        WHERE u.first_name LIKE :search 
                           OR u.last_name LIKE :search 
                           OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search
                           OR f.faculty_number LIKE :search
                           OR u.email LIKE :search
                           OR f.department LIKE :search
                           OR f.designation LIKE :search";
        
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $count_stmt->execute();
        $total_faculty = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $total_results += $total_faculty;
        $results = array_merge($results, $faculty);
    }
    
    // Search in courses
    if ($type_filter === 'all' || $type_filter === 'course') {
        $course_query = "SELECT 
                            c.id,
                            c.course_code,
                            c.course_name,
                            c.department,
                            c.units,
                            c.description,
                            'course' as result_type
                          FROM courses c
                          WHERE c.is_active = 1
                            AND (c.course_code LIKE :search 
                             OR c.course_name LIKE :search 
                             OR c.description LIKE :search
                             OR c.department LIKE :search)
                          ORDER BY c.course_code ASC
                          LIMIT :limit OFFSET :offset";
        
        $search_term = "%{$search_query}%";
        $stmt = $db->prepare($course_query);
        $stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for courses
        $count_query = "SELECT COUNT(*) as total 
                        FROM courses c
                        WHERE c.is_active = 1
                          AND (c.course_code LIKE :search 
                           OR c.course_name LIKE :search 
                           OR c.description LIKE :search
                           OR c.department LIKE :search)";
        
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $count_stmt->execute();
        $total_courses = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $total_results += $total_courses;
        $results = array_merge($results, $courses);
    }
    
    // Search in events
    if ($type_filter === 'all' || $type_filter === 'event') {
        $event_query = "SELECT 
                            e.id,
                            e.title,
                            e.description,
                            e.event_type,
                            e.event_date,
                            e.event_time,
                            e.venue,
                            e.organizer,
                            'event' as result_type
                          FROM events e
                          WHERE e.title LIKE :search 
                             OR e.description LIKE :search 
                             OR e.venue LIKE :search
                             OR e.organizer LIKE :search
                          ORDER BY e.event_date ASC
                          LIMIT :limit OFFSET :offset";
        
        $search_term = "%{$search_query}%";
        $stmt = $db->prepare($event_query);
        $stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for events
        $count_query = "SELECT COUNT(*) as total 
                        FROM events e
                        WHERE e.title LIKE :search 
                           OR e.description LIKE :search 
                           OR e.venue LIKE :search
                           OR e.organizer LIKE :search";
        
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $count_stmt->execute();
        $total_events = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $total_results += $total_events;
        $results = array_merge($results, $events);
    }
    
    // Search in sections
    if ($type_filter === 'all' || $type_filter === 'section') {
        $section_query = "SELECT 
                            sec.id,
                            sec.section_code,
                            sec.year_level,
                            sec.capacity,
                            sec.is_active,
                            c.course_code,
                            c.course_name,
                            CONCAT('Section ', sec.section_code, ' - ', c.course_code) as title,
                            CONCAT('Year ', sec.year_level, ' • ', c.course_name) as description,
                            'section' as result_type
                          FROM sections sec
                          JOIN courses c ON sec.course_id = c.id
                          WHERE sec.section_code LIKE :search 
                             OR c.course_code LIKE :search
                             OR c.course_name LIKE :search
                          ORDER BY c.course_code, sec.year_level, sec.section_code
                          LIMIT :limit OFFSET :offset";
        
        $search_term = "%{$search_query}%";
        $stmt = $db->prepare($section_query);
        $stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for sections
        $count_query = "SELECT COUNT(*) as total 
                        FROM sections sec
                        JOIN courses c ON sec.course_id = c.id
                        WHERE sec.section_code LIKE :search 
                           OR c.course_code LIKE :search
                           OR c.course_name LIKE :search";
        
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $count_stmt->execute();
        $total_sections = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $total_results += $total_sections;
        $results = array_merge($results, $sections);
    }
    
    // Search in syllabi
    if ($type_filter === 'all' || $type_filter === 'syllabus') {
        $syllabus_query = "SELECT 
                            s.id,
                            s.title,
                            s.description,
                            c.course_code,
                            c.course_name,
                            CONCAT(s.title, ' - ', c.course_code) as syllabus_title,
                            'syllabus' as result_type
                          FROM syllabus s
                          JOIN courses c ON s.course_id = c.id
                          WHERE s.title LIKE :search 
                             OR s.description LIKE :search
                             OR c.course_code LIKE :search
                             OR c.course_name LIKE :search
                          ORDER BY s.title ASC
                          LIMIT :limit OFFSET :offset";
        
        $search_term = "%{$search_query}%";
        $stmt = $db->prepare($syllabus_query);
        $stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $syllabi = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for syllabi
        $count_query = "SELECT COUNT(*) as total 
                        FROM syllabus s
                        JOIN courses c ON s.course_id = c.id
                        WHERE s.title LIKE :search 
                           OR s.description LIKE :search
                           OR c.course_code LIKE :search
                           OR c.course_name LIKE :search";
        
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $count_stmt->execute();
        $total_syllabi = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $total_results += $total_syllabi;
        $results = array_merge($results, $syllabi);
    }
    
    // Format results for frontend
    $formatted_results = array_map(function($item) {
        $result = [
            'id' => $item['id'],
            'type' => $item['result_type'],
            'raw_data' => $item
        ];
        
        // Format based on type
        switch ($item['result_type']) {
            case 'student':
                $result['title'] = $item['full_name'];
                $result['subtitle'] = $item['student_number'] . ' • ' . 
                                      ($item['course'] ?? 'No Course') . ' • ' .
                                      'Year ' . ($item['year_level'] ?? 'N/A') . 
                                      ($item['section'] ? ' - Section ' . $item['section'] : '');
                $result['icon'] = 'bi-person';
                $result['url'] = '/admin/students/view/' . $item['id'];
                $result['badge'] = $item['enrollment_status'] ?? 'Student';
                break;
                
            case 'faculty':
                $result['title'] = $item['full_name'];
                $result['subtitle'] = $item['faculty_number'] . ' • ' . 
                                      ($item['designation'] ?? 'Faculty') . ' • ' .
                                      ($item['department'] ?? 'N/A');
                $result['icon'] = 'bi-person-badge';
                $result['url'] = '/admin/faculty/view/' . $item['id'];
                $result['badge'] = $item['designation'] ?? 'Faculty';
                break;
                
            case 'course':
                $result['title'] = $item['course_code'] . ' - ' . $item['course_name'];
                $result['subtitle'] = ($item['units'] ?? '0') . ' units • ' . 
                                      ($item['department'] ?? 'N/A');
                $result['icon'] = 'bi-book';
                $result['url'] = '/admin/courses/view/' . $item['id'];
                $result['badge'] = 'Course';
                break;
                
            case 'event':
                $result['title'] = $item['title'];
                $date = date('M d, Y', strtotime($item['event_date']));
                $time = $item['event_time'] ? date('h:i A', strtotime($item['event_time'])) : '';
                $result['subtitle'] = $date . ($time ? ' • ' . $time : '') . ' • ' . 
                                      ($item['venue'] ?? 'No venue');
                $result['icon'] = 'bi-calendar-event';
                $result['url'] = '/admin/events/view/' . $item['id'];
                $result['badge'] = $item['event_type'] ?? 'Event';
                break;
                
            case 'section':
                $result['title'] = $item['course_code'] . ' - Section ' . $item['section_code'];
                $result['subtitle'] = 'Year ' . $item['year_level'] . ' • Capacity: ' . 
                                      ($item['capacity'] ?? '40') . ' • ' .
                                      ($item['is_active'] ? 'Active' : 'Inactive');
                $result['icon'] = 'bi-columns-gap';
                $result['url'] = '/admin/scheduling/sections';
                $result['badge'] = 'Section';
                break;
                
            case 'syllabus':
                $result['title'] = $item['title'];
                $result['subtitle'] = $item['course_code'] . ' - ' . $item['course_name'];
                $result['icon'] = 'bi-file-text';
                $result['url'] = '/admin/instruction/syllabus/view/' . $item['id'];
                $result['badge'] = 'Syllabus';
                break;
        }
        
        return $result;
    }, $results);
    
    // Sort results by relevance (title match priority)
    usort($formatted_results, function($a, $b) use ($search_query) {
        $a_title = strtolower($a['title']);
        $b_title = strtolower($b['title']);
        $search_lower = strtolower($search_query);
        
        // Exact matches first
        if ($a_title === $search_lower && $b_title !== $search_lower) return -1;
        if ($b_title === $search_lower && $a_title !== $search_lower) return 1;
        
        // Then starts with
        if (strpos($a_title, $search_lower) === 0 && strpos($b_title, $search_lower) !== 0) return -1;
        if (strpos($b_title, $search_lower) === 0 && strpos($a_title, $search_lower) !== 0) return 1;
        
        return 0;
    });
    
    // Get unique results (prevent duplicates)
    $unique_results = [];
    $seen = [];
    foreach ($formatted_results as $result) {
        $key = $result['type'] . '_' . $result['id'];
        if (!in_array($key, $seen)) {
            $seen[] = $key;
            $unique_results[] = $result;
        }
    }
    
    // Paginate results
    $paginated_results = array_slice($unique_results, 0, $limit);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'query' => $search_query,
        'total' => count($unique_results),
        'total_all' => $total_results,
        'limit' => $limit,
        'page' => $page,
        'results' => $paginated_results,
        'filters' => [
            'type' => $type_filter,
            'available_types' => ['all', 'student', 'faculty', 'course', 'event', 'section', 'syllabus']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in search: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
} catch (Exception $e) {
    error_log("General error in search: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>