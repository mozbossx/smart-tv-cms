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
    <link rel="stylesheet" href="style.css">
    <title>Email Verification</title>
    <style>
        .green-button {
            background-color: #334b35;
            color: white;
            border: none;
        }
    </style>
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
                    <input type="text" maxlength="6" name="otp_code" required placeholder=" " class="floating-label-input" pattern="\d{6}">
                    <label for="otp_code" class="floating-label">6-digit OTP Code</label>
                </div>
                <div class="line-separator"></div>
                <input type="submit" name="submit" value="Submit Code" class="login-button">
            </form>
        </div>
    </div>
    <div id="myModal" class="modal">
        <div class="modal-content" style="padding: 15px">
            <div class="green-bar-vertical">
                <p style="text-align: center"><?php echo $successMessage; ?></p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button id="proceedBtn" class="green-button" style="margin: 0; background-color: #334b35;">Okay</button>
                </div>
            </div>
        </div>
    </div>
    <div id="facultyOrStaffModal" class="modal">
        <div class="modal-content" style="padding: 15px">
            <div class="green-bar-vertical">
                <span class="close" onclick="closeModal('facultyOrStaffModal')" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <p style="text-align: center">Are you a faculty or staff member?</p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button id="facultyBtn" class="green-button" style="margin: 0; background-color: #334b35;">Faculty</button>
                    <button id="staffBtn" class="green-button" style="margin: 0; background-color: #334b35;">Staff</button>
                </div>
            </div>
        </div>
    </div>
    <?php include('misc/php/success_modal.php'); ?>
    <?php include('misc/php/error_modal.php'); ?>
    <script>
        // Handle form submission via WebSocket
        const form = document.getElementById('pendingUserForm');
        // const ws = new WebSocket('wss://usccms.online/ws?email=<?php echo urlencode($email); ?>');

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
            
            try {
                const Ws = new WebSocket(wsUrl);
                
                Ws.onopen = function() {
                    console.log('WebSocket Connected Successfully to:', wsUrl);
                };
                
                Ws.onclose = function(event) {
                    console.log('WebSocket Disconnected from:', wsUrl);
                    console.log('Code:', event.code, 'Reason:', event.reason);
                    console.log('Attempting reconnection in 2 seconds...');
                    setTimeout(connectWebSocket, 2000);
                };
                
                Ws.onerror = function(error) {
                    console.error('WebSocket Error:', error);
                    console.log('WebSocket State:', ws.readyState);
                    console.log('WebSocket URL:', ws.url);
                    console.log('Protocol:', ws.protocol);
                    console.log('Extensions:', ws.extensions);
                };
                
                return Ws;
            } catch (error) {
                console.error('Error creating WebSocket:', error);
                console.log('Attempting reconnection in 2 seconds...');
                setTimeout(connectWebSocket, 2000);
                return null;
            }
        }

        let ws = null;
        try {
            ws = connectWebSocket();
        } catch (error) {
            console.error('Error initializing WebSocket:', error);
            console.log('Attempting reconnection in 2 seconds...');
            setTimeout(connectWebSocket, 2000);
        }
        
        form.addEventListener('submit', function (e) {
            e.preventDefault();

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

            console.log('Form Data:', data);
            ws.send(JSON.stringify(data));
            
            // Listen for messages from the WebSocket server
            ws.onmessage = function(event) {
                const message = JSON.parse(event.data);
                if (message.success) {                    
                    // Unset the session mail variable
                    fetch('unset_session_mail.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Display success modal
                                document.getElementById('successMessageVersion2').textContent = "Registration successful!";
                                document.getElementById('successMessageModalVersion2').style.display = 'flex';
                                document.getElementById('errorModal').style.display = 'none';

                                document.getElementById('okayButton').addEventListener('click', function() {
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
                    document.getElementById('errorText').textContent = message.message || "Invalid OTP Code. Try Again.";
                    document.getElementById('errorModal').style.display = 'flex';
                    document.querySelector('.red-button').style.backgroundColor = '#862b2b';
                    document.querySelector('.red-button').style.color = 'white';
                    document.querySelector('.red-button').style.border = 'none';
                }
            };

            ws.onerror = function(event) {
                console.error('WebSocket error observed:', event);
            };
        });

        // Add event listeners for faculty and staff buttons
        document.getElementById('facultyBtn').addEventListener('click', function() {
            updateUserType('Faculty');
        });

        document.getElementById('staffBtn').addEventListener('click', function() {
            updateUserType('Staff');
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