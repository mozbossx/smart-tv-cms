<?php
session_start();

// Include your database connection configuration
@include 'config_connection.php';

require 'phpmailer/vendor/autoload.php';

if(empty($_SESSION['reset_email'])){
    header('location: login.php?pageid=Login'); // Redirect to the index page
    exit;
}

if(!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_token'])){
    header('location: login.php?pageid=Login'); // Redirect to the index page
    exit;
}

if (isset($_POST["submit"])) {
    $new_password = $_POST["new_password"];
    $confirm_new_password = $_POST["confirm_new_password"];

    // Check if the password length is at least 6 characters
    if (strlen($new_password) < 6 || strlen($confirm_new_password) < 6) {
        $error[] = "Passwords should be at least 6 characters long";
    } else{
        $hash = md5($new_password);

        if ($new_password !== $confirm_new_password) {
            $error[] = "Passwords do not match";
        } else {
            if (isset($_SESSION['reset_email']) && !empty($_SESSION['reset_email'])) {
                $email = $_SESSION['reset_email'];

                $stmt = $conn->prepare("UPDATE users_tb SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hash, $email);

                if ($stmt->execute()) {
                    $stmt->close();
                    $successMessage = "Password has been reset successfully! <br><strong>";
                    echo "<script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var modal = document.getElementById('resetPasswordSuccessModal');
                                var proceedBtn = document.getElementById('proceedBtn');
                                
                                modal.style.display = 'flex';
                                
                                proceedBtn.onclick = function() {
                                    // Redirect to the desired location after password reset
                                    window.location.href = 'login.php?pageid=Login';
                                    exit;
                                }
                            });
                          </script>";

                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_token']);
                    
                } else {
                    $error = array("Password update failed");
                }
            } else {
                $error = array("email not found in session");
            }
        }
    }
} else {
    $new_password = "";
    $confirm_new_password = "";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="icon" href="images/CES Logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <link href='https://fonts.googleapis.com/css?family=Libre Baskerville' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
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
            <form action="enter_new_forgot_password.php" method="post">
                <p class="page-title">Reset Password</p>
                <?php include('error_message.php'); ?>
                <div class="floating-label-container">
                    <input type="password" name="new_password" placeholder=" " class="floating-label-input" required value="<?php echo $new_password; ?>">
                    <label for="new_password" class="floating-label">New Password</label>
                    <i id="togglePassword">Show</i>
                </div>
                <div class="floating-label-container">
                    <input type="password" name="confirm_new_password" placeholder=" " class="floating-label-input" required value="<?php echo $confirm_new_password; ?>">
                    <label for="confirm_new_password" class="floating-label">Confirm New Password</label>
                    <i id="toggleConfirmPassword">Show</i>
                </div>
                <div class="line-separator"></div>
                <div class="button-flex">
                    <div class="left-side-button">
                        <button type="button" id="cancelButton" class="cancel-button-2" style="height: 100%;">Cancel</button>
                    </div>
                    <div class="right-side-button">
                        <button type="submit" name="submit" value="Reset Password" class="create-account-button">Reset Password</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Reset Password Successful Modal -->
    <div id="resetPasswordSuccessModal" class="modal">
        <div class="modal-content">
            <div class="green-bar-vertical">
                <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
                <p><?php echo $successMessage; ?></p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button id="proceedBtn" class="green-button" style="margin: 0">Login Page</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Confirm Cancel Modal -->
    <div id="confirmCancelModal" class="modal">
        <div class="modal-content">
            <div class="red-bar-vertical">
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></h1>
                <p>Proceed to cancel reset password?</p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button id="closeConfirmCancelModal" class="grey-button" style="margin: 0">Close</button>
                    <button id="confirmCancelButton" class="red-button" style="margin: 0">Yes, Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // JavaScript to toggle password visibility and show/hide labels
        const togglePassword = document.querySelector("#togglePassword");
        const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
        const newPassword = document.querySelector("[name='new_password']");
        const newConfirmPassword = document.querySelector("[name='confirm_new_password']");
        const cancelButton = document.getElementById("cancelButton");
        const closeConfirmCancelModal = document.getElementById("closeConfirmCancelModal");
        const confirmCancelModal = document.getElementById("confirmCancelModal");

        togglePassword.addEventListener("click", function () {
            // Toggle the type attribute
            const type = newPassword.getAttribute("type") === "password" ? "text" : "password";
            newPassword.setAttribute("type", type);
            // Toggle the text of the toggle button
            this.textContent = type === "password" ? "Show" : "Hide";
        });

        toggleConfirmPassword.addEventListener("click", function () {
            // Toggle the type attribute
            const type = newConfirmPassword.getAttribute("type") === "password" ? "text" : "password";
            newConfirmPassword.setAttribute("type", type);
            // Toggle the text of the toggle button
            this.textContent = type === "password" ? "Show" : "Hide";
        });

        cancelButton.addEventListener("click", function () {
            confirmCancelModal.style.display = "flex";
        });

        closeConfirmCancelModal.addEventListener("click", function () {
            confirmCancelModal.style.display = "none";
        });

        confirmCancelButton.addEventListener("click", function () {
            window.location.href = "logout.php";
            exit;
        });

    </script>
</body>
</html>