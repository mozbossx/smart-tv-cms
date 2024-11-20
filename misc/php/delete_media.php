<?php
include '../../config_connection.php';

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['type']) || !isset($data['media_path']) || !isset($data['content_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$type = $data['type'];
$contentId = $data['content_id'];

// Define the table name based on content type
if ($type === 'announcement' || $type === 'event' || $type === 'promaterial') {
    $table = $type . 's_tb';
} else {
    $table = $type . '_tb';
}

$idField = $type . '_id';

try {
    $stmtMediaPath = $conn->prepare("SELECT media_path FROM {$table} WHERE {$idField} = ?");
    $stmtMediaPath->bind_param('i', $contentId);
    $stmtMediaPath->execute();
    $resultMediaPath = $stmtMediaPath->get_result();
    $mediaPath = $resultMediaPath->fetch_assoc()['media_path'];
    // First, delete the physical file
    $fullPath = "../../servers/{$type}_media/{$mediaPath}";
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }

    // Then, update the database to remove the media path
    $stmt = $conn->prepare("UPDATE {$table} SET media_path = NULL WHERE {$idField} = ?");
    $stmt->bind_param('i', $contentId);
    $success = $stmt->execute();

    if ($success) {
        echo json_encode(['success' => true]);
        error_log("Media deleted successfully for {$type} with ID {$contentId} at {$mediaPath}");
    } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>