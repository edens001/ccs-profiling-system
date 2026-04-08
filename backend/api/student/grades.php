<?php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../config/jwt.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get token from header
$headers = apache_request_headers();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Log for debugging
error_log("Grades API called with token: " . substr($token, 0, 20) . "...");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
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
    
    if ($user_data['role'] !== 'student') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Student role required.'
        ]);
        exit;
    }
    
    try {
        // Get student ID
        $student_query = "SELECT id FROM students WHERE user_id = :user_id";
        $student_stmt = $db->prepare($student_query);
        $student_stmt->bindParam(':user_id', $user_data['id']);
        $student_stmt->execute();
        
        if ($student_stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Student record not found'
            ]);
            exit;
        }
        
        $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get filter parameters
        $semester = isset($_GET['semester']) ? $_GET['semester'] : '';
        $year = isset($_GET['year']) ? $_GET['year'] : '';
        $course = isset($_GET['course']) ? $_GET['course'] : '';
        
        // Build query with filters
        $grades_query = "SELECT 
                            g.id,
                            g.prelim_grade,
                            g.midterm_grade,
                            g.final_grade,
                            g.remarks,
                            c.id as course_id,
                            c.course_code,
                            c.course_name,
                            c.units,
                            c.department,
                            CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                            u.email as instructor_email,
                            sch.semester,
                            sch.academic_year,
                            sec.section_code
                        FROM grades g
                        JOIN schedules sch ON g.schedule_id = sch.id
                        JOIN courses c ON sch.course_id = c.id
                        JOIN faculty f ON sch.faculty_id = f.id
                        JOIN users u ON f.user_id = u.id
                        JOIN sections sec ON sch.section_id = sec.id
                        JOIN enrollments e ON g.enrollment_id = e.id
                        WHERE e.student_id = :student_id";
        
        $params = [':student_id' => $student['id']];
        
        if (!empty($semester)) {
            $grades_query .= " AND sch.semester = :semester";
            $params[':semester'] = $semester;
        }
        
        if (!empty($year)) {
            $grades_query .= " AND sch.academic_year = :academic_year";
            $params[':academic_year'] = $year;
        }
        
        if (!empty($course)) {
            $grades_query .= " AND c.id = :course_id";
            $params[':course_id'] = $course;
        }
        
        $grades_query .= " ORDER BY sch.academic_year DESC, sch.semester DESC, c.course_code ASC";
        
        $grades_stmt = $db->prepare($grades_query);
        foreach ($params as $key => &$val) {
            $grades_stmt->bindParam($key, $val);
        }
        $grades_stmt->execute();
        
        $grades = $grades_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get available filters for dropdowns
        $filters_query = "SELECT 
                            DISTINCT sch.academic_year,
                            sch.semester,
                            c.id as course_id,
                            c.course_code
                          FROM enrollments e
                          JOIN sections sec ON e.section_id = sec.id
                          JOIN schedules sch ON sec.id = sch.section_id
                          JOIN courses c ON sch.course_id = c.id
                          WHERE e.student_id = :student_id
                          ORDER BY sch.academic_year DESC, sch.semester DESC";
        
        $filters_stmt = $db->prepare($filters_query);
        $filters_stmt->bindParam(':student_id', $student['id']);
        $filters_stmt->execute();
        $available_filters = $filters_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate statistics
        $total_units = 0;
        $total_grade_points = 0;
        $completed_courses = 0;
        $failed_courses = 0;
        $incomplete_courses = 0;
        
        // GPA by semester
        $gpa_by_semester = [];
        
        foreach ($grades as $grade) {
            $key = $grade['academic_year'] . '_' . $grade['semester'];
            
            if (!isset($gpa_by_semester[$key])) {
                $gpa_by_semester[$key] = [
                    'academic_year' => $grade['academic_year'],
                    'semester' => $grade['semester'],
                    'semester_name' => $grade['semester'] == 1 ? '1st Semester' : ($grade['semester'] == 2 ? '2nd Semester' : 'Summer'),
                    'total_units' => 0,
                    'total_grade_points' => 0,
                    'courses' => [],
                    'gpa' => '0.00'
                ];
            }
            
            $gpa_by_semester[$key]['courses'][] = [
                'course_code' => $grade['course_code'],
                'course_name' => $grade['course_name'],
                'units' => $grade['units'],
                'prelim' => $grade['prelim_grade'] ? number_format($grade['prelim_grade'], 2) : '—',
                'midterm' => $grade['midterm_grade'] ? number_format($grade['midterm_grade'], 2) : '—',
                'final' => $grade['final_grade'] ? number_format($grade['final_grade'], 2) : '—',
                'remarks' => $grade['remarks'] ?? 'Incomplete',
                'instructor' => $grade['instructor_name']
            ];
            
            if ($grade['final_grade']) {
                $grade_points = floatval($grade['final_grade']);
                $gpa_by_semester[$key]['total_units'] += $grade['units'];
                $gpa_by_semester[$key]['total_grade_points'] += ($grade_points * $grade['units']);
                
                $total_units += $grade['units'];
                $total_grade_points += ($grade_points * $grade['units']);
                
                if ($grade_points <= 3.0) {
                    $completed_courses++;
                } else {
                    $failed_courses++;
                }
            } else {
                $incomplete_courses++;
            }
        }
        
        // Calculate GPA for each semester
        foreach ($gpa_by_semester as &$sem) {
            if ($sem['total_units'] > 0) {
                $sem['gpa'] = number_format($sem['total_grade_points'] / $sem['total_units'], 2);
            }
        }
        
        // Overall GPA
        $overall_gpa = $total_units > 0 ? number_format($total_grade_points / $total_units, 2) : '0.00';
        
        // Grade distribution
        $distribution = [
            ['range' => '1.00 - 1.50', 'count' => 0, 'color' => 'success', 'percentage' => 0],
            ['range' => '1.75 - 2.00', 'count' => 0, 'color' => 'info', 'percentage' => 0],
            ['range' => '2.25 - 2.50', 'count' => 0, 'color' => 'warning', 'percentage' => 0],
            ['range' => '2.75 - 3.00', 'count' => 0, 'color' => 'primary', 'percentage' => 0],
            ['range' => 'Above 3.00', 'count' => 0, 'color' => 'danger', 'percentage' => 0]
        ];
        
        $total_graded = 0;
        foreach ($grades as $grade) {
            if ($grade['final_grade']) {
                $total_graded++;
                $fg = floatval($grade['final_grade']);
                if ($fg <= 1.5) $distribution[0]['count']++;
                elseif ($fg <= 2.0) $distribution[1]['count']++;
                elseif ($fg <= 2.5) $distribution[2]['count']++;
                elseif ($fg <= 3.0) $distribution[3]['count']++;
                else $distribution[4]['count']++;
            }
        }
        
        // Calculate percentages
        if ($total_graded > 0) {
            foreach ($distribution as &$dist) {
                $dist['percentage'] = round(($dist['count'] / $total_graded) * 100);
            }
        }
        
        // Get academic summary
        $summary = [
            'overall_gpa' => $overall_gpa,
            'total_units' => $total_units,
            'total_courses' => count($grades),
            'completed_courses' => $completed_courses,
            'failed_courses' => $failed_courses,
            'incomplete_courses' => $incomplete_courses,
            'total_graded' => $total_graded
        ];
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'summary' => $summary,
            'grades_by_semester' => array_values($gpa_by_semester),
            'grade_distribution' => $distribution,
            'available_filters' => $available_filters,
            'filters_applied' => [
                'semester' => $semester,
                'year' => $year,
                'course' => $course
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in student grades: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred. Please try again.'
        ]);
    } catch (Exception $e) {
        error_log("General error in student grades: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ]);
    }
    
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use GET.'
    ]);
}
?>