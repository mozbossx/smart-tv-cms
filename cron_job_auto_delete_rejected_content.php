<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/smart-tv-cms/cron_auto_delete_rejected_content_error.log');

require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log file setup
$logFile = '/var/www/smart-tv-cms/rejected_content_deletion_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Add email sending function
function sendRejectedContentDeletionEmail($email, $fullName, $contentType, $contentId, $expirationDate) {
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
        $mail->Subject = 'Rejected Content Permanently Deleted';
        $mail->Body = "
            <p>Dear $fullName,</p>
            <p>This is to inform you that your rejected content (ID: $contentId) in the $contentType section has been permanently deleted by the system as it reached its expiration date ($expirationDate).</p>
            <p>If you wish to resubmit this content, please create a new submission addressing the previous rejection reasons.</p>
            <p>If you have any questions, please contact your department administrator.</p>
            <br>
            <p>Regards,<br>University of San Carlos</p>
        ";

        $mail->send();
        logMessage("Rejected content deletion email sent successfully to $email");
        return true;
    } catch (Exception $e) {
        logMessage("Failed to send rejected content deletion email to $email: {$mail->ErrorInfo}");
        return false;
    }
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logMessage("Starting rejected content deletion check...");

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

        // Fetch rejected items that have expired
        $stmt = $pdo->prepare("SELECT * FROM $tableName WHERE status = 'Rejected' AND isArchived = 0 AND isTrashed = 0 AND expiration_datetime <= NOW()");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("Found " . count($items) . " expired rejected items in $tableName");

        foreach ($items as $item) {
            logMessage("Processing expired rejected item {$item["{$type}_id"]} (expired on {$item['expiration_datetime']})");

            // Get author details before deleting content
            $stmt = $pdo->prepare("
                SELECT email, full_name, department 
                FROM users_tb 
                WHERE user_id = ?
            ");
            $stmt->execute([$item["{$type}_author_id"]]);
            $author = $stmt->fetch(PDO::FETCH_ASSOC);

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

            // Send email notification if author exists
            if ($author && isset($author['email'])) {
                sendRejectedContentDeletionEmail(
                    $author['email'],
                    $author['full_name'],
                    $type,
                    $item["{$type}_id"],
                    $item['expiration_datetime']
                );
            }

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
                'rejected_content_deleted_by_system',
                'deleted',
                'System',
                'System',
                'System',
                "Rejected content automatically deleted after expiration",
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
            
            logMessage("Created notification for user {$item["{$type}_author_id"]} about deleted rejected content {$item["{$type}_id"]}");
        }
    }

    logMessage("Completed rejected content deletion check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}