<?php
require_once './dotenv.php';
loadEnv(__DIR__ . '/.env');
$host = $_ENV['DATABASE_HOST'];
$dbname = $_ENV['DATABASE_NAME'];
$username = $_ENV['DATABASE_USER'];
$password = $_ENV['DATABASE_PASS'];
$port = $_ENV['DATABASE_PORT'];
$driver = $_ENV['DATABASE_DRIVER'];
try {
    if ($driver === 'mysql') {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    } elseif($driver === 'sqlsrv') {
        $pdo = new PDO("sqlsrv:Server=$host;Database=$dbname;TrustServerCertificate=yes;", $username, $password);
    } elseif($driver === 'pgsql') {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    } else {
        die("Unsupported database driver.");
    }
    return $pdo;
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Contact admin.". $e->getMessage());
}
?>