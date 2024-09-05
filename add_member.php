<?php
include 'config_connection.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$name = $data['name'];
$title = $data['title'];
$picture = $data['picture'];  // Base64-encoded picture
$parent = $data['parent'];

// Save member details in the database
$sql = "INSERT INTO org_chart_members (name, title, picture, parent_id) VALUES ('$name', '$title', '$picture', '$parent')";
if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>
