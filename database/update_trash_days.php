<?php
// Prevent any output before JSON response
error_reporting(0);

header('Content-Type: application/json');

include '../config_connection.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$trashDays = intval($data['trash_days']);

if ($trashDays < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid number of days']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    
    // Update trash days record
    $stmt = $pdo->prepare("UPDATE trash_days_tb SET trash_days = ? WHERE trash_id = 1");
    $success = $stmt->execute([$trashDays]);
    
    if ($success) {
        echo json_encode(['success' => true, 'action' => 'update_trash_days']);
    } else {
        throw new Exception('Failed to update trash days');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>