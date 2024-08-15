<?php
// fetch_rejected_users.php

// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Fetch users from the database
$statement = $pdo->query("SELECT * FROM users_tb WHERE status = 'Rejected'");
$rejected_users = $statement->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($rejected_users);
