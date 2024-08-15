<?php
// fetch_events.php

// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Fetch announcements from the database
$statement = $pdo->query("SELECT * FROM promaterials_tb");
$promaterials = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($promaterials);
