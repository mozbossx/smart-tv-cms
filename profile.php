<?php
// Start the session
session_start();
include("config_connection.php");

// Fetch user data for the currently logged-in user
include 'get_session.php';
include 'user_theme_color.php';

// Check if the user is not logged in, redirect to the login page (login.php?pageid=Login)
if (!isset($_SESSION['full_name']) || !isset($_SESSION['email'])) {
    header('location: login.php?pageid=Login');
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

// Fetch all features from the database
$stmt = $conn->prepare("SELECT * FROM features_tb");
$stmt->execute();
$features = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// After fetching user data and before the HTML output
$content_types = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];
foreach ($features as $feature) {
    $content_types[] = strtolower(str_replace(' ', '_', $feature['feature_name']));
}

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
                        <?php if ($error_message): ?>
                            <div class="error-message"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> <?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <?php if ($success_message): ?>
                            <div class="success-message"><i class="fa fa-check-circle" aria-hidden="true"></i> <?php echo $success_message; ?></div>
                        <?php endif; ?>
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
                        <br>
                        <div id="themeContainer" class="theme-container">
                            <p>Theme</p>
                            <div class="color-carousel">
                                <div class="color-option-container">
                                    <div class="color-option" data-color="#007638" style="background-color: #007638" onclick="updateTheme('#007638')"></div>
                                    <span class="color-label">Green</span>
                                    <span class="color-label">(Default)</span>
                                </div>
                                <div class="color-option-container">
                                    <div class="color-option" data-color="#193b67" style="background-color: #193b67" onclick="updateTheme('#193b67')"></div>
                                    <span class="color-label">Blue</span>
                                </div>
                                <div class="color-option-container">
                                    <div class="color-option" data-color="#860202" style="background-color: #860202" onclick="updateTheme('#860202')"></div>
                                    <span class="color-label">Red</span>
                                </div>
                                <div class="color-option-container">
                                    <div class="color-option" data-color="#ac8012" style="background-color: #ac8012" onclick="updateTheme('#ac8012')"></div>
                                    <span class="color-label">Gold</span>
                                </div>
                                <div class="color-option-container">
                                    <div class="color-option" data-color="#c50565" style="background-color: #c50565" onclick="updateTheme('#c50565')"></div>
                                    <span class="color-label">Pink</span>
                                </div>
                                <div class="color-option-container">
                                    <div class="color-option" data-color="#8b4513" style="background-color: #8b4513" onclick="updateTheme('#8b4513')"></div>
                                    <span class="color-label">Brown</span>
                                </div>
                                <div class="color-option-container">
                                    <div class="color-option" data-color="#00686c" style="background-color: #00686c" onclick="updateTheme('#00686c')"></div>
                                    <span class="color-label">Teal</span>
                                </div>
                                <div class="color-option-container">
                                    <div class="color-option" data-color="#252525" style="background-color: #252525" onclick="updateTheme('#252525')"></div>
                                    <span class="color-label">Black</span>
                                </div>
                                <div class="color-option-container">
                                    <div class="color-option" data-color="#49006c" style="background-color: #49006c" onclick="updateTheme('#49006c')"></div>
                                    <span class="color-label">Violet</span>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="theme_color" id="theme_color" class="floating-label-input" required value="<?php echo $row['theme_color'] ?? '#334b35'; ?>" style="height: 55px">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="email" value="<?php echo $email; ?>">
                        <div style="display: -webkit-inline-box">
                            <button class="red-button" onclick="openModal('logout')" style="border: none; margin-right: 5px"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</button>
                            <button class="green-button" onclick="openModal('changePassword', '<?php echo $email; ?>', '<?php echo $user_id; ?>')" style="border: none"><i class="fa fa-pencil" aria-hidden="true"></i> Change Password</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content" style="padding: 15px">
            <span class="close" onclick="closeModal('changePassword')"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="font-size: 50px"><i class="fa fa-pencil" aria-hidden="true"></i></h1>
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
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" readonly>
                </div>
                <br>
            </form>
            <div style="align-items: right; text-align: right; right: 0">
                <button class="grey-button" type="button" onclick="closeModal('changePassword')">Cancel</button>
                <button style="margin: 0" class="green-button" onclick="changePasswordSubmit()">Update</button>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content" style="padding: 15px">
            <div class="red-bar-vertical">
                <span class="close" onclick="closeModal('logout')"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="font-size: 50px"><i class="fa fa-sign-out" aria-hidden="true"></i></h1>
                <p>Proceed to logout?</p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button class="grey-button" onclick="closeModal('logout')">No</button>
                    <button class="red-button" style="margin: 0" onclick="logout()">Yes, I want to logout</button>
                </div>
            </div>
        </div>
    </div>
    <script src="js/fetch_myprofile.js"></script>
</body>
</html>