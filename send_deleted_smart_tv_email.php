<?php
require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['tv_name', 'evaluator_name', 'user_type', 'department'];
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
    $tv_name = htmlspecialchars(trim($_POST['tv_name']));
    $evaluator_name = htmlspecialchars(trim($_POST['evaluator_name']));
    $user_type = htmlspecialchars(trim($_POST['user_type']));
    $department = htmlspecialchars(trim($_POST['department']));

    // Get database connection
    require_once 'config_connection.php';

    try {
        error_log("Sending email notification for deleted smart TV");

        // Get email addresses of Super Admins and Admins in the same department
        $stmt = $conn->prepare("
            SELECT email 
            FROM users_tb 
            WHERE (user_type = 'Super Admin' OR user_type = 'Admin')
            AND (department = ? OR user_type = 'Super Admin')
        ");

        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }

        $stmt->bind_param('s', $department);
        
        if (!$stmt->execute()) {
            throw new Exception("Database execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'No recipients found'
            ]);
            error_log("No recipients found for email notification");
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
        while ($user = $result->fetch_assoc()) {
            $mail->addAddress($user['email']);
        }

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Smart TV Deleted - USC CMS";
        
        // Create email body with a professional template
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #02592b; color: white; padding: 20px; text-align: center;'>
                    <h1 style='margin: 0;'>Smart TV Deleted</h1>
                </div>
                
                <div style='padding: 20px; background-color: #f9f9f9;'>
                    <p style='color: #666;'>A Smart TV has been deleted from the system.</p>
                    
                    <div style='background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h2 style='color: #02592b; margin-top: 0;'>TV Details</h2>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>TV Name:</strong></td>
                                <td style='padding: 8px 0;'>{$tv_name}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Deleted By:</strong></td>
                                <td style='padding: 8px 0;'>{$evaluator_name}</td>
                            </tr>
                        </table>
                    </div>

                    <div style='text-align: center; margin-top: 20px;'>
                        <p style='margin-bottom: 20px;'>You can view the updated Smart TV list on the USC CMS.</p>
                        <a href='https://usccms.online/smart_tvs.php?pageid=Smart%20TVs'
                           style='background-color: #02592b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                            View Smart TVs
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
            Smart TV Deleted

            A Smart TV has been deleted from the system.

            TV Details:
            - TV Name: {$tv_name}
            - Deleted By: {$evaluator_name}

            You can view the updated Smart TV list on the USC CMS:
            https://usccms.online/smart_tvs.php?pageid=Smart%20TVs

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