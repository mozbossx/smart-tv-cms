<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/smart-tv-cms/cron_auto_trash_expired_items_error.log');

require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log file setup
$logFile = '/var/www/smart-tv-cms/expired_content_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Add email sending function
function sendExpirationEmail($email, $fullName, $contentType, $contentId, $expirationDate) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usc.smarttvcms@gmail.com';
        $mail->Password = 'nrul yhwc mmrk hwdp';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('usc.smarttvcms@gmail.com', 'USC CMS');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Content Moved to Trash - Expiration Date Reached';
        $mail->Body = "
            <p>Dear $fullName,</p>
            <p>This is to inform you that your content (ID: $contentId) in the $contentType section has been automatically moved to Trash as it reached its expiration date ($expirationDate).</p>
            <p>Your content will remain in the Trash for a specified period before being permanently deleted. If you wish to restore this content, please do so from the Trash section and set a new expiration date.</p>
            <p>If you have any questions, please contact your department administrator.</p>
            <br>
            <p>Regards,<br>University of San Carlos</p>
        ";

        $mail->send();
        logMessage("Expiration notification email sent successfully to $email");
        return true;
    } catch (Exception $e) {
        logMessage("Failed to send expiration email to $email: {$mail->ErrorInfo}");
        return false;
    }
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

        // Get expired content that hasn't been trashed yet
        $stmt = $pdo->prepare("
            SELECT *, 
                   {$type}_author_id as author_id,
                   expiration_datetime,
                   tv_id
            FROM $tableName 
            WHERE isArchived = 0 
            AND isTrashed = 0 
            AND status = 'Approved'
            AND expiration_datetime <= NOW()
            AND expiration_datetime IS NOT NULL
        ");
        $stmt->execute();
        $expiredItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($expiredItems as $item) {
            // Get author details before updating status
            $stmt = $pdo->prepare("
                SELECT u.email, u.full_name, u.user_type, u.department 
                FROM users_tb u 
                WHERE user_id = ?
            ");
            $stmt->execute([$item['author_id']]);
            $author = $stmt->fetch(PDO::FETCH_ASSOC);

            // Update the item status
            $stmt = $pdo->prepare("
                UPDATE $tableName 
                SET isTrashed = 1,
                    datetime_trashed = NOW()
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

            // Send email notification if author exists
            if ($author && isset($author['email'])) {
                sendExpirationEmail(
                    $author['email'],
                    $author['full_name'],
                    $type,
                    $item["{$type}_id"],
                    $item['expiration_datetime']
                );
            }
            
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
            
            // Send notification to WebSocket server using cURL
            $trashData = [
                'action' => 'trash_content',
                'type' => $type,
                "{$type}_id" => $item["{$type}_id"],
                'user_id' => $item['author_id'],
                'author_id' => $item['author_id'],
                'author_name' => $author['full_name'] ?? 'System',
                'author_user_type' => $author['user_type'] ?? 'System',
                'author_department' => $author['department'] ?? 'System',
                'evaluator_name' => 'System',
                'evaluator_message' => "Content automatically moved to Trash upon reaching expiration date ({$item['expiration_datetime']})",
                'tv_id' => $item['tv_id']
            ];

            // Use cURL to send the WebSocket message
            $ch = curl_init('https://usccms.online/ws');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($trashData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($trashData))
            ]);
            
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                logMessage("WebSocket notification error: " . curl_error($ch));
            }
            curl_close($ch);
            
            logMessage("Trashed expired item {$item["{$type}_id"]} in $tableName and sent WebSocket notification");
        }
    }

    logMessage("Completed expiration check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}