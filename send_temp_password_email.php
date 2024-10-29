<?php
require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $full_name = $_POST['full_name'];
    $department = $_POST['department'];
    $evaluator_name = $_POST['evaluator_name'];

    // Initialize PHPMailer
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usc.smarttvcms@gmail.com'; // Your Gmail address
        $mail->Password = 'nrul yhwc mmrk hwdp'; // Your Gmail app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('usc.smarttvcms@gmail.com', 'USC CMS');
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
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}