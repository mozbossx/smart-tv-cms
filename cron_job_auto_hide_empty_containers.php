<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/smart-tv-cms/cron_auto_hide_containers_error.log');

// Log file setup
$logFile = '/var/www/smart-tv-cms/empty_containers_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logMessage("Starting empty container check...");

    // Get all TVs
    $stmt = $pdo->query("SELECT tv_id, tv_department FROM smart_tvs_tb");
    $tvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tvs as $tv) {
        $tvId = $tv['tv_id'];
        $tvDepartment = $tv['tv_department'];

        // Get all containers for this TV
        $stmt = $pdo->prepare("SELECT * FROM containers_tb WHERE tv_id = ?");
        $stmt->execute([$tvId]);
        $containers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($containers as $container) {
            $type = $container['type'];
            $containerId = $container['container_id'];
            $hasContent = false;

            // Determine table name based on type
            if ($type == 'announcement' || $type == 'event' || $type == 'promaterial') {
                $tableName = "{$type}s_tb";
            } else {
                $tableName = "{$type}_tb";
            }

            // Check for valid content in the container
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM $tableName 
                WHERE tv_id = ? 
                AND department = ?
                AND isArchived = 0 
                AND isTrashed = 0 
                AND status = 'Approved'
                AND (expiration_datetime IS NULL OR expiration_datetime > NOW())
            ");
            $stmt->execute([$tvId, $tvDepartment]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $hasContent = $result['count'] > 0;

            // Update container visibility if needed
            if (!$hasContent && $container['visible'] == 1) {
                // Set container to invisible
                $stmt = $pdo->prepare("
                    UPDATE containers_tb 
                    SET visible = 0 
                    WHERE container_id = ? 
                    AND tv_id = ?
                ");
                $stmt->execute([$containerId, $tvId]);

                logMessage("Container $containerId of type $type for TV $tvId set to invisible - no valid content");

                // Send WebSocket notification
                $data = [
                    'action' => 'show_hide_content',
                    'success' => true,
                    'tv_id' => $tvId,
                    'containers' => [
                        $containerId => 0
                    ]
                ];

                // Use cURL to send the WebSocket message
                $ch = curl_init('https://usccms.online/ws');
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen(json_encode($data))
                ]);
                
                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    logMessage("WebSocket notification error: " . curl_error($ch));
                }
                curl_close($ch);
            }
        }
    }

    logMessage("Completed empty container check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
} 