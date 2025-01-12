<?php
// Prevent any output before JSON response
error_reporting(0);

header('Content-Type: application/json');

include '../config_connection.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$archiveDays = intval($data['archive_days']);

if ($archiveDays < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid number of days']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    
    // Update archive days record
    $stmt = $pdo->prepare("UPDATE archive_days_tb SET archive_days = ? WHERE archive_id = 1");
    $success = $stmt->execute([$archiveDays]);
    
    if ($success) {
        echo json_encode(['success' => true, 'action' => 'update_archive_days']);
    } else {
        throw new Exception('Failed to update archive days');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>