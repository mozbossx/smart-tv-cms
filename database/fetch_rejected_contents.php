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
$rejected = isset($_GET['rejected']) && $_GET['rejected'] === 'true';

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
    // $query = "SELECT c.*, u.full_name as author_name, u.user_type as author_user_type, u.department as author_department, u.user_id as author_id
    //         FROM {$tableName} c 
    //         LEFT JOIN users_tb u ON c.{$authorIdField} = u.user_id
    //         WHERE c.status = 'Rejected'";

    $query = "SELECT c.*, 
            LOWER(REPLACE(c.category, ' ', '_')) as category, 
            u.full_name as author_name, 
            u.user_type as author_user_type, 
            u.department as author_department, 
            u.user_id as author_id, 
            notif.evaluator_message as evaluator_message, 
            notif.content_type as notification_content_type, 
            notif.evaluator_name as evaluator_name,
            notif.action_user_name as author_name2
        FROM {$tableName} c 
        LEFT JOIN users_tb u ON c.{$authorIdField} = u.user_id
        LEFT JOIN notifications_tb notif ON (
            notif.content_id = c.{$type}_id 
            AND LOWER(REPLACE(notif.content_type, ' ', '_')) = ?
            AND notif.status = 'rejected'
        )
        WHERE c.status = 'Rejected'";

    $statement = $pdo->prepare($query);
    $statement->execute([$type]);
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