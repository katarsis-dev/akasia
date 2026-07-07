<?php
$kegiatanStmt = $pdo->query("
    SELECT ks.id, ks.nama_kegiatan, ks.jenis_layanan_id, jl.nama AS layanan, ks.estimasi_durasi, ks.estimasi_biaya, ks.is_active,
           (SELECT COUNT(id) FROM reservasi_kegiatan WHERE kegiatan_servis_id = ks.id) AS is_used
    FROM kegiatan_servis ks
    INNER JOIN jenis_layanan jl ON jl.id = ks.jenis_layanan_id
    ORDER BY jl.id ASC, ks.id ASC
");
$kegiatanServisList = $kegiatanStmt->fetchAll();
$jenisLayananStmt = $pdo->query("SELECT id, nama FROM jenis_layanan ORDER BY id ASC");
$jenisLayananList = $jenisLayananStmt->fetchAll();
$editId = (int) ($_GET['edit'] ?? 0);
$showForm = ($_GET['form'] ?? '') === 'tambah' || $editId > 0;
$kegiatanEdit = [
    'id' => 0,
    'jenis_layanan_id' => 0,
    'nama_kegiatan' => '',
    'estimasi_durasi' => 0,
    'estimasi_biaya' => 0
];

if ($editId > 0) {
    $editStmt = $pdo->prepare("SELECT id, jenis_layanan_id, nama_kegiatan, estimasi_durasi, estimasi_biaya FROM kegiatan_servis WHERE id = :id LIMIT 1");
    $editStmt->execute([':id' => $editId]);
    $kegiatanEditData = $editStmt->fetch();
    if ($kegiatanEditData) {
        $kegiatanEdit = $kegiatanEditData;
    }
}
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="toolbar mb-3">
            <div class="toolbar-group">
                <a href="index.php?page=kegiatan_servis&form=tambah" class="btn btn-primary">Tambah Kegiatan Servis</a>
            </div>
        </div>

        <?php if ($showForm): ?>
            <div class="card border form-panel mb-4">
                <div class="card-body">
                    <div class="section-title mb-3 fw-bold"><?= $kegiatanEdit['id'] ? 'Ubah Kegiatan Servis' : 'Tambah Kegiatan Servis' ?></div>
                    <form method="post" action="index.php?page=kegiatan_servis">
                        <input type="hidden" name="action" value="save_kegiatan_servis">
                        <input type="hidden" name="id" value="<?= (int) $kegiatanEdit['id'] ?>">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Paket Layanan</label>
                                <select name="jenis_layanan_id" class="form-select" required>
                                    <option value="">Pilih Paket</option>
                                    <?php foreach ($jenisLayananList as $layanan): ?>
                                        <option value="<?= (int) $layanan['id'] ?>" <?= (int) $kegiatanEdit['jenis_layanan_id'] === (int) $layanan['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($layanan['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Nama Kegiatan</label>
                                <input type="text" name="nama_kegiatan" class="form-control" value="<?= htmlspecialchars($kegiatanEdit['nama_kegiatan']) ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Durasi (Menit)</label>
                                <input type="number" name="estimasi_durasi" class="form-control" value="<?= (int) $kegiatanEdit['estimasi_durasi'] ?>" min="0" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Biaya (Rp)</label>
                                <input type="number" name="estimasi_biaya" class="form-control" value="<?= (float) $kegiatanEdit['estimasi_biaya'] ?>" min="0" step="0.01" required>
                            </div>
                            <div class="col-12 d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="index.php?page=kegiatan_servis" class="btn btn-outline-secondary">Batal</a>
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
                        <th>Kegiatan Servis</th>
                        <th>Durasi</th>
                        <th>Biaya</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kegiatanServisList as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($item['layanan']) ?></td>
                            <td class="fw-semibold">
    <?= htmlspecialchars($item['nama_kegiatan']) ?>
    <?php if ((int)$item['is_active'] === 0): ?>
        <span class="badge bg-danger ms-1">Nonaktif</span>
    <?php endif; ?>
</td>
                            <td><?= (int) $item['estimasi_durasi'] ?> Menit</td>
                            <td>Rp <?= number_format($item['estimasi_biaya'], 0, ',', '.') ?></td>
<td class="text-end">
    <a href="index.php?page=kegiatan_servis&edit=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
    
    <?php if ((int)$item['is_active'] === 0): ?>
        <!-- Jika data sudah dinonaktifkan, tampilkan tombol Aktifkan -->
        <form method="post" action="index.php?page=kegiatan_servis" class="d-inline">
            <input type="hidden" name="action" value="toggle_active_kegiatan_servis">
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <input type="hidden" name="is_active" value="1">
            <button type="submit" class="btn btn-sm btn-success">Aktifkan</button>
        </form>
    <?php else: ?>
        <!-- Jika masih aktif, tampilkan tombol Hapus -->
        <?php if ((int)$item['is_used'] > 0): ?>
            <!-- KONDISI 2: Sudah digunakan -> Muncul dialog peringatan & lakukan Nonaktifkan -->
            <form method="post" action="index.php?page=kegiatan_servis" class="d-inline" onsubmit="return confirm('Data sudah digunakan pada transaksi reservasi.\nData tidak dapat dihapus agar riwayat transaksi tetap terjaga.\nApakah Anda ingin menonaktifkan data ini?');">
                <input type="hidden" name="action" value="toggle_active_kegiatan_servis">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <input type="hidden" name="is_active" value="0">
                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
            </form>
        <?php else: ?>
            <!-- KONDISI 1: Belum digunakan -> Muncul dialog standar & lakukan Hapus Permanen -->
            <form method="post" action="index.php?page=kegiatan_servis" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kegiatan servis ini secara permanen?');">
                <input type="hidden" name="action" value="delete_kegiatan_servis">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$kegiatanServisList): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">Data kegiatan servis belum tersedia.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>