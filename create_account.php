<?php
session_start();
@include 'config_connection.php';

// Initialize $successMessage
$successMessage = "";

// Check if the user is already logged in, redirect to user-home.php
if (isset($_SESSION['full_name'])) {
    header('location: user_home.php');
    exit;
}

require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

if (isset($_POST["submit"])) {
    $email = $_POST["email"];
    $full_name = $_POST["full_name"];
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirmpassword"];
    $department = $_POST["department"];
    $datetime_registered = date("Y-m-d H:i:s");

    // Check if email contains '@usc.edu.ph'
    if (strpos($email, '@usc.edu.ph') === false) {
        $error[] = "Email must be a USC email address (must contain '@usc.edu.ph')";
    } else {
        // Determine user_type based on the email pattern
        if (preg_match('/^\d{1,8}@usc\.edu\.ph$/', $email)) {
            $user_type = "Student";
        } else {
            // User type will still be empty if not student
            $user_type = "TBD";
        }

        // Check if full_name contains symbols and numbers
        $symbolCheck = preg_match('/[~`!1@2#3\$4%5\^6&7\*8\(9\)0_\-+=:;"<>,.\/?\|]/', $full_name);

        if (!empty($email) && !empty($password) && $password === $confirmPassword) {
            // Prepare and execute the email check query
            $stmt = $conn->prepare("SELECT * FROM users_tb WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $check_email_query = $stmt->get_result();
            $rowCountEmail = $check_email_query->num_rows;
            $stmt->close();

            // Prepare and execute the name check query
            $stmt = $conn->prepare("SELECT * FROM users_tb WHERE full_name = ?");
            $stmt->bind_param("s", $full_name);
            $stmt->execute();
            $check_name_query = $stmt->get_result();
            $rowCountName = $check_name_query->num_rows;
            $stmt->close();

            if ($rowCountEmail > 0) {
                $error[] = "User with this USC email already exists!";
            } elseif ($rowCountName > 0) {
                $error[] = "User with this name already exists!";
            } elseif ($symbolCheck) {
                $error[] = "Full Name must not contain symbols and numbers!";
            } else {
                if (strlen($password) < 6) {
                    $error[] = "Password should be at least 6 characters long.";
                } elseif ($department === "") {
                    $error[] = "Please select a department.";
                } else {
                    $password_hash = $password;

                    // Store user details in the session instead of inserting into the database
                    $_SESSION['registration_details'] = [
                        'full_name' => $full_name,
                        'email' => $email,
                        'password' => $password_hash,
                        'department' => $department,
                        'user_type' => $user_type,
                        'datetime_registered' => $datetime_registered,
                    ];

                    // Generate OTP for testing purposes
                    $otp = rand(100000, 999999);
                    $_SESSION['otp'] = $otp;
                    $_SESSION['mail'] = $email;

                    // Initialize PHPMailer
                    $mail = new PHPMailer;
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->Port = 587;
                    $mail->SMTPAuth = true;
                    $mail->SMTPSecure = 'tls';

                    $mail->Username = 'usccms.tv@gmail.com'; // Email to be used for sending OTP Code
                    $mail->Password = 'dcxp gucw kflt dxkj'; // Password of the email (app password)

                    $mail->setFrom('usccms.tv@gmail.com', 'USC CMS');
                    $mail->addAddress($_POST["email"]);

                    $mail->isHTML(true);
                    $mail->Subject = "Your verification code";
                    $mail->Body = "<p>From USC CMS,</p>
                    <h3>Your verification OTP code is $otp</h3>
                    <br><br>
                    <p>Regards,<br>University of San Carlos</p>";

                    if ($mail->send()) {
                        $successMessage = "OTP sent to: <br><br><strong style='background: #c0ffc6; padding: 5px; border-radius: 5px;'>" . $email . "</strong>";
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var modal = document.getElementById('createAccountModal');
                                var proceedBtn = document.getElementById('proceedBtn');
                                var cancelBtn = document.getElementById('cancelBtn');
                                
                                modal.style.display = 'flex';
                                
                                proceedBtn.onclick = function() {
                                    window.location.replace('verify_account.php');
                                }
                                
                                cancelBtn.onclick = function() {
                                    modal.style.display = 'none';
                                }
                                
                                window.onclick = function(event) {
                                    if (event.target == modal) {
                                        modal.style.display = 'none';
                                    }
                                }
                            });
                        </script>";
                    } else {
                        $error[] = "Registration Failed! Error sending email. Try again" . $mail->ErrorInfo;
                    }
                }
            }
        } else {
            $error[] = "Passwords do not match!";
        }
    }
} else {
    // Initialize variables to store the form values
    $full_name = "";
    $email = "";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/png" href="images/usc_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .signup-page {
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

        .signup-wrapper {
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

        .signup-heading {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .signup-subheading {
            color: #666;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .form-fields {
            display: flex;
            flex-direction: column;
            margin-bottom: 1.8rem;
        }

        .form-field {
            position: relative;
            box-sizing: border-box;
            margin-bottom: 2px;
        }

        .validation-message {
            display: none;
            color: #666;
            font-size: 0.7rem;
            padding-left: 10px;
            margin-bottom: 8px;
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

        #togglePasswordVisible {
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

        .signup-button-2 {
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

        .signup-button-2:hover {
            background: #007638;
        }

        .signup-link {
            text-align: center;
            margin-top: 1.4rem;
            color: #666;
        }

        .signup-link a {
            color: #007638;
            text-decoration: none;
            font-weight: 500;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .signup-container {
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
            
            .signup-heading {
                font-size: 1.5rem;
            }
        }
    </style>
    <title>Create Account</title>
</head>
<body class="signup-page">
    <div class="signup-container">
        <div class="signup-wrapper">            
            <div class="signup-form-container">
                <h1 class="signup-heading">Create Account</h1>
                <p class="signup-subheading">Please fill in the details below</p>
                
                <?php include('error_message.php'); ?>
                
                <form action="" method="post">
                    <div class="form-fields">
                        <div class="form-field">
                            <i class="bi bi-person"></i>
                            <input type="text" name="full_name" value="<?php echo $full_name; ?>" 
                                   required placeholder="Full Name" oninput="capitalizeWords(this)" id="fullNameInput">
                        </div>
                        <div class="validation-message" id="fullNameValidation">Full Name must not contain symbols and numbers</div>

                        <div class="form-field" style="margin-top: 5px;">
                            <i class="bi bi-envelope"></i>
                            <input type="email" name="email" value="<?php echo $email; ?>" 
                                   required placeholder="USC Email" id="emailInput">
                        </div>
                        <div class="validation-message" id="emailValidation">Email must contain @usc.edu.ph</div>

                        <div class="form-field" style="margin-top: 5px;">
                            <i class="bi bi-lock"></i>
                            <input type="password" name="password" required placeholder="Password" id="passwordInput" minlength="6">
                            <span class="toggle-password">
                                <i class="bi bi-eye-slash" id="togglePasswordVisible"></i>
                            </span>
                        </div>
                        <div class="validation-message" id="passwordValidation">Password must be at least 6 characters long</div>

                        <div class="form-field" style="margin-top: 5px;">
                            <i class="bi bi-lock"></i>
                            <input type="password" name="confirmpassword" required placeholder="Confirm Password" id="confirmPasswordInput" minlength="6">
                            <span class="toggle-password">
                                <i class="bi bi-eye-slash" id="toggleConfirmPassword"></i>
                            </span>
                        </div>
                        <div class="validation-message" id="confirmPasswordValidation">Passwords must match</div>

                        <div class="form-field" style="margin-top: 5px;">
                            <i class="bi bi-building"></i>
                            <select name="department" required style="color: black" id="departmentInput">
                                <option value="">Select Department</option>
                                <option value="COMPUTER ENGINEERING">Computer Engineering</option>
                                <option value="CHEMICAL ENGINEERING">Chemical Engineering</option>
                                <option value="CIVIL ENGINEERING">Civil Engineering</option>
                                <option value="INDUSTRIAL ENGINEERING">Industrial Engineering</option>
                                <option value="ELECTRICAL ENGINEERING">Electrical Engineering</option>
                                <option value="MECHANICAL ENGINEERING">Mechanical Engineering</option>
                                <option value="ELECTRONICS ENGINEERING">Electronics Engineering</option>
                            </select>
                        </div>
                        <div class="validation-message" id="departmentValidation">Please select a department</div>
                    </div>

                    <button type="submit" name="submit" class="signup-button-2">Create Account</button>
                    
                    <div class="login-link">
                        <p>Already have an account? <a href="login.php?pageid=Login">Sign In</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="createAccountModal" class="modal">
        <div class="modal-content" style="padding: 15px">
            <h1 style="color: #334b35; text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-check-circle"></i> Success</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;"><?php echo $successMessage; ?></p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button id="proceedBtn" class="green-button" style="margin: 0; background-color: #334b35;">Proceed</button>
            </div>
        </div>
    </div>

    <script>
        // JavaScript to toggle password visibility and show/hide labels
        const togglePasswordVisible = document.querySelector("#togglePasswordVisible");
        const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
        const password1 = document.querySelector("[name='password']");
        const password2 = document.querySelector("[name='confirmpassword']");
        
        togglePasswordVisible.addEventListener("click", function () {
            const type = password1.getAttribute("type") === "password" ? "text" : "password";
            password1.setAttribute("type", type);
            this.classList.toggle("bi-eye");
            this.classList.toggle("bi-eye-slash");
        });

        toggleConfirmPassword.addEventListener("click", function () {
            const type = password2.getAttribute("type") === "password" ? "text" : "password";
            password2.setAttribute("type", type);
            this.classList.toggle("bi-eye");
            this.classList.toggle("bi-eye-slash");
        });

        // New validation code
        const fullNameInput = document.getElementById('fullNameInput');
        const emailInput = document.getElementById('emailInput');
        const passwordInput = document.getElementById('passwordInput');
        const confirmPasswordInput = document.getElementById('confirmPasswordInput');
        const departmentInput = document.getElementById('departmentInput');

        const fullNameValidation = document.getElementById('fullNameValidation');
        const emailValidation = document.getElementById('emailValidation');
        const passwordValidation = document.getElementById('passwordValidation');
        const confirmPasswordValidation = document.getElementById('confirmPasswordValidation');
        const departmentValidation = document.getElementById('departmentValidation');

        let fullNameHasBeenFocused = false;
        let emailHasBeenFocused = false;
        let passwordHasBeenFocused = false;
        let confirmPasswordHasBeenFocused = false;
        let departmentHasBeenFocused = false;

        function validateFullName(name) {
            return !(/[~`!1@2#3\$4%5\^6&7\*8\(9\)0_\-+=:;"<>,.\/?\|]/.test(name));
        }

        function validateEmail(email) {
            return email.includes("@usc.edu.ph");
        }

        function validatePassword(password) {
            return password.length >= 6;
        }

        function validateConfirmPassword() {
            return passwordInput.value === confirmPasswordInput.value;
        }

        function validateDepartment(department) {
            return department !== "";
        }

        function updateFullNameValidation() {
            if (fullNameHasBeenFocused) {
                fullNameValidation.style.display = validateFullName(fullNameInput.value) ? "none" : "block";
            }
        }

        function updateEmailValidation() {
            if (emailHasBeenFocused) {
                emailValidation.style.display = validateEmail(emailInput.value) ? "none" : "block";
            }
        }

        function updatePasswordValidation() {
            if (passwordHasBeenFocused) {
                passwordValidation.style.display = validatePassword(passwordInput.value) ? "none" : "block";
            }
        }

        function updateConfirmPasswordValidation() {
            if (confirmPasswordHasBeenFocused) {
                confirmPasswordValidation.style.display = validateConfirmPassword() ? "none" : "block";
            }
        }

        function updateDepartmentValidation() {
            if (departmentHasBeenFocused) {
                departmentValidation.style.display = validateDepartment(departmentInput.value) ? "none" : "block";
            }
        }

        fullNameInput.addEventListener("focus", () => {
            fullNameHasBeenFocused = true;
            updateFullNameValidation();
        });
        fullNameInput.addEventListener("input", updateFullNameValidation);
        fullNameInput.addEventListener("blur", updateFullNameValidation);

        emailInput.addEventListener("focus", () => {
            emailHasBeenFocused = true;
            updateEmailValidation();
        });
        emailInput.addEventListener("input", updateEmailValidation);
        emailInput.addEventListener("blur", updateEmailValidation);

        passwordInput.addEventListener("focus", () => {
            passwordHasBeenFocused = true;
            updatePasswordValidation();
        });
        passwordInput.addEventListener("input", () => {
            updatePasswordValidation();
            if (confirmPasswordHasBeenFocused) {
                updateConfirmPasswordValidation();
            }
        });
        passwordInput.addEventListener("blur", updatePasswordValidation);

        confirmPasswordInput.addEventListener("focus", () => {
            confirmPasswordHasBeenFocused = true;
            updateConfirmPasswordValidation();
        });
        confirmPasswordInput.addEventListener("input", updateConfirmPasswordValidation);
        confirmPasswordInput.addEventListener("blur", updateConfirmPasswordValidation);

        departmentInput.addEventListener("focus", () => {
            departmentHasBeenFocused = true;
            updateDepartmentValidation();
        });
        departmentInput.addEventListener("change", updateDepartmentValidation);
        departmentInput.addEventListener("blur", updateDepartmentValidation);

        function capitalizeWords(input) {
            input.value = input.value.replace(/(?:^|\s)\S/g, function(letter) {
                return letter.toUpperCase();
            });
        }
    </script>
</body>
</html>