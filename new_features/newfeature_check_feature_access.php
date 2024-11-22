<?php
// Get the feature name from the current URL
$currentFile = basename($_SERVER['PHP_SELF']);
$featureName = str_replace(['form_', '.php'], '', $currentFile);

// Query to check if feature exists and get its department
$stmt = $conn->prepare("
    SELECT f.department, f.feature_id 
    FROM features_tb f 
    WHERE f.type = ?
");
$stmt->bind_param("s", $featureName);
$stmt->execute();
$result = $stmt->get_result();
$featureData = $result->fetch_assoc();

if ($featureData) {
    // Check department access
    $hasDepartmentAccess = ($featureData['department'] === $department);
    
    // Check user type access
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM feature_user_types 
        WHERE feature_id = ? AND user_type = ?
    ");
    $stmt->bind_param("is", $featureData['feature_id'], $user_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $userTypeCount = $result->fetch_assoc();
    $hasUserTypeAccess = ($userTypeCount['count'] > 0);
    
    // Redirect if user doesn't have access
    if (!$hasDepartmentAccess || !$hasUserTypeAccess) {
        header("Location: user_home.php?pageid=UserHome&userId=" . $user_id . "&fullName=" . $full_name);
        exit();
    }
} else {
    // Feature not found, redirect
    header("Location: user_home.php?pageid=UserHome&userId=" . $user_id . "&fullName=" . $full_name);
    exit();
}
?>