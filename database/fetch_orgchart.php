<?php
// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Get TV ID from request
$tvId = isset($_GET['tvId']) ? $_GET['tvId'] : null;

if (!$tvId) {
    echo json_encode(['error' => 'TV ID is required']);
    exit;
}

// Prepare and execute the query with proper parameter binding
$statement = $pdo->prepare("SELECT * FROM org_chart_members WHERE tv_id = ? ORDER BY orgchart_id, parent_id");
$statement->execute([$tvId]);
$chartData = $statement->fetchAll(PDO::FETCH_ASSOC);

// Group data by orgchart_id
$groupedData = [];
foreach ($chartData as $member) {
    $orgchartId = $member['orgchart_id'];
    if (!isset($groupedData[$orgchartId])) {
        $groupedData[$orgchartId] = [
            'members' => [],
            'display_time' => $member['display_time']
        ];
    }
    $groupedData[$orgchartId]['members'][] = $member;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($groupedData);
?>