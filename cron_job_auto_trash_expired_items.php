<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/smart-tv-cms/cron_auto_trash_expired_items_error.log');

// Log file setup
$logFile = '/var/www/smart-tv-cms/expired_content_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logMessage("Starting expiration check...");

    // Get all content types (including dynamic features)
    $stmt = $pdo->query("SELECT type FROM features_tb");
    $contentTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add default content types
    $defaultTypes = ['announcement', 'event', 'news', 'promaterial'];
    $contentTypes = array_merge($defaultTypes, $contentTypes);
    
    foreach ($contentTypes as $type) {
        if ($type == 'announcement' || $type == 'event' || $type == 'promaterial') {
            $tableName = "{$type}s_tb";
        } else {
            $tableName = "{$type}_tb";
        }
        
        logMessage("Processing table: $tableName");

        // Update expired content to trashed status
        $stmt = $pdo->prepare("
            UPDATE $tableName 
            SET isTrashed = 1,
                datetime_trashed = NOW()
            WHERE isArchived = 0 
            AND isTrashed = 0 
            AND expiration_datetime <= NOW()
            AND expiration_datetime IS NOT NULL
        ");
        $stmt->execute();
        $trashedCount = $stmt->rowCount();
        
        if ($trashedCount > 0) {
            logMessage("Trashed $trashedCount expired items in $tableName");
            
            // Fetch the trashed items to create notifications
            $stmt = $pdo->prepare("
                SELECT *, {$type}_author_id as author_id 
                FROM $tableName 
                WHERE isTrashed = 1 
                AND datetime_trashed = NOW()
            ");
            $stmt->execute();
            $trashedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($trashedItems as $item) {
                // Insert notification for the content author
                $stmt = $pdo->prepare("
                    INSERT INTO notifications_tb (
                        content_id, 
                        user_id, 
                        content_type, 
                        notification_type, 
                        status, 
                        action_user_name,
                        action_user_type,
                        action_user_department,
                        evaluator_message,
                        evaluator_name,
                        content_tv_id
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ");
                
                $stmt->execute([
                    $item["{$type}_id"],
                    $item['author_id'],
                    $type,
                    'content_deleted_by_system',
                    'deleted',
                    'System',
                    'System',
                    'System',
                    "Content automatically moved to Trash upon reaching expiration date ({$item['expiration_datetime']})",
                    'System',
                    $item['tv_id']
                ]);

                // Update notification count for the user
                $stmt = $pdo->prepare("
                    UPDATE users_tb 
                    SET notification_count = notification_count + 1 
                    WHERE user_id = ?
                ");
                $stmt->execute([$item['author_id']]);
                
                logMessage("Created notification for user {$item['author_id']} about trashed content {$item["{$type}_id"]}");
            }
        }
    }

    logMessage("Completed expiration check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}