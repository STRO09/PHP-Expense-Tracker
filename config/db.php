<?php
$host = 'localhost';
$db   = 'expense_tracker';
$user = 'postgres';
$pass = 'tfws.wow///POP()';
$port = '5432';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); //options is to throw an error and not silently fail
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
