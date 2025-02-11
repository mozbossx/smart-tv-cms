<?php
include 'config_connection.php';

try {
    // Get current timestamp
    $currentTime = time();
    
    // Find TVs that haven't been updated in the last 10 seconds and are still marked as active
    $stmt = $conn->prepare("
        UPDATE smart_tvs_tb 
        SET active_status = 'inactive' 
        WHERE active_status = 'active' 
        AND UNIX_TIMESTAMP(last_ping) < ?
    ");
    
    // Set the threshold time (current time - 10 seconds)
    $thresholdTime = $currentTime - 10;
    $stmt->bind_param("i", $thresholdTime);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update inactive TVs: " . $stmt->error);
    }
    
    $updatedCount = $stmt->affected_rows;
    error_log("Cron job executed: Updated $updatedCount TVs to inactive status");
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Cron job error: " . $e->getMessage());
}
?>