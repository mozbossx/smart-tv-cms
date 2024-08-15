<?php
// fetch_smart_tv_template.php

// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Fetch templates from the database
$statement = $pdo->query("SELECT * FROM content_template_tb");
$content_templates = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($content_templates);