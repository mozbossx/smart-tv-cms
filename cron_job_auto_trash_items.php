<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file setup
$logFile = __DIR__ . '/cron_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logMessage("Starting expired items check...");

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
        if ($type == 'announcement' || $type == 'event' ||$type == 'promaterial') {
            $tableName = "{$type}s_tb";
        } else {
            $tableName = "{$type}_tb";
        }
        logMessage("Processing table: $tableName");

        // Fetch archived items
        $stmt = $pdo->prepare("SELECT * FROM $tableName WHERE isArchived = 1 AND isTrashed = 0");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("Found " . count($items) . " archived items in $tableName");

        foreach ($items as $item) {
            $archivedDate = new DateTime($item['datetime_archived']);
            $expiryDate = clone $archivedDate;
            $expiryDate->modify("+{$archiveDays} days");
            $today = new DateTime();

            if ($today > $expiryDate) {
                logMessage("Processing expired item {$item["{$type}_id"]} (archived on {$item['datetime_archived']})");

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
                    'evaluator_message' => "Content automatically moved to Trash after {$archiveDays} days in Archives",
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
    }

    logMessage("Completed expired items check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}