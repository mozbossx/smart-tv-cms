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
    <link rel="stylesheet" href="style.css">
    <style>
        #scrollDiv {
            height: calc(100vh - 330px);
        }
        /* Media query for smaller screens */
        @media only screen and (max-width: 1000px) {
            #searchByKeywordsForm, #searchByContentTypeForm {
                margin: 0 auto;
            }

            #scrollDiv {
                height: calc(100vh - 400px);
            }
        }
    </style>
    <title>Approved Contents</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form" style="max-width: 800px; margin: 0 auto;">
                    <div id="approvedContentsList" class="content-container" style="height: calc(100vh - 160px); overflow: hidden;">
                        <h1 class="content-title" style="text-align: center;">
                            <i class="fa fa-check" style="margin-right: 6px" aria-hidden="true"></i>Approved Contents <span id="approvedContentsCount" style="font-weight: bold; color: #6E6E6E;"></span>
                        </h1>
                        <div class="approved-contents-buttons-flex">
                            <!-- search form -->
                            <form class="search-form" id="searchByKeywordsForm" style="width: 300px; max-width: 100%; margin-bottom: 10px;">
                                <div class="floating-label-container">
                                    <input type="text" id="searchInput" required placeholder=" " class="floating-label-input" onkeyup="searchApprovedContents()">
                                    <label for="searchInput" class="floating-label"><i class="fa fa-search" style="margin-right: 6px" aria-hidden="true"></i> Search for keywords</label>
                                    <button type="button" id="clearSearch" style="display: none; position: absolute; right: 12px; font-size: 16px; top: 56%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </form>

                            <!-- Search by Content Type form -->
                            <form class="search-form" id="searchByContentTypeForm" style="width: 300px; max-width: 100%; margin-left: auto; margin-bottom: 10px;">
                                <div class="floating-label-container">
                                    <select id="contentType" class="floating-label-input">
                                        <option value="all">All</option>
                                        <!-- foreach content type -->
                                    </select>
                                    <label for="contentType" class="floating-label">Show by Content Type</label>
                                </div>
                            </form>
                        </div>
                        <div id="scrollDiv" class="scroll-div" style="padding: 10px">
                            <div id="approvedContentsContainer" style="margin-right: 10px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('misc/php/success_modal.php'); ?>
    <?php include('misc/php/error_modal.php'); ?>
    <script src="js/fetch_approved_contents.js"></script>
</body>
</html>