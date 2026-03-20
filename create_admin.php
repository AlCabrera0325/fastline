<?php
// ============================================================
// TEMPORARY FILE - Run this ONCE then DELETE it immediately
// Visit: localhost/midtermOutputfastline/create_admin.php
// ============================================================
require 'includes/db.php';

$username = 'admin';
$password = 'Password0323'; // Change this to your preferred password

// Delete any existing admin with this username first
$pdo->prepare("DELETE FROM admin_users WHERE username = ?")->execute([$username]);

// Insert fresh with properly hashed password
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
$stmt->execute([$username, $hash]);

echo "<h2 style='font-family:sans-serif; color:green'> Admin account created!</h2>";
echo "<p style='font-family:sans-serif'>Username: <strong>$username</strong><br>Password: <strong>$password</strong></p>";
echo "<p style='font-family:sans-serif; color:red'><strong> DELETE this file immediately after use!</strong></p>";
echo "<p style='font-family:sans-serif'><a href='admin/login.php'>Go to Admin Login →</a></p>";
?>
