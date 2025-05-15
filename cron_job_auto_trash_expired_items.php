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

// Simple counter to prevent excessive emails per run
$emailCounter = 0;
$maxEmailsPerRun = 50; // Limit emails per cron run to prevent hitting Gmail limits

// Function to send email notification to content author
function sendExpirationEmail($author_email, $author_name, $content_type, $tv_name, $expiration_date) {
    global $emailCounter, $maxEmailsPerRun;
    
    // Check if we've hit the limit for this run
    if ($emailCounter >= $maxEmailsPerRun) {
        logMessage("Email to $author_email not sent due to per-run limit ($maxEmailsPerRun) being reached");
        return false;
    }
    
    try {
        // Create PHPMailer instance
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usccms.tv@gmail.com';
        $mail->Password = 'dcxp gucw kflt dxkj';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('usccms.tv@gmail.com', 'USC CMS Notifications');
        $mail->addAddress($author_email);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Your " . ucfirst($content_type) . " Has Expired - USC CMS";
        
        // Create email body with a professional template
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #02592b; color: white; padding: 20px; text-align: center;'>
                    <h1 style='margin: 0;'>Content Expired</h1>
                </div>
                
                <div style='padding: 20px; background-color: #f9f9f9;'>
                    <p style='color: #666;'>Your content has automatically been moved to the Trash as it has reached its expiration date.</p>
                    
                    <div style='background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h2 style='color: #02592b; margin-top: 0;'>Content Details</h2>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Content Type:</strong></td>
                                <td style='padding: 8px 0;'>" . ucfirst($content_type) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>TV Display:</strong></td>
                                <td style='padding: 8px 0;'>{$tv_name}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Expiration Date:</strong></td>
                                <td style='padding: 8px 0;'>{$expiration_date}</td>
                            </tr>
                        </table>
                    </div>

                    <div style='text-align: center; margin-top: 20px;'>
                        <p style='margin-bottom: 20px;'>You can view your trashed content in the Trash section of the USC CMS.</p>
                        <a href='https://usccms.online/trash.php?pageid=Trash'
                           style='background-color: #02592b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                            View Trashed Content
                        </a>
                    </div>
                </div>
                
                <div style='text-align: center; padding: 20px; color: #666; font-size: 12px;'>
                    <p>This is an automated message from the USC CMS. Please do not reply to this email.</p>
                </div>
            </div>
        ";
        
        $mail->Body = $body;
        
        // Plain text version
        $plainText = "
            Content Expired

            Your content has automatically been moved to the Trash as it has reached its expiration date.

            Content Details:
            - Content Type: " . ucfirst($content_type) . "
            - TV Display: {$tv_name}
            - Expiration Date: {$expiration_date}

            You can view your trashed content in the Trash section of the USC CMS:
            https://usccms.online/trash.php?pageid=Trash

            This is an automated message from the USC CMS. Please do not reply to this email.
        ";
        $mail->AltBody = $plainText;

        // Send email
        $mail->send();
        
        // Increment the email counter
        $emailCounter++;
        logMessage("Email sent successfully. Emails sent this run: $emailCounter/$maxEmailsPerRun");
        
        return true;
    } catch (Exception $e) {
        logMessage("Email sending failed: " . $e->getMessage());
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
    
    // default content types
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
            
            // Get TV name
            $stmt = $pdo->prepare("
                SELECT tv_name 
                FROM smart_tvs_tb 
                WHERE tv_id = ?
            ");
            $stmt->execute([$item['tv_id']]);
            $tv = $stmt->fetch(PDO::FETCH_ASSOC);
            $tv_name = $tv ? $tv['tv_name'] : 'Unknown TV';
            
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
            
            // Send email notification to author
            if (!empty($author['email'])) {
                $result = sendExpirationEmail(
                    $author['email'],
                    $author['full_name'],
                    $type,
                    $tv_name,
                    $item['expiration_datetime']
                );
                
                if ($result) {
                    logMessage("Email notification sent to {$author['email']} for expired {$type}");
                } else {
                    logMessage("Failed to send email notification to {$author['email']}");
                    
                    // Check if we've hit the per-run limit
                    if ($emailCounter >= $maxEmailsPerRun) {
                        logMessage("Per-run email limit of $maxEmailsPerRun reached. No more emails will be sent in this run.");
                        break 2; // Break out of both loops to stop processing more items
                    }
                }
            } else {
                logMessage("No email found for author ID: {$item['author_id']}");
            }
            
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