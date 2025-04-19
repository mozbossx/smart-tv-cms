<?php
// fetch tv data
$options_tv = '';
$user_department = $department; // $department is already set from the session
// Get tvId from URL if it exists
$urlTvId = isset($_GET['tvId']) ? $_GET['tvId'] : null;

// Check if we're in edit mode
$isEditMode = isset($_GET['action']) && $_GET['action'] === 'edit';
$editTvId = $isEditMode ? $_GET['tv_id'] : null;

// Modify the SQL query based on whether we're in edit mode
if ($isEditMode) {
    $sql = "SELECT tv_id, tv_name, tv_department FROM smart_tvs_tb WHERE tv_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $editTvId);
} else {
    // Original logic for non-edit mode
    $sql = "SELECT tv_id, tv_name, tv_department FROM smart_tvs_tb WHERE tv_department = ? AND tv_name != 'Unknown' AND tv_brand != 'Unknown'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $user_department);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$tv_count = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $tv_count++;
    // Add pointer-events-none class in edit mode
    $labelStyle = $isEditMode ? 'display: block; margin-bottom: 7px; padding: 10px; background: #f3f3f3; border-radius: 5px; pointer-events: none;' : 'display: block; margin-bottom: 7px; padding: 10px; background: #f3f3f3; border-radius: 5px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;';
    $options_tv .= '<label style="' . $labelStyle . '">';

    // Check if this TV should be pre-selected
    $isPreselected = ($urlTvId && $row['tv_id'] == $urlTvId);
    
    // In edit mode, make the checkbox checked and disabled
    if ($isEditMode) {
        $options_tv .= '<input type="checkbox" name="tv_id[]" value="' . $row['tv_id'] . '" data-tv-name="' . $row['tv_name'] . '" checked disabled style="pointer-events: none;">';
        // Add a hidden input to ensure the value is still submitted when the checkbox is disabled
        $options_tv .= '<input type="hidden" name="tv_id[]" value="' . $row['tv_id'] . '">';
    } else {
        // $options_tv .= '<input type="checkbox" name="tv_id[]" value="' . $row['tv_id'] . '" data-tv-name="' . $row['tv_name'] . '">';
        $options_tv .= '<input type="checkbox" name="tv_id[]" value="' . $row['tv_id'] . '" data-tv-name="' . $row['tv_name'] . '"' . ($isPreselected ? ' checked' : '') . '>';
    }
    
    $options_tv .= ' ' . $row['tv_name'];
    $options_tv .= '</label>';

    // Add to TV name mapping
    $tv_names[$row['tv_id']] = $row['tv_name'];
}

mysqli_stmt_close($stmt);

// If no TVs are available, display a message
if ($tv_count == 0) {
    $options_tv = '<p style="text-align: center; padding: 10px;">No TVs Available</p>';
    // Ensure $tv_names is at least an empty array
    $tv_names = [];
}
?>