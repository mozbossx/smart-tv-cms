<?php
// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Check if userId parameter is set in the GET request
if (isset($_GET['userId'])) {
    // Fetch a specific user's data based on userId
    $userId = $_GET['userId'];
    $statement = $pdo->prepare("SELECT * FROM users_tb WHERE user_id = ?");
    $statement->execute([$userId]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    // Return JSON response for a single user
    header('Content-Type: application/json');
    echo json_encode($user);
} else {
    // Fetch all users data from the database
    $statement = $pdo->query("SELECT * FROM users_tb");
    $users = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response for all users
    header('Content-Type: application/json');
    echo json_encode($users);
}
