<?php
// fetch tv data
$options_tv = '';
$sql = "SELECT tv_id, tv_name FROM smart_tvs_tb";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $options_tv .= '<label style="display: block; margin-bottom: 7px; padding: 10px; background: #f3f3f3; border-radius: 5px">';
    $options_tv .= '<input type="checkbox" name="tv_id[]" value="' . $row['tv_id'] . '" data-tv-name="' . $row['tv_name'] . '">';
    $options_tv .= ' ' . $row['tv_name'];
    $options_tv .= '</label>';

    // Add to TV name mapping
    $tv_names[$row['tv_id']] = $row['tv_name'];
}

?>