<?php
// fetch_containers.php

// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");

// Get tvId from the query string
$tvId = isset($_GET['tvId']) ? intval($_GET['tvId']) : 0;

if ($tvId > 0) {
    // Fetch all containers for the TV, including visibility status
    $statement = $pdo->prepare("SELECT * FROM containers_tb WHERE tv_id = :tvId");
    $statement->execute([':tvId' => $tvId]);
    $containers = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($containers);
} else {
    // Fetch all smart TVs from the database
    // manage_smart_tv.php section
    $statement = $pdo->query("SELECT * FROM containers_tb");
    $containers = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($containers);
}
