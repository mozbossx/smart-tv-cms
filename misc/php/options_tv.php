<?php
// fetch tv data
$options_tv = '';
$user_department = $department; // Assuming $department is already set from the session

// Modify the SQL query based on user type
if ($user_type == 'Super Admin') {
    $sql = "SELECT tv_id, tv_name, tv_department FROM smart_tvs_tb";
} else {
    $sql = "SELECT tv_id, tv_name, tv_department FROM smart_tvs_tb WHERE tv_department = ?";
}

$stmt = mysqli_prepare($conn, $sql);

if ($user_type != 'Super Admin') {
    mysqli_stmt_bind_param($stmt, "s", $user_department);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$tv_count = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $tv_count++;
    $options_tv .= '<label style="display: block; margin-bottom: 7px; padding: 10px; background: #f3f3f3; border-radius: 5px">';
    $options_tv .= '<input type="checkbox" name="tv_id[]" value="' . $row['tv_id'] . '" data-tv-name="' . $row['tv_name'] . '">';
    $options_tv .= ' ' . $row['tv_name'];
    $options_tv .= '</label>';

    // Add to TV name mapping
    $tv_names[$row['tv_id']] = $row['tv_name'];
}

mysqli_stmt_close($stmt);

// If no TVs are available, display a message
if ($tv_count == 0) {
    $options_tv = '<p style="text-align: center; padding: 10px;">No TVs Available</p>';
}
?>