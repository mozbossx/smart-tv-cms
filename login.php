<?php
@include 'config_connection.php';
session_start();

// include 'check_for_inactive_users.php';

// Check if the user has explicitly logged out
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    unset($_SESSION['full_name']);
    header('location: login.php?pageid=Login'); // Redirect to the login page
    exit;
}

// Check if the user is already logged in, redirect to user-home.php
if (isset($_SESSION['full_name'])) {
    header('location: user_home.php');
    exit;
}

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if email contains '@usc.edu.ph'
    // if (strpos($email, '@usc.edu.ph') === false) {
    //     $error[] = "Email must be a USC email address (must contain '@usc.edu.ph')";
    // } else {
        if (strlen($password) < 6) {
            $error[] = "Password should be at least 6 characters long.";
        } else {
            $pass = md5($password);

            // Use prepared statements to prevent SQL injection
            $stmt = $conn->prepare("SELECT * FROM users_tb WHERE email = ? AND password = ?");
            $stmt->bind_param("ss", $email, $pass);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();

                if ($row['status'] === 'Pending') {
                    $error[] = 'Sorry, your registration status is still pending. Please wait for an Administrator to approve your status.';
                } else if ($row['status'] === 'Rejected') {
                    $error[] = 'Sorry, your registration status got rejected by ' . $row['evaluated_by'] . ' with a message: 
                               <br>
                               <br>"' . $row['evaluator_message'] . '"';
                } else {
                    // Store user details in session
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['full_name'] = $row['full_name'];
                    $_SESSION['department'] = $row['department'];
                    $_SESSION['user_type'] = $row['user_type'];
                    $_SESSION['email'] = $row['email'];

                    header('location: user_home.php?pageid=UserHome?userId=' .$row['user_id'].''.$row['full_name']);
                    exit;
                }
            } else {
                $error[] = 'These credentials do not match our records!';
            }

            $stmt->close();
        }
    // }
} else {
    $email = "";
    $password = "";
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
    <title>Login</title>
    <style>
        .login-page {
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

        .login-wrapper {
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
            height: 50px;
            width: auto;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .school-logo:hover {
            transform: scale(1.05);
        }

        .login-heading {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .login-subheading {
            color: #666;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .form-fields {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            margin-bottom: 5px;
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

        .form-field input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .form-field input:focus {
            border-color: #007638;
            outline: none;
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

        .login-button-2 {
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

        .login-button-2:hover {
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

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 0 1rem;
            box-sizing: border-box;
        }

        @media (max-width: 480px) {            
            .school-logo {
                height: 60px;
            }

            .school-logos {
                margin-bottom: 0.5rem;
            }
            
            .login-heading {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-wrapper">
            <div class="school-logos">
                <img src="images/usc_icon.png" class="school-logo" onclick="window.location.href='index.php'" alt="USC Logo">
                <img src="images/soe_icon.png" class="school-logo" onclick="window.location.href='index.php'" alt="SOE Logo">
            </div>
            
            <div class="login-form-container">
                <h1 class="login-heading">Smart TV Content Management System</h1>
                <p class="login-subheading">Please sign in to continue</p>
                
                <?php include('error_message.php'); ?>
                
                <form action="" method="post">
                    <div class="form-fields">
                        <div class="form-field">
                            <i class="bi bi-envelope"></i>
                            <input type="email" name="email" required placeholder="USC Email" value="<?php echo $email; ?>">
                        </div>
                        
                        <div class="form-field">
                            <i class="bi bi-lock"></i>
                            <input type="password" name="password" required placeholder="Password" value="<?php echo $password; ?>" minlength="6">
                            <span class="toggle-password">
                                <i class="bi bi-eye-slash" id="togglePasswordVisible"></i>
                            </span>
                        </div>
                        
                        
                    </div>
                    <div class="password-reset">
                        <a href="forgot_password.php?pageid=ForgotPassword">Forgot password?</a>
                    </div>

                    <button type="submit" name="submit" class="login-button-2">Sign In</button>
                    
                    <div class="signup-link">
                        <p>Not a member? <a href="create_account.php?pageid=CreateAccount">Create Account</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        const togglePasswordVisible = document.querySelector("#togglePasswordVisible");
        const password = document.querySelector("[name='password']");

        togglePasswordVisible.addEventListener("click", function () {
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            this.classList.toggle("bi-eye");
            this.classList.toggle("bi-eye-slash");
        });
    </script>

</body>
</html>