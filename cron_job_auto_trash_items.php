<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/smart-tv-cms/cron_auto_trash_items_error.log');

// Log file setup
$logFile = '/var/www/smart-tv-cms/archive_trash_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logMessage("Starting archived items check...");

    // Fetch archive days setting
    $stmt = $pdo->query("SELECT archive_days FROM archive_days_tb WHERE archive_id = 1");
    $archiveDays = $stmt->fetchColumn() ?: 120;
    logMessage("Archive days setting: $archiveDays");

    // Get all content types (including dynamic features)
    $stmt = $pdo->query("SELECT type FROM features_tb");
    $contentTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add default content types
    $defaultTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];
    $contentTypes = array_merge($defaultTypes, $contentTypes);
    
    foreach ($contentTypes as $type) {
        if ($type == 'announcement' || $type == 'event' || $type == 'promaterial') {
            $tableName = "{$type}s_tb";
        } else {
            $tableName = "{$type}_tb";
        }
        logMessage("Processing table: $tableName");

        // Fetch archived items
        $stmt = $pdo->prepare("
            SELECT * FROM $tableName 
            WHERE isArchived = 1 
            AND isTrashed = 0
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("Found " . count($items) . " archived items in $tableName");

        foreach ($items as $item) {
            $archivedDate = new DateTime($item['datetime_archived']);
            $expiryDate = clone $archivedDate;
            $expiryDate->modify("+{$archiveDays} days");
            $today = new DateTime();

            if ($today > $expiryDate) {
                logMessage("Processing expired archive item {$item["{$type}_id"]} (archived on {$item['datetime_archived']})");

                // Before updating the item status, fetch user details for email
                $stmt = $pdo->prepare("
                    SELECT email, full_name, department 
                    FROM users_tb 
                    WHERE user_id = ?
                ");
                $stmt->execute([$item["{$type}_author_id"]]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Update the item to trashed status
                $stmt = $pdo->prepare("
                    UPDATE $tableName 
                    SET isTrashed = 1,
                        datetime_trashed = NOW()
                    WHERE {$type}_id = ?
                ");
                $stmt->execute([$item["{$type}_id"]]);
                
                // Create notification for the content author
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
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $item["{$type}_id"],
                    $item["{$type}_author_id"],
                    $type,
                    'content_trashed_by_system',
                    'trashed',
                    'System',
                    'System',
                    'System',
                    "Content automatically moved to Trash after {$archiveDays} days in Archives",
                    'System',
                    $item['tv_id']
                ]);

                // Update notification count for the user
                $stmt = $pdo->prepare("
                    UPDATE users_tb 
                    SET notification_count = notification_count + 1 
                    WHERE user_id = ?
                ");
                $stmt->execute([$item["{$type}_author_id"]]);
                
                logMessage("Created notification for user {$item["{$type}_author_id"]} about trashed content {$item["{$type}_id"]}");
            }
        }
    }

    logMessage("Completed archived items check successfully to be moved to trash");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}