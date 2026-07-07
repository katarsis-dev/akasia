<?php

define('FONNTE_API_TOKEN', 'vJcGd5nocf6AA48hxViq');

$dbHost = 'localhost';
$dbPort = '3306';
$dbName = 'akasia_motor';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Koneksi database gagal: ' . htmlspecialchars($e->getMessage());
    exit;
}
