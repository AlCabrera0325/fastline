<?php
require __DIR__ . '/includes/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/fastline.sql');
    
    // Remove CREATE DATABASE, USE, and blank lines at top
    $sql = preg_replace('/^CREATE DATABASE[^;]+;/im', '', $sql);
    $sql = preg_replace('/^USE[^;]+;/im', '', $sql);
    
    // Split into individual statements and run one by one
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s)
    );

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

    echo "<h2 style='font-family:sans-serif; color:green'>✅ Database imported successfully!</h2>";
    echo "<p><a href='index.php'>Go to FastLine →</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>