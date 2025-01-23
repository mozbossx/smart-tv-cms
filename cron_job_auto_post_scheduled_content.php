<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/smart-tv-cms/cron_auto_post_scheduled_content_error.log');

// Log file setup
$logFile = '/var/www/smart-tv-cms/scheduled_content_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logMessage("Starting scheduled content check...");

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

        // Get scheduled content that's due to be posted
        $stmt = $pdo->prepare("
            SELECT *, 
                   {$type}_author_id as author_id,
                   schedule_datetime,
                   tv_id
            FROM $tableName 
            WHERE isArchived = 0 
            AND isTrashed = 0 
            AND status != 'Pending'
            AND status != 'Rejected'
            AND schedule_datetime <= NOW()
            AND schedule_datetime IS NOT NULL
        ");
        $stmt->execute();
        $scheduledItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($scheduledItems as $item) {
            // Update the content status
            $stmt = $pdo->prepare("
                UPDATE $tableName 
                SET schedule_datetime = NULL,
                    status = 'Approved'
                WHERE {$type}_id = ?
            ");
            $stmt->execute([$item["{$type}_id"]]);
            
            // Get author details
            $stmt = $pdo->prepare("
                SELECT full_name, user_type, department 
                FROM users_tb 
                WHERE user_id = ?
            ");
            $stmt->execute([$item['author_id']]);
            $author = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Insert notification
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
                'content_scheduled_posted',
                'approved',
                'System',
                'System',
                'System',
                "Your scheduled content has been automatically posted by the System",
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
            
            // Send notification to WebSocket server using cURL
            $postData = [
                'action' => 'post_scheduled_content_by_system',
                'type' => $type,
                "{$type}_id" => $item["{$type}_id"],
                'user_id' => $item['author_id'],
                'author_id' => $item['author_id'],
                'author_name' => $author['full_name'] ?? 'System',
                'author_user_type' => $author['user_type'] ?? 'System',
                'author_department' => $author['department'] ?? 'System',
                'evaluator_name' => 'System',
                'evaluator_message' => "Content automatically posted as scheduled",
                'tv_id' => $item['tv_id'],  // Make sure tv_id is included
                'success' => true  // Add success flag
            ];

            // Use cURL to send the WebSocket message
            $ch = curl_init('https://usccms.online/ws');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($postData))
            ]);
            
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                logMessage("WebSocket notification error: " . curl_error($ch));
            }
            curl_close($ch);
            
            logMessage("Posted scheduled item {$item["{$type}_id"]} in $tableName and sent WebSocket notification");
        }
    }

    logMessage("Completed scheduled content check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}