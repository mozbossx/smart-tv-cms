<?php
include 'config_connection.php';

$tvId = $_POST['tvId'] ?? null;
$width = $_POST['width'] ?? null;
$height = $_POST['height'] ?? null;

if ($tvId && $width && $height) {
    $sql = "UPDATE smart_tvs_tb SET width_px = ?, height_px = ? WHERE tv_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $width, $height, $tvId);
    if ($stmt->execute()) {
        echo "Dimensions updated successfully.";
    } else {
        echo "Error updating dimensions: " . $conn->error;
    }
} else {
    echo "Invalid input.";
}
?>