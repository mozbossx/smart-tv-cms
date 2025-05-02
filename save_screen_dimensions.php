<?php
include 'config_connection.php';

$tvId = $_POST['tvId'] ?? null;
$width = $_POST['width'] ?? null;
$height = $_POST['height'] ?? null;

// Old Code: 
// if ($tvId && $width && $height) {
//     $sql = "UPDATE smart_tvs_tb SET width_px = ?, height_px = ? WHERE tv_id = ?";
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("iii", $width, $height, $tvId);
//     if ($stmt->execute()) {
//         echo "Dimensions updated successfully.";
//     } else {
//         echo "Error updating dimensions: " . $conn->error;
//     }
// } else {
//     echo "Invalid input.";
// }


// New Code: 
if ($tvId && $width && $height) {
    $maxRetries = 3;
    $retryCount = 0;
    $success = false;

    while ($retryCount < $maxRetries && !$success) {
        try {
            // Start transaction
            $conn->begin_transaction();

            $sql = "UPDATE smart_tvs_tb SET width_px = ?, height_px = ? WHERE tv_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $width, $height, $tvId);
            
            if ($stmt->execute()) {
                // Commit the transaction
                $conn->commit();
                $success = true;
                echo json_encode(['status' => 'success', 'message' => 'Dimensions updated successfully.']);
            } else {
                throw new Exception("Error updating dimensions: " . $conn->error);
            }
        } catch (Exception $e) {
            // Rollback the transaction
            $conn->rollback();
            
            $retryCount++;
            if ($retryCount < $maxRetries) {
                // Wait for a short time before retrying (500ms)
                usleep(500000);
                continue;
            }
            
            // If we've exhausted all retries, return error
            $errorMessage = $e->getMessage();
            error_log("Failed to update screen dimensions after {$maxRetries} attempts: " . $errorMessage);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update dimensions. Please try again.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input parameters.']);
}

// Close the connection
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();

?>