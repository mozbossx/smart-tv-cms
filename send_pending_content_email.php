<?php
require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['content_type', 'author_name', 'author_type', 'author_department'];
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
    $content_type = htmlspecialchars(trim($_POST['content_type']));
    $author_name = htmlspecialchars(trim($_POST['author_name']));
    $author_type = htmlspecialchars(trim($_POST['author_type']));
    $author_department = htmlspecialchars(trim($_POST['author_department']));

    // Get database connection
    require_once 'config_connection.php';

    try {
        error_log("Sending email notification for new content");
        // Get admin emails from database
        $stmt = $conn->prepare("
            SELECT email, full_name, user_type 
            FROM users_tb 
            WHERE (user_type = 'Admin' AND department = ?) 
            OR user_type = 'Super Admin'
        ");

        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }

        $stmt->bind_param('s', $author_department);
        
        if (!$stmt->execute()) {
            throw new Exception("Database execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $admins = $result->fetch_all(MYSQLI_ASSOC);

        if (empty($admins)) {
            echo json_encode([
                'success' => false,
                'message' => 'No admin users found to notify'
            ]);
            error_log("No admin users found to notify");
            exit;
        }

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

        // Add all admin recipients
        foreach ($admins as $admin) {
            $mail->addAddress($admin['email']);
        }

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "New {$content_type} Pending Approval - USC CMS";
        
        // Create email body with a more professional template
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #02592b; color: white; padding: 20px; text-align: center;'>
                    <h1 style='margin: 0;'>New Content Pending Approval</h1>
                </div>
                
                <div style='padding: 20px; background-color: #f9f9f9;'>
                    <p style='color: #666;'>A new content has been submitted and requires your review.</p>
                    
                    <div style='background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h2 style='color: #02592b; margin-top: 0;'>Content Details</h2>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Content Type:</strong></td>
                                <td style='padding: 8px 0;'>{$content_type}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Author:</strong></td>
                                <td style='padding: 8px 0;'>{$author_name}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Author Type:</strong></td>
                                <td style='padding: 8px 0;'>{$author_type}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Department:</strong></td>
                                <td style='padding: 8px 0;'>{$author_department}</td>
                            </tr>
                        </table>
                    </div>

                    <div style='text-align: center; margin-top: 20px;'>
                        <p style='margin-bottom: 20px;'>Please log in to the USC CMS to review this content.</p>
                        <a href='https://usccms.online/login.php?pageid=Login' 
                           style='background-color: #02592b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                            Review Content
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
            New Content Pending Approval
            
            A new content has been submitted and requires your review.
            
            Content Details:
            - Content Type: {$content_type}
            - Author: {$author_name}
            - Author Type: {$author_type}
            - Department: {$author_department}
            
            Please log in to the USC CMS to review this content:
            https://usccms.online/login.php?pageid=Login
            
            This is an automated message from the USC CMS. Please do not reply to this email.
        ";
        $mail->AltBody = $plainText;

        // Send email
        $mail->send();
        echo json_encode([
            'success' => true, 
            'message' => 'Email notifications sent successfully to ' . count($admins) . ' admin(s)'
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