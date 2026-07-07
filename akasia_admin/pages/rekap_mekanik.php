<?php
// Mengambil nilai filter tanpa menetapkan default bulan ini
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$filterMekanik = (int) ($_GET['mekanik_id'] ?? 0);

// Ambil data mekanik untuk dropdown filter
$mekanikStmt = $pdo->query("SELECT id, nama FROM mekanik ORDER BY nama ASC");
$mekanikList = $mekanikStmt->fetchAll();

// 1. Query untuk Tabel Summary (Total Kinerja)
$params = [];
$summarySql = "
    SELECT m.id, m.nama, m.status, COUNT(r.id) as total_servis
    FROM mekanik m
    LEFT JOIN reservasi r ON r.mekanik_id = m.id
         AND r.status IN ('selesai')
";

if ($startDate !== '' && $endDate !== '') {
    $summarySql .= " AND r.tanggal_servis BETWEEN :start AND :end";
    $params[':start'] = $startDate;
    $params[':end'] = $endDate;
}

if ($filterMekanik > 0) {
    $summarySql .= " WHERE m.id = :mid";
    $params[':mid'] = $filterMekanik;
}

$summarySql .= " GROUP BY m.id ORDER BY total_servis DESC, m.nama ASC";

$summaryStmt = $pdo->prepare($summarySql);
$summaryStmt->execute($params);
$summaryData = $summaryStmt->fetchAll();


// 2. Query untuk Tabel Detail
$detailParams = [];
$detailSql = "
    SELECT 
        r.id, 
        r.no_antrian,
        r.tanggal_servis,
        u.nama AS pelanggan,
        r.no_plat,
        r.tipe_model,
        r.status,
        m.nama AS mekanik_nama
    FROM reservasi r
    INNER JOIN mekanik m ON m.id = r.mekanik_id
    INNER JOIN users u ON u.id = r.user_id
    WHERE r.status IN ('selesai')
";

if ($startDate !== '' && $endDate !== '') {
    $detailSql .= " AND r.tanggal_servis BETWEEN :start AND :end";
    $detailParams[':start'] = $startDate;
    $detailParams[':end'] = $endDate;
}

if ($filterMekanik > 0) {
    $detailSql .= " AND m.id = :mid";
    $detailParams[':mid'] = $filterMekanik;
}

$detailSql .= " ORDER BY r.tanggal_servis DESC, m.nama ASC";

$detailStmt = $pdo->prepare($detailSql);
$detailStmt->execute($detailParams);
$detailData = $detailStmt->fetchAll();
?>

<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="get" action="index.php" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="rekap_mekanik">

            <div class="col-md-3">
                <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px;">Periode Awal</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar"></i></span>
                    <input type="date" name="start_date" class="form-control border-start-0 ps-0" value="<?= htmlspecialchars($startDate) ?>">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px;">Periode Akhir</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-check"></i></span>
                    <input type="date" name="end_date" class="form-control border-start-0 ps-0" value="<?= htmlspecialchars($endDate) ?>">
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px;">Nama Mekanik</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-gear"></i></span>
                    <select name="mekanik_id" class="form-select border-start-0 ps-0">
                        <option value="">Semua Mekanik</option>
                        <?php foreach ($mekanikList as $m): ?>
                            <option value="<?= (int) $m['id'] ?>" <?= $filterMekanik === (int) $m['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1" style="height: 38px;">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                <?php if ($startDate !== '' || $endDate !== '' || $filterMekanik > 0): ?>
                    <a href="index.php?page=rekap_mekanik" class="btn btn-light border" style="height: 38px;">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; height: 100%;">
            <div class="card-body">
                <div class="section-title mb-3 fw-bold"><i class="bi bi-bar-chart-fill text-primary me-2"></i> Rekapitulasi Kinerja</div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Mekanik</th>
                                <th>No. Antrean</th>
                                <th>Pelanggan</th>
                                <th>Kendaraan (Plat)</th>
                                <th class="text-end">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detailData as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars(formatDateIndonesia($row['tanggal_servis'])) ?></td>
                                    <td class="fw-semibold text-primary"><?= htmlspecialchars($row['mekanik_nama']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['no_antrian']) ?></span></td>
                                    <td><?= htmlspecialchars($row['pelanggan']) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($row['tipe_model'] ?: '-') ?></div>
                                        <div class="text-secondary" style="font-size: 11px;"><?= htmlspecialchars($row['no_plat']) ?></div>
                                    </td>
                                    <td class="text-end">
                                        <?php
                                        $statusClass = $row['status'] === 'selesai' ? 'bg-success' : 'bg-warning text-dark';
                                        $statusLabel = $row['status'] === 'selesai' ? 'Selesai' : 'Diproses';
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><i class="bi <?= $row['status'] === 'selesai' ? 'bi-check-circle' : 'bi-tools' ?> me-1"></i> <?= htmlspecialchars($statusLabel) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-detail-reservasi" data-id="<?= (int)$row['id'] ?>">
                                            <i class="bi bi-eye"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$detailData): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                        Belum ada data pengerjaan pada periode ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

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
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const detailButtons = document.querySelectorAll('.btn-detail-reservasi');
        const contentTarget = document.getElementById('isiContentModalDetail');
        const myModal = new bootstrap.Modal(document.getElementById('modalDetailReservasi'));

        detailButtons.forEach(button => {
            button.addEventListener('click', function() {
                const reservasiId = this.getAttribute('data-id');

                // Tampilkan animasi loading selagi data ditarik
                contentTarget.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3 text-muted fw-semibold">Sedang memuat informasi reservasi...</p>
                </div>
            `;

                // Buka Modal Popup
                myModal.show();

                // Panggil file get_detail_reservasi.php menggunakan Fetch API (AJAX)
                fetch(`pages/get_detail_reservasi.php?id=${reservasiId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Gagal memuat data dari server.');
                        }
                        return response.text();
                    })
                    .then(htmlContent => {
                        // Masukkan struktur HTML hasil query ke dalam modal body
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
    });
</script>