<?php
$options_tv = '';
$sql = "SELECT tv_id, tv_name FROM smart_tvs_tb";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $options_tv .= '<li><input type="checkbox" name="tv_id[]" value="' . $row['tv_id'] . '"> ' . $row['tv_name'] . '</li>';
}
?>
