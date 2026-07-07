<?php
$id = (int) ($_GET['id'] ?? 0);
$detail = findReservasi($pdo, $id);

if (!$detail) {
    echo '<div class="alert alert-warning shadow-sm">Data reservasi tidak ditemukan.</div>';
    return;
}

$sparepartStmt = $pdo->prepare("SELECT nama_item, qty, harga, subtotal FROM reservasi_sparepart WHERE reservasi_id = :reservasi_id ORDER BY id ASC");
$sparepartStmt->execute([':reservasi_id' => $id]);
$spareparts = $sparepartStmt->fetchAll();

// --- LOGIKA KEGIATAN SERVIS (DIJAMIN MUNCUL DI SEMUA STATUS) ---

// 1. Cek apakah ini paket custom (Servis Umum)
$layananId = (int) ($detail['jenis_layanan_id'] ?? 0);

$checkCustomStmt = $pdo->prepare("SELECT is_custom FROM jenis_layanan WHERE id = :id");
$checkCustomStmt->execute([':id' => $layananId]);
$isCustom = (bool) ($detail['is_custom'] ?? false);

$kegiatanList = [];

if ($isCustom) {
    // 1. Jika Servis Umum (Custom), wajib ambil dari tabel relasi reservasi_kegiatan
    $kegiatanStmt = $pdo->prepare("
        SELECT ks.nama_kegiatan, ks.estimasi_durasi, ks.estimasi_biaya
        FROM reservasi_kegiatan rk
        INNER JOIN kegiatan_servis ks ON rk.kegiatan_servis_id = ks.id
        WHERE rk.reservasi_id = :reservasi_id
        ORDER BY ks.id ASC
    ");
    $kegiatanStmt->execute([':reservasi_id' => $id]);
    $kegiatanList = $kegiatanStmt->fetchAll();
}
if (empty($kegiatanList)) {
    $masterStmt = $pdo->prepare("
        SELECT nama_kegiatan, estimasi_durasi, estimasi_biaya
        FROM kegiatan_servis
        WHERE jenis_layanan_id = :jenis_layanan_id
        ORDER BY id ASC
    ");
    $masterStmt->execute([':jenis_layanan_id' => $layananId]);
    $kegiatanList = $masterStmt->fetchAll();
}
// ---------------------------------------------------------------
?>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="section-title mb-3 fw-bold">Informasi Reservasi</div>
                <div class="detail-list">
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Nomor Antrean</span>
                        <strong><?= htmlspecialchars($detail['no_antrian']) ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Status</span>
                        <strong><span class="badge <?= getStatusBadgeClass($detail['status']) ?>"><?= htmlspecialchars(getStageMeta($detail['status'])['label']) ?></span></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Jenis Reservasi</span>
                        <strong><?= htmlspecialchars($detail['jenis_reservasi']) ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Tanggal Servis</span>
                        <strong><?= htmlspecialchars(formatDateIndonesia($detail['tanggal_servis'])) ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Kehadiran</span>
                        <strong><?= htmlspecialchars($detail['kehadiran']) ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Mekanik</span>
                        <strong><?= htmlspecialchars($detail['mekanik_nama'] ?: '-') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="section-title mb-3 fw-bold">Data Pelanggan</div>
                <div class="detail-list">
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Nama</span>
                        <strong><?= htmlspecialchars($detail['pelanggan']) ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">No. WhatsApp</span>
                        <strong><?= htmlspecialchars(displayPhone($detail['no_whatsapp'])) ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Alamat</span>
                        <strong><?= htmlspecialchars($detail['alamat'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Kendaraan</span>
                        <strong><?= htmlspecialchars(($detail['tipe_model'] ?: '-') . ' / ' . $detail['no_plat']) ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Jenis Kendaraan</span>
                        <strong><?= htmlspecialchars($detail['jenis_kendaraan'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Tahun</span>
                        <strong><?= htmlspecialchars((string) ($detail['tahun'] ?: '-')) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="section-title mb-3 fw-bold">Data Kendaraan Aktif</div>
                <div class="detail-list">
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Merk</span>
                        <strong><?= htmlspecialchars($detail['kendaraan_merk'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Jenis</span>
                        <strong><?= htmlspecialchars($detail['kendaraan_jenis'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Model</span>
                        <strong><?= htmlspecialchars($detail['kendaraan_model'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Tahun</span>
                        <strong><?= htmlspecialchars((string) ($detail['kendaraan_tahun'] ?: '-')) ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">No. Plat</span>
                        <strong><?= htmlspecialchars($detail['kendaraan_no_plat'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">No. Rangka</span>
                        <strong><?= htmlspecialchars($detail['kendaraan_no_rangka'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">No. Mesin</span>
                        <strong><?= htmlspecialchars($detail['kendaraan_no_mesin'] ?: '-') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="section-title mb-3 fw-bold">Informasi Servis</div>
                <div class="detail-list">
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Jenis Servis</span>
                        <strong><?= htmlspecialchars($detail['layanan']) ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Keluhan</span>
                        <strong><?= htmlspecialchars($detail['keluhan'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Hasil Servis</span>
                        <strong><?= htmlspecialchars($detail['hasil_servis'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Biaya Jasa</span>
                        <strong>Rp <?= number_format((float) $detail['biaya_jasa'], 0, ',', '.') ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Total Biaya</span>
                        <strong class="text-primary">Rp <?= number_format((float) $detail['total_biaya'], 0, ',', '.') ?></strong>
                    </div>
                    <div class="detail-row d-flex justify-content-between mb-2">
                        <span class="text-secondary">Catatan Tambahan</span>
                        <strong><?= htmlspecialchars($detail['catatan_tambahan'] ?: '-') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4 border-0 shadow-sm">
    <div class="card-body">
        <div class="section-title mb-3 fw-bold">Detail Kegiatan Servis</div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th>Nama Kegiatan</th>
                        <th style="width: 25%;">Estimasi Durasi</th>
                        <th style="width: 25%;">Estimasi Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kegiatanList as $index => $keg): ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($keg['nama_kegiatan']) ?></td>
                            <td><?= (int) $keg['estimasi_durasi'] ?> Menit</td>
                            <td>Rp <?= number_format((float) $keg['estimasi_biaya'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($kegiatanList)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">
                                <i class="bi bi-info-circle fs-4 d-block mb-2"></i>
                                Belum ada data kegiatan servis untuk paket ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4 border-0 shadow-sm mb-5">
    <div class="card-body">
        <div class="section-title mb-3 fw-bold">Sparepart Manual</div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th>Nama Item</th>
                        <th style="width: 15%;">Qty</th>
                        <th style="width: 20%;">Harga</th>
                        <th style="width: 20%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($spareparts as $index => $item): ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($item['nama_item']) ?></td>
                            <td><?= (int) $item['qty'] ?></td>
                            <td>Rp <?= number_format((float) $item['harga'], 0, ',', '.') ?></td>
                            <td class="fw-bold">Rp <?= number_format((float) $item['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$spareparts): ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                <i class="bi bi-box-seam fs-4 d-block mb-2"></i>
                                Belum ada item sparepart.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>