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
if (!$user_data || !in_array($user_data['role'], ['admin', 'dean', 'dept_chair', 'secretary'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get student ID from URL parameter
$student_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$student_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

try {
    // Get student details
    $query = "SELECT s.*, u.first_name, u.last_name, u.middle_name, u.email, u.username,
                     u.profile_picture, u.is_active, u.created_at,
                     c.course_name, c.course_code
              FROM students s 
              JOIN users u ON s.user_id = u.id 
              LEFT JOIN courses c ON s.course = c.course_name
              WHERE s.id = :id OR s.student_number = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $student_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get current enrollment
   $enrollment_query = "SELECT e.*, sec.section_code,
                            sch.id as schedule_id, sch.day_of_week, sch.time_start, sch.time_end,
                            sch.semester, sch.academic_year,
                            c.course_code, c.course_name, c.units,
                            r.room_code, r.building,
                            CONCAT(uf.first_name, ' ', uf.last_name) as instructor_name
                     FROM enrollments e
                     JOIN sections sec ON e.section_id = sec.id
                     JOIN schedules sch ON sec.id = sch.section_id
                     JOIN courses c ON sch.course_id = c.id
                     JOIN rooms r ON sch.room_id = r.id
                     JOIN faculty f ON sch.faculty_id = f.id
                     JOIN users uf ON f.user_id = uf.id
                     WHERE e.student_id = :student_id AND e.status = 'Enrolled'
                     ORDER BY sch.day_of_week, sch.time_start";
    
    $enroll_stmt = $db->prepare($enrollment_query);
    $enroll_stmt->bindParam(':student_id', $student['id']);
    $enroll_stmt->execute();
    $enrollments = $enroll_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format enrollments
    $formatted_enrollments = [];
    foreach ($enrollments as $enroll) {
        $formatted_enrollments[] = [
            'id' => $enroll['schedule_id'],
            'course_code' => $enroll['course_code'],
            'course_name' => $enroll['course_name'],
            'units' => $enroll['units'],
            'schedule' => $enroll['day_of_week'] . ' ' . 
                         date('h:i A', strtotime($enroll['time_start'])) . ' - ' . 
                         date('h:i A', strtotime($enroll['time_end'])),
            'room' => $enroll['room_code'] . ' (' . $enroll['building'] . ')',
            'instructor' => $enroll['instructor_name'],
            'section' => $enroll['section_code'],
            'academic_year' => $enroll['academic_year'],
            'semester' => $enroll['semester']
        ];
    }

    // Get grade summary
    $grades_query = "SELECT COUNT(*) as total_subjects,
                            AVG(g.final_grade) as average_grade,
                            SUM(CASE WHEN g.remarks = 'Passed' THEN 1 ELSE 0 END) as passed,
                            SUM(CASE WHEN g.remarks = 'Failed' THEN 1 ELSE 0 END) as failed
                     FROM grades g
                     JOIN schedules sch ON g.schedule_id = sch.id
                     JOIN enrollments e ON g.enrollment_id = e.id
                     WHERE e.student_id = :student_id";
    
    $grades_stmt = $db->prepare($grades_query);
    $grades_stmt->bindParam(':student_id', $student['id']);
    $grades_stmt->execute();
    $grades = $grades_stmt->fetch(PDO::FETCH_ASSOC);

    // Format response
    $response = [
        'success' => true,
        'data' => [
            'id' => $student['id'],
            'user_id' => $student['user_id'],
            'student_number' => $student['student_number'],
            'first_name' => $student['first_name'],
            'last_name' => $student['last_name'],
            'middle_name' => $student['middle_name'],
            'full_name' => trim($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']),
            'email' => $student['email'],
            'username' => $student['username'],
            'course' => $student['course'],
            'course_name' => $student['course_name'] ?? $student['course'],
            'course_code' => $student['course_code'] ?? '',
            'year_level' => $student['year_level'],
            'section' => $student['section'],
            'contact_number' => $student['contact_number'],
            'address' => $student['address'],
            'birth_date' => $student['birth_date'],
            'gender' => $student['gender'],
            'guardian_name' => $student['guardian_name'],
            'guardian_contact' => $student['guardian_contact'],
            'profile_picture' => $student['profile_picture'],
            'status' => $student['is_active'] ? 'Active' : 'Inactive',
            'created_at' => $student['created_at'],
            'enrollments' => $formatted_enrollments,
            'grade_summary' => [
                'total_subjects' => (int)($grades['total_subjects'] ?? 0),
                'average_grade' => $grades['average_grade'] ? number_format($grades['average_grade'], 2) : 'N/A',
                'passed' => (int)($grades['passed'] ?? 0),
                'failed' => (int)($grades['failed'] ?? 0)
            ]
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