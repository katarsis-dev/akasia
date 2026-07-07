<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// Langkah 1: Request OTP
if ($action === 'request_otp') {
    $email = trim($_POST['email'] ?? '');

    // Cari user pelanggan ber  dasarkan email
    $stmt = $pdo->prepare("SELECT id, nama, no_whatsapp FROM users WHERE email = :email AND role = 'pelanggan' LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['no_whatsapp'])) {
        // Generate OTP 6 digit
        $otp = rand(100000, 999999);

        // Simpan di Session
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_time'] = time(); // Untuk expired time

        $pesan = "Halo *{$user['nama']}*,\n\nKami menerima permintaan reset password akun Pelanggan Anda di Akasia Motor.\n\nKode OTP Anda: *{$otp}*\n\n_Kode ini berlaku 5 menit. Jangan berikan kepada siapapun._";

        // Ambil token Fonnte dari tabel pengaturan
        $stmt_pengaturan = $pdo->query("SELECT token_fonnte FROM pengaturan LIMIT 1");
        $pengaturan = $stmt_pengaturan->fetch(PDO::FETCH_ASSOC);
        $tokenFonnte = $pengaturan['token_fonnte'] ?? '';

        // Kirim via Curl Fonnte
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'target' => $user['no_whatsapp'],
                'message' => $pesan,
                'countryCode' => '62'
            ],
            CURLOPT_HTTPHEADER => [
                "Authorization: $tokenFonnte"
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        // ... (kode curl_setopt_array sebelumnya tetap sama)

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        // 1. Cek apakah ada error dari server/koneksi Curl
        if ($error) {
            // Hapus session karena gagal
            unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_time']);
            echo json_encode(['status' => 'error', 'message' => 'Gagal terhubung ke server WhatsApp.']);
            exit;
        }

        // 2. Parse response dari Fonnte (Fonnte mengembalikan format JSON)
        $result = json_decode($response, true);

        // Cek status dari Fonnte (true jika berhasil, false jika gagal)
        if (isset($result['status']) && $result['status'] === true) {
            echo json_encode(['status' => 'success', 'message' => 'Kode OTP berhasil dikirim ke WhatsApp Anda.']);
        } else {
            // Ambil alasan spesifik kenapa gagal dari Fonnte
            $reason = $result['reason'] ?? 'Token tidak valid atau sistem bermasalah.';

            // Hapus session karena OTP urung dikirim
            unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_time']);

            echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim OTP: ' . $reason]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email tidak ditemukan atau nomor WhatsApp belum diatur.']);
    }
    exit;
}

// Langkah 2: Verifikasi OTP
if ($action === 'verify_otp') {
    $otp = trim($_POST['otp'] ?? '');

    if (isset($_SESSION['reset_otp']) && isset($_SESSION['reset_time'])) {
        // Cek kedaluwarsa (5 menit = 300 detik)
        if (time() - $_SESSION['reset_time'] > 300) {
            echo json_encode(['status' => 'error', 'message' => 'Kode OTP kedaluwarsa. Silakan muat ulang halaman.']);
        } elseif ($otp == $_SESSION['reset_otp']) {
            $_SESSION['otp_verified'] = true;
            echo json_encode(['status' => 'success', 'message' => 'OTP Valid.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Kode OTP salah.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Sesi OTP tidak ditemukan.']);
    }
    exit;
}

// Langkah 3: Update Password
if ($action === 'reset_password') {
    $new_password = $_POST['new_password'] ?? '';

    if (isset($_SESSION['reset_email']) && !empty($_SESSION['otp_verified'])) {
        if (strlen($new_password) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'Password minimal 6 karakter.']);
            exit;
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email AND role = 'pelanggan'");

        if ($stmt->execute([':password' => $hashed_password, ':email' => $_SESSION['reset_email']])) {
            // Hapus session setelah berhasil
            unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_time'], $_SESSION['otp_verified']);
            echo json_encode(['status' => 'success', 'message' => 'Password berhasil diubah!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan password baru.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Akses tidak sah atau OTP belum diverifikasi.']);
    }
    exit;
}
