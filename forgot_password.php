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

        if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '192.168.1.10') {
            $passwordResetLink = "http://192.168.1.10:8080/CMS%20Final/enter_new_forgot_password.php?token=$token";
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

        $mail->Username = 'usccms.tv@gmail.com';   // Email to be used for sending OTP Code
        $mail->Password = 'dcxp gucw kflt dxkj';   // Password of the email (app password) dcxp gucw kflt dxkj

        // send by h-hotel email
        $mail->setFrom('usccms.tv@gmail.com', 'Password Reset');
        // get email from input
        $mail->addAddress($_POST["email"]);

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
    <style>
        .forgot-password-page {
            min-height: 100vh;
            background-image: url("images/usc.jpg");
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Roboto Flex', sans-serif;
        }

        .forgot-password-wrapper {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            box-sizing: border-box;
        }

        .school-logos {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1rem;
        }

        .school-logo {
            height: 80px;
            width: auto;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .school-logo:hover {
            transform: scale(1.05);
        }

        .forgot-password-heading {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .forgot-password-subheading {
            color: #666;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .form-fields {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            margin-bottom: 1.2rem;
        }

        .form-field {
            position: relative;
            box-sizing: border-box;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 50px;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 1;
            /* Add these to ensure it doesn't expand */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password i {
            font-style: normal;
        }

        .form-field input[type="password"] {
            padding-right: 40px;
        }

        .form-field i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .form-field input, .form-field select {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .form-field input:focus, .form-field select:focus {
            border-color: #007638;
            outline: none;
        }

        .form-field select {
            background-color: white;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
        }

        #togglePasswordVisibleVisible {
            cursor: pointer;
            font-style: normal;
        }

        .password-reset {
            text-align: right;
            margin-bottom: 1.5rem;
        }

        .password-reset a {
            color: #007638;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password-button-2 {
            width: 100%;
            padding: 1rem;
            background: #007638;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .forgot-password-button-2:hover {
            background: #007638;
        }

        .forgot-password-link {
            text-align: center;
            margin-top: 1.4rem;
            color: #666;
        }

        .forgot-password-link a {
            color: #007638;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-password-link a:hover {
            text-decoration: underline;
        }

        .forgot-password-container {
            width: 100%;
            max-width: 450px;
            padding: 0 1rem;
            box-sizing: border-box;
        }

        .login-link {
            margin-top: 1.4rem;
            text-align: center;
            color: #666;
        }

        .login-link a {
            color: #007638;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {            
            .school-logo {
                height: 60px;
            }

            .school-logos {
                margin-bottom: 0.5rem;
            }
            
            .forgot-password-heading {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="forgot-password-page">
    <div class="forgot-password-container">
        <div class="forgot-password-wrapper">            
            <div class="forgot-password-form-container">
                <h1 class="forgot-password-heading">Forgot Password</h1>
                <p class="forgot-password-subheading">Please fill your USC email and we'll send you a link to recover your password.</p>
                <?php include('error_message.php'); ?>
                <form action="" method="post">
                    <div class="form-fields">
                        <div class="form-field">
                            <i class="bi bi-envelope"></i>
                            <input type="email" name="email" value="<?php echo $email; ?>" 
                                   required placeholder="USC Email" id="emailInput">
                        </div>
                    </div>
                    
                    <div class="button-flex">
                        <div class="left-side-button">
                            <a href="login.php?pageid=Login" class="cancel-button-2" style="color: black">Cancel</a>
                        </div>
                        <div class="right-side-button">
                            <input type="submit" name="submit" value="Confirm" class="create-account-button">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>