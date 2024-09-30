<?php
session_start();

if (isset($_SESSION['mail'])) {
    unset($_SESSION['mail']);
    $response = ['success' => true];
} else {
    $response = ['success' => false];
}

header('Content-Type: application/json');
echo json_encode($response);
?>