<?php
session_start();
include 'config_connection.php';
include 'get_session.php';
include 'admin_access_only.php';

header('Content-Type: application/json');

function debug_log($message) {
    error_log(print_r($message, true));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    
    $sql = "INSERT INTO smart_tvs_tb (tv_name, location) VALUES ('$name', '$location')";
    
    if (mysqli_query($conn, $sql)) {
        $tv_ip = parse_url($location, PHP_URL_HOST);
        $command_url = "http://$tv_ip:8080/ws/apps/BrowseHere";
        $target_url = "http://" . $_SERVER['HTTP_HOST'] . "/tv2.php";
        $payload = json_encode([
            "method" => "POST",
            "params" => [
                "url" => $target_url
            ],
            "id" => "1",
            "jsonrpc" => "2.0"
        ]);

        debug_log("Sending request to TV:");
        debug_log("URL: " . $command_url);
        debug_log("Payload: " . $payload);

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
        $curl_error = curl_error($ch);
        curl_close($ch);

        debug_log("Response from TV:");
        debug_log("HTTP Code: " . $http_code);
        debug_log("Result: " . $result);
        debug_log("Curl Error: " . $curl_error);

        if ($http_code == 200) {
            $response_data = json_decode($result, true);
            if (isset($response_data['result']) && $response_data['result'] === 'OK') {
                echo json_encode(['success' => true, 'message' => 'TV enrolled and browser opened']);
            } else {
                echo json_encode(['success' => true, 'message' => 'TV enrolled but failed to open browser', 'tv_response' => $response_data]);
            }
        } else {
            echo json_encode([
                'success' => true, 
                'message' => 'TV enrolled but failed to open browser',
                'error' => "HTTP Error: $http_code",
                'curl_error' => $curl_error,
                'response' => $result
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

mysqli_close($conn);
?>