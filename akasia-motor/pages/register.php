<?php
$error = '';
$success = '';

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $wa = trim($_POST['no_whatsapp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    // Validasi dasar
    if ($nama === '' || $email === '' || $wa === '' || $alamat === '' || $password === '' || $konfirmasi_password === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi_password) {
        $error = 'Password dan Konfirmasi Password tidak cocok.';
    } else {
        try {
            // Cek apakah email sudah terdaftar
            $stmt_check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt_check->execute([':email' => $email]);

            if ($stmt_check->fetchColumn()) {
                $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
            } else {
                // Insert ke tabel users saja
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = 'INSERT INTO users (nama, email, password, no_whatsapp, alamat, role) VALUES (:nama, :email, :password, :wa, :alamat, "pelanggan")';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nama' => $nama,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':wa' => $wa,
                    ':alamat' => $alamat
                ]);

                $success = 'Pendaftaran berhasil! Silakan login dengan akun Anda.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center mt-4 mb-5">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-primary">Registrasi Akun Baru</h4>
                    <p class="text-muted small">Lengkapi data diri Anda untuk mulai menggunakan layanan kami.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger p-3 small rounded-3"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success p-3 small rounded-3"><i class="bi bi-check-circle-fill me-2"></i> <?= e($success) ?>
                        <br><a href="?page=login" class="alert-link mt-2 d-inline-block">Klik di sini untuk menuju halaman Login</a>.
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Lengkap *</label>
                            <input type="text" name="nama" class="form-control" value="<?= e($_POST['nama'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email *</label>
                            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required placeholder="contoh@email.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">No. WhatsApp *</label>
                            <input type="text" name="no_whatsapp" class="form-control" value="<?= e($_POST['no_whatsapp'] ?? '') ?>" required placeholder="Contoh: 628123456789">
                            <div class="form-text" style="font-size: 11px;">Pastikan nomor aktif untuk menerima notifikasi antrean.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Alamat *</label>
                            <textarea name="alamat" class="form-control" rows="3" required placeholder="Masukkan alamat lengkap Anda"><?= e($_POST['alamat'] ?? '') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-semibold">Password *</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="reg_password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('reg_password', 'icon_reg_pwd')">
                                        <i class="bi bi-eye" id="icon_reg_pwd"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-semibold">Konfirmasi Password *</label>
                                <div class="input-group">
                                    <input type="password" name="konfirmasi_password" id="reg_confirm_password" class="form-control" required minlength="6" placeholder="Ulangi password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('reg_confirm_password', 'icon_reg_confirm')">
                                        <i class="bi bi-eye" id="icon_reg_confirm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mt-3" style="border-radius: 8px;">Daftar Sekarang</button>
                    </form>
                <?php endif; ?>

                <div class="text-center mt-4 pt-3 border-top small">
                    Sudah punya akun? <a href="?page=login" class="text-decoration-none fw-bold">Login di sini</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    }
</script>