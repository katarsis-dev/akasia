<?php
try {
    // Ambil setting bengkel
    $stmt_pengaturan = $pdo->query("SELECT nama_bengkel, alamat, no_whatsapp, jam_buka, jam_tutup, hari_operasional FROM pengaturan LIMIT 1");
    $pengaturan = $stmt_pengaturan->fetch();

    if (!$pengaturan) {
        $pengaturan = [
            'nama_bengkel' => 'Akasia Motor',
            'alamat' => '-',
            'no_whatsapp' => '-',
            'jam_buka' => '08:00',
            'jam_tutup' => '17:00',
            'hari_operasional' => 'Setiap Hari'
        ];
    }

    // Ambil daftar layanan
    try {
        $stmt_layanan = $pdo->query("SELECT id, nama, deskripsi, estimasi_durasi, estimasi_biaya_jasa AS harga FROM jenis_layanan ORDER BY id ASC");
        $layanan = $stmt_layanan->fetchAll();
    } catch (PDOException $e) {
        // Fallback jika tidak ada kolom harga
        $stmt_layanan = $pdo->query("SELECT id, nama, deskripsi, 0 as estimasi_durasi, 0 as harga FROM jenis_layanan ORDER BY id ASC");
        $layanan = $stmt_layanan->fetchAll();
    }

    $stmt_kegiatan = $pdo->query("SELECT jenis_layanan_id, nama_kegiatan FROM kegiatan_servis ORDER BY jenis_layanan_id ASC, id ASC");
    $kegiatanList = $stmt_kegiatan->fetchAll(PDO::FETCH_ASSOC);
    $kegiatanByLayanan = [];
    foreach ($kegiatanList as $kegiatan) {
        $kegiatanByLayanan[(int) $kegiatan['jenis_layanan_id']][] = $kegiatan['nama_kegiatan'];
    }

    // --- AMBIL DATA ANTREAN HARI INI ---
    // 1. Jumlah kendaraan aktif yang masih menunggu pengerjaan hari ini
    $stmt_menunggu = $pdo->query("
        SELECT COUNT(*)
        FROM reservasi
        WHERE DATE(tanggal_servis) = CURDATE()
          AND status IN ('menunggu_konfirmasi', 'dikonfirmasi', 'menunggu_antrean', 'pending')
    ");
    $jml_menunggu = (int) $stmt_menunggu->fetchColumn();

    // 2. Data antrean yang sedang diproses
    $stmt_diproses = $pdo->query("
        SELECT no_antrian, jenis_kendaraan, tipe_model
        FROM reservasi
        WHERE status = 'diproses'
        ORDER BY id DESC
        LIMIT 3
    ");
    $antrean_diproses = $stmt_diproses->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    $layanan = [];
    $jml_menunggu = 0;
    $antrean_diproses = [];
}
?>

<!-- Hero Section -->
<div class="row hero text-center align-items-center mb-4 shadow-sm pb-5">
    <div class="col-12 py-4">
        <div class="d-inline-flex align-items-center justify-content-center mb-3 px-4 py-3 rounded-4" style="background: linear-gradient(180deg, rgba(251, 252, 255, 0.98) 0%, rgba(233, 240, 251, 0.98) 100%); border: 1px solid rgba(13, 110, 253, 0.18); box-shadow: 0 10px 24px rgba(3, 24, 70, 0.14);">
            <img src="./assets/images/logo.png" alt="Akasia Motor" style="max-width: 190px; width: 60%; height: auto; object-fit: contain; filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.22));">
        </div>
        <h1 class="display-6 fw-bold mb-3 text-white"><?= htmlspecialchars($pengaturan['nama_bengkel']) ?></h1>
        <p class="lead mb-4 text-white-75">Solusi terbaik untuk perawatan dan perbaikan kendaraan Anda.</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="?page=login" class="btn btn-light btn-lg text-primary fw-bold px-4 rounded-pill shadow-sm">Booking Sekarang</a>
        <?php else: ?>
            <a href="?page=booking" class="btn btn-light btn-lg text-primary fw-bold px-4 rounded-pill shadow-sm">Booking Sekarang</a>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION LIVE ANTREAN -->
<div class="row justify-content-center" style="margin-top: 0; margin-bottom: 50px; position: relative; z-index: 10;">
    <div class="col-md-10 col-lg-8">
        <div class="card border-0 shadow rounded-4 bg-white">
            <div class="card-body p-4">
                <div class="text-center mb-3 border-bottom pb-3">
                    <h6 class="fw-bold text-primary mb-1"><i class="bi bi-broadcast"></i> Info Antrean Servis Hari Ini</h6>
                    <small class="text-muted"><?= date('d F Y') ?></small>
                </div>
                <div class="row text-center align-items-center mt-3">
                    <div class="col-6 border-end">
                        <h2 class="display-4 fw-bold text-warning mb-0"><?= $jml_menunggu ?></h2>
                        <p class="text-muted small fw-semibold mb-0 mt-1">Kendaraan Menunggu</p>
                        <div class="text-muted" style="font-size: 0.75rem;">Reservasi aktif hari ini</div>
                    </div>
                    <div class="col-6">
                        <?php if (empty($antrean_diproses)): ?>
                            <h2 class="display-4 fw-bold text-success mb-0">-</h2>
                        <?php else: ?>
                            <h2 class="fw-bold text-success mb-0" style="font-size: <?= count($antrean_diproses) > 1 ? '2rem' : 'calc(1.475rem + 2.7vw)' ?>;">
                                <?= htmlspecialchars($antrean_diproses[0]['no_antrian']) ?>
                            </h2>
                            <?php if (count($antrean_diproses) > 1): ?>
                                <div class="text-muted small mt-1">+<?= count($antrean_diproses) - 1 ?> antrean lainnya sedang diproses</div>
                            <?php endif; ?>
                            <div class="mt-2 small text-start px-3">
                                <?php foreach ($antrean_diproses as $antrean): ?>
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="text-muted text-truncate"><?= htmlspecialchars($antrean['jenis_kendaraan'] ?: $antrean['tipe_model'] ?: 'Kendaraan') ?></span>
                                        <span class="fw-semibold text-success"><?= htmlspecialchars($antrean['no_antrian']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <p class="text-muted small fw-semibold mb-0 mt-1">Sedang Diproses</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Lokasi, Jam, Kontak -->
<div class="row mb-5 text-center mt-4">
    <div class="col-md-4 mb-3">
        <div class="card h-100 p-3 bg-white shadow-sm">
            <i class="bi bi-geo-alt fs-1 text-danger mb-2"></i>
            <h5 class="fw-bold">Lokasi Kami</h5>
            <p class="text-muted small"><?= nl2br(htmlspecialchars($pengaturan['alamat'])) ?></p>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card h-100 p-3 bg-white shadow-sm">
            <i class="bi bi-clock fs-1 text-success mb-2"></i>
            <h5 class="fw-bold">Jam Operasional</h5>
            <p class="text-muted small">
                <?= htmlspecialchars($pengaturan['hari_operasional']) ?><br>
                <?= substr($pengaturan['jam_buka'], 0, 5) ?> - <?= substr($pengaturan['jam_tutup'], 0, 5) ?> WIB
            </p>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card h-100 p-3 bg-white shadow-sm">
            <i class="bi bi-whatsapp fs-1 text-success mb-2"></i>
            <h5 class="fw-bold">Hubungi Kami</h5>
            <p class="text-muted small"><?= htmlspecialchars($pengaturan['no_whatsapp']) ?></p>
        </div>
    </div>
</div>

<!-- Layanan -->
<h3 class="fw-bold text-center mb-4 border-bottom pb-2">Layanan Kami</h3>
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4">
    <?php foreach ($layanan as $l): ?>
        <div class="col">
            <div class="card h-100 shadow-sm border-0 border-top border-primary border-4 p-3 bg-white">
                <div class="card-body d-flex flex-column">
                    <?php $isServisUmum = stripos($l['nama'], 'Servis Umum') !== false; ?>
                    <h5 class="card-title fw-bold text-primary"><?= htmlspecialchars($l['nama']) ?></h5>
                    <p class="card-text text-muted small pb-3 mb-3 border-bottom"><?= htmlspecialchars($l['deskripsi']) ?></p>
                    <?php if (!empty($kegiatanByLayanan[(int) $l['id']])): ?>
                        <div class="mb-3">
                            <div class="small fw-semibold text-secondary mb-2">Kegiatan Servis:</div>
                            <ul class="small text-muted mb-0 ps-3">
                                <?php foreach (array_slice($kegiatanByLayanan[(int) $l['id']], 0, 4) as $namaKegiatan): ?>
                                    <li><?= htmlspecialchars($namaKegiatan) ?></li>
                                <?php endforeach; ?>
                                <?php if (count($kegiatanByLayanan[(int) $l['id']]) > 4): ?>
                                    <li>+<?= count($kegiatanByLayanan[(int) $l['id']]) - 4 ?> kegiatan lainnya</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if ($isServisUmum): ?>
                        <div class="mt-auto pt-2 border-top">
                            <div class="alert alert-light border small mb-0 py-2 px-3">
                                <i class="bi bi-list-check text-primary me-1"></i>
                                Pilih kegiatan sesuai kebutuhan, estimasi menyesuaikan checklist yang dicentang di halaman booking.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($l['estimasi_durasi']) || !empty($l['harga'])): ?>
                            <div class="d-flex justify-content-between gap-3 mt-auto pt-2 border-top">
                                <div>
                                    <div class="small fw-semibold text-secondary">Estimasi Waktu</div>
                                    <div class="fw-bold text-dark"><?= (int) $l['estimasi_durasi'] ?> Menit</div>
                                </div>
                                <div class="text-end">
                                    <div class="small fw-semibold text-secondary">Estimasi Biaya</div>
                                    <div class="fw-bold text-success">Rp <?= number_format((float) ($l['harga'] ?? 0), 0, ',', '.') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>