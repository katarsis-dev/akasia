<?php
if (!isset($_SESSION['user_id'])) {
    exit;
}

date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$noAntrianExpr = "CAST(SUBSTRING(no_antrian, 2) AS UNSIGNED)";

if (!function_exists('formatWaitDuration')) {
    function formatWaitDuration(int $minutes): string
    {
        if ($minutes <= 0) {
            return 'Sekarang';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . ' jam';
        }
        if ($remaining > 0) {
            $parts[] = $remaining . ' menit';
        }

        return 'Sekitar ' . implode(' ', $parts);
    }
}

// 1. Cari antrean aktif pelanggan
$stmt_my_queue = $pdo->prepare("
    SELECT r.id, r.no_antrian, r.status, r.tanggal_servis, jl.nama AS layanan 
    FROM reservasi r
    INNER JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
    WHERE r.user_id = :uid 
      AND r.status NOT IN ('dibatalkan', 'selesai') 
    ORDER BY CASE r.status
        WHEN 'diproses' THEN 0
        WHEN 'menunggu_antrean' THEN 1
        WHEN 'dikonfirmasi' THEN 2
        WHEN 'menunggu_konfirmasi' THEN 3
        WHEN 'pending' THEN 4
        ELSE 5
    END, r.tanggal_servis ASC, r.id ASC
    LIMIT 1
");
$stmt_my_queue->execute([':uid' => $user_id]);
$my_queue = $stmt_my_queue->fetch();

$current_processing_antrian = null;
$current_processing_tanggal = null;
$estimasi_jam_servis = null;
$queueAheadCount = null;
$queueWaitMinutes = null;

// 2. Cari antrean yang sedang diproses
$stmt_current = $pdo->prepare("
    SELECT no_antrian, tanggal_servis, waktu_mulai_servis, estimasi_durasi
    FROM reservasi
    WHERE status = 'diproses'
    ORDER BY CASE WHEN tanggal_servis = :today THEN 0 ELSE 1 END, tanggal_servis ASC, id ASC
    LIMIT 1
");
$stmt_current->execute([':today' => $today]);
$current_processing = $stmt_current->fetch();

if ($current_processing) {
    $current_processing_antrian = $current_processing['no_antrian'];
    $current_processing_tanggal = $current_processing['tanggal_servis'] ?? null;
}

// --- LOGIKA MENGHITUNG ESTIMASI JAM SERVIS ---
if ($my_queue && in_array($my_queue['status'], ['dikonfirmasi', 'menunggu_antrean'])) {
    // Pastikan perhitungan antrean harian hanya jalan jika antreannya memang HARI INI
    if ($my_queue['tanggal_servis'] == $today) {
        $no_antrian_anda = $my_queue['no_antrian'];

        if ($current_processing && $current_processing_tanggal === $today) {
            $waktu_mulai_servis = $current_processing['waktu_mulai_servis'];
            $estimasi_durasi = (int)$current_processing['estimasi_durasi'];

            if (!empty($waktu_mulai_servis)) {
                $menit_berjalan = floor((time() - strtotime($waktu_mulai_servis)) / 60);
                $sisa = max(0, $estimasi_durasi - $menit_berjalan);
            } else {
                $sisa = $estimasi_durasi;
            }

            $no_proses_num = (int) preg_replace('/\D+/', '', (string) $current_processing_antrian);
            $no_ku_num = (int) preg_replace('/\D+/', '', (string) $no_antrian_anda);

            $stmt_sebelum = $pdo->prepare("
    SELECT SUM(COALESCE(NULLIF(r.estimasi_durasi, 0), jl.estimasi_durasi, 0)) AS total
    FROM reservasi r
    INNER JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
    WHERE r.tanggal_servis = :today
      AND {$noAntrianExpr} > :no_proses_num
      AND {$noAntrianExpr} < :no_ku_num
      AND r.status IN ('menunggu_antrean')
");
            $stmt_sebelum->execute([
                ':today' => $today,
                ':no_proses_num' => $no_proses_num,
                ':no_ku_num' => $no_ku_num
            ]);

            $row_sebelum = $stmt_sebelum->fetch();
            $durasi_sebelum = $row_sebelum['total'] ? (int)$row_sebelum['total'] : 0;

            $total_menunggu = $sisa + $durasi_sebelum;

            $estimasi = new DateTime();
            $estimasi->modify("+{$total_menunggu} minutes");
            $estimasi_jam_servis = "± " . $estimasi->format('H.i') . " WIB";
        }
    } else {
        // Jika antrean untuk besok/lusa
        $estimasi_jam_servis = "Sesuai jam operasional bengkel (Tanggal: " . date('d-m-Y', strtotime($my_queue['tanggal_servis'])) . ")";
    }
} elseif ($my_queue && $my_queue['status'] === 'diproses') {
    $estimasi_jam_servis = 'Sekarang (Sedang Diproses)';
}

if ($my_queue && in_array($my_queue['status'], ['dikonfirmasi', 'menunggu_antrean'], true) && $my_queue['tanggal_servis'] == $today) {
    $queueWaitMinutes = 0;

    if ($current_processing && $current_processing_tanggal === $today) {
        $waktu_mulai_servis = $current_processing['waktu_mulai_servis'];
        $estimasi_durasi = (int) $current_processing['estimasi_durasi'];

        if (!empty($waktu_mulai_servis)) {
            $menit_berjalan = floor((time() - strtotime($waktu_mulai_servis)) / 60);
            $sisa = max(0, $estimasi_durasi - $menit_berjalan);
        } else {
            $sisa = $estimasi_durasi;
        }

        $no_proses_num = (int) preg_replace('/\D+/', '', (string) $current_processing_antrian);
        $no_ku_num = (int) preg_replace('/\D+/', '', (string) $my_queue['no_antrian']);

        $stmt_sebelum = $pdo->prepare("
            SELECT SUM(COALESCE(NULLIF(r.estimasi_durasi, 0), jl.estimasi_durasi, 0)) AS total
            FROM reservasi r
            INNER JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
            WHERE r.tanggal_servis = :today
              AND {$noAntrianExpr} > :no_proses_num
              AND {$noAntrianExpr} < :no_ku_num
              AND r.status IN ('menunggu_antrean')
        ");
        $stmt_sebelum->execute([
            ':today' => $today,
            ':no_proses_num' => $no_proses_num,
            ':no_ku_num' => $no_ku_num
        ]);

        $row_sebelum = $stmt_sebelum->fetch();
        $durasi_sebelum = $row_sebelum['total'] ? (int) $row_sebelum['total'] : 0;

        $queueWaitMinutes = $sisa + $durasi_sebelum;
    }

    $stmt_ahead = $pdo->prepare("
        SELECT COUNT(*)
        FROM reservasi
        WHERE tanggal_servis = :today
          AND {$noAntrianExpr} < :no_ku_num
          AND status NOT IN ('dibatalkan', 'selesai')
    ");
    $stmt_ahead->execute([
        ':today' => $today,
        ':no_ku_num' => (int) preg_replace('/\D+/', '', (string) $my_queue['no_antrian'])
    ]);
    $queueAheadCount = max(0, (int) $stmt_ahead->fetchColumn());
}

// 3. Ambil riwayat reservasi terbaru
$stmt_history = $pdo->prepare("
    SELECT r.*, jl.nama AS layanan 
    FROM reservasi r
    INNER JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
    WHERE r.user_id = :uid 
    ORDER BY r.id DESC 
    LIMIT 5
");
$stmt_history->execute([':uid' => $user_id]);
$reservasi_history = $stmt_history->fetchAll();

$reservasiIds = [];
if ($my_queue && !empty($my_queue['id'])) {
    $reservasiIds[] = (int) $my_queue['id'];
}
foreach ($reservasi_history as $row) {
    if (!empty($row['id'])) {
        $reservasiIds[] = (int) $row['id'];
    }
}
$reservasiIds = array_values(array_unique(array_filter($reservasiIds)));

$kegiatanReservasiMap = [];
if (!empty($reservasiIds)) {
    $placeholders = implode(',', array_fill(0, count($reservasiIds), '?'));
    $stmt_kegiatan_reservasi = $pdo->prepare("
        SELECT rk.reservasi_id, ks.nama_kegiatan
        FROM reservasi_kegiatan rk
        INNER JOIN kegiatan_servis ks ON ks.id = rk.kegiatan_servis_id
        WHERE rk.reservasi_id IN ($placeholders)
        ORDER BY ks.jenis_layanan_id ASC, ks.id ASC
    ");
    $stmt_kegiatan_reservasi->execute($reservasiIds);
    while ($row = $stmt_kegiatan_reservasi->fetch(PDO::FETCH_ASSOC)) {
        $kegiatanReservasiMap[(int) $row['reservasi_id']][] = $row['nama_kegiatan'];
    }
}
?>

<div class="mb-4">
    <h3 class="fw-bold text-primary"><i class="bi bi-speedometer2"></i> Monitoring Status Servis</h3>
    <p class="text-muted">Selamat datang kembali, <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Pelanggan') ?></strong>. Pantau status antrean Anda di bawah ini.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 text-white bg-primary" style="border-radius: 12px;">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-white-50 fw-semibold mb-0">Nomor Antrean Aktif Anda</h6>
                        <i class="bi bi-person-badge fs-4"></i>
                    </div>
                    <?php if ($my_queue): ?>
                        <h1 class="display-3 fw-bold mb-2"><?= htmlspecialchars($my_queue['no_antrian']) ?></h1>
                        <p class="mb-0 small text-white-50">Jenis Layanan: <strong><?= htmlspecialchars($my_queue['layanan']) ?></strong></p>
                        <?php if (!empty($kegiatanReservasiMap[(int) $my_queue['id']])): ?>
                            <div class="mt-3 p-3 bg-white bg-opacity-10 rounded-3 border border-white border-opacity-25">
                                <span class="text-white-50 small d-block mb-1">Kegiatan Servis:</span>
                                <ul class="mb-0 ps-3 small text-white">
                                    <?php foreach (array_slice($kegiatanReservasiMap[(int) $my_queue['id']], 0, 4) as $kegiatanNama): ?>
                                        <li><?= htmlspecialchars($kegiatanNama) ?></li>
                                    <?php endforeach; ?>
                                    <?php if (count($kegiatanReservasiMap[(int) $my_queue['id']]) > 4): ?>
                                        <li>+<?= count($kegiatanReservasiMap[(int) $my_queue['id']]) - 4 ?> kegiatan lainnya</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($estimasi_jam_servis && in_array($my_queue['status'], ['dikonfirmasi', 'menunggu_antrean'])): ?>
                            <div class="mt-3 p-3 bg-white bg-opacity-10 rounded-3 border border-white border-opacity-25">
                                <span class="text-white-50 small d-block mb-1">Estimasi Jam Servis:</span>
                                <div class="fw-bold fs-5 text-white mb-2">
                                    <i class="bi bi-clock-history me-2"></i><?= $estimasi_jam_servis ?>
                                </div>
                                <?php if ($my_queue['tanggal_servis'] == $today): ?>
                                    <div style="font-size: 0.7rem;" class="text-white-50 lh-sm">
                                        *Estimasi dapat berubah mengikuti kondisi antrean dan proses pengerjaan servis.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($estimasi_jam_servis === 'Sekarang (Sedang Diproses)'): ?>
                            <div class="mt-3 p-3 bg-white bg-opacity-10 rounded-3 border border-white border-opacity-25 text-center">
                                <span class="fw-bold fs-5 text-white"><i class="bi bi-gear-wide-connected me-2"></i>Kendaraan Anda Sedang Dikerjakan</span>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <h2 class="fw-bold mb-2">Tidak Ada</h2>
                        <p class="mb-0 small text-white-50">Anda tidak memiliki jadwal atau antrean aktif hari ini.</p>
                    <?php endif; ?>
                </div>

                <?php if ($my_queue): ?>
                    <div class="mt-4 pt-3 border-top border-white-10">
                        <?php
                        $status_label = match ($my_queue['status']) {
                            'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                            'dikonfirmasi' => 'Dikonfirmasi (Silakan ke Bengkel)',
                            'menunggu_antrean' => 'Menunggu Antrean',
                            'diproses' => 'Sedang Dikerjakan Mekanik',
                            'pending' => 'Pending (Tertunda)',
                            default => htmlspecialchars($my_queue['status'])
                        };
                        ?>
                        <div class="d-flex align-items-center">
                            <span class="text-white-50 small me-2">Status Reservasi:</span>
                            <span class="badge bg-white text-primary fw-bold px-3 py-2 rounded-pill shadow-sm">
                                <?= $status_label ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-4 pt-3 border-top border-white-10">
                        <a href="?page=booking" class="btn btn-light btn-sm fw-bold text-primary rounded-pill px-3 shadow-sm">
                            <i class="bi bi-calendar-plus me-1"></i> Booking Sekarang
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 bg-white" style="border-radius: 12px; border-left: 5px solid #ffc107 !important;">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted fw-semibold mb-0">Antrean Sedang Diproses</h6>
                        <i class="bi bi-gear-wide-connected text-warning fs-4"></i>
                    </div>
                    <?php if ($current_processing_antrian): ?>
                        <h1 class="display-3 fw-bold text-warning mb-2"><?= htmlspecialchars($current_processing_antrian) ?></h1>
                        <p class="mb-0 small text-muted">
                            Mekanik saat ini sedang mengerjakan nomor antrean di atas.
                            <?php if ($current_processing_tanggal): ?>
                                <br><span class="fw-semibold">Tanggal servis:</span> <?= htmlspecialchars(date('d-m-Y', strtotime($current_processing_tanggal))) ?>
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <h2 class="fw-bold text-secondary mb-2">-</h2>
                        <p class="mb-0 small text-muted">Belum ada antrean kendaraan yang sedang diproses di bilik servis saat ini.</p>
                    <?php endif; ?>
                </div>
                <div class="mt-4 pt-3 border-top">
                    <button onclick="window.location.reload();" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh Status
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <h6 class="fw-bold text-secondary mb-1"><i class="bi bi-bar-chart-line me-1 text-primary"></i> Informasi Antrean Realtime</h6>
                <p class="text-muted mb-0">Ringkasan kondisi antrean pelanggan saat ini berdasarkan data reservasi aktif.</p>
            </div>
            <?php if ($my_queue): ?>
                <span class="badge text-bg-primary rounded-pill px-3 py-2">Aktif</span>
            <?php else: ?>
                <span class="badge text-bg-secondary rounded-pill px-3 py-2">Tidak Ada Antrean</span>
            <?php endif; ?>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <div class="p-3 border rounded-4 bg-white h-100">
                    <div class="text-muted small fw-semibold mb-1">Nomor Antrean Pelanggan</div>
                    <div class="fw-bolder fs-2 text-primary">
                        <?= $my_queue ? htmlspecialchars($my_queue['no_antrian']) : '-' ?>
                    </div>
                    <div class="text-muted small">
                        <?= $my_queue ? htmlspecialchars($my_queue['layanan']) : 'Belum ada antrean aktif' ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="p-3 border rounded-4 bg-white h-100">
                    <div class="text-muted small fw-semibold mb-1">Nomor Sedang Diproses</div>
                    <div class="fw-bolder fs-2 text-warning">
                        <?= $current_processing_antrian ? htmlspecialchars($current_processing_antrian) : '-' ?>
                    </div>
                    <div class="text-muted small">
                        <?= $current_processing_tanggal ? 'Tanggal servis: ' . htmlspecialchars(date('d-m-Y', strtotime($current_processing_tanggal))) : 'Nomor yang sedang dikerjakan mekanik saat ini' ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="p-3 border rounded-4 bg-white h-100">
                    <div class="text-muted small fw-semibold mb-1">Estimasi Giliran Pelanggan</div>
                    <div class="fw-bolder fs-3 <?= $queueWaitMinutes !== null ? 'text-success' : 'text-secondary' ?>">
                        <?= $queueWaitMinutes !== null ? htmlspecialchars(formatWaitDuration($queueWaitMinutes)) : '-' ?>
                    </div>
                    <div class="text-muted small">
                        <?php if ($queueAheadCount !== null): ?>
                            Ada <?= (int) $queueAheadCount ?> antrean di depan Anda
                        <?php else: ?>
                            Menunggu antrean aktif berikutnya
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-body p-4">
        <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-clock-history me-1"></i> Riwayat Servis</h6>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Antrean</th>
                        <th>Tanggal Servis</th>
                        <th>Kendaraan</th>
                        <th>Layanan</th>
                        <th>Kegiatan Servis</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservasi_history as $res): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= htmlspecialchars($res['no_antrian']) ?></td>
                            <td><?= date('d-m-Y', strtotime($res['tanggal_servis'])) ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($res['jenis_kendaraan']) ?></div>
                                <div class="text-secondary small"><?= htmlspecialchars($res['no_plat']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($res['layanan']) ?></td>
                            <td class="small text-muted">
                                <?php if (!empty($kegiatanReservasiMap[(int) $res['id']])): ?>
                                    <?= htmlspecialchars(implode(', ', array_slice($kegiatanReservasiMap[(int) $res['id']], 0, 3))) ?>
                                    <?php if (count($kegiatanReservasiMap[(int) $res['id']]) > 3): ?>
                                        <div class="text-secondary">+<?= count($kegiatanReservasiMap[(int) $res['id']]) - 3 ?> lainnya</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php
                                $badge_color = match ($res['status']) {
                                    'menunggu_konfirmasi' => 'bg-secondary',
                                    'dikonfirmasi' => 'bg-info text-white',
                                    'menunggu_antrean' => 'bg-primary',
                                    'diproses' => 'bg-warning text-dark',
                                    'selesai' => 'bg-success',
                                    'dibatalkan' => 'bg-danger',
                                    'pending' => 'bg-dark',
                                    default => 'bg-light text-dark'
                                };
                                ?>
                                <span class="badge <?= $badge_color ?> text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $res['status'])) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$reservasi_history): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat pendaftaran data reservasi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>