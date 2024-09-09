<?php
// Database Connection
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");

// Fetch org chart members from the database
$statement = $pdo->query("SELECT * FROM org_chart_members ORDER BY orgchart_id, parent_id");
$chartData = $statement->fetchAll(PDO::FETCH_ASSOC);

// Group data by orgchart_id
$groupedData = [];
foreach ($chartData as $member) {
    $groupedData[$member['orgchart_id']][] = $member;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($groupedData);