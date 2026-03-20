<?php
require __DIR__ . '/includes/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/fastline.sql');
    
    // Remove CREATE DATABASE and USE statements
    $sql = preg_replace('/CREATE DATABASE.*?;/is', '', $sql);
    $sql = preg_replace('/USE.*?;/is', '', $sql);
    
    $pdo->exec($sql);
    echo "<h2 style='font-family:sans-serif; color:green'>✅ Database imported successfully!</h2>";
    echo "<p><a href='index.php'>Go to FastLine →</a></p>";
} catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>