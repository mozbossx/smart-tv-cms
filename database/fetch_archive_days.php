<?php
include '../config_connection.php';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");
    $stmt = $pdo->query("SELECT archive_days FROM archive_days_tb");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'archive_days' => $result ? $result['archive_days'] : 120
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>