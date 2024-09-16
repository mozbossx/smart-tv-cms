<?php
// Database connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Get the feature type from the query string
$type = $_GET['type'] ?? '';

// Prepare and execute the query
$stmt = $pdo->prepare("SELECT * FROM features_tb WHERE LOWER(REPLACE(feature_name, ' ', '_')) = :type");
$stmt->execute(['type' => $type]);

// Fetch the result
$featureInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($featureInfo);