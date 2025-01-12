<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file setup
$logFile = __DIR__ . '/trash_deletion_log.txt';
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
    $trashDays = $stmt->fetchColumn() ?: 30;
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

                // Create message data for ContentHandler
                $messageData = [
                    'action' => 'delete_content',
                    'type' => $type,
                    "{$type}_id" => $item["{$type}_id"],
                    'user_id' => $item["{$type}_author_id"],
                    'author_id' => $item["{$type}_author_id"],
                    'author_name' => 'System',
                    'author_user_type' => 'System',
                    'author_department' => 'System',
                    'evaluator_message' => "Content automatically deleted after {$trashDays} days in Trash",
                    'evaluator_name' => 'System',
                    'tv_id' => $item['tv_id']
                ];

                // Use ContentHandler to process the deletion
                require_once __DIR__ . '/../MyApp/ContentHandler.php';
                $contentHandler = new \MyApp\ContentHandler($pdo);
                
                // Create a mock connection for the ContentHandler
                $mockConnection = new class {
                    public function send($message) {
                        // Log the message instead of sending it
                        logMessage("Would send WebSocket message: " . $message);
                    }
                };

                // Process the deletion through ContentHandler
                $contentHandler->onMessage($mockConnection, json_encode($messageData));
                logMessage("Deletion request processed through ContentHandler for item {$item["{$type}_id"]}");
            }
        }
    }

    logMessage("Completed trash deletion check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}