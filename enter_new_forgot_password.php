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
    <link rel="icon" href="images/usc_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="style.css">
    <link href='https://fonts.googleapis.com/css?family=Libre Baskerville' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
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
            margin-bottom: 1.8rem;
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
            border-color: #264b2b;
            outline: none;
        }

        .form-field select {
            background-color: white;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
        }

        #togglePasswordVisible {
            cursor: pointer;
            font-style: normal;
        }

        .password-reset {
            text-align: right;
            margin-bottom: 1.5rem;
        }

        .password-reset a {
            color: #264b2b;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password-button-2 {
            width: 100%;
            padding: 1rem;
            background: #264b2b;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .forgot-password-button-2:hover {
            background: #264b2b;
        }

        .forgot-password-link {
            text-align: center;
            margin-top: 1.4rem;
            color: #666;
        }

        .forgot-password-link a {
            color: #264b2b;
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
            color: #264b2b;
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
                <h1 class="forgot-password-heading">Reset Password</h1>
                <p class="forgot-password-subheading">Please enter your new password.</p>
                <?php include('error_message.php'); ?>
                <form action="" method="post">
                    <div class="form-fields">
                        <div class="form-field">
                            <i class="bi bi-lock"></i>
                            <input type="password" name="new_password" required placeholder="Password" value="<?php echo $new_password; ?>" minlength="6">
                            <span class="toggle-password">
                                <i class="bi bi-eye-slash" id="togglePasswordVisible"></i>
                            </span>
                        </div>
                        <div class="form-field">
                            <i class="bi bi-lock"></i>
                            <input type="password" name="confirm_new_password" required placeholder="Confirm Password" value="<?php echo $confirm_new_password; ?>" minlength="6">
                            <span class="toggle-password">
                                <i class="bi bi-eye-slash" id="toggleConfirmPassword"></i>
                            </span>
                        </div>
                    </div>
                    
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
    </div>
    <!-- Reset Password Successful Modal -->
    <div id="resetPasswordSuccessModal" class="modal">
        <div class="modal-content" style="padding: 15px">
            <div class="green-bar-vertical">
                <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
                <p><?php echo $successMessage; ?></p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button id="proceedBtn" class="green-button" style="margin: 0; background-color: #334b35;">Login Page</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Confirm Cancel Modal -->
    <div id="confirmCancelModal" class="modal">
        <div class="modal-content" style="padding: 15px">
            <div class="red-bar-vertical">
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></h1>
                <p>Proceed to cancel reset password?</p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button id="closeConfirmCancelModal" class="grey-button" style="margin: 0">Close</button>
                    <button id="confirmCancelButton" class="red-button" style="margin: 0; background-color: rgb(134, 43, 43); border: none;">Yes, Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // JavaScript to toggle password visibility and show/hide labels
        const togglePasswordVisible = document.querySelector("#togglePasswordVisible");
        const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
        const newPassword = document.querySelector("[name='new_password']");
        const newConfirmPassword = document.querySelector("[name='confirm_new_password']");
        const cancelButton = document.getElementById("cancelButton");
        const closeConfirmCancelModal = document.getElementById("closeConfirmCancelModal");
        const confirmCancelModal = document.getElementById("confirmCancelModal");

        togglePasswordVisible.addEventListener("click", function () {
            const type = newPassword.getAttribute("type") === "password" ? "text" : "password";
            newPassword.setAttribute("type", type);
            this.classList.toggle("bi-eye");
            this.classList.toggle("bi-eye-slash");
        });

        toggleConfirmPassword.addEventListener("click", function () {
            const type = newConfirmPassword.getAttribute("type") === "password" ? "text" : "password";
            newConfirmPassword.setAttribute("type", type);
            this.classList.toggle("bi-eye");
            this.classList.toggle("bi-eye-slash");
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