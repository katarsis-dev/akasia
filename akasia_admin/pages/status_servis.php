<?php
$search = trim($_GET['q'] ?? '');
// DEFAULT STAGE KOSONG = TAMPILKAN SEMUA
$stage = trim($_GET['stage'] ?? '');

$stages = getReservasiStages();
if ($stage !== '' && !isset($stages[$stage])) {
    $stage = '';
}

$filterDate = trim($_GET['date'] ?? '');
$filterLayanan = (int) ($_GET['layanan'] ?? 0);
$showWalkinForm = ($_GET['form'] ?? '') === 'walkin';

$statusCounts = [];
$countQuery = $pdo->query("SELECT status, COUNT(*) as total FROM reservasi GROUP BY status");
while ($row = $countQuery->fetch()) {
    $statusCounts[$row['status']] = (int) $row['total'];
}

$pageSize = 10;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));

// LOGIKA WHERE DINAMIS
$whereSql = "WHERE 1=1";
$params = [];

if ($stage !== '') {
    $whereSql .= " AND r.status = :status";
    $params[':status'] = $stage;
}
if ($filterDate !== '') {
    $whereSql .= " AND r.tanggal_servis = :filter_date";
    $params[':filter_date'] = $filterDate;
}
if ($filterLayanan > 0) {
    $whereSql .= " AND r.jenis_layanan_id = :filter_layanan";
    $params[':filter_layanan'] = $filterLayanan;
}
if ($search !== '') {
    $whereSql .= " AND (u.nama LIKE :search OR r.no_antrian LIKE :search OR r.no_plat LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$countStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM reservasi r
    INNER JOIN users u ON u.id = r.user_id
    $whereSql
");
$countStmt->execute($params);
$totalRows = (int) ($countStmt->fetch()['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalRows / $pageSize));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $pageSize;

// ==== MENGAMBIL DATA RESERVASI ====
$stmt = $pdo->prepare(
    "
    SELECT 
        r.*,
        u.nama AS pelanggan,
        u.no_whatsapp,
        jl.nama AS layanan,
        jl.estimasi_biaya_jasa AS biaya_jasa_default,
        m.nama AS mekanik_nama
FROM reservasi r
    INNER JOIN users u ON u.id = r.user_id
    INNER JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
    LEFT JOIN mekanik m ON m.id = r.mekanik_id
    $whereSql
    ORDER BY 
        FIELD(r.status, 'menunggu_konfirmasi', 'dikonfirmasi', 'menunggu_antrean', 'diproses', 'pending', 'selesai', 'dibatalkan'),
        COALESCE(r.waktu_pending, r.waktu_mulai_servis, r.waktu_hadir, r.waktu_konfirmasi, r.created_at) ASC, 
        r.id ASC
    LIMIT " . (int) $pageSize . " OFFSET " . (int) $offset
);
$stmt->execute($params);
$reservasiList = $stmt->fetchAll();

// Data master untuk modal & form
$mekanikStmt = $pdo->query("SELECT id, nama, status FROM mekanik WHERE LOWER(TRIM(status)) = 'tersedia' ORDER BY nama ASC");
$mekanikList = $mekanikStmt->fetchAll();
$merkMotorStmt = $pdo->query("SELECT id_merk, nama_merk FROM merk_motor ORDER BY nama_merk ASC");
$merkMotorList = $merkMotorStmt->fetchAll(PDO::FETCH_ASSOC);
$kategoriMotorStmt = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori_motor ORDER BY id_kategori ASC");
$kategoriMotorList = $kategoriMotorStmt->fetchAll(PDO::FETCH_ASSOC);
$modelMotorStmt = $pdo->query("SELECT id_model, id_merk, id_kategori, nama_model FROM model_motor ORDER BY nama_model ASC");
$modelMotorList = $modelMotorStmt->fetchAll(PDO::FETCH_ASSOC);
$jenisLayananStmt = $pdo->query("SELECT id, nama, deskripsi, estimasi_durasi, estimasi_biaya_jasa, is_custom FROM jenis_layanan WHERE is_active = 1 ORDER BY id ASC");
$jenisLayananList = $jenisLayananStmt->fetchAll(PDO::FETCH_ASSOC);
$kegiatanStmt = $pdo->query("SELECT id, nama_kegiatan, jenis_layanan_id, estimasi_durasi, estimasi_biaya FROM kegiatan_servis WHERE is_active = 1 ORDER BY jenis_layanan_id ASC, nama_kegiatan ASC");
$kegiatanServisList = $kegiatanStmt->fetchAll(PDO::FETCH_ASSOC);

$daftarEstimasiAntrean = [];
if (function_exists('hitungEstimasiGiliranDinamis')) {
    $daftarEstimasiAntrean = hitungEstimasiGiliranDinamis($pdo);
}

$redirectToCurrent = buildPageUrl([
    'page' => 'status_servis',
    'stage' => $stage !== '' ? $stage : null,
    'q' => $search !== '' ? $search : null,
    'date' => $filterDate !== '' ? $filterDate : null,
    'layanan' => $filterLayanan > 0 ? $filterLayanan : null,
    'p' => $currentPage > 1 ? $currentPage : null,
]);
?>

<div class="card border-0 bg-transparent shadow-none">
    <div class="card-body p-0">

        <!-- HEADER & FILTER -->
        <div class="card mb-4 shadow-sm border-0" style="border-radius: 16px; background-color: #ffffff;">
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-4">

                    <!-- TAB FILTER STATUS -->
                    <div class="d-flex flex-wrap gap-2">
                        <a href="index.php?page=status_servis" class="stage-chip <?= $stage === '' ? 'active' : '' ?>">
                            <i class="bi bi-collection-fill"></i> Semua Reservasi
                        </a>
                        <?php foreach ($stages as $stageKey => $meta): ?>
                            <?php $count = $statusCounts[$stageKey] ?? 0; ?>
                            <a href="index.php?page=status_servis&stage=<?= urlencode($stageKey) ?>" class="stage-chip <?= $stage === $stageKey ? 'active' : '' ?>">
                                <i class="bi <?= htmlspecialchars($meta['icon']) ?>"></i>
                                <?= htmlspecialchars($meta['label']) ?>
                                <span class="ms-1" style="font-weight: 800; opacity: 0.75;">(<?= $count ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
                        <form method="get" action="index.php" class="d-flex align-items-end flex-wrap gap-3" style="flex: 1;">
                            <input type="hidden" name="page" value="status_servis">
                            <?php if ($stage !== ''): ?><input type="hidden" name="stage" value="<?= htmlspecialchars($stage) ?>"><?php endif; ?>

                            <div>
                                <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px;">Pencarian</label>
                                <div class="input-group" style="width: 250px;">
                                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control border-start-0 ps-0" placeholder="Cari pelanggan / antrean...">
                                </div>
                            </div>

                            <div>
                                <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px;">Tanggal Servis</label>
                                <div class="input-group" style="width: 170px;">
                                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-calendar"></i></span>
                                    <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>" class="form-control border-start-0 ps-0 text-muted">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary px-4" style="height: 38px;">
                                <i class="bi bi-funnel-fill me-1"></i> Filter
                            </button>

                            <?php if ($filterDate !== '' || $filterLayanan > 0 || $search !== ''): ?>
                                <a href="index.php?page=status_servis&stage=<?= urlencode($stage) ?>" class="btn btn-light border px-3" style="height: 38px;">Reset</a>
                            <?php endif; ?>
                        </form>


                    </div>
                </div>
            </div>
        </div>


        <!-- TAMPILAN DATA (FULL WIDTH) -->
        <!-- TAMPILAN DATA (FULL WIDTH) -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-body">
                        <div class="section-title">
                            <?= $stage === '' ? 'Semua Data Reservasi' : htmlspecialchars($stages[$stage]['label'] ?? 'Data Reservasi') ?>
                        </div>
                        <?php if (empty($reservasiList)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="mt-3 text-muted">Belum ada data pada status/filter ini.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No. Antrean</th>
                                            <th>Tanggal Servis</th>
                                            <th>Pelanggan</th>
                                            <th>Kendaraan</th>
                                            <?php if ($stage === '' || in_array($stage, ['diproses', 'selesai'], true)): ?>
                                                <th>Mekanik</th>
                                            <?php endif; ?>
                                            <th>Jenis Servis</th>
                                            <th>Status</th>
                                            <th class="text-end" style="min-width: 180px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $modalsHtml = ''; // Variabel untuk mengumpulkan semua modal
                                        foreach ($reservasiList as $item):
                                            $itemStage = (string) ($item['status'] ?? '');
                                        ?>
                                            <tr>
                                                <td class="fw-bold"><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['no_antrian']) ?></span></td>
                                                <td><?= htmlspecialchars(formatDateIndonesia($item['tanggal_servis'])) ?></td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($item['pelanggan']) ?></div>
                                                    <div class="text-secondary small"><?= htmlspecialchars(displayPhone($item['no_whatsapp'])) ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-warning"><?= htmlspecialchars($item['no_plat']) ?></div>
                                                    <div class="text-secondary small"><?= htmlspecialchars($item['tipe_model'] ?: '-') ?></div>
                                                </td>
                                                <?php if ($stage === '' || in_array($stage, ['diproses', 'selesai'], true)): ?>
                                                    <td class="fw-semibold text-success"><?= htmlspecialchars($item['mekanik_nama'] ?: '-') ?></td>
                                                <?php endif; ?>
                                                <td>
                                                    <?= htmlspecialchars($item['layanan']) ?>
                                                    <?php if ($itemStage === 'menunggu_antrean' && isset($daftarEstimasiAntrean[$item['id']])): ?>
                                                        <br><small class="text-danger fw-semibold">
                                                            <i class="bi bi-clock-history"></i> Giliran: ± <?= $daftarEstimasiAntrean[$item['id']] ?> Menit
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= getStatusBadgeClass($item['status']) ?> px-2 py-1">
                                                        <?= htmlspecialchars(getStageMeta($item['status'])['label']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <!-- TOMBOL DETAIL AJAX -->
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-detail-reservasi" data-id="<?= (int) $item['id'] ?>">
                                                        <i class="bi bi-eye"></i> Detail
                                                    </button>

                                                    <!-- Modal Triggers -->
                                                    <?php if ($itemStage === 'menunggu_konfirmasi'): ?>
                                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#confirmModal<?= (int) $item['id'] ?>">Konfirmasi</button>
                                                        <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#alihModal<?= (int) $item['id'] ?>">Alihkan</button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelNewModal<?= (int) $item['id'] ?>">Batal</button>
                                                    <?php endif; ?>
                                                    <?php if ($itemStage === 'dikonfirmasi'): ?>
                                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#hadirModal<?= (int) $item['id'] ?>">Hadir</button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#pendingModal<?= (int) $item['id'] ?>">Pending</button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#batalModal<?= (int) $item['id'] ?>">Batal</button>
                                                    <?php endif; ?>
                                                    <?php if ($itemStage === 'menunggu_antrean'): ?>
                                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#prosesModal<?= (int) $item['id'] ?>">Proses Servis</button>
                                                    <?php endif; ?>
                                                    <?php if ($itemStage === 'diproses'): ?>
                                                        <button type="button" class="btn btn-sm btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#selesaiModal<?= (int) $item['id'] ?>">Selesaikan</button>
                                                    <?php endif; ?>
                                                    <?php if ($itemStage === 'pending'): ?>
                                                        <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#callbackModal<?= (int) $item['id'] ?>"><i class="bi bi-whatsapp"></i> Callback</button>
                                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#hadirPendingModal<?= (int) $item['id'] ?>">Konfirmasi Kehadiran</button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#batalPendingModal<?= (int) $item['id'] ?>">Batal</button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <?php
                                            // ========================================================
                                            // MULAI MENYIMPAN KODE MODAL KE VARIABEL (AGAR DI LUAR TABEL)
                                            // ========================================================
                                            ob_start();
                                            ?>

                                            <?php if ($itemStage === 'diproses'): ?>
                                                <!-- Modal Selesaikan Servis -->
                                                <div class="modal fade" id="selesaiModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content" style="border-radius: 14px;">
                                                            <div class="modal-header bg-light border-0">
                                                                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-check2-circle text-warning me-2"></i> Penyelesaian Servis</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body p-4 text-start">
                                                                <form method="post" action="index.php?page=status_servis&stage=diproses">
                                                                    <input type="hidden" name="action" value="complete_service">
                                                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                                                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectToCurrent) ?>">

                                                                    <div class="row g-3">
                                                                        <div class="col-12">
                                                                            <label class="form-label text-muted fw-semibold">Hasil Diagnosa / Tindakan Servis</label>
                                                                            <textarea name="hasil_servis" class="form-control" rows="3" placeholder="Jelaskan apa saja yang telah diperbaiki..." required></textarea>
                                                                        </div>
                                                                        <?php
                                                                        $isServisUmum = stripos($item['layanan'], 'Umum') !== false;
                                                                        $defaultBiaya = (float) $item['biaya_jasa'];
                                                                        ?>
                                                                        <div class="col-md-5 mt-3">
                                                                            <label class="form-label text-muted fw-semibold">Biaya Jasa Dasar</label>
                                                                            <div class="input-group">
                                                                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                                                                <input type="number" name="biaya_jasa" class="form-control border-start-0 js-biaya-jasa <?= $isServisUmum ? 'bg-white' : 'bg-light' ?>" min="0" value="<?= $defaultBiaya ?>" <?= $isServisUmum ? '' : 'readonly' ?>>
                                                                            </div>
                                                                            <?php if (!$isServisUmum): ?>
                                                                                <small class="text-danger mt-1 d-block" style="font-size: 11.5px;">*Biaya diambil dari Paket Servis (Terkunci)</small>
                                                                            <?php else: ?>
                                                                                <small class="text-primary mt-1 d-block" style="font-size: 11.5px;">*Servis Umum, biaya jasa dapat diubah manual</small>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="col-12 mt-4">
                                                                            <label class="form-label fw-bold text-dark">Sparepart Tambahan / Suku Cadang</label>
                                                                            <div class="table-responsive border rounded-3">
                                                                                <table class="table table-borderless table-sm sparepart-table mb-0 align-middle" data-sparepart-table>
                                                                                    <thead class="table-light text-secondary" style="font-size: 13px;">
                                                                                        <tr>
                                                                                            <th class="ps-3">Nama Item</th>
                                                                                            <th style="width: 100px;">Qty</th>
                                                                                            <th style="width: 180px;">Harga Satuan</th>
                                                                                            <th style="width: 200px;">Subtotal</th>
                                                                                            <th style="width: 80px;" class="pe-3"></th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td class="ps-3 py-2"><input type="text" name="sparepart_nama[]" class="form-control form-control-sm" placeholder="Oli Mesin, Busi, dll..."></td>
                                                                                            <td class="py-2"><input type="number" name="sparepart_qty[]" class="form-control form-control-sm js-sparepart-qty" value="1" min="1"></td>
                                                                                            <td class="py-2"><input type="number" name="sparepart_harga[]" class="form-control form-control-sm js-sparepart-harga" value="0" min="0"></td>
                                                                                            <td class="py-2">
                                                                                                <div class="input-group input-group-sm">
                                                                                                    <span class="input-group-text bg-light border-end-0">Rp</span>
                                                                                                    <input type="text" class="form-control js-sparepart-subtotal bg-light border-start-0" value="0" readonly>
                                                                                                </div>
                                                                                            </td>
                                                                                            <td class="pe-3 py-2"><button type="button" class="btn btn-outline-danger btn-sm js-remove-item"><i class="bi bi-trash"></i></button></td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                            <button type="button" class="btn btn-light border btn-sm mt-2 js-add-sparepart text-primary fw-semibold"><i class="bi bi-plus-lg"></i> Tambah Baris Item</button>
                                                                        </div>
                                                                        <div class="col-12 mt-4">
                                                                            <label class="form-label text-muted fw-semibold">Catatan Tambahan (Opsional)</label>
                                                                            <textarea name="catatan_tambahan" class="form-control" rows="2" placeholder="Catatan internal atau pesan untuk pelanggan..."></textarea>
                                                                        </div>
                                                                        <div class="col-md-6 ms-auto mt-4">
                                                                            <div class="p-3 bg-warning bg-opacity-10 rounded-3 border border-warning-subtle text-end">
                                                                                <label class="form-label fw-bold text-warning-emphasis mb-1">TOTAL BIAYA KESELURUHAN</label>
                                                                                <div class="input-group input-group-lg">
                                                                                    <span class="input-group-text bg-warning text-dark border-warning fw-bold">Rp</span>
                                                                                    <input type="text" class="form-control js-total-biaya bg-white border-warning fw-bold fs-4 text-end" value="<?= number_format($defaultBiaya, 0, ',', '.') ?>" readonly>
                                                                                </div>
                                                                                <small class="text-muted d-block mt-2" style="font-size: 11px;">Otomatis Kalkulasi (Biaya Jasa + Total Sparepart)</small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="d-flex justify-content-end gap-2 mt-4 border-top pt-3">
                                                                        <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Batal</button>
                                                                        <button type="submit" class="btn btn-warning fw-bold px-5">Simpan & Selesaikan</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Modal Konfirmasi / Proses / Batal (Untuk status lainnya) -->
                                            <?php if ($itemStage === 'menunggu_konfirmasi'): ?>
                                                <div class="modal fade" id="confirmModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <!-- Isi Form Modal Konfirmasi Tetap Sama -->
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-body p-4">
                                                                <h5 class="mb-3">Konfirmasi Reservasi</h5>
                                                                <p class="text-secondary mb-4">Apakah reservasi ini ingin dikonfirmasi?</p>
                                                                <form method="post" action="index.php?page=status_servis&stage=menunggu_konfirmasi">
                                                                    <input type="hidden" name="action" value="confirm_reservasi">
                                                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                                                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectToCurrent) ?>">
                                                                    <div class="d-flex justify-content-end gap-2">
                                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                                        <button type="submit" class="btn btn-primary">Ya, Konfirmasi</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal fade" id="alihModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-body p-4">
                                                                <h5 class="mb-3">Alihkan Reservasi</h5>
                                                                <form method="post" action="index.php?page=status_servis&stage=menunggu_konfirmasi">
                                                                    <input type="hidden" name="action" value="alihkan_reservasi">
                                                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                                                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectToCurrent) ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Pilih Tanggal Pengganti</label>
                                                                        <input type="date" name="tanggal_baru" class="form-control" required>
                                                                    </div>
                                                                    <div class="mb-4">
                                                                        <label class="form-label">Alasan Pengalihan</label>
                                                                        <textarea name="alasan" class="form-control" rows="2" placeholder="Contoh: Jadwal bengkel penuh..." required></textarea>
                                                                    </div>
                                                                    <div class="d-flex justify-content-end gap-2">
                                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                                        <button type="submit" class="btn btn-info text-white">Alihkan & Konfirmasi</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal fade" id="cancelNewModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-body p-4">
                                                                <h5 class="mb-3">Batalkan Reservasi</h5>
                                                                <form method="post" action="index.php?page=status_servis&stage=menunggu_konfirmasi">
                                                                    <input type="hidden" name="action" value="cancel_reservasi">
                                                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                                                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectToCurrent) ?>">
                                                                    <div class="mb-4">
                                                                        <label class="form-label">Alasan Pembatalan</label>
                                                                        <textarea name="alasan" class="form-control" rows="2" required></textarea>
                                                                    </div>
                                                                    <div class="d-flex justify-content-end gap-2">
                                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                                                                        <button type="submit" class="btn btn-danger">Ya, Batalkan</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($itemStage === 'dikonfirmasi'): ?>
                                                <!-- Modal Konfirmasi Kehadiran dll Sama... (Disingkat karena terlalu panjang, pastikan ada) -->
                                                <div class="modal fade" id="hadirModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-body p-4">
                                                                <form method="post" action="index.php?page=status_servis&stage=dikonfirmasi"><input type="hidden" name="action" value="confirm_kehadiran"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectToCurrent) ?>">
                                                                    <h5 class="mb-3">Konfirmasi Kehadiran</h5>
                                                                    <p class="text-secondary">Apakah pelanggan sudah hadir di bengkel?</p>
                                                                    <div class="d-flex justify-content-end gap-2"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Ya, Hadir</button></div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal fade" id="pendingModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-body p-4">
                                                                <form method="post" action="index.php?page=status_servis&stage=dikonfirmasi"><input type="hidden" name="action" value="mark_pending"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectToCurrent) ?>">
                                                                    <h5 class="mb-3">Pindah ke Pending</h5>
                                                                    <p class="text-secondary">Pindahkan ke status pending kehadiran?</p>
                                                                    <div class="d-flex justify-content-end gap-2"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-warning">Ya, Pending</button></div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal fade" id="batalModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-body p-4">
                                                                <form method="post" action="index.php?page=status_servis&stage=dikonfirmasi"><input type="hidden" name="action" value="cancel_reservasi"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectToCurrent) ?>">
                                                                    <h5 class="mb-3">Batalkan Reservasi</h5>
                                                                    <div class="mb-4"><label class="form-label">Alasan</label><textarea name="alasan" class="form-control" rows="2" required></textarea></div>
                                                                    <div class="d-flex justify-content-end gap-2"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button><button type="submit" class="btn btn-danger">Batalkan</button></div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($itemStage === 'menunggu_antrean'): ?>
                                                <div class="modal fade" id="prosesModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-body p-4">
                                                                <h5 class="mb-3">Mulai Proses Servis</h5>
                                                                <form method="post" action="index.php?page=status_servis&stage=menunggu_antrean">
                                                                    <input type="hidden" name="action" value="start_service">
                                                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                                                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectToCurrent) ?>">
                                                                    <div class="mb-4">
                                                                        <label class="form-label">Pilih Mekanik</label>
                                                                        <select name="mekanik_id" class="form-select" required>
                                                                            <option value="">Pilih mekanik tersedia</option>
                                                                            <?php foreach ($mekanikList as $mekanik): ?>
                                                                                <option value="<?= (int) $mekanik['id'] ?>"><?= htmlspecialchars($mekanik['nama']) ?> - <?= htmlspecialchars($mekanik['status']) ?></option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="d-flex justify-content-end gap-2">
                                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                                        <button type="submit" class="btn btn-primary">Mulai Proses</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($itemStage === 'pending'): ?>
                                                <div class="modal fade" id="callbackModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-body p-4">
                                                                <form method="post" action="index.php?page=status_servis&stage=pending"><input type="hidden" name="action" value="callback_pending"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectToCurrent) ?>">
                                                                    <h5 class="mb-3">Kirim Callback</h5>
                                                                    <p class="text-secondary">Kirim pesan WhatsApp panggilan?</p>
                                                                    <div class="d-flex justify-content-end"><button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-info text-white">Kirim Notifikasi</button></div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal fade" id="hadirPendingModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-body p-4">
                                                                <form method="post" action="index.php?page=status_servis&stage=pending"><input type="hidden" name="action" value="confirm_kehadiran"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectToCurrent) ?>">
                                                                    <h5 class="mb-3">Konfirmasi Kehadiran</h5>
                                                                    <p class="text-secondary">Pelanggan sudah hadir?</p>
                                                                    <div class="d-flex justify-content-end"><button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Tidak</button><button type="submit" class="btn btn-success">Ya, Antre</button></div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal fade" id="batalPendingModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-body p-4">
                                                                <form method="post" action="index.php?page=status_servis&stage=pending"><input type="hidden" name="action" value="cancel_reservasi"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectToCurrent) ?>">
                                                                    <h5 class="mb-3">Batalkan Reservasi</h5><textarea name="alasan" class="form-control mb-3" rows="2" required placeholder="Alasan..."></textarea>
                                                                    <div class="d-flex justify-content-end"><button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Tutup</button><button type="submit" class="btn btn-danger">Batalkan</button></div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php
                                            // SIMPAN KE STRING DAN BERSIHKAN BUFFER
                                            $modalsHtml .= ob_get_clean();
                                            ?>

                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?= renderPagination($currentPage, $totalPages, [
                            'page' => 'status_servis',
                            'stage' => $stage,
                            'q' => $search,
                            'date' => $filterDate,
                            'layanan' => $filterLayanan ?: null,
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- RENDER SEMUA MODAL DI SINI (DI LUAR STRUKTUR TABEL HTML) -->
        <!-- ======================================================== -->
        <?= $modalsHtml ?? '' ?>
    </div>
</div>

<!-- =========================================================
     STRUKTUR MODAL (POPUP) DETAIL RESERVASI (AJAX)
     ========================================================= -->
<div class="modal fade" id="modalDetailReservasi" tabindex="-1" aria-labelledby="modalDetailReservasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header bg-light border-0 py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalDetailReservasiLabel">
                    <i class="bi bi-receipt text-primary me-2"></i> Detail Ringkasan Reservasi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="isiContentModalDetail">
                <!-- Konten dinamis dari AJAX -->
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    // MODAL AJAX DETAIL RESERVASI
    document.addEventListener("DOMContentLoaded", function() {
        const detailButtons = document.querySelectorAll('.btn-detail-reservasi');
        const contentTarget = document.getElementById('isiContentModalDetail');
        const modalEl = document.getElementById('modalDetailReservasi');

        if (modalEl) {
            const myModal = new bootstrap.Modal(modalEl);

            detailButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const reservasiId = this.getAttribute('data-id');

                    contentTarget.innerHTML = `
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                            <p class="mt-3 text-muted fw-semibold">Sedang memuat informasi reservasi...</p>
                        </div>
                    `;

                    myModal.show();

                    fetch(`pages/get_detail_reservasi.php?id=${reservasiId}`)
                        .then(response => {
                            if (!response.ok) throw new Error('Gagal memuat data dari server.');
                            return response.text();
                        })
                        .then(htmlContent => {
                            contentTarget.innerHTML = htmlContent;
                        })
                        .catch(error => {
                            contentTarget.innerHTML = `
                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <div> Terjadi kesalahan: ${error.message} </div>
                                </div>
                            `;
                        });
                });
            });
        }
    });

    // Otomatisasi perhitungan subtotal & total untuk Form Selesaikan Servis
    document.addEventListener('input', function() {
        document.querySelectorAll('.modal').forEach(function(modal) {
            var biayaJasaInput = modal.querySelector('.js-biaya-jasa');
            if (!biayaJasaInput) return;
            var total = parseFloat(biayaJasaInput.value || '0');
            modal.querySelectorAll('tbody tr').forEach(function(row) {
                var qty = parseFloat((row.querySelector('.js-sparepart-qty') || {}).value || '0');
                var harga = parseFloat((row.querySelector('.js-sparepart-harga') || {}).value || '0');
                var subtotal = qty * harga;
                var subtotalField = row.querySelector('.js-sparepart-subtotal');
                if (subtotalField) {
                    subtotalField.value = subtotal.toLocaleString('id-ID');
                }
                total += subtotal;
            });
            var totalField = modal.querySelector('.js-total-biaya');
            if (totalField) {
                totalField.value = total.toLocaleString('id-ID');
            }
        });
    });

    document.addEventListener('click', function(event) {
        if (event.target.closest('.js-add-sparepart')) {
            var table = event.target.closest('form').querySelector('[data-sparepart-table] tbody');
            var row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="sparepart_nama[]" class="form-control" placeholder="Nama Sparepart..."></td>
                <td><input type="number" name="sparepart_qty[]" class="form-control js-sparepart-qty" value="1" min="1"></td>
                <td><input type="number" name="sparepart_harga[]" class="form-control js-sparepart-harga" value="0" min="0"></td>
                <td>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">Rp</span>
                        <input type="text" class="form-control js-sparepart-subtotal bg-light border-start-0" value="0" readonly>
                    </div>
                </td>
                <td><button type="button" class="btn btn-outline-danger btn-sm js-remove-item">Hapus</button></td>
            `;
            table.appendChild(row);
        }
        if (event.target.classList.contains('js-remove-item')) {
            var rows = event.target.closest('tbody').querySelectorAll('tr');
            if (rows.length > 1) {
                event.target.closest('tr').remove();
                document.dispatchEvent(new Event('input'));
            }
        }
    });
</script>