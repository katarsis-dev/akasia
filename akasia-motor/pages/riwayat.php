<?php
if (!isset($_SESSION['user_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Ambil histori (selesai atau dibatalkan) beserta nama mekanik dan nama layanan
    $sql = "SELECT r.*, jl.nama as nama_layanan, m.nama as mekanik_nama
            FROM reservasi r
            LEFT JOIN jenis_layanan jl ON r.jenis_layanan_id = jl.id
            LEFT JOIN mekanik m ON r.mekanik_id = m.id
            WHERE r.user_id = :uid 
            AND r.status IN ('selesai', 'dibatalkan')
            ORDER BY r.tanggal_servis DESC, r.updated_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $history = $stmt->fetchAll();

    // Ambil data sparepart untuk semua reservasi user ini (agar efisien, tidak query di dalam loop)
    $spareparts = [];
    if (!empty($history)) {
        $res_ids = array_column($history, 'id');
        $in_placeholders = implode(',', array_fill(0, count($res_ids), '?'));

        $sql_sp = "SELECT reservasi_id, nama_item, qty, harga, subtotal 
                   FROM reservasi_sparepart 
                   WHERE reservasi_id IN ($in_placeholders)";
        $stmt_sp = $pdo->prepare($sql_sp);
        $stmt_sp->execute($res_ids);
        $sp_data = $stmt_sp->fetchAll();

        foreach ($sp_data as $sp) {
            $spareparts[$sp['reservasi_id']][] = $sp;
        }
    }
} catch (PDOException $e) {
    die("Error database: " . $e->getMessage());
}
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="fw-bold text-primary"><i class="bi bi-clock-history"></i> Riwayat Servis</h3>
        <p class="text-muted mb-0">Catatan pengerjaan dan detail biaya kendaraan Anda di masa lalu.</p>
    </div>
</div>

<?php if (empty($history)): ?>
    <div class="card border-0 shadow-sm text-center p-5 rounded-3">
        <i class="bi bi-folder2-open fs-1 text-muted mb-3"></i>
        <h5 class="text-muted">Riwayat Kosong</h5>
        <p class="small text-muted">Belum ada riwayat servis yang telah selesai atau dibatalkan.</p>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($history as $row): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="bi bi-tools fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold"><?= htmlspecialchars($row['nama_layanan']) ?></h6>
                                    <div class="text-muted small">
                                        <i class="bi bi-calendar-event me-1"></i> <?= date('d F Y', strtotime($row['tanggal_servis'])) ?>
                                        <span class="mx-1">&bull;</span>
                                        <i class="bi bi-hash"></i> <?= htmlspecialchars($row['no_antrian']) ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <?php if ($row['status'] == 'selesai'): ?>
                                    <span class="badge bg-success rounded-pill px-3 py-2 fw-normal"><i class="bi bi-check2-circle me-1"></i> Selesai</span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-pill px-3 py-2 fw-normal"><i class="bi bi-x-circle me-1"></i> Dibatalkan</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row align-items-center">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <span class="text-muted d-block small mb-1">Kendaraan</span>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($row['jenis_kendaraan'] ?? '-') ?></span>
                                <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($row['no_plat']) ?></span>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <?php if ($row['status'] == 'selesai'): ?>
                                    <span class="text-muted d-block small mb-1">Total Biaya</span>
                                    <span class="fw-bold fs-5 text-primary">Rp <?= number_format((float)$row['total_biaya'], 0, ',', '.') ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <button type="button" class="btn btn-outline-primary rounded-pill px-4 w-100" data-bs-toggle="modal" data-bs-target="#detailModal<?= $row['id'] ?>">
                                    <i class="bi bi-receipt-cutoff me-1"></i> Lihat Detail
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="detailModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="detailModalLabel<?= $row['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-primary text-white border-0">
                            <h5 class="modal-title fs-6 fw-bold" id="detailModalLabel<?= $row['id'] ?>">
                                <i class="bi bi-receipt me-2"></i> Detail Servis - <?= htmlspecialchars($row['no_antrian']) ?>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 small">

                            <h6 class="fw-bold border-bottom pb-2 text-secondary mb-3"><i class="bi bi-bicycle me-1"></i> Informasi Kendaraan</h6>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">No. Plat</div>
                                <div class="col-7 fw-semibold text-dark"><?= htmlspecialchars($row['no_plat']) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Kendaraan</div>
                                <div class="col-7 fw-semibold text-dark"><?= htmlspecialchars($row['jenis_kendaraan'] ?? '-') ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Tipe/Model</div>
                                <div class="col-7 fw-semibold text-dark"><?= htmlspecialchars($row['tipe_model'] ?? '-') ?> <?= $row['tahun'] ? '(' . htmlspecialchars($row['tahun']) . ')' : '' ?></div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-5 text-muted">Keluhan Awal</div>
                                <div class="col-7 fst-italic text-dark">"<?= htmlspecialchars($row['keluhan'] ?: 'Tidak ada keluhan tertulis') ?>"</div>
                            </div>

                            <?php if ($row['status'] == 'selesai'): ?>
                                <h6 class="fw-bold border-bottom pb-2 text-secondary mb-3"><i class="bi bi-person-gear me-1"></i> Informasi Pengerjaan</h6>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Mekanik</div>
                                    <div class="col-7 fw-semibold text-dark"><?= htmlspecialchars($row['mekanik_nama'] ?? '-') ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Waktu Selesai</div>
                                    <div class="col-7 fw-semibold text-dark"><?= $row['waktu_selesai'] ? date('d M Y, H:i', strtotime($row['waktu_selesai'])) : '-' ?></div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-12 text-muted mb-2">Hasil Servis / Catatan Mekanik:</div>
                                    <div class="col-12 bg-light p-3 rounded-3 border">
                                        <?= nl2br(htmlspecialchars($row['hasil_servis'] ?: 'Tidak ada catatan tambahan dari mekanik.')) ?>
                                    </div>
                                </div>

                                <h6 class="fw-bold border-bottom pb-2 text-secondary mb-3"><i class="bi bi-wallet2 me-1"></i> Rincian Biaya</h6>

                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Biaya Jasa (<?= htmlspecialchars($row['nama_layanan']) ?>)</span>
                                    <span class="fw-semibold text-dark">Rp <?= number_format((float)$row['biaya_jasa'], 0, ',', '.') ?></span>
                                </div>

                                <?php if (isset($spareparts[$row['id']]) && count($spareparts[$row['id']]) > 0): ?>
                                    <div class="mt-3 mb-2 fw-bold text-muted" style="font-size: 0.85rem;">Suku Cadang (Sparepart):</div>
                                    <?php foreach ($spareparts[$row['id']] as $sp): ?>
                                        <div class="d-flex justify-content-between mb-2 ps-2 border-start border-2 border-secondary">
                                            <div>
                                                <span class="text-dark d-block"><?= htmlspecialchars($sp['nama_item']) ?></span>
                                                <span class="text-muted" style="font-size: 0.75rem;">x<?= $sp['qty'] ?> @ Rp <?= number_format((float)$sp['harga'], 0, ',', '.') ?></span>
                                            </div>
                                            <span class="text-dark align-self-center">Rp <?= number_format((float)$sp['subtotal'], 0, ',', '.') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Suku Cadang</span>
                                        <span class="fst-italic text-muted">-</span>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-4 p-3 bg-primary bg-opacity-10 rounded-3 border border-primary border-opacity-25">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-primary">TOTAL BIAYA</span>
                                        <span class="fw-bold text-primary fs-5">Rp <?= number_format((float)$row['total_biaya'], 0, ',', '.') ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger mt-3 mb-0">
                                    <i class="bi bi-x-circle me-1"></i> Reservasi ini dibatalkan.
                                    <?php if (!empty($row['alasan_status'])): ?>
                                        <hr class="my-2 opacity-25">
                                        <strong>Alasan:</strong> <?= htmlspecialchars($row['alasan_status']) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                        </div>
                        <div class="modal-footer border-0 bg-light p-3">
                            <button type="button" class="btn btn-secondary w-100 rounded-pill fw-bold" data-bs-dismiss="modal">Tutup Detail</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>