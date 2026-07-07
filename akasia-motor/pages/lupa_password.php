<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-primary">Lupa Password</h4>
                    <p class="text-muted small">Atur ulang sandi akun pelanggan Anda</p>
                </div>

                <!-- Box Alert -->
                <div id="alertBox" class="alert d-none p-2 small" role="alert"></div>

                <!-- Form Step 1: Input Email -->
                <div id="step1">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Terdaftar</label>
                        <input type="email" id="email" class="form-control" placeholder="nama@email.com" required>
                    </div>
                    <button type="button" class="btn btn-primary w-100 fw-bold" onclick="requestOTP()" id="btnRequest">
                        Kirim Kode OTP
                    </button>
                    <div class="text-center mt-4 mb-2 small">
                        Ingat password Anda? <a href="?page=login" class="text-decoration-none fw-semibold">Kembali ke Login</a>
                    </div>
                </div>

                <!-- Form Step 2: Input OTP (Hidden) -->
                <div id="step2" class="d-none">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Masukkan Kode OTP (6 Digit)</label>
                        <input type="text" id="otp_code" class="form-control text-center fs-4 letter-spacing-2" maxlength="6" placeholder="------" required>
                        <div class="form-text small mt-2">Kode OTP telah dikirim ke WhatsApp Anda.</div>
                    </div>
                    <button type="button" class="btn btn-success w-100 fw-bold" onclick="verifyOTP()" id="btnVerify">
                        Verifikasi OTP
                    </button>

                    <div class="text-center mt-3 small">
                        Belum menerima OTP atau gagal? <br>
                        <a href="javascript:void(0)" onclick="requestOTP()" class="text-decoration-none fw-semibold">Kirim Ulang Kode</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal Popup Buat Password Baru -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-shield-lock me-2"></i> Buat Password Baru</h5>
            </div>
            <div class="modal-body p-4">
                <div id="modalAlertBox" class="alert d-none p-2 small" role="alert"></div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Password Baru</label>
                    <div class="input-group">
                        <input type="password" id="new_password" class="form-control" placeholder="Minimal 6 karakter" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', 'icon_new')">
                            <i class="bi bi-eye" id="icon_new"></i>
                        </button>
                    </div>
                </div>

                <button type="button" class="btn btn-primary w-100 fw-bold rounded-pill" onclick="resetPassword()" id="btnReset">
                    Simpan Password
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // -----------------------------------------
    // Fungsi Baru untuk Show/Hide Password
    // -----------------------------------------
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

    function showAlert(boxId, type, message) {
        const box = document.getElementById(boxId);
        box.className = `alert alert-${type} p-2 small`;
        box.innerHTML = message;
        box.classList.remove('d-none');
    }

    async function requestOTP() {
        const email = document.getElementById('email').value;
        if (!email) return showAlert('alertBox', 'danger', 'Email wajib diisi.');

        const btn = document.getElementById('btnRequest');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';

        const formData = new FormData();
        formData.append('action', 'request_otp');
        formData.append('email', email);

        try {
            const res = await fetch('proses_forgot.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                showAlert('alertBox', 'success', data.message);
                document.getElementById('step1').classList.add('d-none');
                document.getElementById('step2').classList.remove('d-none');
            } else {
                showAlert('alertBox', 'danger', data.message);
            }
        } catch (e) {
            showAlert('alertBox', 'danger', 'Terjadi kesalahan sistem.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Kirim Kode OTP';
        }
    }

    async function verifyOTP() {
        const otp = document.getElementById('otp_code').value;
        if (!otp) return showAlert('alertBox', 'danger', 'Masukkan kode OTP.');

        const btn = document.getElementById('btnVerify');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'verify_otp');
        formData.append('otp', otp);

        try {
            const res = await fetch('proses_forgot.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                document.getElementById('alertBox').classList.add('d-none');
                // Tampilkan Modal Bootstrap
                const resetModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
                resetModal.show();
            } else {
                showAlert('alertBox', 'danger', data.message);
            }
        } catch (e) {
            showAlert('alertBox', 'danger', 'Terjadi kesalahan saat verifikasi.');
        } finally {
            btn.disabled = false;
        }
    }

    async function resetPassword() {
        const newPassword = document.getElementById('new_password').value;
        if (newPassword.length < 6) return showAlert('modalAlertBox', 'danger', 'Password minimal 6 karakter.');

        const btn = document.getElementById('btnReset');
        btn.disabled = true;
        btn.innerHTML = 'Menyimpan...';

        const formData = new FormData();
        formData.append('action', 'reset_password');
        formData.append('new_password', newPassword);

        try {
            const res = await fetch('proses_forgot.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                showAlert('modalAlertBox', 'success', data.message);
                setTimeout(() => {
                    window.location.href = '?page=login';
                }, 2000);
            } else {
                showAlert('modalAlertBox', 'danger', data.message);
                btn.disabled = false;
                btn.innerHTML = 'Simpan Password';
            }
        } catch (e) {
            showAlert('modalAlertBox', 'danger', 'Terjadi kesalahan saat menyimpan password.');
            btn.disabled = false;
            btn.innerHTML = 'Simpan Password';
        }
    }
</script>