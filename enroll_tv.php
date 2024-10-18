<?php
// enroll_tv.php
session_start();
include 'config_connection.php';
include 'get_session.php';
include 'admin_access_only.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    
    // Generate a unique TV ID (you might want to use a more sophisticated method)
    $tv_name = "Unknown";
    $sql = "INSERT INTO smart_tvs_tb (tv_name, location) VALUES ('$name', '$location')";
    
    if (mysqli_query($conn, $sql)) {
        // TV enrolled successfully, now send command to open browser
        $tv_ip = parse_url($location, PHP_URL_HOST);
        $command_url = "http://$tv_ip:8080/api/v2/applications/TVWeb%20Browser";
        $payload = json_encode([
            "action" => "DEEP_LINK",
            "target" => "http://" . $_SERVER['HTTP_HOST'] . "/tv2.php"
        ]);
        $ch = curl_init($command_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code == 200) {
            echo json_encode(['success' => true, 'message' => 'TV enrolled and browser opened']);
        } else {
            echo json_encode(['success' => true, 'message' => 'TV enrolled but failed to open browser']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
mysqli_close($conn);
?>