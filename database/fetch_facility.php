<?php
// fetch_facility.php

// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");

// Fetch facility from the database
$statement = $pdo->query("SELECT * FROM facility_tb");
$facility = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($facility);