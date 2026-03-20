<?php
$host = getenv('DB_HOST');
$name = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

echo "<h3>Connection Details:</h3>";
echo "Host: $host<br>";
echo "Name: $name<br>";
echo "User: $user<br>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8", $user, $pass);
    echo "<h2 style='color:green'>✅ Connected successfully!</h2>";
} catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ Connection failed:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>