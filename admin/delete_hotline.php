<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];

    // Get hotline name before deleting for the log
    $stmt = $pdo->prepare("SELECT name, category FROM hotlines WHERE id = ?");
    $stmt->execute([$id]);
    $hotline = $stmt->fetch(PDO::FETCH_ASSOC);

    // Delete the hotline
    $pdo->prepare("DELETE FROM hotlines WHERE id = ?")->execute([$id]);

    // AUDIT TRAIL: log the deletion
    if ($hotline) {
        $admin = $_SESSION['admin'] ?? 'unknown';
        $pdo->prepare("INSERT INTO activity_logs (admin_username, action, details) VALUES (?,?,?)")
            ->execute([$admin, 'Deleted Hotline', "\"" . $hotline['name'] . "\" (" . $hotline['category'] . ") — ID #$id"]);
    }
}
header('Location: add_hotline.php');
exit;
