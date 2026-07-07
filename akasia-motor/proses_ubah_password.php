<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Pastikan pelanggan sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi Anda telah berakhir. Silakan login kembali.']);
    exit;
}

$userId = $_SESSION['user_id'];
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validasi input kosong
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['status' => 'error', 'message' => 'Semua kolom password wajib diisi.']);
    exit;
}

// Validasi kesamaan password baru
if ($newPassword !== $confirmPassword) {
    echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password baru tidak cocok.']);
    exit;
}

// Validasi panjang password
if (strlen($newPassword) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password baru minimal 6 karakter.']);
    exit;
}

try {
    // Ambil data password lama dari database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id AND role = 'pelanggan' LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // ALUR UTAMA: Verifikasi apakah "Password Saat Ini" benar
        if (password_verify($currentPassword, $user['password'])) {

            // Cek jika password baru sama dengan password lama
            if (password_verify($newPassword, $user['password'])) {
                echo json_encode(['status' => 'error', 'message' => 'Password baru tidak boleh sama dengan password saat ini.']);
                exit;
            }

            // Hash password baru dan update database
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");

            if ($updateStmt->execute([':password' => $hashedPassword, ':id' => $userId])) {
                echo json_encode(['status' => 'success', 'message' => 'Password Anda berhasil diperbarui.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui password. Silakan coba lagi.']);
            }
        } else {
            // Jika password saat ini salah
            echo json_encode(['status' => 'error', 'message' => 'Password saat ini yang Anda masukkan salah.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data pengguna tidak ditemukan.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem database.']);
}
exit;
