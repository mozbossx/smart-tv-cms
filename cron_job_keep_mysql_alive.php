<?php
try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Simple query to keep connection alive
    $stmt = $pdo->query("SELECT 1");
    
    // Log successful ping
    error_log("MySQL connection ping successful at " . date('Y-m-d H:i:s'));
    
} catch (PDOException $e) {
    error_log("MySQL ping error: " . $e->getMessage());
}
?>