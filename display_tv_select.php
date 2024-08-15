<?php

// Use a prepared statement for fetching TV data
$query_tvs = "SELECT tv_name, device_id FROM smart_tvs_tb";
$result_tv = $conn->query($query_tvs);

$options_tv = '';

// Check if data is found
if ($result_tv && $result_tv->num_rows > 0) {
    while ($row = $result_tv->fetch_assoc()) {
        // Generate options for select based on TV names where tv_display is 'Classrooms'
        $options_tv .= '<option value="' . htmlspecialchars($row['tv_name'], ENT_QUOTES, 'UTF-8') . '" data-device-id="' . htmlspecialchars($row['device_id'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row['tv_name'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
}
