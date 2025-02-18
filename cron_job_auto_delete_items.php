<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/smart-tv-cms/cron_auto_delete_items_error.log');

// Log file setup
$logFile = '/var/www/smart-tv-cms/trash_deletion_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logMessage("Starting trash deletion check...");

    // Fetch trash days setting
    $stmt = $pdo->query("SELECT trash_days FROM trash_days_tb WHERE trash_id = 1");
    $trashDays = $stmt->fetchColumn();
    logMessage("Trash days setting: $trashDays");

    // Get all content types (including dynamic features)
    $stmt = $pdo->query("SELECT type FROM features_tb");
    $contentTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add default content types
    $defaultTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];
    $contentTypes = array_merge($defaultTypes, $contentTypes);
    
    foreach ($contentTypes as $type) {
        if ($type == 'announcement' || $type == 'event' ||$type == 'promaterial') {
            $tableName = "{$type}s_tb";
        } else {
            $tableName = "{$type}_tb";
        }
        logMessage("Processing table: $tableName");

        // Fetch trashed items
        $stmt = $pdo->prepare("SELECT * FROM $tableName WHERE isTrashed = 1 AND isArchived = 0");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("Found " . count($items) . " trashed items in $tableName");

        foreach ($items as $item) {
            $trashedDate = new DateTime($item['datetime_trashed']);
            $expiryDate = clone $trashedDate;
            $expiryDate->modify("+{$trashDays} days");
            $today = new DateTime();

            if ($today > $expiryDate) {
                logMessage("Processing expired item {$item["{$type}_id"]} (trashed on {$item['datetime_trashed']})");

                // Check for media files to delete
                if ($type === 'facility') {
                    // Handle facility images
                    if (!empty($item['facility_images_array'])) {
                        $mediaFolder = "facility_media";
                        $imageNames = array_map('trim', explode(',', $item['facility_images_array']));
                        
                        foreach ($imageNames as $imageName) {
                            $fullPath = "/var/www/smart-tv-cms/{$mediaFolder}/{$imageName}";
                            if (file_exists($fullPath)) {
                                unlink($fullPath);
                                logMessage("Deleted facility image: {$fullPath}");
                            }
                        }
                    }
                } else {
                    // Check if media_path column exists
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as column_exists 
                        FROM information_schema.COLUMNS 
                        WHERE TABLE_NAME = ? 
                        AND COLUMN_NAME = 'media_path'
                        AND TABLE_SCHEMA = DATABASE()
                    ");
                    $stmt->execute([$tableName]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($result['column_exists']) {
                        // Delete associated media file if it exists
                        if (!empty($item['media_path'])) {
                            $mediaFolder = "{$type}_media";
                            $fullPath = "/var/www/smart-tv-cms/{$mediaFolder}/{$item['media_path']}";
                            if (file_exists($fullPath)) {
                                unlink($fullPath);
                                logMessage("Deleted media file: {$fullPath}");
                            }
                        }
                    }
                }

                // Delete the record from database
                $stmt = $pdo->prepare("DELETE FROM $tableName WHERE {$type}_id = ?");
                $stmt->execute([$item["{$type}_id"]]);
                logMessage("Deleted record {$item["{$type}_id"]} from $tableName");

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
                    'content_deleted_by_system',
                    'deleted',
                    'System',
                    'System',
                    'System',
                    "Content automatically deleted after {$trashDays} days in Trash",
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

                // After creating the notification, fetch user email and send notification
                $stmt = $pdo->prepare("
                    SELECT email, full_name, department 
                    FROM users_tb 
                    WHERE user_id = ?
                ");
                $stmt->execute([$item["{$type}_author_id"]]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                logMessage("Created notification for user {$item["{$type}_author_id"]} about deleted content {$item["{$type}_id"]}");
            }
        }
    }

    logMessage("Completed trash deletion check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}