<?php
session_start();

// Ensure that the necessary session variables are set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['full_name']) || !isset($_SESSION['user_type']) || !isset($_SESSION['department'])) {
    echo "Error: Missing required session variables.";
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$full_name = urlencode($_SESSION['full_name']);
$user_type = urlencode($_SESSION['user_type']);
$department = urlencode($_SESSION['department']);
$email = urlencode($_SESSION['email']);

$websocket_url = "ws://192.168.1.35:8081?user_id={$user_id}&full_name={$full_name}&user_type={$user_type}&department={$department}&email={$email}";

echo $websocket_url;
?>
