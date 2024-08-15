<?php
// start the session
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

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
    <title>Home</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-grid-container">
                    <div id="announcementList" class="content-container">
                        <h1 class="content-title"><i class="fa fa-bullhorn" style="margin-right: 6px" aria-hidden="true"></i>Announcements</h1>
                        <div class="scroll-div">
                            <div id="annCarouselContainer">
                                <!-- Latest announcement will be displayed here -->
                            </div>
                        </div>
                    </div>
                    <div id="eventList" class="content-container">
                        <h1 class="content-title"><i class="fa fa-calendar-check-o" style="margin-right: 6px" aria-hidden="true"></i>Upcoming Events</h1>
                        <div class="scroll-div">
                            <div id="eveCarouselContainer">
                                <!-- Latest events  will be displayed here -->
                            </div>
                        </div>
                    </div>
                    <div id="newsList" class="content-container">
                        <h1 class="content-title"><i class="fa fa-newspaper-o" style="margin-right: 6px" aria-hidden="true"></i>News</h1>
                        <div class="scroll-div">
                            <div id="newsCarouselContainer">
                                <!-- Latest news will be displayed here -->
                            </div>
                        </div>
                    </div>
                    <div id="promaterialList" class="content-container">
                        <h1 class="content-title"><i class="fa fa-object-group" style="margin-right: 6px" aria-hidden="true"></i>Promotional Materials</h1>
                        <div class="scroll-div">
                            <div id="promaterialsCarouselContainer">
                                <!-- Latest promotional materials will be displayed here -->
                            </div>
                        </div>
                    </div>
                    <div id="peoList" class="content-container">
                        <h1 class="content-title"><i class="fa fa-map" style="margin-right: 6px" aria-hidden="true"></i>Program Educational Objectives (PEO)</h1>
                        <div class="scroll-div">
                            <div id="peoCarouselContainer">
                                <!-- Latest program educational objectives will be displayed here -->
                            </div>
                        </div>
                    </div>
                    <div id="soList" class="content-container">
                        <h1 class="content-title"><i class="fa fa-graduation-cap" style="margin-right: 6px" aria-hidden="true"></i>Student Outcomes (SO)</h1>
                        <div class="scroll-div">
                            <div id="soCarouselContainer">
                                <!-- Latest student outcomes will be displayed here -->
                            </div>
                        </div>
                    </div>
                    <div id="departmentOrganizationalChartList" class="content-container">
                        <h1 class="content-title"><i class="fa fa-university" style="margin-right: 6px" aria-hidden="true"></i>Department Organizational Chart</h1>
                        <div class="scroll-div">
                            <div id="departmentOrganizationalChartCarouselContainer">
                                <!-- Latest department organizational chart will be displayed here -->
                            </div>
                        </div>
                    </div>
                    <div id="facilitiesList" class="content-container">
                        <h1 class="content-title"><i class="fa fa-building" style="margin-right: 6px" aria-hidden="true"></i>Facilities</h1>
                        <div class="scroll-div">
                            <div id="facilitiesCarouselContainer">
                                <!-- Latest facilities will be displayed here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="confirmDeleteAnnouncementModal" class="modal"></div>
    <div id="confirmDeleteEventModal" class="modal"></div>
    <div id="confirmDeleteNewsModal" class="modal"></div>
    <div id="confirmDeletePromaterialModal" class="modal"></div>
    <div id="confirmDeletePEOModal" class="modal"></div>
    <div id="confirmDeleteSOModal" class="modal"></div>
    <div id="confirmArchiveAnnouncementModal" class="modal"></div>
    <div id="confirmArchiveEventModal" class="modal"></div>
    <div id="confirmArchiveNewsModal" class="modal"></div>
    <div id="confirmArchivePromaterialModal" class="modal"></div>
    <div id="confirmArchivePEOModal" class="modal"></div>
    <div id="confirmArchiveSOModal" class="modal"></div>

    <!-- JavaScript to fetch all content using WebSocket-->
    <script src="js/fetch_content.js"></script>
    <script>
        const userType = '<?php echo $user_type; ?>';
        const full_name = '<?php echo $full_name; ?>';
    </script>
</body>
</html>