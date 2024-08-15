
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
                    <img src="images/usc_icon.png" class="usc-logo">
                </div>
                <div class="right">
                    <img src="images/soe_icon.png" class="soe-logo">
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
                        <a href="index.php" class="cancel-button-2">Cancel</a>
                    </div>
                    <div class="right-side-button">
                        <input type="submit" name="submit" value="Search USC Email Address" class="create-account-button">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        // JavaScript to toggle password visibility
        const togglePassword = document.querySelector("#togglePassword");
        const password = document.querySelector("[name='password']");

        togglePassword.addEventListener("click", function () {
            // Toggle the type attribute
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            // Toggle the icon
            this.classList.toggle("bi-eye-slash");
            this.classList.toggle("bi-eye");
        });
    </script>
</body>
</html>