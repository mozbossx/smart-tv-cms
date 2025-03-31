<?php
session_start();
@include 'config_connection.php';

if (isset($_SESSION['mail'])) {
    $email = $_SESSION['mail'];
} else {
    header('location: create_account.php?pageid=CreateAccount');
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Email Verification</title>
    <style>
        .green-button {
            background-color: #334b35;
            color: white;
            border: none;
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-radius: 50%;
            border-top: 4px solid #334b35;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .verify-account-page {
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

        .verify-account-wrapper {
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

        .verify-account-heading {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .verify-account-subheading {
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

        .verify-account-button-2 {
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

        .verify-account-button-2:hover {
            background: #007638;
        }

        .verify-account-link {
            text-align: center;
            margin-top: 1.4rem;
            color: #666;
        }

        .verify-account-link a {
            color: #007638;
            text-decoration: none;
            font-weight: 500;
        }

        .verify-account-link a:hover {
            text-decoration: underline;
        }

        .verify-account-container {
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
            
            .verify-account-heading {
                font-size: 1.5rem;
            }
        }
    </style>        
</head>
<body class="verify-account-page">
    <div class="verify-account-container">
        <div class="verify-account-wrapper">
            <div class="verify-account-form-container">
                <h1 class="verify-account-heading">Email Verification</h1>
                <p class="verify-account-subheading">Please enter the 6-digit code we sent via your USC email:</p>
                <p class="display-email"><?php echo $email; ?></p>
                <p style="text-align: center; user-select: none; margin-bottom: 10px">We want to make sure it is a valid USC email</p>
                <?php include('error_message.php'); ?>
                <form action="" method="post" id="verifyEmailForm">
                    <div class="form-fields" style="margin-bottom: 0">
                        <div class="form-field">
                            <i class="bi bi-hash"></i>
                            <input type="text" maxlength="6" name="otp_code" 
                                   required placeholder="6-digit OTP Code" pattern="\d{6}">
                        </div>
                        <input type="submit" name="submit" value="Submit Code" class="login-button">
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="successMessageVerificationModal" class="modal">
        <div class="modal-content" style="padding: 15px">
            <h1 style="color: #334b35; text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-check-circle"></i> Success</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;" id="successMessageVerification"></p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button id="okayButtonSuccess" class="green-button" style="margin: 0; background-color: #334b35;">Okay</button>
            </div>
        </div>
    </div>
    <div id="errorMessageVerificationModal" class="modal">
        <div class="modal-content" style="padding: 15px">
            <h1 style="color: rgb(134, 43, 43); text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-times-circle"></i> Error</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;" id="errorMessageVerification"></p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button id="okayButtonError" class="red-button" style="margin: 0; background-color: rgb(134, 43, 43); border: none;">Okay</button>
            </div>
        </div>
    </div>
    <div id="facultyOrStaffModal" class="modal">
        <div class="modal-content" style="padding: 15px">
            <h1 style="color: #334b35; text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-question-circle"></i> Role</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;">Are you a Faculty or Staff member?</p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button id="facultyBtn" class="green-button" style="margin: 0; background-color: #334b35;">Faculty</button>
                <button id="staffBtn" class="green-button" style="margin: 0; background-color: #334b35;">Staff</button>
            </div>
        </div>
    </div>
    <div id="loadingModal" class="modal">
        <div class="modal-content" style="padding: 15px">
            <p id="loadingMessage" style="text-align: center">Processing your request...</p>
            <div class="loader"></div>
        </div>
    </div>
    
    <script>
        // Handle form submission via WebSocket
        const form = document.getElementById('verifyEmailForm');
        let ws = null;

        function connectWebSocket() {
            console.log('Attempting WebSocket connection...');
            
            // Determine if we're in localhost or production
            const isLocalhost = window.location.hostname === 'localhost' || 
                            window.location.hostname === '127.0.0.1' ||
                            window.location.hostname === '192.168.1.14';
            
            // Choose WebSocket URL based on environment
            const wsUrl = isLocalhost 
                ? 'ws://192.168.1.14:8081?email=<?php echo urlencode($email); ?>'
                : 'wss://usccms.online/ws?email=<?php echo urlencode($email); ?>';

            const newWs = new WebSocket(wsUrl);
            
            newWs.onopen = function() {
                console.log('WebSocket Connected Successfully to:', wsUrl);
                ws = newWs;
            };
            
            newWs.onclose = function(event) {
                console.log('WebSocket Disconnected. Code:', event.code, 'Reason:', event.reason);
                console.log('Attempting reconnection in 1 second...');
                setTimeout(connectWebSocket, 1000);
            };
            
            newWs.onerror = function(error) {
                console.error('WebSocket Error:', error);
                setTimeout(connectWebSocket, 1000);
            };
            
            return newWs;
        }

        // Initial connection
        connectWebSocket();
        
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Show loading modal
            const loadingModal = document.getElementById('loadingModal');
            loadingModal.style.display = 'flex';
            document.getElementById('loadingMessage').textContent = 'Verifying your code...';

            const formData = new FormData(form);
            const data = { 
                session_data: { 
                    otp: "<?php echo $_SESSION['otp']; ?>", 
                    registration_details: <?php echo json_encode($_SESSION['registration_details']); ?> 
                } 
            };
            
            formData.forEach((value, key) => {
                data[key] = value;
            });

            const sendData = () => {
                if (ws && ws.readyState === WebSocket.OPEN) {
                    console.log('Form Data:', data);
                    ws.send(JSON.stringify(data));
                } else {
                    console.log('WebSocket not ready, attempting reconnection...');
                    document.getElementById('loadingMessage').textContent = 'Reconnecting to server...';
                    ws = connectWebSocket();
                    // Retry sending data after connection is established
                    ws.onopen = function() {
                        document.getElementById('loadingMessage').textContent = 'Verifying your code...';
                        ws.send(JSON.stringify(data));
                    };
                }
            };

            sendData();
            
            // Listen for messages from the WebSocket server
            ws.onmessage = function(event) {
                loadingModal.style.display = 'none';
                const message = JSON.parse(event.data);
                if (message.success == true) {                    
                    // Unset the session mail variable
                    fetch('unset_session_mail.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Display success modal
                                document.getElementById('successMessageVerification').textContent = "Registration successful!";
                                document.getElementById('successMessageVerificationModal').style.display = 'flex';
                                document.getElementById('errorMessageVerificationModal').style.display = 'none';

                                document.getElementById('okayButtonSuccess').addEventListener('click', function() {
                                    if (message.user_type === 'TBD') {
                                        // Show the faculty or staff modal
                                        document.getElementById('facultyOrStaffModal').style.display = 'flex';
                                    } else {
                                        // Redirect the user to user_home.php if success
                                        window.location.href = "login.php?pageid=Login";
                                    }
                                });
                            } else {
                                console.error('Failed to unset session mail');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                } else if (message.success == false) {
                    // Display error modal
                    document.getElementById('errorMessageVerification').textContent = message.message || "Invalid OTP Code. Try Again.";
                    document.getElementById('errorMessageVerificationModal').style.display = 'flex';
                    document.getElementById('okayButtonError').addEventListener('click', function() {
                        document.getElementById('errorMessageVerificationModal').style.display = 'none';
                    });
                }
            };

            ws.onerror = function(event) {
                console.error('WebSocket error observed:', event);
            };
        });

        // Add event listeners for faculty and staff buttons
        document.getElementById('facultyBtn').addEventListener('click', function() {
            updateUserType('Faculty');
            window.location.href = "login.php?pageid=Login";
        });

        document.getElementById('staffBtn').addEventListener('click', function() {
            updateUserType('Staff');
            window.location.href = "login.php?pageid=Login";
        });

        function updateUserType(userType) {
            ws.send(JSON.stringify({
                action: 'update_user_type',
                user_type: userType,
                email: '<?php echo $email; ?>'
            }));
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

    </script>

</body>
</html>