<?php
$pengaturanStmt = $pdo->query("
    SELECT id, nama_bengkel, alamat, no_whatsapp, jam_buka, jam_tutup, jam_istirahat_mulai, jam_istirahat_selesai, hari_operasional
    FROM pengaturan
    ORDER BY id ASC
    LIMIT 1
");
$pengaturanBengkel = $pengaturanStmt->fetch();

if (!$pengaturanBengkel) {
    echo '<div class="alert alert-warning border-0">Data pengaturan belum tersedia.</div>';
    return;
}
?>

<style>
    .settings-card {
        border: 1px solid #dee2e6;
        border-radius: 14px;
        overflow: hidden;
        background: #ffffff;
    }

    .settings-header {
        background: linear-gradient(135deg, #0d6efd, #2563eb);
        padding: 24px 28px;
        color: #ffffff;
    }

    .settings-header h3 {
        font-size: 1.4rem;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .settings-header p {
        margin-bottom: 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .settings-body {
        padding: 28px;
        background: #ffffff;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }

    .form-control {
        border-radius: 10px;
        border: 1px solid #ced4da;
        min-height: 46px;
        padding: 10px 14px;
        box-shadow: none;
    }

    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.15);
    }

    textarea.form-control {
        min-height: 110px;
        resize: none;
    }

    .section-divider {
        margin: 28px 0 18px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #6c757d;
        letter-spacing: 0.08em;
    }

    .save-btn {
        border-radius: 10px;
        padding: 10px 18px;
        font-weight: 600;
    }

    .info-card {
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 14px;
        padding: 24px;
        height: 100%;
    }

    .info-card h5 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 20px;
        color: #212529;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f3f5;
        gap: 16px;
    }

    .info-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .info-label {
        font-size: 14px;
        color: #6c757d;
    }

    .info-value {
        font-size: 14px;
        font-weight: 600;
        color: #212529;
        text-align: right;
    }

    @media (max-width: 768px) {

        .settings-header,
        .settings-body,
        .info-card {
            padding: 20px;
        }
    }
</style>

<div class="card settings-card">
    <div class="settings-header">
        <h3>Pengaturan Bengkel</h3>
        <p>Kelola informasi operasional dan konfigurasi sistem.</p>
    </div>

    <div class="settings-body">
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <form method="post" action="index.php?page=pengaturan">
                    <input type="hidden" name="action" value="update_pengaturan">
                    <input type="hidden" name="id" value="<?= (int) $pengaturanBengkel['id'] ?>">

                    <div class="section-divider">Informasi Bengkel</div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Bengkel</label>
                            <input
                                type="text"
                                name="nama_bengkel"
                                class="form-control"
                                placeholder="Masukkan nama bengkel"
                                value="<?= htmlspecialchars($pengaturanBengkel['nama_bengkel']) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Alamat Bengkel</label>
                            <textarea
                                name="alamat"
                                class="form-control"
                                placeholder="Masukkan alamat lengkap bengkel"><?= htmlspecialchars($pengaturanBengkel['alamat']) ?></textarea>
                        </div>
                    </div>

                    <div class="section-divider">Kontak & Operasional</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nomor WhatsApp</label>
                            <input
                                type="text"
                                name="no_whatsapp"
                                class="form-control"
                                placeholder="08xxxxxxxxxx"
                                value="<?= htmlspecialchars($pengaturanBengkel['no_whatsapp']) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Jam Buka</label>
                            <input
                                type="time"
                                name="jam_buka"
                                class="form-control"
                                value="<?= htmlspecialchars(substr((string) $pengaturanBengkel['jam_buka'], 0, 5)) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Jam Tutup</label>
                            <input
                                type="time"
                                name="jam_tutup"
                                class="form-control"
                                value="<?= htmlspecialchars(substr((string) $pengaturanBengkel['jam_tutup'], 0, 5)) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Istirahat Mulai</label>
                            <input
                                type="time"
                                name="jam_istirahat_mulai"
                                class="form-control"
                                value="<?= htmlspecialchars(substr((string) ($pengaturanBengkel['jam_istirahat_mulai'] ?? ''), 0, 5)) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Istirahat Selesai</label>
                            <input
                                type="time"
                                name="jam_istirahat_selesai"
                                class="form-control"
                                value="<?= htmlspecialchars(substr((string) ($pengaturanBengkel['jam_istirahat_selesai'] ?? ''), 0, 5)) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Hari Operasional</label>
                            <input
                                type="text"
                                name="hari_operasional"
                                class="form-control"
                                placeholder="Senin - Sabtu"
                                value="<?= htmlspecialchars($pengaturanBengkel['hari_operasional']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Token API Fonnte</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="text" name="token_fonnte" class="form-control" value="<?= htmlspecialchars($pengaturan['token_fonnte'] ?? '') ?>" placeholder="Masukkan token fonnte di sini...">
                            </div>
                            <div class="form-text text-muted">Dapatkan token dari <a href="https://md.fonnte.com/device" target="_blank">dashboard Fonnte</a>.</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary save-btn">
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-12 col-xl-4">
                <div class="info-card">
                    <h5>Informasi Sistem</h5>

                    <div class="info-item">
                        <div class="info-label">Aplikasi</div>
                        <div class="info-value">Admin Akasia Motor</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Versi</div>
                        <div class="info-value">v1.0.0</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Backend</div>
                        <div class="info-value">PHP Native</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Frontend</div>
                        <div class="info-value">Bootstrap 5</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Database</div>
                        <div class="info-value">MySQL</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Server Time</div>
                        <div class="info-value"><?= date('d M Y H:i') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Environment</div>
                        <div class="info-value">Production</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
