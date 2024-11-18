<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file setup
$logFile = __DIR__ . '/expired_content_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logMessage("Starting expiration check...");

    // Get all content types (including dynamic features)
    $stmt = $pdo->query("SELECT type FROM features_tb");
    $contentTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add default content types
    $defaultTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];
    $contentTypes = array_merge($defaultTypes, $contentTypes);
    
    $currentDateTime = new DateTime();
    
    foreach ($contentTypes as $type) {
        if ($type == 'announcement' || $type == 'event' || $type == 'promaterial') {
            $tableName = "{$type}s_tb";
        } else {
            $tableName = "{$type}_tb";
        }
        logMessage("Processing table: $tableName");

        // Fetch active content that has reached expiration
        $stmt = $pdo->prepare("
            SELECT * FROM $tableName 
            WHERE isArchived = 0 
            AND isTrashed = 0 
            AND expiration_datetime <= NOW()
            AND expiration_datetime IS NOT NULL
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("Found " . count($items) . " expired items in $tableName");

        foreach ($items as $item) {
            logMessage("Processing expired item {$item["{$type}_id"]} (expires on {$item['expiration_datetime']})");

            // Create message data for ContentHandler
            $messageData = [
                'action' => 'trash_content',
                'type' => $type,
                "{$type}_id" => $item["{$type}_id"],
                'user_id' => $item["{$type}_author_id"],
                'author_id' => $item["{$type}_author_id"],
                'author_name' => 'System',
                'author_user_type' => 'System',
                'author_department' => 'System',
                'evaluator_message' => "Content automatically moved to Trash upon reaching expiration date ({$item['expiration_datetime']})",
                'evaluator_name' => 'System',
                'tv_id' => $item['tv_id']
            ];

            // Use ContentHandler to process the trashing
            require_once __DIR__ . '/../MyApp/ContentHandler.php';
            $contentHandler = new \MyApp\ContentHandler($pdo);
            
            // Create a mock connection for the ContentHandler
            $mockConnection = new class {
                public function send($message) {
                    // Log the message instead of sending it
                    logMessage("Would send WebSocket message: " . $message);
                }
            };

            // Process the trash action through ContentHandler
            $contentHandler->onMessage($mockConnection, json_encode($messageData));
            logMessage("Trash request processed through ContentHandler for item {$item["{$type}_id"]}");
        }
    }

    logMessage("Completed expiration check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}