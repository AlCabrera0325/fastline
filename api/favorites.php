<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $id     = (int)($input['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }

        if ($action === 'add') {
            if (!in_array($id, $_SESSION['favorites'])) {
                $_SESSION['favorites'][] = $id;
            }
        } elseif ($action === 'remove') {
            $_SESSION['favorites'] = array_values(
                array_filter($_SESSION['favorites'], fn($f) => $f !== $id)
            );
        } elseif ($action === 'get') {
            // just return current favorites
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
        }
    }

    echo json_encode([
        'success'   => true,
        'favorites' => array_values($_SESSION['favorites'])
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}