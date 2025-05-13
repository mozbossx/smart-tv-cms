<?php
// if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
//     // Localhost configuration (Testing purposes only)
//     $server = 'localhost';
//     $username = 'root';
//     $password = '';
//     $dbname = 'smart_tv_cms_db';
// } 

if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '192.168.10.80:8080' || $_SERVER['HTTP_HOST'] == '192.168.10.43:8080') {
    // Localhost configuration (Testing purposes only)
    $server = 'localhost';
    $username = 'root';
    $password = 'Mozar@123456';
    $dbname = 'smart_tv_cms_db';
} else {
    // Production server configuration
    $server = 'localhost';
    $username = 'root';
    $password = 'Mozar@123456';
    $dbname = 'smart_tv_cms_db';
}

$conn = mysqli_connect($server, $username, $password, $dbname);

if (!$conn) {
    error_log('Connection Failed: ' . mysqli_connect_error());
    die('Connection Failed: ' . mysqli_connect_error());
}
?>