<?php
session_start();
include("config_connection.php");
include 'get_session.php';

// Check if the user is not logged in, return an error
if (!isset($_SESSION['full_name']) || !isset($_SESSION['email'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

// Fetch all features from the database
$stmt = $conn->prepare("SELECT * FROM features_tb");
$stmt->execute();
$features = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Combine default content types with new features
$valid_types = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];
foreach ($features as $feature) {
    $valid_types[] = strtolower(str_replace(' ', '_', $feature['feature_name']));
}

if (!in_array($type, $valid_types)) {
    echo json_encode(['error' => 'Invalid content type']);
    exit;
}

// Set up the query based on the content type
if ($type === 'announcement' || $type === 'event' || $type === 'promaterial') {
    $table_name = $type . 's_tb';
} else {
    $table_name = $type . '_tb';
}
$author_column = $type . '_author_id';
$title_column = $type . '_body';

// Fetch posts for the specific content type
$query = "SELECT * FROM $table_name WHERE $author_column = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}
$stmt->close();

echo json_encode($posts);