<?php
// fetch_for_all.php
// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Fetch for_all_tb from the database
$statement = $pdo->query("SELECT * FROM for_all_tb");
$for_all = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($for_all);
