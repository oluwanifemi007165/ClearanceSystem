<?php
// Database configuration 
define('DB_HOST', 'localhost');
define('DB_USER', 'root');    
define('DB_PASS', '');        
define('DB_NAME', 'clearance_db');

//  Database connection
function db(): mysqli {
    static $conn;
    if (!$conn) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
            exit;
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

//  Shared helpers 
function respond(bool $ok, string $msg, array $extra = []): void {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}