<?php
// Hubungkan koneksi database
require_once __DIR__ . '/../config/database.php';

// Fungsi format tanggal Indonesia
if (!function_exists('formatDateIndonesia')) {
    function formatDateIndonesia($date)
    {
        if (!$date) return '-';
        $months = [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];
        $timestamp = strtotime($date);
        $d = date('j', $timestamp);
        $m = (int)date('n', $timestamp);
        $y = date('Y', $timestamp);
        return "$d {$months[$m]} $y";
    }
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<div class='alert alert-danger border-0 shadow-sm'>ID Reservasi tidak valid.</div>";
    exit;
}

// 1. Query Utama (Informasi Akasia Motor Reservasi, Pelanggan, & Kendaraan)
$sqlReservasi = "
    SELECT 
        r.*, 
        u.nama AS pelanggan_nama, 
        u.email AS pelanggan_email, 
        u.no_whatsapp AS pelanggan_wa, 
        u.alamat AS pelanggan_alamat,
        jl.nama AS nama_layanan,
        jl.is_custom,
        m.nama AS mekanik_nama
    FROM reservasi r
    INNER JOIN users u ON r.user_id = u.id
    INNER JOIN jenis_layanan jl ON r.jenis_layanan_id = jl.id
    LEFT JOIN mekanik m ON r.mekanik_id = m.id
    WHERE r.id = ?
";
$stmt = $pdo->prepare($sqlReservasi);
$stmt->execute([$id]);
$reservasi = $stmt->fetch();

if (!$reservasi) {
    echo "<div class='alert alert-warning text-center border-0 shadow-sm py-3'>Data ringkasan reservasi tidak ditemukan.</div>";
    exit;
}

// 2. Query kegiatan servis sesuai jenis layanan
$isCustom = (bool) ($reservasi['is_custom'] ?? false);

if ($isCustom) {
    $sqlKegiatan = "
        SELECT ks.nama_kegiatan, ks.estimasi_biaya
        FROM reservasi_kegiatan rk
        INNER JOIN kegiatan_servis ks ON rk.kegiatan_servis_id = ks.id
        WHERE rk.reservasi_id = :reservasi_id
        ORDER BY ks.id ASC
    ";
    $stmtKegiatan = $pdo->prepare($sqlKegiatan);
    $stmtKegiatan->execute([
        ':reservasi_id' => $id
    ]);
} else {
    $sqlKegiatan = "
        SELECT ks.nama_kegiatan
        FROM kegiatan_servis ks
        WHERE ks.jenis_layanan_id = :jenis_layanan_id
          AND ks.is_active = 1
        ORDER BY ks.id ASC
    ";
    $stmtKegiatan = $pdo->prepare($sqlKegiatan);
    $stmtKegiatan->execute([
        ':jenis_layanan_id' => $reservasi['jenis_layanan_id']
    ]);
}

$kegiatanList = $stmtKegiatan->fetchAll();

// 3. Query Suku Cadang / Sparepart Manual
$sqlSparepart = "
    SELECT nama_item, qty, harga, subtotal
    FROM reservasi_sparepart
    WHERE reservasi_id = ?
