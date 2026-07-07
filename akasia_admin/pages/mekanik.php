<?php
$search = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$whereSql = "WHERE 1=1";
$params = [];

if ($search !== '') {
    $whereSql .= " AND nama LIKE :search";
    $params[':search'] = '%' . $search . '%';
}
if ($filterStatus !== '') {
    $whereSql .= " AND status = :status";
    $params[':status'] = $filterStatus;
}

$mekanikStmt = $pdo->prepare("SELECT id, nama, no_hp, status FROM mekanik $whereSql ORDER BY nama ASC");
$mekanikStmt->execute($params);
$mekanikList = $mekanikStmt->fetchAll();

$editId = (int) ($_GET['edit'] ?? 0);
$showForm = ($_GET['form'] ?? '') === 'tambah' || $editId > 0;
$mekanikEdit = [
    'id' => 0,
    'nama' => '',
    'no_hp' => '',
    'status' => 'tersedia'
];

if ($editId > 0) {
    $editStmt = $pdo->prepare("SELECT id, nama, no_hp, status FROM mekanik WHERE id = :id LIMIT 1");
    $editStmt->execute([':id' => $editId]);
    $mekanikEditData = $editStmt->fetch();
    if ($mekanikEditData) {
        $mekanikEdit = $mekanikEditData;
    }
}
?>

<div class="card border-0 shadow-sm bg-transparent shadow-none">
    <div class="card-body p-0">

        <div class="card mb-4 shadow-sm border-0" style="border-radius: 16px; background-color: #ffffff;">
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="index.php?page=mekanik" class="stage-chip <?= empty($filterStatus) ? 'active' : '' ?>">Semua Mekanik</a>
                        <a href="index.php?page=mekanik&status=tersedia" class="stage-chip <?= $filterStatus === 'tersedia' ? 'active' : '' ?>"><i class="bi bi-circle-fill text-success" style="font-size:10px;"></i> Tersedia</a>
                        <a href="index.php?page=mekanik&status=sibuk" class="stage-chip <?= $filterStatus === 'sibuk' ? 'active' : '' ?>"><i class="bi bi-circle-fill text-warning" style="font-size:10px;"></i> Sibuk</a>
                        <a href="index.php?page=mekanik&status=libur" class="stage-chip <?= $filterStatus === 'libur' ? 'active' : '' ?>"><i class="bi bi-circle-fill text-secondary" style="font-size:10px;"></i> Libur</a>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <form method="get" action="index.php" class="d-flex align-items-center" style="width: 100%; max-width: 400px;">
                            <input type="hidden" name="page" value="mekanik">
                            <?php if (!empty($filterStatus)): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
                            <div class="input-group">
                                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari nama mekanik..." style="border-radius: 20px 0 0 20px; padding-left: 20px; border-color: #cbd5e1;">
                                <button type="submit" class="btn btn-primary" style="border-radius: 0 20px 20px 0; padding-right: 20px; padding-left: 20px;">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                        </form>

                        <div>
                            <a href="index.php?page=mekanik&form=tambah" class="btn btn-primary" style="border-radius: 10px; font-weight: 600;">
                                <i class="bi bi-person-plus-fill me-1"></i> Tambah Mekanik
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($showForm): ?>
            <div class="card border form-panel mb-4">
                <div class="card-body">
                    <div class="section-title mb-3"><?= $mekanikEdit['id'] ? 'Ubah Mekanik' : 'Tambah Mekanik' ?></div>
                    <form method="post" action="index.php?page=mekanik">
                        <input type="hidden" name="action" value="save_mekanik">
                        <input type="hidden" name="id" value="<?= (int) $mekanikEdit['id'] ?>">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Nama Mekanik</label>
                                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($mekanikEdit['nama']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No. HP</label>
                                <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($mekanikEdit['no_hp'] ?: '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status_mekanik" class="form-select">
                                    <?php foreach (['tersedia', 'sibuk', 'libur'] as $statusOption): ?>
                                        <option value="<?= htmlspecialchars($statusOption) ?>" <?= $mekanikEdit['status'] === $statusOption ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($statusOption)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="index.php?page=mekanik" class="btn btn-outline-secondary">Batal</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php foreach ($mekanikList as $mekanik): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card mechanic-card border h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="mechanic-avatar"><?= strtoupper(substr($mekanik['nama'], 0, 1)) ?></div>
                            <h5 class="mb-1"><?= htmlspecialchars($mekanik['nama']) ?></h5>
                            <div class="text-secondary mb-3"><?= htmlspecialchars($mekanik['no_hp'] ?: '-') ?></div>
                            <div class="mb-3">
                                <span class="badge <?= getMekanikBadgeClass($mekanik['status']) ?>"><?= htmlspecialchars(ucfirst($mekanik['status'])) ?></span>
                            </div>
                            <div class="d-flex gap-2 mt-auto">
                                <a href="index.php?page=mekanik&edit=<?= (int) $mekanik['id'] ?>" class="btn btn-outline-primary btn-sm w-100">Edit</a>
                                <form method="post" action="index.php?page=mekanik" class="w-100">
                                    <input type="hidden" name="action" value="delete_mekanik">
                                    <input type="hidden" name="id" value="<?= (int) $mekanik['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Hapus data mekanik ini?');">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($mekanikList)): ?>
                <div class="col-12">
                    <div class="text-center text-secondary py-5">
                        <i class="bi bi-person-x display-4 text-muted"></i>
                        <p class="mt-3">Data mekanik tidak ditemukan untuk pencarian ini.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>