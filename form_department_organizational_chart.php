<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// fetch tv data from the select options
include 'misc/php/options_tv.php';

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="misc/js/treant-js-master/vendor/jquery.min.js"></script>
    <script src="misc/js/treant-js-master/vendor/raphael.js"></script>
    <script src="misc/js/treant-js-master/Treant.js"></script>
    <link rel="stylesheet" href="misc/js/treant-js-master/Treant.css" />

    <title>Create an Organizational Chart</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <div class="content-form">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb" style="background: none">
                                <li class="breadcrumb-item"><a href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">Create Post</a></li>
                                <li class="breadcrumb-item"><a href="general_info.php?pageid=GeneralInformationForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">Create Post</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Department Organizational Chart Form</li>
                            </ol>
                        </nav>
                        <form id="orgchartForm" enctype="multipart/form-data" class="main-form">
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="orgchart">
                            <h1 style="text-align: center">Department Organizational Chart Form</h1>
                            <?php include('misc/php/displaytime_tvdisplay.php')?>
                            <div id="chart-container" style="width: 100%; height: 600px; border: 1px solid black;"></div>
                            <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px">
                                <div>
                                    <button type="button" name="preview" id="previewButton" class="preview-button" style="margin-right: 0" onclick="validateAndOpenPreviewModal()">
                                        <i class="fa fa-eye" style="padding-right: 5px"></i> Preview 
                                    </button>
                                </div>
                            </div>
                            <?php include('misc/php/preview_modal.php') ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('misc/php/error_modal.php') ?>
    <?php include('misc/php/success_modal.php') ?>
    <script src="misc/js/capitalize_first_letter.js"></script>
    <script src="misc/js/wsform_submission.js"></script>

    <script>
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo json_encode($tv_names); ?>; 

        function fetchOrgChartData() {
            return $.ajax({
                url: 'fetch_org_chart.php',
                dataType: 'json'
            }).done(function(data) {
                console.log("Fetched data:", data);  // Log data to check its structure
            });
        }

        function createOrgChart(data) {
            // Create a map of all nodes by their ID
            const nodes = {};
            data.forEach(member => {
                nodes[member.key] = {
                    text: {
                        name: member.name,
                        title: member.title,
                        image: member.picture ? member.picture : ""
                    },
                    HTMLclass: "custom-node",
                    children: []
                };
                console.log("Nodes:", nodes);
            });

            // Build hierarchy by linking nodes
            let rootNode = null;
            data.forEach(member => {
                if (member.parent) {
                    if (nodes[member.parent]) {
                        nodes[member.parent].children.push(nodes[member.key]);
                    }
                } else {
                    rootNode = nodes[member.key];  // This should be the root node
                }
            });

            // Check if rootNode is defined
            if (rootNode) {
                // Initialize Treant with the root node
                new Treant({
                    chart: {
                        container: "#chart-container",
                        nodeAlign: "BOTTOM",
                        levelSeparation: 50,
                        siblingSeparation: 40,
                        subTeeSeparation: 40
                    },
                    nodeStructure: rootNode
                });
                console.log("Root Node:", rootNode);
            } else {
                console.error("No root node found");
            }
        }

        $(document).ready(function() {
            fetchOrgChartData().done(createOrgChart);
        });
    </script>
</body>
</html>