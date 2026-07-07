<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Jangan disanitize, biarkan raw untuk verifikasi hash

    try {
        $stmt = $pdo->prepare("SELECT id, nama, password, role FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        // Verifikasi password
        if ($user && password_verify($password, $user['password'])) {
            // Pengecekan role agar hanya pelanggan yang bisa login
            if ($user['role'] === 'pelanggan') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                header('Location: ?page=dashboard');
                exit;
            } else {
                $error = "Portal ini hanya khusus untuk pelanggan.";
            }
        } else {
            $error = "Email atau password salah.";
        }
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-primary">Login Pelanggan</h4>
                    <p class="text-muted small">Silakan masuk ke akun Anda</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger p-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold mb-0">Password</label>
                            <a href="?page=lupa_password" class="text-decoration-none small text-primary fw-semibold">
                                Lupa Password?
                            </a>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" id="login_password" class="form-control" placeholder="*****" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('login_password', 'icon_login')">
                                <i class="bi bi-eye" id="icon_login"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Login</button>
                </form>

                <div class="text-center mt-4 mb-2 small">
                    Belum punya akun? <a href="?page=register" class="text-decoration-none fw-semibold">Daftar sekarang</a>
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