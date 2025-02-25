<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/smart-tv-cms/cron_check_inactive_users_error.log');

require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log file setup
$logFile = '/var/www/smart-tv-cms/inactive_users_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Function to send email using PHPMailer
function sendInactivityEmail($email, $fullName, $department) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usccms.tv@gmail.com';
        $mail->Password = 'dcxp gucw kflt dxkj';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('usccms.tv@gmail.com', 'USC CMS');
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
        logMessage("Inactivity email sent successfully to $email");
        return true;
    } catch (Exception $e) {
        logMessage("Failed to send inactivity email to $email: {$mail->ErrorInfo}");
        return false;
    }
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logMessage("Starting inactive users check...");

    // Fetch inactivity settings
    $stmt = $pdo->query("SELECT * FROM auto_delete_user_inactivity_tb");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['department']] = [
            'faculty_days' => $row['faculty_days'],
            'staff_days' => $row['staff_days'],
            'student_days' => $row['student_days']
        ];
    }
    
    logMessage("Fetched inactivity settings for " . count($settings) . " departments");

    // Fetch active users (excluding admins)
    $stmt = $pdo->prepare("
        SELECT user_id, email, full_name, user_type, department, last_post_date 
        FROM users_tb 
        WHERE user_type NOT IN ('Admin', 'Super Admin')
        AND status = 'Approved'
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Found " . count($users) . " users to check");

    $currentDate = new DateTime();

    foreach ($users as $user) {
        if (!$user['last_post_date']) {
            logMessage("Skipping user {$user['user_id']} ({$user['email']}) - no last post date");
            continue;
        }

        $lastPostDate = new DateTime($user['last_post_date']);
        $daysInactive = $currentDate->diff($lastPostDate)->days;
        
        // Get threshold based on user type
        $roleKey = strtolower($user['user_type']) . '_days';
        $maxInactiveDays = $settings[$user['department']][$roleKey] ?? null;

        if (!$maxInactiveDays) {
            logMessage("No inactivity threshold found for {$user['user_type']} in {$user['department']}");
            continue;
        }

        logMessage("Checking user {$user['email']} - Inactive for $daysInactive days (max: $maxInactiveDays)");

        // Delete if inactive
        if ($daysInactive > $maxInactiveDays) {
            logMessage("User {$user['email']} exceeded inactivity threshold - deleting account");

            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users_tb WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            // Send email notification
            sendInactivityEmail(
                $user['email'],
                $user['full_name'],
                $user['department']
            );

            // Create notifications for admins
            $evaluator_message = "User automatically deleted after {$maxInactiveDays} days of inactivity";
            
            // For department admins
            $stmt = $pdo->prepare("
                SELECT user_id FROM users_tb 
                WHERE user_type = 'Admin' 
                AND department = ?
            ");
            $stmt->execute([$user['department']]);
            $departmentAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($departmentAdmins as $admin) {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications_tb (
                        user_id, 
                        notification_type, 
                        status, 
                        evaluator_name, 
                        action_user_name, 
                        action_user_type, 
                        action_user_department, 
                        evaluator_message
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $admin['user_id'],
                    'user_deleted_by_system',
                    'deleted',
                    'System',
                    $user['full_name'],
                    $user['user_type'],
                    $user['department'],
                    $evaluator_message
                ]);
            }
            
            // For super admins
            $stmt = $pdo->prepare("SELECT user_id FROM users_tb WHERE user_type = 'Super Admin'");
            $stmt->execute();
            $superAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($superAdmins as $superAdmin) {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications_tb (
                        user_id, 
                        notification_type, 
                        status, 
                        evaluator_name, 
                        action_user_name, 
                        action_user_type, 
                        action_user_department, 
                        evaluator_message
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $superAdmin['user_id'],
                    'user_deleted_by_system',
                    'deleted',
                    'System',
                    $user['full_name'],
                    $user['user_type'],
                    $user['department'],
                    $evaluator_message
                ]);
            }

            // Update notification counts
            $stmt = $pdo->prepare("
                UPDATE users_tb 
                SET notification_count = notification_count + 1 
                WHERE (user_type = 'Admin' AND department = ?) 
                OR user_type = 'Super Admin'
            ");
            $stmt->execute([$user['department']]);

            logMessage("Deleted inactive user {$user['email']} and notified admins");
        }
    }

    logMessage("Completed inactive users check successfully");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}