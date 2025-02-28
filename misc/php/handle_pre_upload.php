<?php
session_start();
include '../../config_connection.php';

header('Content-Type: application/json');

try {
    if (!isset($_FILES['media'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['media'];
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetDir = '../../servers/temp/';
    
    // Create temp directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $targetFile = $targetDir . $fileName;

    // Validate file size (e.g., 100MB limit)
    if ($file['size'] > 100 * 1024 * 1024) {
        throw new Exception('File is too large');
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type');
    }

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        echo json_encode([
            'success' => true,
            'tempFile' => $fileName
        ]);
    } else {
        throw new Exception('Failed to move uploaded file');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}