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

switch ($method) {
    case 'GET':
        handleGet($db);
        break;
    case 'POST':
        handlePost($db, $user_data);
        break;
    case 'PUT':
        handlePut($db);
        break;
    case 'DELETE':
        handleDelete($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function handleGet($db) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    
    try {
        if ($id > 0) {
            // Get single section with adviser details
            $query = "SELECT 
                        s.*, 
                        c.course_code, 
                        c.course_name,
                        f.id as adviser_id,
                        CONCAT(uf.first_name, ' ', uf.last_name) as adviser_name,
                        uf.email as adviser_email
                     FROM sections s
                     LEFT JOIN courses c ON s.course_id = c.id
                     LEFT JOIN faculty f ON s.adviser_id = f.id
                     LEFT JOIN users uf ON f.user_id = uf.id
                     WHERE s.id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $section = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($section) {
                echo json_encode(['success' => true, 'data' => $section]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Section not found']);
            }
        } else {
            // Get all sections with adviser details and student count
            $query = "SELECT 
                        s.*, 
                        c.course_code, 
                        c.course_name,
                        f.id as adviser_id,
                        CONCAT(uf.first_name, ' ', uf.last_name) as adviser_name,
                        uf.email as adviser_email,
                        (SELECT COUNT(*) FROM enrollments WHERE section_id = s.id AND status = 'Enrolled') as student_count
                      FROM sections s
                      LEFT JOIN courses c ON s.course_id = c.id
                      LEFT JOIN faculty f ON s.adviser_id = f.id
                      LEFT JOIN users uf ON f.user_id = uf.id";
            
            if ($course_id > 0) {
                $query .= " WHERE s.course_id = :course_id";
            }
            
            $query .= " ORDER BY s.year_level ASC, s.section_code ASC";
            
            $stmt = $db->prepare($query);
            
            if ($course_id > 0) {
                $stmt->bindParam(':course_id', $course_id);
            }
            
            $stmt->execute();
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $sections]);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function handlePost($db, $user_data) {
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    $required = ['course_id', 'section_code', 'year_level'];
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
        return;
    }
    
    try {
        // Check if section code already exists for the same course
        $check_query = "SELECT id FROM sections WHERE course_id = :course_id AND section_code = :section_code";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':course_id', $data->course_id);
        $check_stmt->bindParam(':section_code', $data->section_code);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Section code already exists for this course']);
            return;
        }
        
        // If adviser_id is provided, check if faculty exists
        $adviser_id = null;
        if (!empty($data->adviser_id)) {
            $adviser_check = "SELECT id FROM faculty WHERE id = :adviser_id";
            $adviser_stmt = $db->prepare($adviser_check);
            $adviser_stmt->bindParam(':adviser_id', $data->adviser_id);
            $adviser_stmt->execute();
            if ($adviser_stmt->rowCount() > 0) {
                $adviser_id = $data->adviser_id;
            }
        }
        
        $query = "INSERT INTO sections (course_id, section_code, year_level, capacity, adviser_id, is_active, created_by, created_at) 
                  VALUES (:course_id, :section_code, :year_level, :capacity, :adviser_id, :is_active, :created_by, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':course_id', $data->course_id);
        $stmt->bindParam(':section_code', $data->section_code);
        $stmt->bindParam(':year_level', $data->year_level);
        $capacity = $data->capacity ?? 40;
        $stmt->bindParam(':capacity', $capacity);
        $stmt->bindParam(':adviser_id', $adviser_id);
        $is_active = isset($data->is_active) ? $data->is_active : 1;
        $stmt->bindParam(':is_active', $is_active);
        $stmt->bindParam(':created_by', $user_data['id']);
        $stmt->execute();
        
        $section_id = $db->lastInsertId();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Section created successfully',
            'id' => $section_id
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function handlePut($db) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Section ID is required']);
        return;
    }
    
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    try {
        // Check if section exists
        $check_query = "SELECT id FROM sections WHERE id = :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':id', $id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Section not found']);
            return;
        }
        
        // Check for duplicate section code (excluding current)
        if (!empty($data->section_code) && !empty($data->course_id)) {
            $dup_query = "SELECT id FROM sections WHERE course_id = :course_id AND section_code = :section_code AND id != :id";
            $dup_stmt = $db->prepare($dup_query);
            $dup_stmt->bindParam(':course_id', $data->course_id);
            $dup_stmt->bindParam(':section_code', $data->section_code);
            $dup_stmt->bindParam(':id', $id);
            $dup_stmt->execute();
            
            if ($dup_stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Section code already exists for this course']);
                return;
            }
        }
        
        // If adviser_id is provided, check if faculty exists
        $adviser_id = null;
        if (!empty($data->adviser_id)) {
            $adviser_check = "SELECT id FROM faculty WHERE id = :adviser_id";
            $adviser_stmt = $db->prepare($adviser_check);
            $adviser_stmt->bindParam(':adviser_id', $data->adviser_id);
            $adviser_stmt->execute();
            if ($adviser_stmt->rowCount() > 0) {
                $adviser_id = $data->adviser_id;
            }
        }
        
        $query = "UPDATE sections SET 
                    course_id = :course_id,
                    section_code = :section_code,
                    year_level = :year_level,
                    capacity = :capacity,
                    adviser_id = :adviser_id,
                    is_active = :is_active,
                    updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':course_id', $data->course_id);
        $stmt->bindParam(':section_code', $data->section_code);
        $stmt->bindParam(':year_level', $data->year_level);
        $stmt->bindParam(':capacity', $data->capacity);
        $stmt->bindParam(':adviser_id', $adviser_id);
        $stmt->bindParam(':is_active', $data->is_active);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Section updated successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function handleDelete($db) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Section ID is required']);
        return;
    }
    
    try {
        // Check if section has enrollments
        $check_enrollments = "SELECT id FROM enrollments WHERE section_id = :id LIMIT 1";
        $enroll_stmt = $db->prepare($check_enrollments);
        $enroll_stmt->bindParam(':id', $id);
        $enroll_stmt->execute();
        
        if ($enroll_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot delete section with existing enrollments']);
            return;
        }
        
        // Check if section has schedules
        $check_schedules = "SELECT id FROM schedules WHERE section_id = :id LIMIT 1";
        $sched_stmt = $db->prepare($check_schedules);
        $sched_stmt->bindParam(':id', $id);
        $sched_stmt->execute();
        
        if ($sched_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot delete section with existing schedules']);
            return;
        }
        
        $query = "DELETE FROM sections WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Section deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}
?>