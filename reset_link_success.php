<?php
session_start();
@include 'config_connection.php';

if (isset($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
} else {
    header('location: login.php?pageid=Login'); // Redirect to the index page
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="icon" href="images/usc_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <link href='https://fonts.googleapis.com/css?family=Libre Baskerville' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
    <style>
        .reset-link-heading {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .reset-link-subheading {
            color: #666;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 480px) {                        
            .reset-link-heading {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="main-body">
    <div class="modal-content" style="padding: 2rem; margin: 10px; border-radius: 20px">
        <?php if ($email) { ?>
        <h1 class="reset-link-heading">Reset Link sent to: </h1>
        <p class="display-email"><?php echo $email; ?></p>
        <h1 style="font-size: 50px; color: #334b35; text-align: center"><i class="fa fa-envelope" aria-hidden="true"></i></h1>
        <p class="reset-link-subheading">Please check inbox and click the reset password link.</p>
        <div class="button-flex" style="flex-direction: column; align-items: center;">
            <a href="https://gmail.com" target="_blank" class="green-button" style="background-color: #334b35; margin-bottom: 10px; margin-right: 0; text-decoration: none; font-size: 15px;">Open Gmail</a>
            <a href="login.php?pageid=Login" class="go-to-home-link" style="text-decoration: underline; font-size: 15px;">Back to Login Page</a>
        </div>
        <?php } else { ?>
            <h1>No email found!</h1>
            <a href="login.php?pageid=Login" class="cancel-button">Back</a>
        <?php } ?>
    </div>
</body>
</html>