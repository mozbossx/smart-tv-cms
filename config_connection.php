<?php
// if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
//     // Localhost configuration (Testing purposes only)
//     $server = 'localhost';
//     $username = 'root';
//     $password = '';
//     $dbname = 'smart_tv_cms_db';
// } 

if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '192.168.1.11:8080') {
    // Localhost configuration (Testing purposes only)
    $server = 'localhost';
    $username = 'root';
    $password = '';
    $dbname = 'smart_tv_cms_db';
} else {
    // Production server configuration
    $server = 'sql105.infinityfree.com';
    $username = 'if0_35239409';
    $password = 'V6UuVhxgAhM';
    $dbname = 'if0_35239409_cms_users';
}

$conn = mysqli_connect($server, $username, $password, $dbname);

if (!$conn) {
    die('Connection Failed: ' . mysqli_connect_error());
}
?>