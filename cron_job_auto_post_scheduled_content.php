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

    // Define content types and their tables
    $contentTypes = [
        'announcement' => ['table' => 'announcements_tb', 'idField' => 'announcement_id', 'authorField' => 'announcement_author_id'],
        'event' => ['table' => 'events_tb', 'idField' => 'event_id', 'authorField' => 'event_author_id'],
        'news' => ['table' => 'news_tb', 'idField' => 'news_id', 'authorField' => 'news_author_id'],
        'promaterial' => ['table' => 'promaterials_tb', 'idField' => 'promaterial_id', 'authorField' => 'promaterial_author_id']
    ];

    foreach ($contentTypes as $type => $config) {
        logMessage("Processing {$type}s...");

        // Fetch scheduled content that's due to be posted
        $stmt = $pdo->prepare("
            SELECT t.*, u.full_name, u.user_type, u.department 
            FROM {$config['table']} t
            JOIN users_tb u ON t.{$config['authorField']} = u.user_id
            WHERE t.schedule_datetime <= NOW() 
            AND t.schedule_datetime IS NOT NULL
            AND t.isArchived = 0 
            AND t.isTrashed = 0
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("Found " . count($items) . " scheduled {$type}s to post");

        foreach ($items as $item) {
            logMessage("Processing {$type} ID: {$item[$config['idField']]}");

            // Update the content to be posted
            $updateStmt = $pdo->prepare("
                UPDATE {$config['table']}
                SET schedule_datetime = NULL,
                    status = 'Approved'
                WHERE {$config['idField']} = ?
            ");
            $updateStmt->execute([$item[$config['idField']]]);

            // Create notification for the content author
            $notifyStmt = $pdo->prepare("
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

            $notifyStmt->execute([
                $item[$config['idField']],
                $item[$config['authorField']],
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
            $updateCountStmt = $pdo->prepare("
                UPDATE users_tb 
                SET notification_count = notification_count + 1 
                WHERE user_id = ?
            ");
            $updateCountStmt->execute([$item[$config['authorField']]]);

            logMessage("Successfully posted {$type} ID: {$item[$config['idField']]}");
        }
    }

    logMessage("Completed scheduled content check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}