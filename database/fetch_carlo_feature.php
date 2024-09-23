<?php
// fetch_carlo_feature.php
// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Fetch carlo_feature_tb from the database
$statement = $pdo->query("SELECT * FROM carlo_feature_tb");
$carlo_feature = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($carlo_feature);
