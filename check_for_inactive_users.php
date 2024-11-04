<?php
require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send email using PHPMailer
function sendInactivityEmail($email, $fullName, $department) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usc.smarttvcms@gmail.com';
        $mail->Password = 'nrul yhwc mmrk hwdp';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('usc.smarttvcms@gmail.com', 'USC CMS');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Account Deleted Due to Inactivity';
        $mail->Body = "
            <p>Dear $fullName,</p>
            <p>We regret to inform you that your USC Smart TV CMS account associated with the $department has been automatically deleted due to inactivity.</p>
            <p>If you wish to continue using the system, please register for a new account.</p>
            <p>If you believe this is an error or have any questions, please contact the USC CMS administration.</p>
            <br>
            <p>Regards,<br>University of San Carlos</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Failed to send inactivity email to $email: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to check and delete inactive users
function checkAndDeleteInactiveUsers($conn) {
    // Fetch inactivity settings
    $settingsQuery = "SELECT * FROM auto_delete_user_inactivity_tb";
    $settingsResult = $conn->query($settingsQuery);

    $settings = [];
    while ($row = $settingsResult->fetch_assoc()) {
        $settings[$row['department']] = [
            'faculty_days' => $row['faculty_days'],
            'staff_days' => $row['staff_days'],
            'student_days' => $row['student_days']
        ];
    }

    // Fetch active users (excluding admins)
    $usersQuery = "SELECT user_id, email, full_name, user_type, department, last_post_date 
                   FROM users_tb 
                   WHERE user_type NOT IN ('Admin', 'Super Admin')
                   AND status = 'Approved'";
    $usersResult = $conn->query($usersQuery);

    $currentDate = new DateTime();

    while ($user = $usersResult->fetch_assoc()) {
        if (!$user['last_post_date']) continue;

        $lastPostDate = new DateTime($user['last_post_date']);
        $daysInactive = $currentDate->diff($lastPostDate)->days;
        
        // Get threshold based on user type
        $roleKey = strtolower($user['user_type']) . '_days';
        $maxInactiveDays = $settings[$user['department']][$roleKey];

        // Delete if inactive
        if ($daysInactive > $maxInactiveDays) {
            // Delete user
            $deleteQuery = "DELETE FROM users_tb WHERE user_id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("i", $user['user_id']);
            
            if ($stmt->execute()) {
                // Send email notification using PHPMailer
                sendInactivityEmail(
                    $user['email'],
                    $user['full_name'],
                    $user['department']
                );
            }
            $stmt->close();
        }
    }
}

// Add check to prevent too frequent runs
function shouldRunInactivityCheck($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS system_settings (
        setting_name VARCHAR(50) PRIMARY KEY,
        setting_value VARCHAR(255),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_name = 'last_inactivity_check'");
    
    if ($result->num_rows === 0) {
        $conn->query("INSERT INTO system_settings (setting_name, setting_value) VALUES ('last_inactivity_check', '0')");
        return true;
    }

    $row = $result->fetch_assoc();
    $lastCheck = (int)$row['setting_value'];
    $currentTime = time();
    
    // Only run check once per hour to prevent too frequent runs and performance issues
    // 3600 seconds = 1 hour
    if ($currentTime - $lastCheck >= 3600) {
        $conn->query("UPDATE system_settings SET setting_value = '$currentTime', updated_at = CURRENT_TIMESTAMP WHERE setting_name = 'last_inactivity_check'");
        return true;
    }
    return false;
}

// Run the check if enough time has passed
if (shouldRunInactivityCheck($conn)) {
    checkAndDeleteInactiveUsers($conn);
}

?>