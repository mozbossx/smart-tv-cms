<?php
session_start();
@include 'config_connection.php';

if (isset($_SESSION['mail'])) {
    $email = $_SESSION['mail'];
} else {
    header('location: create_account.php');
    exit;
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
    <title>Email Verification</title>
</head>
<body class="main-body">
    <div class="container-body">
        <div class="container-flex top">
            <div class="logo-flex">
                <div class="left">
                    <img src="images/usc_icon.png" class="usc-logo">
                </div>
                <div class="right">
                    <img src="images/soe_icon.png" class="soe-logo">
                </div>
            </div>
        </div>
        <div class="container-flex bottom">
            <form method="post" id="pendingUserForm">
                <p class="page-title">Email Verification</p>
                <?php include('error_message.php'); ?>
                <p style="text-align: center; user-select: none">Please enter the 6-digit code we sent via your USC email:</p>
                <br>
                <p class="display-email"><?php echo $email; ?></p>
                <br>
                <p style="text-align: center; user-select: none">We want to make sure it is a valid USC email</p>
                <div class="floating-label-container">
                    <input type="text" name="otp_code" required placeholder=" " class="floating-label-input">
                    <label for="otp_code" class="floating-label">6-digit OTP Code</label>
                </div>
                <div class="line-separator"></div>
                <input type="submit" name="submit" value="Submit Code" class="login-button">
            </form>
        </div>
    </div>
    <div id="myModal" class="modal">
        <div class="modal-content">
            <div class="green-bar-vertical">
                <p style="text-align: center"><?php echo $successMessage; ?></p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button id="proceedBtn" class="green-button" style="margin: 0">Okay</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Handle form submission via WebSocket
        const form = document.getElementById('pendingUserForm');
        const ws = new WebSocket('ws://192.168.1.13:8081?email=<?php echo urlencode($email); ?>');
        
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(form);
            const data = { session_data: { otp: "<?php echo $_SESSION['otp']; ?>", registration_details: <?php echo json_encode($_SESSION['registration_details']); ?> } };
            
            formData.forEach((value, key) => {
                data[key] = value;
            });

            console.log('Form Data:', data);
            ws.send(JSON.stringify(data));
            
            // Listen for messages from the WebSocket server
            ws.onmessage = function(event) {
                const message = JSON.parse(event.data);
                if (message.success) {
                    // Redirect the user to user_home.php if success
                    window.location.href = "index.php";
                } else {
                    // Display an error message
                    console.error(message.message);
                }
            };

            ws.onerror = function(event) {
                console.error('WebSocket error observed:', event);
            };
        });

    </script>

</body>
</html>