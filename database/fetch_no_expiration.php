<?php
// fetch_no_expiration.php
// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Fetch no_expiration_tb from the database
$statement = $pdo->query("SELECT * FROM no_expiration_tb");
$no_expiration = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($no_expiration);
