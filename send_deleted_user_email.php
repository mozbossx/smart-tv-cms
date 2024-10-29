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
        $mail->Username = 'usc.smarttvcms@gmail.com'; // Your Gmail address
        $mail->Password = 'nrul yhwc mmrk hwdp'; // Your Gmail app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('usc.smarttvcms@gmail.com', 'USC CMS');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your USC Smart TV CMS Account Has Been Deleted';
        $mail->Body = "
            <p>Dear $full_name,</p>
            <p>We regret to inform you that your USC Smart TV CMS account associated with the $department has been deleted by $evaluator_name.</p>
            <p>Message from the evaluator:</p>
            <p><em>$evaluator_message</em></p>
            <p>If you believe this is an error or have any questions, please contact the USC CMS administration.</p>
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