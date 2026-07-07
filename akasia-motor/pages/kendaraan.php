<?php
if (!isset($_SESSION['user_id'])) exit;

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$return_url = $_GET['return_url'] ?? '';

// Hapus Kendaraan
if ($action === 'hapus' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM kendaraan WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $user_id]);
    header("Location: ?page=kendaraan");
    exit;
}

// Tambah / Edit Kendaraan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['tambah', 'edit'])) {
    $merk = trim($_POST['merk_kendaraan'] ?? '');
    $jenis = trim($_POST['jenis_kendaraan'] ?? '');
    $model = trim($_POST['model_kendaraan'] ?? '');
    $tahun = (int)($_POST['tahun_kendaraan'] ?? 0);
    $warna = trim($_POST['warna'] ?? ''); // Menangkap input warna
    $plat = strtoupper(trim($_POST['no_plat'] ?? ''));
    $rangka = strtoupper(trim($_POST['no_rangka'] ?? ''));
    $mesin = strtoupper(trim($_POST['no_mesin'] ?? ''));

    if ($merk === 'Lainnya') $merk = trim($_POST['merk_kendaraan_manual'] ?? '');
    if ($model === 'Lainnya') $model = trim($_POST['model_kendaraan_manual'] ?? '');

    if ($action === 'tambah') {
        // Query insert diperbarui dengan kolom warna
        $stmt = $pdo->prepare("INSERT INTO kendaraan (user_id, merk_kendaraan, jenis_kendaraan, model_kendaraan, tahun_kendaraan, warna, no_plat, no_rangka, no_mesin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $merk, $jenis, $model, $tahun, $warna, $plat, $rangka, $mesin]);

        if ($return_url === 'booking') {
            header("Location: ?page=booking");
            exit;
        }
    } else {
        // Query update diperbarui dengan kolom warna
        $stmt = $pdo->prepare("UPDATE kendaraan SET merk_kendaraan=?, jenis_kendaraan=?, model_kendaraan=?, tahun_kendaraan=?, warna=?, no_plat=?, no_rangka=?, no_mesin=? WHERE id=? AND user_id=?");
        $stmt->execute([$merk, $jenis, $model, $tahun, $warna, $plat, $rangka, $mesin, $_POST['id'], $user_id]);
    }
    header("Location: ?page=kendaraan");
    exit;
}
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="fw-bold text-primary"><i class="bi bi-bicycle"></i> Kelola Data Kendaraan</h3>
        <p class="text-muted mb-0">Kelola daftar kendaraan Anda untuk keperluan servis.</p>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="?page=kendaraan&action=tambah" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg"></i> Tambah Kendaraan</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM kendaraan WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$user_id]);
    $kendaraan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="row g-3">
        <?php if (empty($kendaraan)): ?>
            <div class="col-12">
                <div class="alert alert-warning text-center p-4 rounded-3">
                    <i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>
                    Belum ada data kendaraan. Silakan klik tombol "Tambah Kendaraan" di atas.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($kendaraan as $k): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 h-100 rounded-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="fw-bold text-dark mb-0"><?= e($k['merk_kendaraan']) ?> <?= e($k['model_kendaraan']) ?></h5>
                                <span class="badge bg-light text-dark border"><?= e($k['no_plat']) ?></span>
                            </div>
                            <p class="small text-muted mb-3"><?= e($k['jenis_kendaraan']) ?> &bull; Tahun <?= e($k['tahun_kendaraan']) ?> &bull; Warna <?= e($k['warna'] ?? '-') ?></p>
                            <div class="d-flex gap-2 mt-auto">
                                <a href="?page=kendaraan&action=edit&id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill rounded-pill">Edit</a>
                                <a href="?page=kendaraan&action=hapus&id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Hapus kendaraan ini?')"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (in_array($action, ['tambah', 'edit'])): ?>
    <?php
    $k = [];
    if ($action === 'edit') {
        $stmt = $pdo->prepare("SELECT * FROM kendaraan WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['id'], $user_id]);
        $k = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$k) {
            header("Location: ?page=kendaraan");
            exit;
        }
    }

    $merkMotorList = $pdo->query("SELECT id_merk, nama_merk FROM merk_motor ORDER BY nama_merk ASC")->fetchAll(PDO::FETCH_ASSOC);
    $kategoriMotorList = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori_motor ORDER BY id_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);
    $modelMotorList = $pdo->query("SELECT id_model, id_merk, id_kategori, nama_model FROM model_motor ORDER BY nama_model ASC")->fetchAll(PDO::FETCH_ASSOC);

    $selMerk = $k['merk_kendaraan'] ?? '';
    $selKat = $k['jenis_kendaraan'] ?? '';
    $selMod = $k['model_kendaraan'] ?? '';
    ?>
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <?php if ($return_url === 'booking'): ?>
                <div class="alert alert-info small rounded-3"><i class="bi bi-info-circle me-2"></i>Anda perlu mendaftarkan kendaraan minimal satu sebelum melakukan Booking.</div>
            <?php endif; ?>
            <form method="POST" action="">
                <?php if ($action === 'edit'): ?> <input type="hidden" name="id" value="<?= $k['id'] ?>"> <?php endif; ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-semibold">Merk Kendaraan *</label>
                        <select name="merk_kendaraan" id="merk_kendaraan" class="form-select" required>
                            <option value="">-- Pilih Merk --</option>
                            <?php foreach ($merkMotorList as $merk): ?>
                                <option value="<?= e($merk['nama_merk']) ?>" <?= $selMerk === $merk['nama_merk'] ? 'selected' : '' ?>><?= e($merk['nama_merk']) ?></option>
                            <?php endforeach; ?>
                            <option value="Lainnya" <?= (!empty($selMerk) && array_search($selMerk, array_column($merkMotorList, 'nama_merk')) === false) ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                        <input type="text" name="merk_kendaraan_manual" id="merk_kendaraan_manual" class="form-control mt-2 d-none" placeholder="Ketik merk..." value="<?= (!empty($selMerk) && array_search($selMerk, array_column($merkMotorList, 'nama_merk')) === false) ? e($selMerk) : '' ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-semibold">Jenis Kendaraan *</label>
                        <select name="jenis_kendaraan" id="jenis_kendaraan" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategoriMotorList as $kat): ?>
                                <option value="<?= e($kat['nama_kategori']) ?>" <?= $selKat === $kat['nama_kategori'] ? 'selected' : '' ?>><?= e($kat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Model Kendaraan *</label>
                    <select name="model_kendaraan" id="model_kendaraan" class="form-select" required>
                        <option value="">-- Pilih Merk & Kategori Dahulu --</option>
                    </select>
                    <input type="text" name="model_kendaraan_manual" id="model_kendaraan_manual" class="form-control mt-2 d-none" placeholder="Ketik model..." value="<?= (!empty($selMod) && array_search($selMod, array_column($modelMotorList, 'nama_model')) === false) ? e($selMod) : '' ?>">
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label small fw-semibold">Tahun *</label>
                        <input type="number" name="tahun_kendaraan" class="form-control" value="<?= e($k['tahun_kendaraan'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-semibold">Warna *</label>
                        <input type="text" name="warna" class="form-control text-capitalize" value="<?= e($k['warna'] ?? '') ?>" placeholder="Cth: Hitam" required>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label small fw-semibold">Nomor Plat *</label>
                        <input type="text" name="no_plat" class="form-control text-uppercase" value="<?= e($k['no_plat'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-semibold">Nomor Rangka *</label>
                        <input type="text" name="no_rangka" class="form-control text-uppercase" value="<?= e($k['no_rangka'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-semibold">Nomor Mesin *</label>
                        <input type="text" name="no_mesin" class="form-control text-uppercase" value="<?= e($k['no_mesin'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary flex-fill fw-bold rounded-pill">Simpan Kendaraan</button>
                    <?php if ($return_url !== 'booking'): ?>
                        <a href="?page=kendaraan" class="btn btn-light border flex-fill rounded-pill text-center">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const merkSelect = document.getElementById('merk_kendaraan');
            const merkManual = document.getElementById('merk_kendaraan_manual');
            const kategoriSelect = document.getElementById('jenis_kendaraan');
            const modelSelect = document.getElementById('model_kendaraan');
            const modelManual = document.getElementById('model_kendaraan_manual');
            const selectedModel = '<?= e($selMod) ?>';
            const jsModelMotor = <?= json_encode($modelMotorList) ?>;
            const jsMerkMotor = <?= json_encode($merkMotorList) ?>;
            const jsKategoriMotor = <?= json_encode($kategoriMotorList) ?>;

            function syncManual(sel, man) {
                if (sel.value === 'Lainnya' || (!sel.querySelector(`option[value="${sel.value}"]`) && sel.value !== '')) {
                    man.classList.remove('d-none');
                    man.required = true;
                    if (sel.value !== 'Lainnya') sel.value = 'Lainnya';
                } else {
                    man.classList.add('d-none');
                    man.required = false;
                }
            }

            function updateModels() {
                const merkObj = jsMerkMotor.find(m => m.nama_merk === merkSelect.value);
                const katObj = jsKategoriMotor.find(k => k.nama_kategori === kategoriSelect.value);
                modelSelect.innerHTML = '<option value="">-- Pilih Model --</option>';

                if (merkObj && katObj) {
                    const list = jsModelMotor.filter(m => parseInt(m.id_merk) === parseInt(merkObj.id_merk) && parseInt(m.id_kategori) === parseInt(katObj.id_kategori) && m.nama_model !== 'Lainnya');
                    list.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = opt.textContent = m.nama_model;
                        modelSelect.appendChild(opt);
                    });
                }
                const optLain = document.createElement('option');
                optLain.value = optLain.textContent = 'Lainnya';
                modelSelect.appendChild(optLain);

                // Pre-select model jika sedang mode Edit
                if (selectedModel) {
                    const exists = Array.from(modelSelect.options).some(opt => opt.value === selectedModel);
                    if (exists) {
                        modelSelect.value = selectedModel;
                    } else {
                        modelSelect.value = 'Lainnya';
                        modelManual.value = selectedModel;
                    }
                }
                syncManual(modelSelect, modelManual);
            }

            merkSelect.addEventListener('change', () => {
                syncManual(merkSelect, merkManual);
                updateModels();
            });
            kategoriSelect.addEventListener('change', updateModels);
            modelSelect.addEventListener('change', () => syncManual(modelSelect, modelManual));

            updateModels();
            syncManual(merkSelect, merkManual);
        });
    </script>
<?php endif; ?>