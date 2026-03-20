<?php
header('Content-Type: application/json');

require __DIR__ . '/../includes/db.php';

try {
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
    $hotlines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'hotlines' => $hotlines]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}