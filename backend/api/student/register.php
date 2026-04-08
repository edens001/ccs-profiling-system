<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../config/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get POST data
$input = file_get_contents("php://input");
$data = json_decode($input);

error_log("Registration data received: " . $input);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use POST.'
    ]);
    exit;
}

// Validate required fields
$missing_fields = [];

if (empty($data->first_name)) $missing_fields[] = 'first_name';
if (empty($data->last_name)) $missing_fields[] = 'last_name';
if (empty($data->email)) $missing_fields[] = 'email';
if (empty($data->password)) $missing_fields[] = 'password';
if (empty($data->course)) $missing_fields[] = 'course';
if (empty($data->year_level)) $missing_fields[] = 'year_level';
if (empty($data->birth_date)) $missing_fields[] = 'birth_date';
if (empty($data->gender)) $missing_fields[] = 'gender';
if (empty($data->contact_number)) $missing_fields[] = 'contact_number';
if (empty($data->address)) $missing_fields[] = 'address';

if (!empty($missing_fields)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields',
        'missing_fields' => $missing_fields
    ]);
    exit;
}

// Validate email format
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

// Validate contact number format
if (!preg_match('/^09\d{9}$/', $data->contact_number)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid contact number. Must be 11 digits starting with 09'
    ]);
    exit;
}

// Validate password strength
if (strlen($data->password) < 6) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 6 characters long'
    ]);
    exit;
}

// Validate year level
if (!in_array($data->year_level, ['1', '2', '3', '4'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid year level'
    ]);
    exit;
}

// Validate gender
if (!in_array($data->gender, ['Male', 'Female', 'Other'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid gender value'
    ]);
    exit;
}

