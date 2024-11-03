<?php
require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $department = $_POST['department'];
    $user_type = $_POST['user_type'];
    $evaluator_name = $_POST['evaluator_name'];

    // Initialize PHPMailer
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
        $mail->Subject = 'Your USC Smart TV CMS Account Has Been Updated';
        $mail->Body = "
            <p>Hello $full_name,</p>
            <p>Your USC Smart TV CMS account has been updated by $evaluator_name.</p>
            <p>Your current credentials are:</p>
            <ul>
                <li>Role: $user_type</li>
                <li>Department: $department</li>
            </ul>
            <p>If you believe this change was made in error, please contact your system administrator.</p>
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