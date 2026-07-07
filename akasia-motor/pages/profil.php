<?php
if (!isset($_SESSION['user_id'])) {
    exit;
}

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$user_id = (int) $_SESSION['user_id'];
$success = '';
$error = '';

// Ambil data pelanggan
$stmt_user = $pdo->prepare("SELECT nama, email, no_whatsapp, alamat FROM users WHERE id = :id LIMIT 1");
$stmt_user->execute([':id' => $user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $no_whatsapp = trim($_POST['no_whatsapp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($nama === '' || $no_whatsapp === '' || $alamat === '') {
        $error = 'Semua field data pelanggan wajib diisi.';
    } else {
        try {
            $sql_user = "UPDATE users SET nama = :nama, no_whatsapp = :wa, alamat = :alamat WHERE id = :id";
            $stmt_user_update = $pdo->prepare($sql_user);
            $stmt_user_update->execute([
                ':nama' => $nama,
                ':wa' => $no_whatsapp,
                ':alamat' => $alamat,
                ':id' => $user_id,
            ]);

            $_SESSION['nama'] = $nama;
            $success = 'Data profil berhasil diperbarui.';

            // Refresh data user setelah update
            $stmt_user->execute([':id' => $user_id]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan database: ' . $e->getMessage();
        }
    }
}

$showForm = $error !== '' || empty($user);
$editMode = $showForm ? 'on' : 'off';
?>

<style>
    .inline-edit-field {
        min-height: 38px;
    }

    .inline-edit-field[readonly] {
        background-color: #f8f9fa;
    }

    @media (min-width: 1200px) {
        .profile-wrap {
            max-width: 700px;
            /* Diperkecil karena formnya lebih sedikit */
            margin-left: auto;
            margin-right: auto;
        }
    }
</style>

<div class="mb-4">
    <h3 class="fw-bold text-primary"><i class="bi bi-person-badge"></i> Profil Saya</h3>
    <p class="text-muted">Kelola data diri Anda di sini.</p>
</div>

<form method="POST" action="">
    <div class="row g-4 justify-content-center profile-wrap">
        <div class="col-12 col-xl-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-3">
                        <div>
                            <h6 class="fw-bold text-secondary mb-1">Informasi Pribadi</h6>
                            <div class="small text-muted">Pastikan nomor WhatsApp Anda aktif.</div>
                        </div>
                        <button id="toggleEditBtn" class="btn btn-sm btn-outline-primary rounded-pill" type="button" data-edit-label="Edit Data" data-cancel-label="Batal" data-edit-mode="<?= e($editMode) ?>">
                            <i class="bi bi-pencil-square me-1"></i> Edit Data
                        </button>

                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success p-3 small mb-3"><i class="bi bi-check-circle me-1"></i> <?= e($success) ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger p-3 small mb-3"><i class="bi bi-exclamation-triangle me-1"></i> <?= e($error) ?></div>
                    <?php endif; ?>

                    <div id="accountSummary">
                        <div class="vstack gap-3 mb-4">
                            <div class="d-flex justify-content-between gap-3 border-bottom pb-2 align-items-start">
                                <span class="text-muted pt-1">Nama</span>
                                <span class="view-value fw-semibold text-dark text-end"><?= e($user['nama'] ?? '-') ?></span>
                                <input class="edit-value form-control form-control-sm inline-edit-field d-none text-end" type="text" name="nama" value="<?= e($_POST['nama'] ?? ($user['nama'] ?? '')) ?>" required>
                            </div>
                            <div class="d-flex justify-content-between gap-3 border-bottom pb-2 align-items-start">
                                <span class="text-muted pt-1">Email</span>
                                <span class="view-value fw-semibold text-dark text-end"><?= e($user['email'] ?? '-') ?></span>
                                <input class="edit-value form-control form-control-sm inline-edit-field d-none text-end" type="email" value="<?= e($user['email'] ?? '') ?>" readonly disabled>
                            </div>
                            <div class="d-flex justify-content-between gap-3 border-bottom pb-2 align-items-start">
                                <span class="text-muted pt-1">No. WhatsApp</span>
                                <span class="view-value fw-semibold text-dark text-end"><?= e($user['no_whatsapp'] ?? '-') ?></span>
                                <input class="edit-value form-control form-control-sm inline-edit-field d-none text-end" type="text" name="no_whatsapp" value="<?= e($_POST['no_whatsapp'] ?? ($user['no_whatsapp'] ?? '')) ?>" required>
                            </div>
                            <div class="d-flex justify-content-between gap-3 align-items-start">
                                <span class="text-muted pt-1">Alamat</span>
                                <span class="view-value fw-semibold text-dark text-end"><?= e($user['alamat'] ?? '-') ?></span>
                                <textarea class="edit-value form-control form-control-sm inline-edit-field d-none text-end" name="alamat" rows="2" required><?= e($_POST['alamat'] ?? ($user['alamat'] ?? '')) ?></textarea>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalUbahPassword">
                            <i class="bi bi-shield-lock"></i> Ganti Password
                        </button>
                        <div class="mt-4 pt-3 border-top d-none" id="saveBarWrap">
                            <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm py-2">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleEditBtn = document.getElementById('toggleEditBtn');
        const editFields = document.querySelectorAll('.edit-value');
        const viewValues = document.querySelectorAll('.view-value');
        const saveBarWrap = document.getElementById('saveBarWrap');

        function toggleInlineEdit(isEditing) {
            editFields.forEach(function(field) {
                field.classList.toggle('d-none', !isEditing);
            });
            viewValues.forEach(function(field) {
                field.classList.toggle('d-none', isEditing);
            });
            if (saveBarWrap) {
                saveBarWrap.classList.toggle('d-none', !isEditing);
            }
            if (toggleEditBtn) {
                toggleEditBtn.dataset.editMode = isEditing ? 'on' : 'off';
                toggleEditBtn.innerHTML = isEditing ?
                    '<i class="bi bi-x-circle me-1"></i> ' + (toggleEditBtn.dataset.cancelLabel || 'Batal') :
                    '<i class="bi bi-pencil-square me-1"></i> ' + (toggleEditBtn.dataset.editLabel || 'Edit Data');
            }
        }

        if (toggleEditBtn) {
            toggleEditBtn.addEventListener('click', function() {
                const isEditing = toggleEditBtn.dataset.editMode !== 'on';
                toggleInlineEdit(isEditing);
                if (!isEditing) {
                    if (toggleEditBtn.closest('form')) {
                        toggleEditBtn.closest('form').reset();
                    }
                }
            });
        }

        toggleInlineEdit(toggleEditBtn ? toggleEditBtn.dataset.editMode === 'on' : false);
    });
</script>
<div class="modal fade" id="modalUbahPassword" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-shield-lock me-2"></i>Ganti Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body p-4 pt-0">
                <div id="pwdAlertBox" class="alert d-none p-2 small" role="alert"></div>

                <form id="formUbahPassword" onsubmit="updatePassword(event)">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password Saat Ini</label>
                        <div class="input-group">
                            <input type="password" id="current_password" class="form-control" placeholder="Masukkan password lama" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password', 'icon_current')">
                                <i class="bi bi-eye" id="icon_current"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password Baru</label>
                        <div class="input-group">
                            <input type="password" id="new_password" class="form-control" placeholder="Minimal 6 karakter" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', 'icon_new')">
                                <i class="bi bi-eye" id="icon_new"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Konfirmasi Password Baru</label>
                        <div class="input-group">
                            <input type="password" id="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', 'icon_confirm')">
                                <i class="bi bi-eye" id="icon_confirm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btnUpdatePwd" class="btn btn-primary fw-bold">
                            Simpan Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk Show/Hide Password
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

    // Kosongkan form saat modal ditutup
    const modalUbahPwdElement = document.getElementById('modalUbahPassword');
    if (modalUbahPwdElement) {
        modalUbahPwdElement.addEventListener('hidden.bs.modal', function() {
            document.getElementById('formUbahPassword').reset();
            document.getElementById('pwdAlertBox').classList.add('d-none');

            // Kembalikan semua input ke tipe password (jika sebelumnya di-show)
            ['current_password', 'new_password', 'confirm_password'].forEach(id => {
                document.getElementById(id).type = "password";
            });
            ['icon_current', 'icon_new', 'icon_confirm'].forEach(id => {
                const icon = document.getElementById(id);
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            });
        });
    }

    async function updatePassword(event) {
        event.preventDefault();

        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const btn = document.getElementById('btnUpdatePwd');

        if (newPassword.length < 6) {
            showAlertBox("alert-danger", "Password baru minimal 6 karakter.");
            return;
        }

        if (newPassword !== confirmPassword) {
            showAlertBox("alert-danger", "Konfirmasi password tidak cocok.");
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

        const formData = new FormData();
        formData.append('current_password', currentPassword);
        formData.append('new_password', newPassword);
        formData.append('confirm_password', confirmPassword);

        try {
            const response = await fetch('proses_ubah_password.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                showAlertBox("alert-success", data.message);
                document.getElementById('formUbahPassword').reset();

                setTimeout(() => {
                    const modalInstance = bootstrap.Modal.getInstance(modalUbahPwdElement);
                    if (modalInstance) modalInstance.hide();
                }, 2000);
            } else {
                showAlertBox("alert-danger", data.message);
            }
        } catch (error) {
            showAlertBox("alert-danger", "Terjadi kegagalan sistem.");
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Simpan Password';
        }
    }

    function showAlertBox(typeClass, message) {
        const alertBox = document.getElementById('pwdAlertBox');
        alertBox.className = `alert ${typeClass} p-2 small`;
        alertBox.innerHTML = message;
        alertBox.classList.remove('d-none');
    }
</script>