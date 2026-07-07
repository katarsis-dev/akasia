<?php
$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status_notif'] ?? '');

// Pagination
$perPage = 15;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($currentPage - 1) * $perPage;

$whereSql = "WHERE 1=1";
$params = [];

if ($statusFilter !== '') {
    $whereSql .= " AND n.status = :status";
    $params[':status'] = $statusFilter;
}

if ($search !== '') {
    $whereSql .= " AND (u.nama LIKE :search OR r.no_antrian LIKE :search OR n.no_tujuan LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$countStmt = $pdo->prepare("
    SELECT COUNT(*) AS total 
    FROM notifikasi_wa n 
    JOIN reservasi r ON r.id = n.reservasi_id 
    JOIN users u ON u.id = r.user_id 
    $whereSql
");
$countStmt->execute($params);
$totalRows = (int) ($countStmt->fetch()['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare(
    "
    SELECT n.*, r.no_antrian, u.nama AS pelanggan
    FROM notifikasi_wa n
    JOIN reservasi r ON r.id = n.reservasi_id
    JOIN users u ON u.id = r.user_id
    $whereSql
    ORDER BY n.sent_at DESC
    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset
);
$stmt->execute($params);
$notifikasiList = $stmt->fetchAll();
?>

<div class="card mb-4 shadow-sm border-0" style="border-radius: 16px; background-color: #ffffff;">
    <div class="card-body p-4">
        <div class="d-flex flex-column gap-3">
            <div class="d-flex flex-wrap gap-2">
                <a href="index.php?page=notifikasi" class="stage-chip <?= empty($statusFilter) ? 'active' : '' ?>">Semua Status</a>
                <a href="index.php?page=notifikasi&status_notif=Terkirim" class="stage-chip <?= $statusFilter === 'Terkirim' ? 'active' : '' ?>"><i class="bi bi-check-circle text-success"></i> Terkirim</a>
                <a href="index.php?page=notifikasi&status_notif=Gagal" class="stage-chip <?= $statusFilter === 'Gagal' ? 'active' : '' ?>"><i class="bi bi-x-circle text-danger"></i> Gagal</a>
            </div>
            <form method="get" action="index.php" class="d-flex align-items-center" style="width: 100%; max-width: 400px;">
                <input type="hidden" name="page" value="notifikasi">
                <?php if (!empty($statusFilter)): ?><input type="hidden" name="status_notif" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
                <div class="input-group">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari pelanggan, antrean, atau WA" style="border-radius: 20px 0 0 20px; padding-left: 20px; border-color: #cbd5e1;">
                    <button type="submit" class="btn btn-primary" style="border-radius: 0 20px 20px 0; padding-right: 20px; padding-left: 20px;">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body">
        <?php if (empty($notifikasiList)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bell-slash display-4 text-muted"></i>
                <p class="mt-3 text-muted">Tidak ada data untuk filter ini.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pelanggan</th>
                            <th>No. WA Tujuan</th>
                            <th>Jenis Notifikasi</th>
                            <th>Isi Pesan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifikasiList as $n): ?>
                            <tr>
                                <td><?= formatDateTimeIndonesia($n['sent_at']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($n['no_antrian']) ?></strong><br>
                                    <?= htmlspecialchars($n['pelanggan']) ?>
                                </td>
                                <td><?= htmlspecialchars($n['no_tujuan']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($n['jenis']) ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#pesanModal<?= $n['id'] ?>">
                                        <i class="bi bi-eye"></i> Lihat
                                    </button>

                                    <div class="modal fade" id="pesanModal<?= $n['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Isi Pesan WhatsApp</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php
                                                    $escapedPesan = htmlspecialchars($n['pesan']);
                                                    $linkedPesan = preg_replace(
                                                        '/(https?:\/\/[^\s<]+)/',
                                                        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
                                                        $escapedPesan
                                                    );
                                                    ?>
                                                    <div class="border rounded-3 p-3 bg-light" style="white-space: pre-wrap; word-break: break-word; font-family: sans-serif;"><?= $linkedPesan ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($n['status'] === 'Terkirim'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check"></i> Terkirim</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x"></i> Gagal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <?= renderPagination($currentPage, $totalPages, ['page' => 'notifikasi', 'status_notif' => $statusFilter, 'q' => $search]) ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
