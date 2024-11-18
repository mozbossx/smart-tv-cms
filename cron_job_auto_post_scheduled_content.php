<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file setup
$logFile = __DIR__ . '/scheduled_content_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");
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

            // Create message data for ContentHandler
            $messageData = [
                'action' => 'post_now_content',
                'type' => $type,
                "{$config['idField']}" => $item[$config['idField']],
                'user_id' => $item[$config['authorField']],
                'full_name' => $item['full_name'],
                'user_type' => $item['user_type'],
                'department' => $item['department']
            ];

            // Use ContentHandler to process the posting
            require_once __DIR__ . '/../MyApp/ContentHandler.php';
            $contentHandler = new \MyApp\ContentHandler($pdo);
            
            // Create a mock connection for the ContentHandler
            $mockConnection = new class {
                public $user_id;
                public $full_name;
                public $user_type;
                public $department;

                public function send($message) {
                    logMessage("Would send WebSocket message: " . $message);
                }
            };

            // Set the user properties on the mock connection
            $mockConnection->user_id = $item[$config['authorField']];
            $mockConnection->full_name = $item['full_name'];
            $mockConnection->user_type = $item['user_type'];
            $mockConnection->department = $item['department'];

            // Process the post action through ContentHandler
            $contentHandler->onMessage($mockConnection, json_encode($messageData));
            logMessage("Post request processed through ContentHandler for {$type} {$item[$config['idField']]}");
        }
    }

    logMessage("Completed scheduled content check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}