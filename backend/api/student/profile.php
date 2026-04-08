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

require_once '../config/database.php';
require_once '../config/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get token from header
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
$token = str_replace('Bearer ', '', $auth_header);

error_log("========== PROFILE API CALLED ==========");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Token: " . ($token ? substr($token, 0, 30) . '...' : 'Missing'));

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
$student_id = $student['id'];

$method = $_SERVER['REQUEST_METHOD'];

// Helper functions
function sendError($code, $message) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function sendSuccess($data) {
    http_response_code(200);
    echo json_encode(['success' => true] + $data);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            // Get student profile - simplified, no complex joins
            $profile_query = "SELECT 
                                u.id as user_id,
                                u.username,
                                u.email,
                                u.first_name,
                                u.last_name,
                                u.middle_name,
                                u.profile_picture,
                                s.id as student_id,
                                s.student_number,
                                s.course,
                                s.year_level,
                                s.section,
                                s.contact_number,
                                s.address,
                                s.birth_date,
                                s.gender,
                                s.guardian_name,
                                s.guardian_contact,
                                s.status,
                                s.enrolled_at
                              FROM users u
                              JOIN students s ON u.id = s.user_id
                              WHERE u.id = :user_id";
            
            $profile_stmt = $db->prepare($profile_query);
            $profile_stmt->bindParam(':user_id', $user_data['id']);
            $profile_stmt->execute();
            
            $profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$profile) {
                sendError(404, 'Profile not found');
            }
            
            // Format full name
            $profile['full_name'] = trim(
                ($profile['first_name'] ?? '') . ' ' . 
                ($profile['middle_name'] ? $profile['middle_name'] . ' ' : '') . 
                ($profile['last_name'] ?? '')
            );
            
            // Format birth date
            if ($profile['birth_date']) {
                $profile['birth_date'] = date('Y-m-d', strtotime($profile['birth_date']));
            }
            
            // Format enrolled date
            if ($profile['enrolled_at']) {
                $profile['enrolled_at'] = date('Y-m-d', strtotime($profile['enrolled_at']));
            }
            
            // Get enrolled subjects count (from enrollments and schedules)
            $subject_query = "SELECT COUNT(DISTINCT sch.id) as total 
                              FROM enrollments e
                              LEFT JOIN schedules sch ON e.schedule_id = sch.id
                              WHERE e.student_id = :student_id AND e.status = 'Enrolled'";
            $subject_stmt = $db->prepare($subject_query);
            $subject_stmt->bindParam(':student_id', $student_id);
            $subject_stmt->execute();
            $subject_result = $subject_stmt->fetch(PDO::FETCH_ASSOC);
            $total_subjects = (int)($subject_result['total'] ?? 0);
            
            // Get total units
            $units_query = "SELECT SUM(c.units) as total 
                            FROM enrollments e
                            LEFT JOIN schedules sch ON e.schedule_id = sch.id
                            LEFT JOIN courses c ON sch.course_id = c.id
                            WHERE e.student_id = :student_id AND e.status = 'Enrolled'";
            $units_stmt = $db->prepare($units_query);
            $units_stmt->bindParam(':student_id', $student_id);
            $units_stmt->execute();
            $units_result = $units_stmt->fetch(PDO::FETCH_ASSOC);
            $total_units = (int)($units_result['total'] ?? 0);
            
            // Get GPA from grades
            $grades_query = "SELECT AVG(g.final_grade) as gpa 
                             FROM grades g
                             JOIN enrollments e ON g.enrollment_id = e.id
                             WHERE e.student_id = :student_id 
                             AND g.final_grade IS NOT NULL 
                             AND g.final_grade > 0";
            $grades_stmt = $db->prepare($grades_query);
            $grades_stmt->bindParam(':student_id', $student_id);
            $grades_stmt->execute();
            $grades_result = $grades_stmt->fetch(PDO::FETCH_ASSOC);
            $gpa = $grades_result['gpa'] ? number_format($grades_result['gpa'], 2) : '0.00';
            
            $summary = [
                'total_subjects' => $total_subjects,
                'total_units' => $total_units,
                'current_gpa' => $gpa
            ];
            
            // Get enrollment history (simpler - no academic_year/semester from sections)
            $history_query = "SELECT 
                                e.id,
                                e.enrolled_at,
                                e.status,
                                sec.section_code
                              FROM enrollments e
                              LEFT JOIN sections sec ON e.section_id = sec.id
                              WHERE e.student_id = :student_id
                              ORDER BY e.enrolled_at DESC
                              LIMIT 5";
            $history_stmt = $db->prepare($history_query);
            $history_stmt->bindParam(':student_id', $student_id);
            $history_stmt->execute();
            $enrollment_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendSuccess([
                'profile' => $profile,
                'summary' => $summary,
                'enrollment_history' => $enrollment_history
            ]);
            break;
            
        case 'PUT':
            // Update student profile
            $data = json_decode(file_get_contents("php://input"));
            
            if (!$data) {
                sendError(400, 'Invalid JSON data');
            }
            
            $db->beginTransaction();
            
            try {
                // Update users table
                $user_update = "UPDATE users SET 
                                first_name = :first_name,
                                last_name = :last_name,
                                middle_name = :middle_name,
                                email = :email
                                WHERE id = :user_id";
                
                $user_stmt = $db->prepare($user_update);
                $user_stmt->bindParam(':first_name', $data->first_name);
                $user_stmt->bindParam(':last_name', $data->last_name);
                $middle_name = $data->middle_name ?? '';
                $user_stmt->bindParam(':middle_name', $middle_name);
                $user_stmt->bindParam(':email', $data->email);
                $user_stmt->bindParam(':user_id', $user_data['id']);
                $user_stmt->execute();
                
                // Update students table
                $student_update = "UPDATE students SET 
                                  contact_number = :contact_number,
                                  address = :address
                                  WHERE user_id = :user_id";
                
                $student_stmt = $db->prepare($student_update);
                $contact_number = $data->contact_number ?? '';
                $student_stmt->bindParam(':contact_number', $contact_number);
                $address = $data->address ?? '';
                $student_stmt->bindParam(':address', $address);
                $student_stmt->bindParam(':user_id', $user_data['id']);
                $student_stmt->execute();
                
                // Update guardian info if provided
                if (isset($data->guardian_name) || isset($data->guardian_contact)) {
                    $guardian_update = "UPDATE students SET 
                                        guardian_name = :guardian_name,
                                        guardian_contact = :guardian_contact
                                        WHERE user_id = :user_id";
                    $guardian_stmt = $db->prepare($guardian_update);
                    $guardian_name = $data->guardian_name ?? '';
                    $guardian_contact = $data->guardian_contact ?? '';
                    $guardian_stmt->bindParam(':guardian_name', $guardian_name);
                    $guardian_stmt->bindParam(':guardian_contact', $guardian_contact);
                    $guardian_stmt->bindParam(':user_id', $user_data['id']);
                    $guardian_stmt->execute();
                }
                
                $db->commit();
                
                // Fetch updated profile
                $profile_query = "SELECT 
                                    u.first_name, u.last_name, u.middle_name, u.email,
                                    s.contact_number, s.address, s.guardian_name, s.guardian_contact
                                  FROM users u
                                  JOIN students s ON u.id = s.user_id
                                  WHERE u.id = :user_id";
                
                $profile_stmt = $db->prepare($profile_query);
                $profile_stmt->bindParam(':user_id', $user_data['id']);
                $profile_stmt->execute();
                $updated_profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
                
                sendSuccess([
                    'message' => 'Profile updated successfully',
                    'profile' => $updated_profile
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        case 'POST':
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            
            if ($action === 'change_password') {
                $data = json_decode(file_get_contents("php://input"));
                
                if (!$data || empty($data->current_password) || empty($data->new_password)) {
                    sendError(400, 'Current password and new password are required');
                }
                
                // Verify current password
                $password_query = "SELECT password FROM users WHERE id = :user_id";
                $password_stmt = $db->prepare($password_query);
                $password_stmt->bindParam(':user_id', $user_data['id']);
                $password_stmt->execute();
                $user = $password_stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!password_verify($data->current_password, $user['password'])) {
                    sendError(400, 'Current password is incorrect');
                }
                
                // Validate new password
                if (strlen($data->new_password) < 6) {
                    sendError(400, 'New password must be at least 6 characters long');
                }
                
                // Update password
                $hashed_password = password_hash($data->new_password, PASSWORD_DEFAULT);
                $update_password_query = "UPDATE users SET password = :password WHERE id = :user_id";
                $update_password_stmt = $db->prepare($update_password_query);
                $update_password_stmt->bindParam(':password', $hashed_password);
                $update_password_stmt->bindParam(':user_id', $user_data['id']);
                $update_password_stmt->execute();
                
                sendSuccess(['message' => 'Password changed successfully']);
                
            } elseif ($action === 'upload_picture') {
                // Upload profile picture
                if (!isset($_FILES['profile_picture'])) {
                    sendError(400, 'No file uploaded');
                }
                
                $file = $_FILES['profile_picture'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                $max_size = 5 * 1024 * 1024;
                
                // Validate file
                $file_type = mime_content_type($file['tmp_name']);
                if (!in_array($file_type, $allowed_types)) {
                    sendError(400, 'Invalid file type. Only JPG, JPEG, and PNG are allowed.');
                }
                
                if ($file['size'] > $max_size) {
                    sendError(400, 'File too large. Maximum size is 5MB.');
                }
                
                // Create upload directory
                $upload_dir = __DIR__ . '/../../uploads/profile_pictures/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'student_' . $user_data['id'] . '_' . time() . '.' . $extension;
                $upload_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old profile picture
                    $old_pic_query = "SELECT profile_picture FROM users WHERE id = :user_id";
                    $old_pic_stmt = $db->prepare($old_pic_query);
                    $old_pic_stmt->bindParam(':user_id', $user_data['id']);
                    $old_pic_stmt->execute();
                    $old_pic = $old_pic_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($old_pic && $old_pic['profile_picture'] && file_exists($upload_dir . $old_pic['profile_picture'])) {
                        unlink($upload_dir . $old_pic['profile_picture']);
                    }
                    
                    // Update database
                    $update_query = "UPDATE users SET profile_picture = :profile_picture WHERE id = :user_id";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(':profile_picture', $filename);
                    $update_stmt->bindParam(':user_id', $user_data['id']);
                    $update_stmt->execute();
                    
                    sendSuccess([
                        'message' => 'Profile picture uploaded successfully',
                        'filename' => $filename,
                        'url' => '/uploads/profile_pictures/' . $filename
                    ]);
                } else {
                    sendError(500, 'Failed to upload file');
                }
            } else {
                sendError(400, 'Invalid action');
            }
            break;
            
        default:
            sendError(405, 'Method not allowed');
            break;
    }
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Database error in profile: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in profile: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>