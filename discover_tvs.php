<?php
// discover_tvs.php

header('Content-Type: application/json');

function discoverTVs() {
    $ssdpMessage = 
        "M-SEARCH * HTTP/1.1\r\n" .
        "HOST: 239.255.255.250:1900\r\n" .
        "MAN: \"ssdp:discover\"\r\n" .
        "MX: 2\r\n" .
        "ST: urn:dial-multiscreen-org:service:dial:1\r\n" .
        "\r\n";

    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if (!$socket) {
        error_log("Socket creation failed: " . socket_strerror(socket_last_error()));
        return ['success' => false, 'error' => 'Failed to create socket'];
    }

    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
    socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
    socket_bind($socket, '0.0.0.0', 0);

    if (!socket_sendto($socket, $ssdpMessage, strlen($ssdpMessage), 0, '239.255.255.250', 1900)) {
        $error = socket_strerror(socket_last_error($socket));
        error_log("Failed to send discovery message: " . $error);
        socket_close($socket);
        return ['success' => false, 'error' => 'Failed to send discovery message: ' . $error];
    }

    $tvs = [];
    while (true) {
        $response = '';
        $from = '';
        $port = 0;
        $bytes = @socket_recvfrom($socket, $response, 2048, 0, $from, $port);
        if ($bytes === false) break;
        if ($bytes > 0) {
            if (preg_match('/LOCATION: (.*?)\r\n/i', $response, $matches)) {
                $location = trim($matches[1]);
                $name = "Smart TV at $from";
                $tvs[] = ['name' => $name, 'location' => $location];
            }
        }
    }

    socket_close($socket);

    return ['success' => true, 'tvs' => $tvs];
}

$result = discoverTVs();
error_log("Discovery result: " . json_encode($result));
echo json_encode($result);
?>