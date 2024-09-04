<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// Prepare SQL query to fetch organizational chart data
$sqlChart = "SELECT id, parent_id, name, title, picture FROM org_chart_members";
$resultChart = $conn->query($sqlChart);

// Initialize an array to store the chart data
$chartData = [];

if ($resultChart->num_rows > 0) {
    // Fetch each row and store in the array
    while ($rowChart = $resultChart->fetch_assoc()) {
        $chartData[] = [
            "key" => $rowChart["id"], // Node's key (unique ID)
            "parent" => $rowChart["parent_id"] ? $rowChart["parent_id"] : null, // Parent node's ID
            "name" => $rowChart["name"], // Node's name
            "title" => $rowChart["title"], // Node's title
            "picture" => $rowChart["picture"] // Node's title
        ];
    }
}

// Convert the array to JSON format
echo json_encode($chartData);

?>
