<?php
// fetch_chemical_engineering_only.php
// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Fetch chemical_engineering_only_tb from the database
$statement = $pdo->query("SELECT * FROM chemical_engineering_only_tb");
$chemical_engineering_only = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($chemical_engineering_only);
