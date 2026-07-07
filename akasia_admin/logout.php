<?php
session_start();
unset($_SESSION['admin']);
$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'Logout berhasil.'
];
header('Location: index.php?page=login');
exit;
