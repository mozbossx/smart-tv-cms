<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// Get the content type and ID from the query string
$type = $_GET['type'] ?? '';
$id = $_GET['tvId'] ?? '';

// Sanitize the inputs
$type = preg_replace('/[^a-z_]/', '', strtolower($type));
$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);

if ($type == 'announcement' || $type == 'event' || $type == 'promaterial') {
    $tableName = "{$type}s_tb";
} else {
    $tableName = "{$type}_tb";
}

try {
    $query = "SELECT ST.tv_name as tv_display
              FROM {$tableName} CT
              LEFT JOIN smart_tvs_tb ST ON CT.tv_id = ST.tv_id
              WHERE CT.tv_id = :contentId";

    $statement = $pdo->prepare($query);
    $statement->execute([
        ':contentId' => $id
    ]);
    $result = $statement->fetch(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($result ? $result : ['tv_display' => 'No TV assigned']);
} catch (PDOException $e) {
    // Log the error and return a JSON error response
    error_log('Database query failed: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to fetch TV info: ' . $e->getMessage()]);
}