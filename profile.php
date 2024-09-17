<?php
// Start the session
session_start();
include("config_connection.php");

// Fetch user data for the currently logged-in user
include 'get_session.php';

// Check if the user is not logged in, redirect to the login page (index.php)
if (!isset($_SESSION['full_name']) || !isset($_SESSION['email'])) {
    header('location: index.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Fetch user data from the database using prepared statements
$query = "SELECT * FROM users_tb WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if query executed successfully
if ($result->num_rows > 0) {
    // Fetch the first (and only) row
    $row = $result->fetch_assoc();
    
    // Extract user information
    $full_name = $row['full_name'];
    $password = md5($row['password']);
    $department = $row['department'];
    $user_type = $row['user_type'];
    $email = $row['email'];
} else {
    // No user data found
    $error[] = "No user data found.";
}

// Close the statement
$stmt->close();

// After fetching user data and before the HTML output
$content_types = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];
$statuses = ['Pending', 'Approved', 'Rejected'];
$post_counts = [];

foreach ($content_types as $type) {
    if ($type === 'announcement' || $type === 'event' || $type === 'promaterial') {
        $table_name = $type . 's_tb';
    } else {
        $table_name = $type . '_tb';
    }
    $author_column = $type . '_author_id';
    
    if ($type === 'peo' || $type === 'so') {
        // PEO and SO don't have status column
        $query = "SELECT COUNT(*) as count FROM $table_name WHERE $author_column = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $post_counts[$type] = ['Total' => $count];
    } else {
        // For other content types with status
        $post_counts[$type] = [];
        foreach ($statuses as $status) {
            $query = "SELECT COUNT(*) as count FROM $table_name WHERE $author_column = ? AND status = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $user_id, $status);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $post_counts[$type][$status] = $count;
        }
    }
    $stmt->close();
}

// Check for error message from change_password.php
$error_message = isset($_GET['error']) ? $_GET['error'] : null;
// Check for success message from change_password.php
$success_message = isset($_GET['success']) ? $_GET['success'] : null;

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
    <title><?php echo $full_name ?></title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <div class="content-form">
                            <?php include('error_message.php'); ?>
                            <table class="user-details-table">
                                <tr>
                                    <th>Full Name</th>
                                    <td><?php echo $full_name; ?></td>
                                </tr>
                                <tr>
                                    <th>Department</th>
                                    <td><?php echo $department; ?></td>
                                </tr>
                                <tr>
                                    <th>Role</th>
                                    <td><?php echo $user_type; ?></td>
                                </tr>
                                <tr>
                                    <th>USC Email</th>
                                    <td><?php echo $email; ?></td>
                                </tr>
                            </table>
                            <div class="table-container">
                            <table class="post-counts-table">
                                <thead>
                                    <tr>
                                        <th>Content Type</th>
                                        <th>Pending</th>
                                        <th>Approved</th>
                                        <th>Rejected</th>
                                        <th>Total</th>
                                        <th>View All</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($content_types as $type): ?>
                                        <tr>
                                            <td><?php echo ucfirst($type); ?></td>
                                            <?php if ($type === 'peo' || $type === 'so'): ?>
                                                <td colspan="3">N/A</td>
                                                <td><?php echo $post_counts[$type]['Total']; ?></td>
                                            <?php else: ?>
                                                <?php foreach ($statuses as $status): ?>
                                                    <td><?php echo $post_counts[$type][$status]; ?></td>
                                                <?php endforeach; ?>
                                                <td><?php echo array_sum($post_counts[$type]); ?></td>
                                            <?php endif; ?>
                                            <td>
                                                <button type="button" class="green-button" style="margin: 0" onclick="openModal('viewPosts', '<?php echo $type; ?>')">View All</button>
                                                <button type="button" class="red-button" style="margin: 0" onclick="openModal('viewPosts', '<?php echo $type; ?>')">Delete All</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                            <br>
                            <br>
                            <button class="logout-button" onclick="openModal('logout')" style="border: none">Logout</button>
                            <button class="change-password-button" onclick="openModal('changePassword', '<?php echo $email; ?>', '<?php echo $user_id; ?>')" style="border: none">Change Password</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <div class="green-bar-vertical">
                <span class="close" onclick="closeModal('changePassword')" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-pencil-square" aria-hidden="true"></i></h1>
                <p>Change Password</p>
                <form action="change_password.php" method="POST" enctype="multipart/form-data" id="changePasswordForm">
                    <div class="floating-label-container">
                        <input type="password" name="password" placeholder=" " class="floating-label-input" required style="padding-right: 50px">
                        <label for="password" class="floating-label">New Password</label>
                        <i id="togglePassword" style="font-size: 14px">Show</i>
                    </div>
                    <div class="floating-label-container">
                        <input type="password" name="confirm_password" placeholder=" " class="floating-label-input" required style="padding-right: 50px">
                        <label for="confirm_password" class="floating-label">Confirm New Password</label>
                        <i id="toggleConfirmPassword" style="font-size: 14px">Show</i>
                    </div>
                    <div style="display: none; height: 0">
                        <input type="hidden" name="user_id" readonly>
                        <input type="hidden" name="email" readonly>
                    </div>
                    <br>
                </form>
                <div style="align-items: right; text-align: right; right: 0">
                    <button class="cancel-button" type="button" onclick="closeModal('changePassword')">Cancel</button>
                    <button style="margin: 0" class="confirm-button" onclick="changePasswordSubmit()">Change</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" onclick="closeModal('logout')" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-sign-out" aria-hidden="true"></i></h1>
                <p>Are you sure to logout?</p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button class="cancel-button" onclick="closeModal('logout')">No</button>
                    <button class="red-button" style="margin: 0" onclick="logout()">Yes, I want to logout</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function confirmLogout() {
        return confirm("Are you sure you want to log out?");
    }

    function logout() {
        window.location.href = "logout.php";
    }

    function openModal(modalId, userName, userId) {
        var modal = document.getElementById(modalId + 'Modal');
        var userNameInput = document.querySelector('#' + modalId + 'Modal [name="user_name"]');
        var userIdInput = document.querySelector('#' + modalId + 'Modal [name="user_id"]');

        modal.style.display = 'flex';
        userNameInput.value = userName;
        userIdInput.value = userId;
    }

    function closeModal(modalId) {
        var modal = document.getElementById(modalId + 'Modal');
        modal.style.display = 'none';

        // Clear password fields
        var passwordFields = modal.querySelectorAll("[type='password']");
        passwordFields.forEach(function(field) {
            field.value = '';
        });
    }

    function changeUserNameSubmit() {
        document.getElementById('updateUserNameForm').submit();
    }

    function changePasswordSubmit() {
        document.getElementById('changePasswordForm').submit();
    }

    // JavaScript to toggle password visibility and show/hide labels
    const togglePassword = document.querySelector("#togglePassword");
    const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
    const password1 = document.querySelector("[name='password']");
    const password2 = document.querySelector("[name='confirm_password']");
    const floatingInput = document.querySelector(".floating-label-input");

    togglePassword.addEventListener("click", function () {
        // Toggle the type attribute
        const type = password1.getAttribute("type") === "password" ? "text" : "password";
        password1.setAttribute("type", type);
        // Toggle the text of the toggle button
        this.textContent = type === "password" ? "Show" : "Hide";
    });

    toggleConfirmPassword.addEventListener("click", function () {
        // Toggle the type attribute
        const type = password2.getAttribute("type") === "password" ? "text" : "password";
        password2.setAttribute("type", type);
        // Toggle the text of the toggle button
        this.textContent = type === "password" ? "Show" : "Hide";
    });
    </script>
</body>
</html>