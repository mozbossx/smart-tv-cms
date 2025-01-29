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
</head>
<body class="main-body">
    <div class="modal-content" style="padding: 20px; margin: 10px">
        <p class="page-title">Reset Link sent to: </p>
            <?php if ($email) { ?>
            <p class="display-email"><?php echo $email; ?></p>
            <h1 style="font-size: 50px; color: #334b35; text-align: center"><i class="fa fa-envelope" aria-hidden="true"></i></h1>
            <p class="ann-author" style="color: #6E6E6E; text-align: center"><small>Please check your email inbox and click the reset link</small></p>
            <br>
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