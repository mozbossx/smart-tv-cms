<?php
include '../config_connection.php';

$query = "SELECT * FROM auto_delete_user_inactivity_tb";
$result = $conn->query($query);

$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['department']] = [
        'faculty_days' => (int)$row['faculty_days'],
        'staff_days' => (int)$row['staff_days'],
        'student_days' => (int)$row['student_days']
    ];
}

header('Content-Type: application/json');
echo json_encode($settings);

$conn->close();
?>