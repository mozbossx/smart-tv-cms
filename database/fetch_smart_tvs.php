<?php
// fetch_smart_tvs.php

// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Get tvId from the query string
$tvId = isset($_GET['tvId']) ? intval($_GET['tvId']) : 0;

if ($tvId > 0) {
    // Fetch the specific smart TV from the database
    $statement = $pdo->prepare("SELECT * FROM smart_tvs_tb WHERE tv_id = :tvId");
    $statement->execute([':tvId' => $tvId]);
    $smart_tv = $statement->fetch(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($smart_tv);
} else {
    // Fetch all smart TVs from the database
    $statement = $pdo->query("SELECT * FROM smart_tvs_tb");
    $smart_tvs = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($smart_tvs);
}
