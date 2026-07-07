<?php
$jenisStmt = $pdo->query("
    SELECT id, nama, deskripsi, estimasi_durasi, estimasi_biaya_jasa, is_active,
           (SELECT COUNT(id) FROM reservasi WHERE jenis_layanan_id = jenis_layanan.id) AS is_used 
    FROM jenis_layanan 
    ORDER BY id ASC
");
$jenisLayananList = $jenisStmt->fetchAll();
$editId = (int) ($_GET['edit'] ?? 0);
$showForm = ($_GET['form'] ?? '') === 'tambah' || $editId > 0;
$jenisEdit = [
    'id' => 0,
    'nama' => '',
    'deskripsi' => '',
    'estimasi_durasi' => 0,
    'estimasi_biaya_jasa' => 0
];

if ($editId > 0) {
    $editStmt = $pdo->prepare("SELECT id, nama, deskripsi, estimasi_durasi, estimasi_biaya_jasa FROM jenis_layanan WHERE id = :id LIMIT 1");
    $editStmt->execute([':id' => $editId]);
    $jenisEditData = $editStmt->fetch();
    if ($jenisEditData) {
        $jenisEdit = $jenisEditData;
    }
}
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="toolbar mb-3">
            <div class="toolbar-group">
                <a href="index.php?page=jenis_layanan&form=tambah" class="btn btn-primary">Tambah Paket Layanan</a>
            </div>
        </div>

        <?php if ($showForm): ?>
            <div class="card border form-panel mb-4">
                <div class="card-body">
                    <div class="section-title mb-3 fw-bold"><?= $jenisEdit['id'] ? 'Ubah Paket Layanan' : 'Tambah Paket Layanan' ?></div>
                    <form method="post" action="index.php?page=jenis_layanan">
                        <input type="hidden" name="action" value="save_jenis_layanan">
                        <input type="hidden" name="id" value="<?= (int) $jenisEdit['id'] ?>">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nama Paket Layanan</label>
                                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($jenisEdit['nama']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estimasi Durasi (Menit)</label>
                                <input type="number" name="estimasi_durasi" class="form-control" value="<?= (int) $jenisEdit['estimasi_durasi'] ?>" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estimasi Biaya Jasa (Rp)</label>
                                <input type="number" name="estimasi_biaya_jasa" class="form-control" value="<?= (float) $jenisEdit['estimasi_biaya_jasa'] ?>" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="2"><?= htmlspecialchars($jenisEdit['deskripsi'] ?: '') ?></textarea>
                            </div>
                            <div class="col-12 d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="index.php?page=jenis_layanan" class="btn btn-outline-secondary">Batal</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Paket Layanan</th>
                        <th>Deskripsi</th>
                        <th>Est. Durasi</th>
                        <th>Est. Biaya Jasa</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jenisLayananList as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                           <td class="fw-semibold">
    <?= htmlspecialchars($item['nama']) ?>
    <?php if ((int)$item['is_active'] === 0): ?>
        <span class="badge bg-danger ms-1">Nonaktif</span>
    <?php endif; ?>
</td>
                            <td><?= htmlspecialchars($item['deskripsi']) ?></td>
                            <td><?= (int) $item['estimasi_durasi'] ?> Menit</td>

                            <td class="text-end">
    <a href="index.php?page=jenis_layanan&edit=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
    
    <?php if ((int)$item['is_active'] === 0): ?>
        <!-- Jika data sudah dinonaktifkan, tampilkan tombol Aktifkan -->
        <form method="post" action="index.php?page=jenis_layanan" class="d-inline">
            <input type="hidden" name="action" value="toggle_active_jenis_layanan">
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <input type="hidden" name="is_active" value="1">
            <button type="submit" class="btn btn-sm btn-success">Aktifkan</button>
        </form>
    <?php else: ?>
        <!-- Jika masih aktif, tampilkan tombol Hapus -->
        <?php if ((int)$item['is_used'] > 0): ?>
            <!-- KONDISI 2: Sudah digunakan -> Muncul dialog peringatan & lakukan Nonaktifkan -->
            <form method="post" action="index.php?page=jenis_layanan" class="d-inline" onsubmit="return confirm('Data sudah digunakan pada transaksi reservasi.\nData tidak dapat dihapus agar riwayat transaksi tetap terjaga.\nApakah Anda ingin menonaktifkan data ini?');">
                <input type="hidden" name="action" value="toggle_active_jenis_layanan">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <input type="hidden" name="is_active" value="0">
                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
            </form>
        <?php else: ?>
            <!-- KONDISI 1: Belum digunakan -> Muncul dialog standar & lakukan Hapus Permanen -->
            <form method="post" action="index.php?page=jenis_layanan" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket layanan ini secara permanen?');">
                <input type="hidden" name="action" value="delete_jenis_layanan">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$jenisLayananList): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">Data paket layanan belum tersedia.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>