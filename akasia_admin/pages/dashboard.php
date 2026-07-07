<?php
$stages = getReservasiStages();
$counts = [];

// Menghitung jumlah per status
foreach (array_keys($stages) as $stageKey) {
    if ($stageKey === 'selesai') {
        // Khusus selesai, hitung yang hari ini saja sesuai mockup
        $countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM reservasi WHERE status = :status AND DATE(waktu_selesai) = CURDATE()");
    } else {
        $countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM reservasi WHERE status = :status");
    }
    $countStmt->execute([':status' => $stageKey]);
    $counts[$stageKey] = (int) ($countStmt->fetch()['total'] ?? 0);
}

// 1. Antrean Dipanggil Saat Ini (mengambil 1 data teratas yang sedang diproses)
$antreanStmt = $pdo->query("
    SELECT r.id, r.no_antrian, u.nama AS pelanggan, jl.nama AS layanan, m.nama AS mekanik
    FROM reservasi r
    INNER JOIN users u ON u.id = r.user_id
    INNER JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
    LEFT JOIN mekanik m ON m.id = r.mekanik_id
    WHERE r.status = 'diproses'
    ORDER BY r.waktu_mulai_servis DESC
    LIMIT 1
");
$antreanSekarang = $antreanStmt->fetch();

// 2. Reservasi Baru (menunggu konfirmasi)
$menungguKonfirmasiStmt = $pdo->query("
    SELECT r.id, r.no_antrian, u.nama AS pelanggan, jl.nama AS layanan, r.created_at
    FROM reservasi r
    INNER JOIN users u ON u.id = r.user_id
    INNER JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
    WHERE r.status = 'menunggu_konfirmasi'
    ORDER BY r.created_at DESC
    LIMIT 5
");
$menungguKonfirmasiList = $menungguKonfirmasiStmt->fetchAll();

// 3. Mekanik Tersedia
$mekanikTersediaStmt = $pdo->query("
    SELECT nama, status
    FROM mekanik
    WHERE status IN ('tersedia', 'sibuk')
    ORDER BY FIELD(status, 'tersedia', 'sibuk', 'libur'), nama ASC
");
$mekanikTersediaList = $mekanikTersediaStmt->fetchAll();

// 4. Aktivitas Terbaru
$aktivitasStmt = $pdo->query("
    SELECT 
        r.id, r.no_antrian, r.tanggal_servis, r.jenis_kendaraan, r.tipe_model, r.no_plat, 
        r.status, r.alasan_status, r.updated_at, jl.nama AS layanan, u.nama AS pelanggan
    FROM reservasi r
    INNER JOIN users u ON u.id = r.user_id
    INNER JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
    ORDER BY r.updated_at DESC
    LIMIT 5
");
$aktivitasList = $aktivitasStmt->fetchAll();
$todayCapacity = getCapacityInfo($pdo, date('Y-m-d'));
$todayCapacityUsed = (int) ($todayCapacity['used'] ?? 0);
$todayCapacityTotal = (int) ($todayCapacity['capacity'] ?? 0);
$todayCapacityRemaining = (int) ($todayCapacity['remaining'] ?? 0);
$todayCapacityPercent = $todayCapacityTotal > 0 ? min(100, (int) round(($todayCapacityUsed / $todayCapacityTotal) * 100)) : 0;

?>

<div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
    <div class="col">
        <a href="index.php?page=status_servis&stage=menunggu_konfirmasi" class="card text-decoration-none h-100 shadow-sm" style="border: 1px solid var(--border); border-left: 4px solid #f59e0b !important; background-color: #f4f8fb; border-radius: 12px;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-start gap-3 mb-3">
                    <div class="rounded-3 d-flex justify-content-center align-items-center flex-shrink-0" style="width: 48px; height: 48px; background-color: #fef3c7; color: #f59e0b;">
                        <i class="bi bi-calendar-plus fs-4"></i>
                    </div>
                    <div>
                        <div class="text-dark fw-bold" style="font-size: 0.95rem;">Reservasi Baru</div>
                        <div class="fw-bolder text-dark" style="font-size: 1.8rem; line-height: 1.2;"><?= $counts['menunggu_konfirmasi'] ?? 0 ?></div>
                    </div>
                </div>
                <div class="text-muted small mb-2">Kendaraan</div>
                <div><span class="badge rounded-pill px-3 py-1" style="background-color: #ffc107; color: #000; font-weight: 600;">pending_konfirmasi</span></div>
            </div>
        </a>
    </div>

    <div class="col">
        <a href="index.php?page=status_servis&stage=menunggu_antrean" class="card text-decoration-none h-100 shadow-sm" style="border: 1px solid var(--border); border-left: 4px solid #3b82f6 !important; background-color: #f4f8fb; border-radius: 12px;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-start gap-3 mb-3">
                    <div class="rounded-3 d-flex justify-content-center align-items-center flex-shrink-0" style="width: 48px; height: 48px; background-color: #dbeafe; color: #3b82f6;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                    <div>
                        <div class="text-dark fw-bold" style="font-size: 0.95rem;">Menunggu Antrean</div>
                        <div class="fw-bolder text-dark" style="font-size: 1.8rem; line-height: 1.2;"><?= $counts['menunggu_antrean'] ?? 0 ?></div>
                    </div>
                </div>
                <div class="text-muted small mb-2">Kendaraan</div>
                <div><span class="badge bg-primary rounded-pill px-3 py-1" style="font-weight: 600;">menunggu</span></div>
            </div>
        </a>
    </div>

    <div class="col">
        <a href="index.php?page=status_servis&stage=diproses" class="card text-decoration-none h-100 shadow-sm" style="border: 1px solid var(--border); border-left: 4px solid #06b6d4 !important; background-color: #f4f8fb; border-radius: 12px;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-start gap-3 mb-3">
                    <div class="rounded-3 d-flex justify-content-center align-items-center flex-shrink-0" style="width: 48px; height: 48px; background-color: #cffafe; color: #06b6d4;">
                        <i class="bi bi-wrench-adjustable fs-4"></i>
                    </div>
                    <div>
                        <div class="text-dark fw-bold" style="font-size: 0.95rem;">Sedang Diproses</div>
                        <div class="fw-bolder text-dark" style="font-size: 1.8rem; line-height: 1.2;"><?= $counts['diproses'] ?? 0 ?></div>
                    </div>
                </div>
                <div class="text-muted small mb-2">Kendaraan</div>
                <div><span class="badge rounded-pill px-3 py-1" style="background-color: #0dcaf0; color: #fff; font-weight: 600;">diproses</span></div>
            </div>
        </a>
    </div>

    <div class="col">
        <a href="index.php?page=status_servis&stage=pending" class="card text-decoration-none h-100 shadow-sm" style="border: 1px solid var(--border); border-left: 4px solid #e11d48 !important; background-color: #f4f8fb; border-radius: 12px;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-start gap-3 mb-3">
                    <div class="rounded-3 d-flex justify-content-center align-items-center flex-shrink-0" style="width: 48px; height: 48px; background-color: #ffe4e6; color: #e11d48;">
                        <i class="bi bi-clock fs-4"></i>
                    </div>
                    <div>
                        <div class="text-dark fw-bold" style="font-size: 0.95rem;">Pending</div>
                        <div class="fw-bolder text-dark" style="font-size: 1.8rem; line-height: 1.2;"><?= $counts['pending'] ?? 0 ?></div>
                    </div>
                </div>
                <div class="text-muted small mb-2">Kendaraan</div>
                <div><span class="badge bg-danger rounded-pill px-3 py-1" style="font-weight: 600;">pending</span></div>
            </div>
        </a>
    </div>

    <div class="col">
        <a href="index.php?page=status_servis&stage=selesai" class="card text-decoration-none h-100 shadow-sm" style="border: 1px solid var(--border); border-left: 4px solid #10b981 !important; background-color: #f4f8fb; border-radius: 12px;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-start gap-3 mb-3">
                    <div class="rounded-3 d-flex justify-content-center align-items-center flex-shrink-0" style="width: 48px; height: 48px; background-color: #d1fae5; color: #10b981;">
                        <i class="bi bi-check2-circle fs-4"></i>
                    </div>
                    <div>
                        <div class="text-dark fw-bold" style="font-size: 0.95rem;">Selesai Hari Ini</div>
                        <div class="fw-bolder text-dark" style="font-size: 1.8rem; line-height: 1.2;"><?= $counts['selesai'] ?? 0 ?></div>
                    </div>
                </div>
                <div class="text-muted small mb-2">Servis</div>
                <div><span class="badge bg-success rounded-pill px-3 py-1" style="font-weight: 600;">selesai</span></div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0" style="border-radius: 16px; background: linear-gradient(135deg, #ffffff 0%, #f4f8fb 100%);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-bar-chart-line-fill text-primary fs-5"></i>
                            <h5 class="mb-0 fw-bold">Kapasitas Antrean Hari Ini</h5>
                        </div>
                        <p class="text-muted mb-0">Ringkasan kapasitas antrean aktif berdasarkan estimasi durasi servis pada tanggal <?= htmlspecialchars(formatDateIndonesia(date('Y-m-d'))) ?>.</p>
                    </div>
                    <a href="index.php?page=status_servis&stage=menunggu_konfirmasi" class="btn btn-outline-primary rounded-pill px-3">
                        Lihat Antrean
                    </a>
                </div>

                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-white rounded-4 border h-100">
                            <div class="text-muted small fw-semibold mb-1">Total Kapasitas</div>
                            <div class="fw-bolder fs-3 text-dark"><?= (int) $todayCapacityTotal ?> Menit</div>
                            <div class="text-muted small">Jam operasional efektif hari ini</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-white rounded-4 border h-100">
                            <div class="text-muted small fw-semibold mb-1">Terpakai</div>
                            <div class="fw-bolder fs-3 text-primary"><?= (int) $todayCapacityUsed ?> Menit</div>
                            <div class="text-muted small">Reservasi aktif pada hari ini</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-white rounded-4 border h-100">
                            <div class="text-muted small fw-semibold mb-1">Sisa Kapasitas</div>
                            <div class="fw-bolder fs-3 <?= $todayCapacityRemaining > 0 ? 'text-success' : 'text-danger' ?>"><?= (int) $todayCapacityRemaining ?> Menit</div>
                            <div class="text-muted small"><?= $todayCapacityRemaining > 0 ? 'Masih bisa menerima reservasi' : 'Hari ini sudah penuh' ?></div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">Keterisian kapasitas</span>
                        <span class="fw-semibold <?= $todayCapacityPercent >= 100 ? 'text-danger' : 'text-primary' ?>"><?= (int) $todayCapacityPercent ?>%</span>
                    </div>
                    <div class="progress" style="height: 12px; border-radius: 999px;">
                        <div class="progress-bar <?= $todayCapacityPercent >= 100 ? 'bg-danger' : 'bg-primary' ?>" role="progressbar" style="width: <?= (int) $todayCapacityPercent ?>%;" aria-valuenow="<?= (int) $todayCapacityPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-event fs-5 text-warning"></i>
                        <h5 class="mb-0 fw-bold">Reservasi Baru</h5>
                    </div>
                    <a href="index.php?page=status_servis&stage=menunggu_konfirmasi" class="btn btn-sm btn-light border fw-semibold">
                        Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <?php if (empty($menungguKonfirmasiList)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-card-checklist display-4"></i>
                        <p class="mt-3">Belum ada reservasi baru.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Antrean</th>
                                    <th>Tanggal Servis</th>
                                    <th>Kendaraan</th>
                                    <th>Layanan</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aktivitasList as $aktivitas): ?>
                                    <?php
                                    $badgeClass = getStatusBadgeClass($aktivitas['status']);
                                    $statusLabel = getStageMeta($aktivitas['status'])['label'];
                                    $alasanLabel = '';

                                    // Logika Status Dialihkan (Karena di index.php dialihkan = dikonfirmasi + alasan)
                                    if ($aktivitas['status'] === 'dikonfirmasi' && !empty($aktivitas['alasan_status'])) {
                                        $statusLabel = 'Dialihkan';
                                        $badgeClass = 'text-bg-info text-white';
                                        $alasanLabel = 'Alasan: ' . htmlspecialchars($aktivitas['alasan_status']);
                                    }
                                    // Logika Status Dibatalkan
                                    elseif ($aktivitas['status'] === 'dibatalkan' && !empty($aktivitas['alasan_status'])) {
                                        $alasanLabel = 'Alasan: ' . htmlspecialchars($aktivitas['alasan_status']);
                                    }
                                    ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?= htmlspecialchars($aktivitas['no_antrian']) ?></td>
                                        <td><?= formatDateIndonesia($aktivitas['tanggal_servis']) ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark">
                                                <?= htmlspecialchars($aktivitas['jenis_kendaraan'] ?: '-') ?> - <?= htmlspecialchars($aktivitas['tipe_model'] ?: '-') ?>
                                            </div>
                                            <small class="text-muted" style="letter-spacing: 1px;"><?= htmlspecialchars($aktivitas['no_plat']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($aktivitas['layanan']) ?></td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-1"><?= htmlspecialchars($statusLabel) ?></span>
                                            <?php if ($alasanLabel !== ''): ?>
                                                <small class="d-block text-muted mt-1" style="font-size: 11px; max-width: 180px; line-height: 1.2;">
                                                    <?= $alasanLabel ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="index.php?page=detail_reservasi&id=<?= (int)$aktivitas['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($aktivitasList)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada aktivitas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-people fs-5 text-purple" style="color: #6f42c1;"></i>
                    <h5 class="mb-0 fw-bold">Mekanik Tersedia</h5>
                </div>

                <div class="table-responsive bg-light bg-opacity-50 rounded-3">
                    <table class="table table-borderless align-middle mb-0">
                        <thead>
                            <tr class="border-bottom">
                                <th class="text-muted fw-semibold">Mekanik</th>
                                <th class="text-muted fw-semibold text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mekanikTersediaList as $mekanik): ?>
                                <tr class="border-bottom">
                                    <td>
                                        <div class="d-flex align-items-center gap-3 py-1">
                                            <div class="avatar-circle flex-shrink-0" style="width: 35px; height: 35px; font-size: 14px;">
                                                <?= strtoupper(substr($mekanik['nama'], 0, 1)) ?>
                                            </div>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($mekanik['nama']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge rounded-pill px-3 py-2 <?= $mekanik['status'] === 'tersedia' ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' ?>">
                                            <i class="bi bi-circle-fill" style="font-size: 0.5rem; vertical-align: middle; margin-right: 4px;"></i>
                                            <?= ucfirst($mekanik['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($mekanikTersediaList)): ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">Tidak ada data mekanik.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-activity fs-5" style="color: #6f42c1;"></i>
                        <h5 class="mb-0 fw-bold">Aktivitas Terbaru</h5>
                    </div>
                </div>

                <div class="list-group list-group-flush">
                    <?php foreach ($aktivitasList as $aktivitas): ?>
                        <?php
                        // Tentukan icon dan teks berdasarkan status
                        $icon = 'bi-circle';
                        $iconColor = 'text-secondary';
                        $actionText = 'diperbarui';

                        switch ($aktivitas['status']) {
                            case 'selesai':
                                $icon = 'bi-check-circle';
                                $iconColor = 'text-success';
                                $actionText = 'selesai servis';
                                break;
                            case 'diproses':
                                $icon = 'bi-wrench';
                                $iconColor = 'text-info';
                                $actionText = 'sedang diproses';
                                break;
                            case 'menunggu_antrean':
                                $icon = 'bi-megaphone';
                                $iconColor = 'text-warning';
                                $actionText = 'dipanggil ke antrean';
                                break;
                            case 'menunggu_konfirmasi':
                                $icon = 'bi-calendar';
                                $iconColor = 'text-warning';
                                $actionText = 'reservasi baru';
                                break;
                        }
                        ?>
                        <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi <?= $icon ?> fs-5 <?= $iconColor ?>"></i>
                                <div>
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($aktivitas['no_antrian']) ?></span> -
                                    <span class="text-secondary"><?= htmlspecialchars($aktivitas['pelanggan']) ?> <?= $actionText ?></span>
                                </div>
                            </div>
                            <div class="text-muted small">
                                <?= formatDateTimeIndonesia($aktivitas['updated_at']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($aktivitasList)): ?>
                        <div class="text-center py-4 text-muted">Belum ada aktivitas.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Script ini akan berjalan diam-diam di background setiap kali halaman Dashboard dibuka
    fetch('cron_reminder_jadwal.php?token=akasia-reminder-2026')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Reminder berhasil diproses:', data);
            } else {
                console.log('Status Reminder:', data.reason);
            }
        })
        .catch(error => console.error('Error memproses reminder:', error));
</script>