<?php
// Get the origin of the request
$http_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Allowed origins
$allowed_origins = [
    'http://localhost:8080',
    'http://localhost:8081',
    'http://127.0.0.1:8080',
    'http://127.0.0.1:8081',
    'http://localhost:3000', // Add your Vue dev server port
    'http://localhost:5173', // Vite default port
    'http://localhost:5174'  // Alternative Vite port
];

// Check if the origin is allowed
if (in_array($http_origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $http_origin");
} else {
    // For development, allow all localhost origins
    if (strpos($http_origin, 'http://localhost') === 0 || strpos($http_origin, 'http://127.0.0.1') === 0) {
        header("Access-Control-Allow-Origin: $http_origin");
    } else {
        // Default fallback
        header("Access-Control-Allow-Origin: http://localhost:8080");
    }
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 hours cache

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    
    // Add headers for content type and authorization
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>