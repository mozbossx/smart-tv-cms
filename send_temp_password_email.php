<?php
require 'phpmailer/vendor/autoload.php';
include 'config_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $full_name = $_POST['full_name'];
    $department = $_POST['department'];
    $evaluator_name = $_POST['evaluator_name'];
    $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : 'User';

    // Get all Super Admins and Admins from the same department
    $admin_emails = [];
    try {
        // Query to get all Super Admins and department-specific Admins
        $query = "SELECT email, full_name, user_type FROM users_tb WHERE 
                 (user_type = 'Super Admin' OR 
                 (user_type = 'Admin' AND department = ?)) AND 
                 status = 'Approved'";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $department);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $admin_emails[] = [
                'email' => $row['email'],
                'name' => $row['full_name'],
                'type' => $row['user_type']
            ];
        }
    } catch (Exception $e) {
        // Log error but continue to send email to new user
        error_log("Database error: " . $e->getMessage());
    }

    // Send email to new user
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usccms.tv@gmail.com'; // Your Gmail address
        $mail->Password = 'dcxp gucw kflt dxkj'; // Your Gmail app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('usccms.tv@gmail.com', 'USC CMS');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your Temporary Password for USC Smart TV CMS';
        $mail->Body = "
            <p>Hello $full_name,</p>
            <p>You've been added to the USC Smart TV CMS by $evaluator_name. Here is your temporary password:</p>
            <h3>$password</h3>
            <p>Please log in and change your temporary password as soon as possible.</p>
            <br>
            <p>Regards,<br>University of San Carlos</p>
        ";

        $mail->send();
        
        // Now send notification emails to all admins
        if (!empty($admin_emails)) {
            foreach ($admin_emails as $admin) {
                $admin_mail = new PHPMailer(true);
                $admin_mail->isSMTP();
                $admin_mail->Host = 'smtp.gmail.com';
                $admin_mail->SMTPAuth = true;
                $admin_mail->Username = 'usccms.tv@gmail.com';
                $admin_mail->Password = 'dcxp gucw kflt dxkj';
                $admin_mail->SMTPSecure = 'tls';
                $admin_mail->Port = 587;
                
                $admin_mail->setFrom('usccms.tv@gmail.com', 'USC CMS');
                $admin_mail->addAddress($admin['email']);
                
                $admin_mail->isHTML(true);
                $admin_mail->Subject = 'New User Account Added';
                $admin_mail->Body = "
                    <p>Dear {$admin['name']},</p>
                    <p>This is to inform you that a new user account has been added to the USC Smart TV CMS:</p>
                    <ul>
                        <li><strong>Name:</strong> $full_name</li>
                        <li><strong>Email:</strong> $email</li>
                        <li><strong>Department:</strong> $department</li>
                        <li><strong>Role:</strong> $user_type</li>
                        <li><strong>Added by:</strong> $evaluator_name</li>
                    </ul>
                    <br>
                    <p>Regards,<br>USC CMS System</p>
                ";
                
                $admin_mail->send();
            }
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
    
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}