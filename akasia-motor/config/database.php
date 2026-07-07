<?php
if (session_status() === PHP_SESSION_NONE) {
     session_start();
}

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
               PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
               PDO::ATTR_EMULATE_PREPARES => false // Mencegah SQL Injection di level prepare statement
          ]
     );
} catch (PDOException $e) {
     http_response_code(500);
     echo 'Koneksi database Akasia Motor gagal: ' . htmlspecialchars($e->getMessage());
     exit;
}


function base_url($path = '')
{
     $base = "http://" . $_SERVER['HTTP_HOST'] . "/";
     return $base . '/' . ltrim($path, '/');
}

function formatRupiah($angka)
{
     return "Rp " . number_format($angka, 0, ',', '.');
}

function formatTanggal($tanggal)
{
     return date('d-m-Y', strtotime($tanggal));
}
