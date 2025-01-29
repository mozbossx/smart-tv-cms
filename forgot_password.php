<?php
session_start();
include("config_connection.php");

// Check if the user has explicitly logged out
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    unset($_SESSION['email']);
    header('location: login.php?pageid=Login'); // Redirect to the login page
    exit;
}

// Check if the user is already logged in, redirect to user_home.php
if (isset($_SESSION['email'])) {
    header('location: user_home.php');
    exit;
}

require 'phpmailer/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

if (isset($_POST["submit"])) {
    $email = $_POST["email"];

    // Prepare the SQL statement to check if the email exists in the database
    $stmt = $conn->prepare("SELECT * FROM users_tb WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->num_rows;

    if ($count <= 0) {
        $error[] = "Email not found in our records.";
    } else if($count > 0) {
        // The email exists in the database
        $token = bin2hex(random_bytes(16)); // Generate a unique token
        $_SESSION['reset_token'] = $token;
        $_SESSION['reset_email'] = $email;

        if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '192.168.1.14') {
            $passwordResetLink = "http://192.168.1.14:8080/CMS%20Final/enter_new_forgot_password.php?token=$token";
        } else {
            $passwordResetLink = "https://usccms.online/enter_new_forgot_password.php?token=$token";
        }

        // Send an email to the user with the password reset link
        $mail = new PHPMailer();

        // Configure the PHPMailer instance here (set From, Subject, Body, etc.)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';

        $mail->Username = 'usc.smarttvcms@gmail.com';   // Email to be used for sending OTP Code
        $mail->Password = 'nrul yhwc mmrk hwdp';        // Password of the email (app password) yqph bmcj lztu djuf

        // send by h-hotel email
        $mail->setFrom('usc.smarttvcms@gmail.com', 'Password Reset');
        // get email from input
        $mail->addAddress($_POST["email"]);
        //$mail->addReplyTo('mozarcompany@gmail.com');

        // HTML body
        $mail->isHTML(true);
        $mail->addAddress($email); // Add the user's email here
        $mail->Subject = 'Password Reset';
        $mail->Body = "To reset your password, click this link: $passwordResetLink";
        if ($mail->send()) {
            header("Location: reset_link_success.php");
            exit;
        } else {
            $error[] = "Failed to send the password reset link. Please try again.";
        }
    } else {
        $error[] = "Email not found in our records.";
    }

} else {
    // Initialize variables to store the form values
    $email = "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
    <link rel="icon" type="image/png" href="images/usc_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <title>Forgot Password</title>
</head>
<body class="main-body">
    <div class="container-body">
        <div class="container-flex top">
            <div class="logo-flex">
                <div class="left">
                    <img src="images/usc_icon.png" class="usc-logo" onclick="window.location.href='index.php'" style="cursor: pointer;">
                </div>
                <div class="right">
                    <img src="images/soe_icon.png" class="soe-logo" onclick="window.location.href='index.php'" style="cursor: pointer;">
                </div>
            </div>
        </div>
        <div class="container-flex bottom">
            <form action="" method="post">
                <p class="page-title">Forgot Password</p>
                <?php include('error_message.php'); ?>
                <div class="floating-label-container">
                    <input type="email" name="email" required placeholder=" " class="floating-label-input" value="<?php echo $email; ?>">
                    <label for="email" class="floating-label">USC Email</label>
                </div>
                <div class="line-separator"></div>
                <div class="button-flex">
                    <div class="left-side-button">
                        <a href="login.php?pageid=Login" class="cancel-button-2">Cancel</a>
                    </div>
                    <div class="right-side-button">
                        <input type="submit" name="submit" value="Search USC Email Address" class="create-account-button">
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>