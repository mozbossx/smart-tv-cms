<?php
// fetch_layout.php

// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Fetch layout from the database
$statement = $pdo->query("SELECT * FROM containers_tb");
$layout = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($layout);