<?php
// fetch_peo.php

// Database Connectionz
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Fetch announcements from the database
$statement = $pdo->query("SELECT * FROM peo_tb");
$peo = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($peo);
