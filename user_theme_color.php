<?php
// Function to lighten a hex color
if (!function_exists('lightenColorPercent')) {
    function lightenColorPercent($hex, $percent) {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert hex to rgb
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Lighten
        $r = round($r + (255 - $r) * $percent / 100);
        $g = round($g + (255 - $g) * $percent / 100);
        $b = round($b + (255 - $b) * $percent / 100);
        
        // Convert back to hex
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
}

// Function to darken a hex color
if (!function_exists('darkenColorPercent')) {
    function darkenColorPercent($hex, $percent) {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert hex to rgb
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Darken
        $r = round($r * (100 - $percent) / 100);
        $g = round($g * (100 - $percent) / 100);
        $b = round($b * (100 - $percent) / 100);
        
        // Convert back to hex
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
}

if (isset($user_id)) {
    $query = "SELECT theme_color FROM users_tb WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $theme_color = $row['theme_color'];

        $light_theme_color_98_percent = lightenColorPercent($theme_color, 98);
        $light_theme_color_95_percent = lightenColorPercent($theme_color, 95);
        $light_theme_color_90_percent = lightenColorPercent($theme_color, 90);
        $light_theme_color_80_percent = lightenColorPercent($theme_color, 80);
        $light_theme_color_70_percent = lightenColorPercent($theme_color, 70);
        $light_theme_color_75_percent = lightenColorPercent($theme_color, 75);
        $light_theme_color_60_percent = lightenColorPercent($theme_color, 60);
        $light_theme_color_50_percent = lightenColorPercent($theme_color, 50);
        $light_theme_color_40_percent = lightenColorPercent($theme_color, 40);
        $light_theme_color_30_percent = lightenColorPercent($theme_color, 30);
        $light_theme_color_20_percent = lightenColorPercent($theme_color, 20);
        $light_theme_color_10_percent = lightenColorPercent($theme_color, 10);

        $dark_theme_color_90_percent = darkenColorPercent($theme_color, 90);
        $dark_theme_color_80_percent = darkenColorPercent($theme_color, 80);
        $dark_theme_color_70_percent = darkenColorPercent($theme_color, 70);
        $dark_theme_color_60_percent = darkenColorPercent($theme_color, 60);
        $dark_theme_color_50_percent = darkenColorPercent($theme_color, 50);
        $dark_theme_color_40_percent = darkenColorPercent($theme_color, 40);
        $dark_theme_color_30_percent = darkenColorPercent($theme_color, 30);
        $dark_theme_color_20_percent = darkenColorPercent($theme_color, 20);
        $dark_theme_color_10_percent = darkenColorPercent($theme_color, 10);
    }
}
?>