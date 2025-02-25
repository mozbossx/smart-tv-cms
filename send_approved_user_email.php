<?php
require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $department = $_POST['department'];
    $evaluator_name = $_POST['evaluator_name'];

    // Initialize PHPMailer
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
        $mail->Subject = 'Registration Approved';
        $mail->Body = "
            <p>Hello $full_name,</p>
            <p>Your registration to USC Smart TV CMS is approved by $evaluator_name. You can now log in to the system by clicking the link below.</p>
            <a href='http://192.168.1.3:8080/CMS%20Final/login.php?pageid=login'>Log in to USC Smart TV CMS</a>
            <br>
            <p>Regards,<br>University of San Carlos</p>
        ";

        $mail->send();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}