<?php
// Start the session
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// Student and Faculty do not have access to this page
if($user_type == 'Student'|| $user_type == 'Faculty'){
    header("location: user_home.php?pageid=UserHome&userId=$user_id''$full_name");
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
    <title>General Information</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb" style="background: none">
                            <li class="breadcrumb-item"><a href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">Create Post</a></li>
                            <li class="breadcrumb-item active" aria-current="page">General Information</li>
                        </ol>
                    </nav>
                    <?php include('error_message.php'); ?>
                    <div class="button-flex" style="margin-top: 10px">
                        <div class="button-container">
                            <a href="form_peo.php?pageid=PEOForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-map"></i></div>
                                <div class="button-text">Program Educational Objectives (PEO)</div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="form_student_outcomes.php?pageid=StudentOutcomesForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-graduation-cap"></i></div>
                                <div class="button-text">Student Outcomes (SO)</div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="form_department_organizational_chart.php?pageid=DepartmentOrganizationalChart?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-university"></i></div>
                                <div class="button-text">Department Organizational Chart</div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="form_facilities.php?pageid=FacilitiesForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-building"></i></div>
                                <div class="button-text">Facilities</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/fetch_user_session.js"></script>
</body>
</html>