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

require_once '../../../config/database.php';
require_once '../../../config/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwt = new JWT();

// Get token from header
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Validate token
$user_data = $jwt->validate($token);
if (!$user_data || !in_array($user_data['role'], ['admin', 'dean', 'dept_chair', 'secretary'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$semester = isset($_GET['semester']) ? $_GET['semester'] : '1';
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '2025-2026';

$query = "SELECT 
            sch.id,
            c.course_code,
            c.course_name,
            c.units,
            sec.section_code,
            sec.year_level,
            r.room_code,
            r.building,
            CONCAT(u.first_name, ' ', u.last_name) as faculty_name,
            sch.day_of_week,
            TIME_FORMAT(sch.time_start, '%h:%i %p') as time_start,
            TIME_FORMAT(sch.time_end, '%h:%i %p') as time_end,
            sch.semester,
            sch.academic_year
          FROM schedules sch
          JOIN courses c ON sch.course_id = c.id
          JOIN sections sec ON sch.section_id = sec.id
          JOIN rooms r ON sch.room_id = r.id
          JOIN faculty f ON sch.faculty_id = f.id
          JOIN users u ON f.user_id = u.id
          WHERE sch.semester = :semester
          AND sch.academic_year = :academic_year
          AND c.is_active = 1
          ORDER BY c.course_code, sec.section_code";

$stmt = $db->prepare($query);
$stmt->bindParam(':semester', $semester);
$stmt->bindParam(':academic_year', $academic_year);
$stmt->execute();

$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $schedules,
    'total' => count($schedules)
]);
?>