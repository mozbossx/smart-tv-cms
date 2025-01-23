<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// Get the content type from the query string
$type = $_GET['type'] ?? '';
$archived = isset($_GET['archived']) && $_GET['archived'] === 'true';
$trashed = isset($_GET['trashed']) && $_GET['trashed'] === 'true';
$approved = isset($_GET['approved']) && $_GET['approved'] === 'true';
$rejected = isset($_GET['rejected']) && $_GET['rejected'] === 'true';
$pending = isset($_GET['pending']) && $_GET['pending'] === 'true';

// Sanitize the type to prevent SQL injection
$type = preg_replace('/[^a-z_]/', '', strtolower($type));

if ($type == 'announcement' || $type == 'event' || $type == 'promaterial') {
    $tableName = "{$type}s_tb";
} else {
    $tableName = "{$type}_tb";
}

// Construct author ID field name
$authorIdField = "{$type}_author_id";

try {
    // Fetch content with author names
    $query = "SELECT c.*, u.full_name as author_name, u.user_type as author_user_type, u.department as author_department
              FROM {$tableName} c 
              LEFT JOIN users_tb u ON c.{$authorIdField} = u.user_id
              WHERE c.isArchived = :isArchived AND c.isTrashed = :isTrashed";

    if ($approved) {
        if ($type == 'peo' || $type == 'so') {
            // PEO and SO don't need status check
            $query .= "";
        } else {
            // Other content types need status = 'Approved'
            $query .= " AND c.status = 'Approved'";
        }
    }

    if ($rejected) {
        if ($type == 'peo' || $type == 'so') {
            // PEO and SO don't need status check
            $query .= "";
        } else {
            // Other content types need status = 'Rejected'
            $query .= " AND c.status = 'Rejected'";
        }
    }

    if ($pending) {
        if ($type == 'peo' || $type == 'so') {
            // PEO and SO don't need status check
            $query .= "";
        } else {
            // Other content types need status = 'Pending'
            $query .= " AND c.status = 'Pending'";
        }
    }
    
    $params = [
        ':isArchived' => $archived ? 1 : 0, 
        ':isTrashed' => $trashed ? 1 : 0
    ];

    $statement = $pdo->prepare($query);
    $statement->execute($params);
    $content = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($content);
} catch (PDOException $e) {
    // Log the error and return a JSON error response
    error_log('Database query failed: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to fetch content: ' . $e->getMessage()]);
}