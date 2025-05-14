<?php
// start the session
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// Fetch all features from the database
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");

if ($user_type == 'Super Admin') {
    $stmtNewFeatures = $pdo->query("SELECT * FROM features_tb");
} else {
    $stmtNewFeatures = $pdo->prepare("
        SELECT DISTINCT f.* 
        FROM features_tb f
        LEFT JOIN feature_user_types fut ON f.feature_id = fut.feature_id
        WHERE (f.department = :department AND (fut.user_type = :user_type OR :user_type = 'Admin'))
           OR :user_type = 'Super Admin'
    ");
    $stmtNewFeatures->execute([':department' => $department, ':user_type' => $user_type]);
}

$featuresNewFeatures = $stmtNewFeatures->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .form-field {
            position: relative;
            box-sizing: border-box;
        }

        /* Loading animation styles */
        .loading-container {
            display: none;
            justify-content: center;
            align-items: center;
            padding: 20px;
            flex-direction: column;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-text {
            margin-top: 10px;
            color: #666;
            font-size: 16px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            padding: 1rem 2rem 1rem 3rem;
            border: 1px solid #858585;
            background: #f4f4f4;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .form-field input:focus {
            border-color: #007638;
            outline: none;
        }

        #scrollDiv {
            height: calc(100vh - 285px); 
        }
        /* Media query for smaller screens */
        @media only screen and (max-width: 1000px) {
            #scrollDiv {
                height: calc(100vh - 290px);
            }
        }
    </style>
    <title>Pending Contents</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form" style="max-width: 600px; margin: 0 auto;">
                    <div id="pendingContentsList" class="content-container" style="height: calc(100vh - 160px); overflow: hidden; padding: 0">
                        <div style="background: <?php echo $theme_color; ?>; padding: 10px; display: flex; flex-direction: row; justify-content: space-between; align-items: center;">
                            <h1 class="content-title" style="text-align: left; font-size: 20px; padding: 5px">
                                <i class="fa fa-clock-o" style="margin-right: 6px" aria-hidden="true"></i>Pending Contents
                            </h1>
                        </div>
                        <p id="trashMessage2" style="font-size: 16px; color: #6E6E6E; padding: 12px; text-align: center">Pending items will be permanently deleted after reaching their expiration date.</p>
                        <div id="scrollDiv" class="scroll-div" style="padding-left: 15px; padding-right: 5px; margin-right: 10px;">
                            <div id="pendingContentsContainer" style="margin-right: 5px;"></div>
                            <div id="loadingIndicator" class="loading-container">
                                <div class="loading-spinner"></div>
                                <div class="loading-text">Loading pending contents...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('misc/php/success_modal.php'); ?>
    <?php include('misc/php/error_modal.php'); ?>
    <script src="js/fetch_pending_contents.js"></script>
</body>
</html>