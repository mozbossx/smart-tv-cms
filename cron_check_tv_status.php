<?php
try {
    // Get current timestamp
    $currentTime = time();
    
    // Set the threshold time (current time - 10 seconds)
    $thresholdTime = $currentTime - 10;
    
    // Create PDO connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Find TVs that haven't been updated in the last 10 seconds and are still marked as active
    $stmt = $pdo->prepare("
        UPDATE smart_tvs_tb 
        SET active_status = 'inactive' 
        WHERE active_status = 'active' 
        AND UNIX_TIMESTAMP(last_ping) < :threshold
    ");
    
    $stmt->bindParam(':threshold', $thresholdTime, PDO::PARAM_INT);
    $stmt->execute();
    
    $updatedCount = $stmt->rowCount();
    error_log("Cron job executed: Updated $updatedCount TVs to inactive status");
    
} catch (PDOException $e) {
    error_log("Cron job error: " . $e->getMessage());
}
?>