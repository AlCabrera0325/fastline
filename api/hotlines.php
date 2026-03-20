<?php
header('Content-Type: application/json');
require '../includes/db.php';

$category = $_GET['category'] ?? '';
$city     = $_GET['city']     ?? '';
$search   = trim($_GET['search'] ?? '');

$sql    = "SELECT * FROM hotlines WHERE is_active = 1";
$params = [];

if (!empty($category) && $category !== 'favorites') {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if (!empty($city)) {
    $sql .= " AND (city = 'national' OR city = ?)";
    $params[] = $city;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR description LIKE ? OR city LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode(['success' => true, 'hotlines' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