";
$stmtSparepart = $pdo->prepare($sqlSparepart);
$stmtSparepart->execute([$id]);
$sparepartList = $stmtSparepart->fetchAll();
?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="p-3 bg-light border rounded-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <span class="text-muted small d-block">Nomor Antrean</span>
                <span class="fs-4 fw-bold text-primary"><?= htmlspecialchars($reservasi['no_antrian']) ?></span>
            </div>
            <div>
                <span class="text-muted small d-block text-md-end">Status Pengerjaan</span>
                <?php $badgeColor = $reservasi['status'] === 'selesai' ? 'bg-success' : 'bg-warning text-dark'; ?>
                <span class="badge <?= $badgeColor ?> px-3 py-2 rounded-2 fw-semibold">
                    <i class="bi <?= $reservasi['status'] === 'selesai' ? 'bi-check-circle-fill' : 'bi-tools' ?> me-1"></i>
                    <?= strtoupper(htmlspecialchars($reservasi['status'])) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border border-light-subtle h-100 shadow-sm" style="border-radius: 10px;">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-info-circle text-primary me-2"></i>Informasi Reservasi</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0 align-middle" style="font-size: 13.5px;">
                    <tr>
                        <td class="text-muted py-2" style="width: 35%;">Tanggal Servis</td>
                        <td class="fw-semibold text-dark py-2">: <?= htmlspecialchars(formatDateIndonesia($reservasi['tanggal_servis'])) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">Jenis Registrasi</td>
                        <td class="py-2">: <span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($reservasi['jenis_reservasi']) ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">Paket Layanan</td>
                        <td class="fw-bold text-primary py-2">: <?= htmlspecialchars($reservasi['nama_layanan']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">Mekanik</td>
                        <td class="fw-semibold text-success py-2">: <?= htmlspecialchars($reservasi['mekanik_nama'] ?? 'Belum Ditugaskan') ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border border-light-subtle h-100 shadow-sm" style="border-radius: 10px;">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person-vcard text-primary me-2"></i>Pelanggan & Kendaraan</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0 align-middle" style="font-size: 13.5px;">
                    <tr>
                        <td class="text-muted py-1" style="width: 35%;">Nama</td>
                        <td class="fw-semibold text-dark py-1">: <?= htmlspecialchars($reservasi['pelanggan_nama']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">WhatsApp</td>
                        <td class="py-1">: <?= htmlspecialchars($reservasi['pelanggan_wa'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Plat Nomor</td>
                        <td class="fw-bold text-danger py-1">: <?= htmlspecialchars($reservasi['no_plat']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Model Motor</td>
                        <td class="fw-semibold py-1">: <?= htmlspecialchars($reservasi['tipe_model'] ?: '-') ?> (<?= htmlspecialchars($reservasi['warna'] ?: '-') ?>)</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="p-3 bg-light rounded-3 border">
            <div class="row g-2" style="font-size: 13px;">
                <div class="col-md-4">
                    <span class="text-muted fw-semibold d-block mb-1"><i class="bi bi-exclamation-circle me-1"></i> Keluhan Pelanggan:</span>
                    <span class="text-danger fw-medium"><?= nl2br(htmlspecialchars($reservasi['keluhan'] ?: '-')) ?></span>
                </div>
                <div class="col-md-4">
                    <span class="text-muted fw-semibold d-block mb-1"><i class="bi bi-journal-text me-1"></i> Catatan Mekanik:</span>
                    <span class="text-secondary italic"><i><?= nl2br(htmlspecialchars($reservasi['catatan_mekanik'] ?: '-')) ?></i></span>
                </div>
                <div class="col-md-4">
                    <span class="text-muted fw-semibold d-block mb-1"><i class="bi bi-check-all me-1"></i> Hasil Diagnosa Akhir:</span>
                    <span class="text-success fw-medium"><?= htmlspecialchars($reservasi['hasil_servis'] ?: 'Belum diisi') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border border-light-subtle shadow-sm" style="border-radius: 10px;">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clipboard2-check text-primary me-2"></i>Detail Kegiatan & Checklist Servis</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle" style="font-size: 13px;">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th style="width: 60px;" class="ps-3">No</th>
                                <th>Nama Aktivitas / Kegiatan Tindakan</th>
                                <?php if ($isCustom): ?>
                                    <th class="text-end pe-3" style="width: 160px;">Estimasi Jasa</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $noK = 1;
                            foreach ($kegiatanList as $keg): ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $noK++ ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($keg['nama_kegiatan']) ?></td>
                                    <?php if ($isCustom): ?>
                                        <td class="text-end pe-3 text-secondary">Rp <?= number_format((float) ($keg['estimasi_biaya'] ?? 0), 0, ',', '.') ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$kegiatanList): ?>
                                <tr>
                                    <td colspan="<?= $isCustom ? 3 : 2 ?>" class="text-center text-muted py-3">Tidak ada aktivitas checklist yang terikat pada reservasi ini.</td>
                                <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border border-light-subtle shadow-sm" style="border-radius: 10px;">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam text-primary me-2"></i>Penggunaan Suku Cadang / Sparepart</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle" style="font-size: 13px;">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th style="width: 60px;" class="ps-3">No</th>
                                <th>Nama Suku Cadang / Sparepart</th>
                                <th class="text-center" style="width: 100px;">QTY</th>
                                <th class="text-end" style="width: 150px;">Harga Satuan</th>
                                <th class="text-end pe-3" style="width: 160px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $noS = 1;
                            foreach ($sparepartList as $sp): ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $noS++ ?></td>
                                    <td class="fw-medium text-dark"><?= htmlspecialchars($sp['nama_item']) ?></td>
                                    <td class="text-center fw-semibold text-secondary"><?= (int)$sp['qty'] ?></td>
                                    <td class="text-end text-secondary">Rp <?= number_format($sp['harga'], 0, ',', '.') ?></td>
                                    <td class="text-end pe-3 fw-bold text-dark">Rp <?= number_format($sp['subtotal'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$sparepartList): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Tidak ada penggantian sparepart pada pengerjaan ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mt-4">
        <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3 text-end d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="text-start">
                <span class="text-muted small d-block">Biaya Jasa Dasar</span>
                <span class="fw-semibold text-dark" style="font-size: 14px;">Rp <?= number_format($reservasi['biaya_jasa'], 0, ',', '.') ?></span>
            </div>
            <div>
                <span class="text-muted small d-block">Total Akhir Pembayaran</span>
                <span class="fs-4 fw-bold text-primary">Rp <?= number_format($reservasi['total_biaya'], 0, ',', '.') ?></span>
            </div>
        </div>
    </div>

</div>
