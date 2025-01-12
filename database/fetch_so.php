<?php
// fetch_so.php

// Database Connectionz
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");

// Fetch announcements from the database
$statement = $pdo->query("SELECT * FROM so_tb");
$so = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($so);
