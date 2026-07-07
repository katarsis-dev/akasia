<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Login') ?> - Akasia Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* CSS Tambahan khusus untuk mempercantik Login Page */
        body.login-body {
            background-color: #f8fafc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            background-image: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .login-shell {
            width: 100%;
            max-width: 420px;
            padding: 15px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
        }

        .login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
        }

        .login-logo img {
            max-width: 100%;
            height: auto;
            max-height: 140px;
            filter: drop-shadow(0px 8px 12px rgba(0, 0, 0, 0.1));
            mix-blend-mode: multiply;
            opacity: 0.9;
        }

        .input-group-text {
            background-color: transparent;
            border-right: none;
            color: #94a3b8;
        }

        .form-control.login-input {
            border-left: none;
            padding-left: 0;
        }

        .form-control.login-input:focus {
            box-shadow: none;
            border-color: #dee2e6;
        }

        .input-group:focus-within {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-radius: 0.375rem;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control,
        .input-group:focus-within .password-toggle {
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .password-toggle {
            cursor: pointer;
            background: transparent;
            border: 1px solid #dee2e6;
            border-left: none;
            color: #94a3b8;
        }

        .password-toggle:hover {
            color: #475569;
        }
    </style>
</head>

<body class="login-body">
    <div class="login-shell">
        <div class="login-card">
            <div class="login-logo">
                <img src="assets/images/logo.png" alt="Akasia Motor Logo"
                    style="filter: drop-shadow(0px 10px 15px rgba(0,0,0,0.2)); border-radius: 12px;">
            </div>

            <?php if (!empty($flash)): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert" style="border-radius: 10px; font-size: 14px;">
                    <i class="bi <?= $flash['type'] === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' ?> me-2"></i>
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['step']) && $_GET['step'] === 'verify_otp'): ?>

                <!-- TAMPILAN FORM 2: VERIFIKASI OTP & PASSWORD BARU -->
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-dark mb-1">Ganti Password</h4>
                    <p class="text-muted small">Masukkan 6 digit kode OTP yang dikirim ke WhatsApp Anda.</p>
                </div>

                <form method="post" action="index.php?page=login">
                    <input type="hidden" name="action" value="process_reset_password">

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 14px;">Kode OTP</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="number" name="otp" class="form-control" placeholder="Masukkan 6 angka OTP" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size: 14px;">Password Baru</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" name="new_password" id="newPasswordInput" class="form-control login-input" placeholder="Masukkan password baru" required minlength="6">
                            <button class="input-group-text password-toggle" type="button" id="toggleNewPassword">
                                <i class="bi bi-eye-slash" id="toggleNewIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold" style="border-radius: 10px; font-size: 15px;">
                        Simpan Password Baru <i class="bi bi-check-circle ms-1"></i>
                    </button>

                    <div class="text-center mt-3">
                        <a href="index.php?page=login" class="text-decoration-none small text-danger">Batal & Kembali Login</a>
                    </div>
                </form>

            <?php else: ?>

                <!-- TAMPILAN FORM 1: LOGIN BIASA & MODAL MINTA OTP -->
                <div class="text-center mb-4">
                    <h5 class="fw-bold text-dark" style="font-size: 18px;">Selamat Datang!</h5>
                    <p class="text-muted small">Silakan masuk menggunakan akun admin Anda.</p>
                </div>

                <form method="post" action="index.php?page=login">
                    <input type="hidden" name="action" value="login">

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 14px; color: #475569;">Email Admin</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control login-input" placeholder="admin@akasiamotor.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size: 14px; color: #475569;">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="passwordInput" class="form-control login-input" placeholder="Masukkan password" required>
                            <button class="input-group-text password-toggle" type="button" id="togglePassword">
                                <i class="bi bi-eye-slash" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="border-radius: 10px; font-size: 15px;">
                        Masuk ke Sistem <i class="bi bi-box-arrow-in-right ms-1"></i>
                    </button>

                    <!-- Link Lupa Password -->
                    <div class="text-center mt-3">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#requestOtpModal" class="text-decoration-none small" style="color: #0d6efd; font-weight: 600;">
                            Lupa Password?
                        </a>
                    </div>
                </form>

                <!-- Modal Minta OTP Reset Password -->
                <div class="modal fade" id="requestOtpModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 16px;">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold">Reset Password</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="text-muted small mb-4">Masukkan email akun Anda. Kami akan mengirimkan kode OTP ke nomor WhatsApp yang terdaftar untuk mengganti password.</p>
                                <form method="post" action="index.php?page=login">
                                    <input type="hidden" name="action" value="request_otp_reset">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size: 14px;">Email Terdaftar</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" name="email" class="form-control" placeholder="Contoh: admin@akasiamotor.com" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-warning w-100 fw-bold" style="border-radius: 10px;">
                                        Kirim Kode OTP <i class="bi bi-whatsapp ms-1"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div>
        <div class="text-center mt-4">
            <small class="text-muted" style="font-weight: 500;">&copy; <?= date('Y') ?> Akasia Motor. All rights reserved.</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi dinamis untuk mengelola toggle password
        function setupPasswordToggle(buttonId, inputId, iconId) {
            const toggleBtn = document.getElementById(buttonId);
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);

            if (toggleBtn && passwordInput && toggleIcon) {
                toggleBtn.addEventListener('click', function() {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        toggleIcon.classList.remove('bi-eye-slash');
                        toggleIcon.classList.add('bi-eye');
                    } else {
                        passwordInput.type = 'password';
                        toggleIcon.classList.remove('bi-eye');
                        toggleIcon.classList.add('bi-eye-slash');
                    }
                });
            }
        }

        // Terapkan ke form login utama
        setupPasswordToggle('togglePassword', 'passwordInput', 'toggleIcon');

        // Terapkan ke form password baru (saat reset OTP)
        setupPasswordToggle('toggleNewPassword', 'newPasswordInput', 'toggleNewIcon');
    </script>
</body>

</html>