<?php
require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['content_id', 'content_type', 'author_name', 'author_user_type', 'author_department', 'logged_in_full_name', 'evaluator_message', 'tv_id'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit;
    }

    // Sanitize inputs
    $content_id = htmlspecialchars(trim($_POST['content_id']));
    $content_type = htmlspecialchars(trim($_POST['content_type']));
    $author_name = htmlspecialchars(trim($_POST['author_name']));
    $author_user_type = htmlspecialchars(trim($_POST['author_user_type']));
    $author_department = htmlspecialchars(trim($_POST['author_department']));
    $logged_in_full_name = htmlspecialchars(trim($_POST['logged_in_full_name']));
    $evaluator_message = htmlspecialchars(trim($_POST['evaluator_message']));
    $tv_id = htmlspecialchars(trim($_POST['tv_id']));

    // Get database connection
    require_once 'config_connection.php';

    try {
        error_log("Sending email notification for deleted content");

        // Get user email from database based on author details
        $stmt = $conn->prepare("
            SELECT email 
            FROM users_tb 
            WHERE full_name = ?
            AND user_type = ?
            AND department = ?
        ");

        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }

        $stmt->bind_param('sss', $author_name, $author_user_type, $author_department);
        
        if (!$stmt->execute()) {
            throw new Exception("Database execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
            error_log("User not found for email notification");
            exit;
        }

        // Get TV name
        $stmt = $conn->prepare("
            SELECT tv_name 
            FROM smart_tvs_tb 
            WHERE tv_id = ?
        ");
        $stmt->bind_param('i', $tv_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tv = $result->fetch_assoc();
        $tv_name = $tv ? $tv['tv_name'] : 'Unknown TV';

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
        $mail->addAddress($user['email']);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Your " . ucfirst($content_type) . " Has Been Permanently Deleted - USC CMS";
        
        // Create email body with a professional template
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #02592b; color: white; padding: 20px; text-align: center;'>
                    <h1 style='margin: 0;'>Content Permanently Deleted</h1>
                </div>
                
                <div style='padding: 20px; background-color: #f9f9f9;'>
                    <p style='color: #666;'>Your content has been permanently deleted from the system.</p>
                    
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
                                <td style='padding: 8px 0; color: #666;'><strong>Deleted By:</strong></td>
                                <td style='padding: 8px 0;'>{$logged_in_full_name}</td>
                            </tr>
                        </table>
                    </div>

                    <div style='background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h2 style='color: #02592b; margin-top: 0;'>Message</h2>
                        <p style='color: #666; margin-bottom: 0;'>{$evaluator_message}</p>
                    </div>

                    <div style='background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p style='margin: 0;'><strong>Note:</strong> This action cannot be undone. The content has been permanently removed from the system.</p>
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
            Content Permanently Deleted

            Your content has been permanently deleted from the system.

            Content Details:
            - Content Type: " . ucfirst($content_type) . "
            - TV Display: {$tv_name}
            - Deleted By: {$logged_in_full_name}

            Message:
            {$evaluator_message}

            Note: This action cannot be undone. The content has been permanently removed from the system.

            This is an automated message from the USC CMS. Please do not reply to this email.
        ";
        $mail->AltBody = $plainText;

        // Send email
        $mail->send();
        echo json_encode([
            'success' => true, 
            'message' => 'Email notification sent successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"
        ]);
    } finally {
        // Close database connection
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
} 