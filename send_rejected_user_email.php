<?php
require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $department = $_POST['department'];
    $evaluator_name = $_POST['evaluator_name'];
    $evaluator_message = $_POST['evaluator_message'];
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
        $mail->Subject = 'Registration Rejected';
        $mail->Body = "
            <p>Hello $full_name,</p>
            <p>Your registration to USC Smart TV CMS is rejected by $evaluator_name. The reason is $evaluator_message.</p>
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