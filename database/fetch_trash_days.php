<?php
include '../config_connection.php';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    $stmt = $pdo->query("SELECT trash_days FROM trash_days_tb");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'trash_days' => $result ? $result['trash_days'] : 30
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>