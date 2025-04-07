<?php
include '../config_connection.php';

// sending JSON response headers
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['settings'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE auto_delete_user_inactivity_tb 
                           SET faculty_days = ?, staff_days = ?, student_days = ?,
                               faculty_enabled = ?, staff_enabled = ?, student_enabled = ?
                           WHERE department = ?");

    foreach ($data['settings'] as $department => $values) {
        $facultyDays = $values['faculty_days'];
        $staffDays = $values['staff_days'];
        $studentDays = $values['student_days'];
        $facultyEnabled = $values['faculty_enabled'] ? 1 : 0;
        $staffEnabled = $values['staff_enabled'] ? 1 : 0;
        $studentEnabled = $values['student_enabled'] ? 1 : 0;

        $stmt->bind_param("iiiiiis", 
            $facultyDays, $staffDays, $studentDays,
            $facultyEnabled, $staffEnabled, $studentEnabled,
            $department
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update settings for department: $department");
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>