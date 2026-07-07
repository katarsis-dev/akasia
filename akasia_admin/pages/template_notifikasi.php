<?php
$templateStmt = $pdo->query("SELECT id, kode_template, nama_template, isi_pesan, is_active FROM template_whatsapp ORDER BY id ASC");
$templateList = $templateStmt->fetchAll();

$editId = (int) ($_GET['edit'] ?? 0);
$showForm = $editId > 0;
$templateEdit = ['id' => 0, 'kode_template' => '', 'nama_template' => '', 'isi_pesan' => '', 'is_active' => 1];

if ($editId > 0) {
    $editStmt = $pdo->prepare("SELECT id, kode_template, nama_template, isi_pesan, is_active FROM template_whatsapp WHERE id = :id LIMIT 1");
    $editStmt->execute([':id' => $editId]);
    $editData = $editStmt->fetch();
    if ($editData) {
        $templateEdit = $editData;
    }
}
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">

        <?php if ($showForm): ?>
            <div class="card border form-panel mb-4">
                <div class="card-body">
                    <div class="section-title mb-3">Edit Template: <?= htmlspecialchars($templateEdit['nama_template']) ?></div>
                    <form method="post" action="index.php?page=template_notifikasi">
                        <input type="hidden" name="action" value="save_template">
                        <input type="hidden" name="id" value="<?= (int) $templateEdit['id'] ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Template</label>
                                <input type="text" name="nama_template" class="form-control" value="<?= htmlspecialchars($templateEdit['nama_template']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" <?= $templateEdit['is_active'] ? 'selected' : '' ?>>Aktif</option>
                                    <option value="0" <?= !$templateEdit['is_active'] ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Isi Pesan</label>
                                <textarea name="isi_pesan" id="isi_pesan" class="form-control font-monospace" rows="8" required><?= htmlspecialchars($templateEdit['isi_pesan']) ?></textarea>

                                <div class="form-text mt-2">
                                    <span class="d-block mb-1 text-muted">Klik variabel di bawah ini untuk menyisipkan ke dalam pesan:</span>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary var-btn" data-var="{nama}">{nama}</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary var-btn" data-var="{antrean}">{antrean}</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary var-btn" data-var="{tanggal}">{tanggal}</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary var-btn" data-var="{mekanik}">{mekanik}</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary var-btn" data-var="{total}">{total}</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary var-btn" data-var="{link}">{link}</button>
                                    </div>
                                </div>

                            </div>
                            <div class="col-12 d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                                <a href="index.php?page=template_notifikasi" class="btn btn-outline-secondary">Batal</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Template</th>
                        <th>Kode</th>
                        <th>Pratinjau Pesan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templateList as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($item['nama_template']) ?></td>
                            <td><code class="text-secondary"><?= htmlspecialchars($item['kode_template']) ?></code></td>
                            <td class="text-secondary" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars(str_replace(['\n', "\n"], ' ', $item['isi_pesan'])) ?>
                            </td>
                            <td>
                                <span class="badge <?= $item['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $item['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="index.php?page=template_notifikasi&edit=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$templateList): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">Data template belum tersedia.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const varBtns = document.querySelectorAll('.var-btn');
        const textarea = document.getElementById('isi_pesan');

        if (varBtns.length > 0 && textarea) {
            varBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const varText = this.getAttribute('data-var');

                    // Ambil posisi kursor saat ini
                    const startPos = textarea.selectionStart;
                    const endPos = textarea.selectionEnd;
                    const text = textarea.value;

                    // Sisipkan teks variabel di posisi kursor
                    textarea.value = text.substring(0, startPos) + varText + text.substring(endPos, text.length);

                    // Pindahkan kursor ke ujung teks yang baru saja disisipkan
                    textarea.selectionStart = startPos + varText.length;
                    textarea.selectionEnd = startPos + varText.length;

                    // Kembalikan fokus ke textarea
                    textarea.focus();
                });
            });
        }
    });
</script>
