<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// Get the feature type from the query string
$type = $_GET['type'] ?? '';

try {
    if ($type === 'all') {
        // Fetch all features
        $stmt = $pdo->query("SELECT * FROM features_tb");
        $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Transform feature names to match the expected format
        $features = array_map(function($feature) {
            return [
                'type' => strtolower(str_replace(' ', '_', $feature['feature_name'])),
                'name' => $feature['feature_name'],
                'icon' => $feature['icon']
            ];
        }, $features);
    } else {
        // Fetch specific feature
        $stmt = $pdo->prepare("SELECT * FROM features_tb WHERE LOWER(REPLACE(feature_name, ' ', '_')) = :type");
        $stmt->execute(['type' => $type]);
        $features = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($features);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to fetch features: ' . $e->getMessage()]);
}