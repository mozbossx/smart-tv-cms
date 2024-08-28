<?php
session_start();

// Ensure that the necessary session variables are set
if (!isset($_SESSION['full_name']) || !isset($_SESSION['user_type']) || !isset($_SESSION['department'])) {
    echo "Error: Missing required session variables.";
    exit;
}

$full_name = urlencode($_SESSION['full_name']);
$user_type = urlencode($_SESSION['user_type']);
$department = urlencode($_SESSION['department']);

$websocket_url = "ws://192.168.1.11:8081?full_name={$full_name}&user_type={$user_type}&department={$department}";
echo $websocket_url;
?>
