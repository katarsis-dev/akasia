<?php
ob_start();
session_start();
require_once 'config/database.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Daftar halaman yang diizinkan
$allowed_pages = ['home', 'login', 'register', 'dashboard', 'booking', 'riwayat', 'profil', 'kendaraan', 'notifikasi', 'logout', 'lupa_password'];
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

// Redirect jika belum login pada halaman yang diproteksi
$protected_pages = ['dashboard', 'booking', 'riwayat', 'profil', 'kendaraan', 'notifikasi'];
if (in_array($page, $protected_pages) && !isset($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

// Redirect jika sudah login namun mengakses login/register
if (($page == 'login' || $page == 'register') && isset($_SESSION['user_id'])) {
    header('Location: ?page=dashboard');
    exit;
}

// Handler Logout
if ($page == 'logout') {
    session_destroy();
    header('Location: ?page=home');
    exit;
}

// Load views
include 'layout/header.php';
include "pages/$page.php";
include 'layout/footer.php';

ob_end_flush();
