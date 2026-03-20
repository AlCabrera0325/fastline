<?php
require 'includes/db.php';

$sql = file_get_contents('fastline.sql');
$pdo->exec($sql);

echo "<h2 style='font-family:sans-serif; color:green'>✅ Database imported successfully!</h2>";
echo "<p style='font-family:sans-serif'><a href='index.php'>Go to FastLine →</a></p>";
?>