try {
    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $data->email);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email already exists'
        ]);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Get course name and ID
    $course_name = '';
    $course_id = 0;
    
    if (is_numeric($data->course)) {
        $course_query = "SELECT id, course_name FROM courses WHERE id = :course_id";
        $course_stmt = $db->prepare($course_query);
        $course_stmt->bindParam(':course_id', $data->course);
        $course_stmt->execute();
        
        if ($course_stmt->rowCount() > 0) {
            $course_row = $course_stmt->fetch(PDO::FETCH_ASSOC);
            $course_name = $course_row['course_name'];
            $course_id = $course_row['id'];
        }
    }
    
    if (empty($course_name)) {
        $course_map = [
            '1' => 'BS Computer Science',
            '2' => 'BS Information Technology',
            '3' => 'BS Information Systems'
        ];
        
        if (isset($course_map[$data->course])) {
            $course_name = $course_map[$data->course];
        } else {
            $course_name = 'BS Computer Science';
        }
        
        // Get actual course ID from database
        $course_id_query = "SELECT id FROM courses WHERE course_name = :course_name LIMIT 1";
        $course_id_stmt = $db->prepare($course_id_query);
        $course_id_stmt->bindParam(':course_name', $course_name);
        $course_id_stmt->execute();
        if ($course_id_stmt->rowCount() > 0) {
            $course_id_row = $course_id_stmt->fetch(PDO::FETCH_ASSOC);
            $course_id = $course_id_row['id'];
        }
    }
    
    // Generate unique student number
    $year = date('Y');
    $found_unique = false;
    $attempts = 0;
    $max_attempts = 10;
    
    while (!$found_unique && $attempts < $max_attempts) {
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $student_number = $year . '-' . $random;
        
        $check_sn_query = "SELECT id FROM students WHERE student_number = :student_number";
        $check_sn_stmt = $db->prepare($check_sn_query);
        $check_sn_stmt->bindParam(':student_number', $student_number);
        $check_sn_stmt->execute();
        
        if ($check_sn_stmt->rowCount() == 0) {
            $found_unique = true;
        }
        $attempts++;
    }
    
    if (!$found_unique) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to generate unique student number. Please try again.'
        ]);
        exit;
    }
    
    // Generate unique username
    $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data->first_name . '.' . $data->last_name));
    $username = $base_username;
    $counter = 1;
    
    while (true) {
        $check_username_query = "SELECT id FROM users WHERE username = :username";
        $check_username_stmt = $db->prepare($check_username_query);
        $check_username_stmt->bindParam(':username', $username);
        $check_username_stmt->execute();
        
        if ($check_username_stmt->rowCount() == 0) {
            break;
        }
        $username = $base_username . $counter;
        $counter++;
    }
    
    // Hash password
    $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
    
    // Insert into users table
    $user_query = "INSERT INTO users (
        username, 
        email, 
        password, 
        first_name, 
        last_name, 
        middle_name, 
        role,
        is_active,
        created_at
    ) VALUES (
        :username, 
        :email, 
        :password, 
        :first_name, 
        :last_name, 
        :middle_name, 
        'student',
        1,
        NOW()
    )";
    
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':username', $username);
    $user_stmt->bindParam(':email', $data->email);
    $user_stmt->bindParam(':password', $hashed_password);
    $user_stmt->bindParam(':first_name', $data->first_name);
    $user_stmt->bindParam(':last_name', $data->last_name);
    $middle_name = !empty($data->middle_name) ? $data->middle_name : '';
    $user_stmt->bindParam(':middle_name', $middle_name);
    $user_stmt->execute();
    
    $user_id = $db->lastInsertId();
    
    // Set default section
    $section = !empty($data->section) ? $data->section : 'A';
    $year_level = $data->year_level;
    
    // Insert into students table
    $student_query = "INSERT INTO students (
        user_id, 
        student_number, 
        course, 
        year_level, 
        section, 
        contact_number, 
        address, 
        birth_date, 
        gender, 
        guardian_name, 
        guardian_contact,
        status
    ) VALUES (
        :user_id, 
        :student_number, 
        :course, 
        :year_level, 
        :section, 
        :contact_number, 
        :address, 
        :birth_date, 
        :gender, 
        :guardian_name, 
        :guardian_contact,
        'Pending'
    )";
    
    $student_stmt = $db->prepare($student_query);
    $student_stmt->bindParam(':user_id', $user_id);
    $student_stmt->bindParam(':student_number', $student_number);
    $student_stmt->bindParam(':course', $course_name);
    $student_stmt->bindParam(':year_level', $year_level);
    $student_stmt->bindParam(':section', $section);
    $student_stmt->bindParam(':contact_number', $data->contact_number);
    $student_stmt->bindParam(':address', $data->address);
    $student_stmt->bindParam(':birth_date', $data->birth_date);
    $student_stmt->bindParam(':gender', $data->gender);
    $guardian_name = !empty($data->guardian_name) ? $data->guardian_name : '';
    $student_stmt->bindParam(':guardian_name', $guardian_name);
    $guardian_contact = !empty($data->guardian_contact) ? $data->guardian_contact : '';
    $student_stmt->bindParam(':guardian_contact', $guardian_contact);
    $student_stmt->execute();
    
    $student_id = $db->lastInsertId();
    
    // =============================================
    // AUTO-ENROLLMENT: Find schedules that match student's course, year, section
    // =============================================
    $enrolled_count = 0;
    
    // Get all schedules that match the student's course, year level, and section
    $schedule_query = "SELECT 
                        s.id as schedule_id,
                        sec.id as section_id
                      FROM schedules s
                      JOIN sections sec ON s.section_id = sec.id
                      JOIN courses c ON s.course_id = c.id
                      WHERE c.id = :course_id
                      AND sec.year_level = :year_level
                      AND sec.section_code = :section
                      AND s.semester = :semester
                      AND s.academic_year = :academic_year";
    
    // Determine current semester and academic year
    $current_month = (int)date('m');
    $current_year = date('Y');
    $semester = ($current_month >= 8) ? '1' : (($current_month >= 1 && $current_month <= 5) ? '2' : '3');
    $academic_year = ($current_month >= 8) ? $current_year . '-' . ($current_year + 1) : ($current_year - 1) . '-' . $current_year;
    
    $schedule_stmt = $db->prepare($schedule_query);
    $schedule_stmt->bindParam(':course_id', $course_id);
    $schedule_stmt->bindParam(':year_level', $year_level);
    $schedule_stmt->bindParam(':section', $section);
    $schedule_stmt->bindParam(':semester', $semester);
    $schedule_stmt->bindParam(':academic_year', $academic_year);
    $schedule_stmt->execute();
    
    $matching_schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($matching_schedules) . " matching schedules for auto-enrollment");
    
    // Auto-enroll student to matching schedules
    foreach ($matching_schedules as $schedule) {
        $enroll_query = "INSERT INTO enrollments (student_id, section_id, schedule_id, status) 
                         VALUES (:student_id, :section_id, :schedule_id, 'Enrolled')";
        $enroll_stmt = $db->prepare($enroll_query);
        $enroll_stmt->bindParam(':student_id', $student_id);
        $enroll_stmt->bindParam(':section_id', $schedule['section_id']);
        $enroll_stmt->bindParam(':schedule_id', $schedule['schedule_id']);
        $enroll_stmt->execute();
        $enrolled_count++;
    }
    
    error_log("Auto-enrolled to $enrolled_count subjects");
    
    $db->commit();
    
    // Generate JWT token
    $token_data = [
        'id' => $user_id,
        'username' => $username,
        'role' => 'student',
        'student_number' => $student_number,
        'email' => $data->email
    ];
    
    $token = $jwt->generate($token_data);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Your account is pending approval from the administrator.',
        'token' => $token,
        'student' => [
            'id' => $user_id,
            'student_number' => $student_number,
            'username' => $username,
            'name' => $data->first_name . ' ' . $data->last_name,
            'first_name' => $data->first_name,
            'last_name' => $data->last_name,
            'email' => $data->email,
            'course' => $course_name,
            'year_level' => $year_level,
            'section' => $section,
            'status' => 'Pending',
            'auto_enrolled_subjects' => $enrolled_count
        ]
    ]);
    
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log("Database error in registration: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log("General error in registration: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>