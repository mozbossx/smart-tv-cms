<?php
// fetch_events.php

// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");

// Fetch announcements from the database
$statement = $pdo->query("SELECT * FROM news_tb");
$news = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($news);
