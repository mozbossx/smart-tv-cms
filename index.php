<?php
@include 'config_connection.php';
session_start();

// Check if the user has explicitly logged out
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    unset($_SESSION['full_name']);
    header('location: index.php'); // Redirect to the login page
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
    if (strpos($email, '@usc.edu.ph') === false) {
        $error[] = "Email must be a USC email address (must contain '@usc.edu.ph')";
    } else {
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
                    $error[] = 'Sorry, your registration status got rejected by '. $row['evaluated_by'] .' with a message: <br><br>'. $row['evaluated_message'];
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
    }
} else {
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
    <title>Login</title>
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
            <form action="" method="post">
                <p class="page-title">Sign In</p>
                <?php include('error_message.php'); ?>
                <div class="floating-label-container">
                    <input type="email" name="email" required placeholder=" " class="floating-label-input" value="<?php echo $email; ?>">
                    <label for="email" class="floating-label">USC Email</label>
                </div>
                <div class="floating-label-container">
                    <input type="password" name="password" required placeholder=" " class="floating-label-input">
                    <label for="password" class="floating-label">Password</label>
                    <i id="togglePassword">Show</i>
                </div>
                <p style="text-align: right">
                    <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                </p>
                <br>
                <input type="submit" name="submit" value="Login" class=login-button>
                <div class="line-separator"></div>
                <p style="text-align: center; user-select: none">Not a member? <a href="create_account.php" class="create-account-link">Create Account</a></p>
            </form>
        </div>
    </div>
    <script>
        // JavaScript to toggle password visibility and show/hide labels
        const togglePassword = document.querySelector("#togglePassword");
        const password = document.querySelector("[name='password']");
        const floatingInput = document.querySelector(".floating-label-input");

        togglePassword.addEventListener("click", function () {
            // Toggle the type attribute
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            // Toggle the text of the toggle button
            this.textContent = type === "password" ? "Show" : "Hide";
        });

        floatingInput.addEventListener("input", function () {
            // Trigger the focus event when the input value is not empty
            if (this.value.trim() !== "") {
                this.focus();
            }
        });
    </script>

</body>
</html>