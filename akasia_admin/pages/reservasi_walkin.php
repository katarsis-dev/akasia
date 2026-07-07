<?php
// 1. Ambil data layanan & kegiatan untuk form dinamis
$jenisLayananStmt = $pdo->query("SELECT id, nama, deskripsi, estimasi_durasi, estimasi_biaya_jasa, is_custom FROM jenis_layanan WHERE is_active = 1 ORDER BY id ASC");
$jenisLayananList = $jenisLayananStmt->fetchAll(PDO::FETCH_ASSOC);

$kegiatanStmt = $pdo->query("SELECT id, nama_kegiatan, jenis_layanan_id, estimasi_durasi, estimasi_biaya FROM kegiatan_servis WHERE is_active = 1 ORDER BY jenis_layanan_id ASC, nama_kegiatan ASC");
$kegiatanServisList = $kegiatanStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Ambil master data kendaraan dari database
$merkStmt = $pdo->query("SELECT nama_merk FROM merk_motor ORDER BY nama_merk ASC");
$merkList = $merkStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card border-0 shadow-sm" style="border-radius: 16px; background-color: #ffffff;">
    <div class="card-body p-4">
        <div class="section-title mb-4">Form Pendaftaran Servis Walk-in</div>

        <form method="post" action="index.php">
            <input type="hidden" name="action" value="save_walkin">

            <div class="row g-3">
                <!-- DATA PELANGGAN -->
                <div class="col-12">
                    <h6 class="fw-bold text-secondary border-bottom pb-2 mb-2">Data Pelanggan</h6>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Nama Pelanggan *</label>
                    <input type="text" name="nama" class="form-control" required="">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Nomor WhatsApp</label>
                    <input type="text" name="no_whatsapp" class="form-control" placeholder="Misal: 0812xxxxxxx" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Tanggal Servis *</label>
                    <input type="date" name="tanggal_servis" class="form-control" value="<?= date('Y-m-d') ?>" required="">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2"></textarea>
                </div>

                <!-- DATA KENDARAAN -->
                <div class="col-12 mt-4">
                    <h6 class="fw-bold text-secondary border-bottom pb-2 mb-2">Data Kendaraan</h6>
                    <small class="text-muted">
                        ℹ️ Akasia Motor saat ini hanya melayani servis dan perawatan sepeda motor berbahan bakar bensin.
                    </small>
                </div>

                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold">Merk Kendaraan *</label>
                    <select name="merk_kendaraan" id="merk_kendaraan" class="form-select" required="">
                        <option value="">-- Pilih Merk --</option>
                        <?php foreach ($merkList as $merk): ?>
                            <option value="<?= htmlspecialchars($merk['nama_merk']) ?>"><?= htmlspecialchars($merk['nama_merk']) ?></option>
                        <?php endforeach; ?>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                    <input type="text" name="merk_kendaraan_manual" id="merk_kendaraan_manual" class="form-control mt-2 d-none" placeholder="Ketik merk kendaraan Anda...">
                </div>

                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold">Jenis/Kategori Kendaraan *</label>
                    <select name="jenis_kendaraan" id="jenis_kendaraan" class="form-select" required="">
                        <option value="">-- Pilih Merk Dahulu --</option>
                    </select>
                </div>

                <div class="col-12 mb-2">
                    <label class="form-label small fw-semibold">Model Kendaraan *</label>
                    <select name="model_kendaraan" id="model_kendaraan" class="form-select" required="">
                        <option value="">-- Pilih Merk & Kategori Dahulu --</option>
                    </select>
                    <input type="text" name="model_kendaraan_manual" id="model_kendaraan_manual" class="form-control mt-2 d-none" placeholder="Ketik model kendaraan Anda...">
                </div>

                <div class="col-md-3 mb-2">
                    <label class="form-label small fw-semibold">Tahun Kendaraan *</label>
                    <input type="number" name="tahun_kendaraan" class="form-control" placeholder="Misal: 2021" min="1990" max="2099" required="">
                </div>

                <div class="col-md-3 mb-2">
                    <label class="form-label small fw-semibold">Nomor Plat *</label>
                    <input type="text" name="no_plat" class="form-control text-uppercase" placeholder="Misal: N 1234 ABC" required="">
                </div>

                <div class="col-12 mb-2">
                    <label class="form-label small fw-semibold">Warna Kendaraan</label>
                    <input type="text" name="warna" class="form-control" placeholder="Warna kendaraan">
                </div>

                <!-- LAYANAN & KELUHAN -->
                <div class="col-12 mt-4">
                    <h6 class="fw-bold text-secondary border-bottom pb-2 mb-2">Layanan & Keluhan</h6>
                </div>

                <div class="col-md-12">
                    <label class="form-label small fw-semibold">Jenis Layanan Servis <span class="text-danger">*</span></label>
                    <select name="jenis_layanan_id" id="jenis_layanan_id" class="form-select layanan-selector" required="">
                        <option value="">Pilih Jenis Layanan Servis</option>
                        <?php foreach ($jenisLayananList as $layanan): ?>
                            <option value="<?= $layanan['id'] ?>"><?= htmlspecialchars($layanan['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text mt-1">Setelah dipilih, rincian pekerjaan dari master kegiatan servis akan muncul di bawah.</div>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Keluhan</label>
                    <textarea name="keluhan" class="form-control" rows="3"></textarea>
                </div>

                <!-- DETAIL LAYANAN (Dinamis via JS sesuai gambar) -->
                <div class="col-12 mt-3" id="layanan-detail-wrapper" style="display: none;">
                    <div class="p-4 border rounded-3" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">

                        <h5 class="fw-bold mb-1" style="color: #4a6fa5;">
                            <i class="bi bi-tools me-2 text-primary"></i><span id="detail-nama"></span>
                        </h5>
                        <p class="text-secondary small mb-3" id="detail-deskripsi"></p>

                        <!-- Teks "Apa saja yang dilakukan:" (Muncul khusus Paket) -->
                        <div id="paket-kegiatan-title" class="text-primary fw-semibold small mb-2 d-none" style="font-size: 13.5px;">Apa saja yang dilakukan:</div>

                        <!-- Kontainer dinamis List Kegiatan / Checkbox -->
                        <div id="detail-activities-container" class="mb-4"></div>

                        <!-- Bagian bawah: Estimasi Durasi & Biaya -->
                        <div class="d-flex justify-content-between align-items-end mt-4 pt-3 border-top" style="border-color: #e2e8f0 !important;">
                            <div>
                                <div class="text-secondary small mb-1 fw-semibold">Estimasi Durasi</div>
                                <div class="fw-bold fs-6 text-dark"><span id="detail-durasi">0</span> Menit</div>
                            </div>
                            <div class="text-end">
                                <div class="text-secondary small mb-1 fw-semibold">Estimasi Biaya Jasa</div>
                                <div class="fw-bold fs-4 text-primary">Rp <span id="detail-biaya">0</span></div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px; max-width: 250px;">
                                    Estimasi biaya diatas hanyalah estimasi biaya jasa saja, belum termasuk biaya part jika diperlukan pergantian part.
                                </small>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- TOMBOL SUBMIT -->
                <div class="col-12 d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4">Simpan Reservasi</button>
                    <a href="index.php?page=reservasi_walkin" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // 1. DATA DATABASE DIUBAH KE JSON UNTUK JAVASCRIPT
    const layananData = <?= json_encode($jenisLayananList) ?>;

    const kegiatanData = <?= json_encode($kegiatanServisList) ?>; // Data kegiatan diambil via JS
    const kendaraanData = {
        "Honda": {
            "Matic": ["Beat FI", "Beat Street", "Genio", "Scoopy", "Vario 125", "Vario 150", "Vario 160", "PCX 150", "PCX 160", "ADV 150", "ADV 160", "Stylo 160"],
            "Bebek/Cub": ["Revo Fit", "Revo X", "Supra X 125", "Supra GTR 150", "Blade 125"],
            "Sport": ["CB150 Verza", "CB150R Streetfire", "Sonic 150R", "CBR150R", "CBR250R", "CBR250RR"],
            "Trail": ["CRF150L", "CRF250L", "CRF250 Rally"],
            "Touring": ["CB150X", "CRF250 Rally"]
        },
        "Yamaha": {
            "Matic": ["Mio Sporty", "Mio J", "Mio M3", "Mio Z", "Mio S", "Fino", "Gear 125", "FreeGo", "X-Ride", "Lexi", "Fazzio", "Grand Filano", "Aerox 155", "NMAX 155", "XMAX 250"],
            "Bebek/Cub": ["Vega Force", "Jupiter Z1", "MX King 150"],
            "Sport": ["Vixion", "Vixion R", "R15", "MT-15", "XSR155", "R25", "MT-25"],
            "Trail": ["WR155R"],
            "Touring": ["XSR155", "WR155R"]
        },
        "Suzuki": {
            "Matic": ["Nex II", "Address FI", "Avenis 125", "Burgman Street 125 EX"],
            "Bebek/Cub": ["Smash FI", "Shooter", "Satria F150"],
            "Sport": ["GSX-R150", "GSX-S150", "Gixxer SF 250"],
            "Touring": ["V-Strom 250SX"]
        },
        "Kawasaki": {
            "Sport": ["Ninja 150 R", "Ninja 150 RR", "Ninja RR Mono", "Ninja 250 FI", "Z125 Pro", "Z250"],
            "Trail": ["KLX 150", "KLX 230", "KLX 250", "D-Tracker 150", "D-Tracker X"],
            "Touring": ["Versys X250"]
        },
        "Vespa": {
            "Matic": ["LX 125", "S 125", "Primavera 150", "Sprint 150", "GTS 150"],
            "Touring": ["GTS Touring 150"]
        },
        "TVS": {
            "Matic": ["Dazz", "Ntorq 125", "Callisto 110", "Callisto 125"],
            "Bebek/Cub": ["Neo XR", "Rockz"],
            "Sport": ["Apache RTR 160", "Apache RTR 200"]
        },
        "Benelli": {
            "Matic": ["Panarea 125"],
            "Sport": ["TNT 135", "TNT 25", "Leoncino 250"],
            "Trail": ["TRK 251"]
        }
    };

    document.addEventListener("DOMContentLoaded", function() {

        // ==========================================
        // LOGIKA DINAMIS KENDARAAN (MERK, KATEGORI, MODEL)
        // ==========================================
        const merkSelect = document.getElementById('merk_kendaraan');
        const merkManual = document.getElementById('merk_kendaraan_manual');
        const katSelect = document.getElementById('jenis_kendaraan');
        const modelSelect = document.getElementById('model_kendaraan');
        const modelManual = document.getElementById('model_kendaraan_manual');

        function updateKategoriDropdown() {
            const selectedMerk = merkSelect.value;

            if (selectedMerk === 'Lainnya') {
                merkManual.classList.remove('d-none');
                merkManual.required = true;
            } else {
                merkManual.classList.add('d-none');
                merkManual.required = false;
            }

            katSelect.innerHTML = '<option value="">-- Pilih Kategori --</option>';

            if (selectedMerk && selectedMerk !== 'Lainnya' && kendaraanData[selectedMerk]) {
                Object.keys(kendaraanData[selectedMerk]).forEach(function(kategori) {
                    const opt = document.createElement('option');
                    opt.value = kategori;
                    opt.textContent = kategori;
                    katSelect.appendChild(opt);
                });
            }

            const optLainnya = document.createElement('option');
            optLainnya.value = 'Lainnya';
            optLainnya.textContent = 'Lainnya';
            katSelect.appendChild(optLainnya);

            updateModelDropdown();
        }

        function updateModelDropdown() {
            const selectedMerk = merkSelect.value;
            const selectedKat = katSelect.value;

            modelSelect.innerHTML = '<option value="">-- Pilih Model --</option>';

            if (selectedMerk && selectedKat && selectedMerk !== 'Lainnya' && selectedKat !== 'Lainnya' && kendaraanData[selectedMerk] && kendaraanData[selectedMerk][selectedKat]) {
                kendaraanData[selectedMerk][selectedKat].forEach(function(model) {
                    const opt = document.createElement('option');
                    opt.value = model;
                    opt.textContent = model;
                    modelSelect.appendChild(opt);
                });
            }

            const optLainnya = document.createElement('option');
            optLainnya.value = 'Lainnya';
            optLainnya.textContent = 'Lainnya / Input Manual';
            modelSelect.appendChild(optLainnya);

            modelSelect.dispatchEvent(new Event('change'));
        }

        merkSelect.addEventListener('change', updateKategoriDropdown);
        katSelect.addEventListener('change', updateModelDropdown);

        modelSelect.addEventListener('change', function() {
            if (this.value === 'Lainnya') {
                modelManual.classList.remove('d-none');
                modelManual.required = true;
            } else {
                modelManual.classList.add('d-none');
                modelManual.required = false;
            }
        });

        updateKategoriDropdown();


        // ==========================================
        // LOGIKA DINAMIS LAYANAN & KEGIATAN SERVIS
        // ==========================================
        const layananSelect = document.getElementById('jenis_layanan_id');
        const detailWrapper = document.getElementById('layanan-detail-wrapper');
        const detailNama = document.getElementById('detail-nama');
        const detailDeskripsi = document.getElementById('detail-deskripsi');
        const titleApaSaja = document.getElementById('paket-kegiatan-title');
        const activitiesContainer = document.getElementById('detail-activities-container');

        function calculateTotals(selectedService) {
            let isCustom = parseInt(selectedService.is_custom) === 1;
            let totalBiaya = parseInt(selectedService.estimasi_biaya_jasa) || 0;
            let totalDurasi = parseInt(selectedService.estimasi_durasi) || 0;

            if (isCustom) {
                document.querySelectorAll('.act-checkbox:checked').forEach(cb => {
                    totalBiaya += parseInt(cb.getAttribute('data-biaya')) || 0;
                    totalDurasi += parseInt(cb.getAttribute('data-durasi')) || 0;
                });
            }

            document.getElementById('detail-durasi').textContent = totalDurasi;
            document.getElementById('detail-biaya').textContent = new Intl.NumberFormat('id-ID').format(totalBiaya);
        }

        layananSelect.addEventListener('change', function() {
            const id = this.value;

            if (!id) {
                detailWrapper.style.display = 'none';
                return;
            }


            const selected = layananData.find(l => l.id == id);
            if (selected) {

                let isCustom = parseInt(selected.is_custom) === 1;
                detailWrapper.style.display = 'block';
                detailNama.textContent = selected.nama;
                detailDeskripsi.textContent = selected.deskripsi || '';

                // Ambil daftar kegiatan yang sesuai dengan ID layanan
                const relatedKegiatan = kegiatanData.filter(k => k.jenis_layanan_id == id);

                activitiesContainer.innerHTML = ''; // Bersihkan kontainer kegiatan sebelumnya

                if (isCustom) {
                    // MODE SERVIS UMUM: Render Checkbox
                    titleApaSaja.classList.add('d-none'); // Sembunyikan teks "Apa saja yang dilakukan"

                    relatedKegiatan.forEach(k => {
                        const div = document.createElement('div');
                        div.className = 'form-check mb-2';
                        div.innerHTML = `
                            <input class="form-check-input act-checkbox" type="checkbox" name="kegiatan_ids[]" 
                                   value="${k.id}" id="kegiatan_${k.id}" 
                                   data-biaya="${k.estimasi_biaya}" data-durasi="${k.estimasi_durasi}">
                            <label class="form-check-label" for="kegiatan_${k.id}" style="font-size: 13.5px; color: #1e293b;">
                                ${k.nama_kegiatan} <span class="text-muted ms-1">(+Rp ${new Intl.NumberFormat('id-ID').format(k.estimasi_biaya)})</span>
                            </label>
                        `;
                        activitiesContainer.appendChild(div);
                    });

                    // Bind event listener ke checkbox yang baru dibuat
                    document.querySelectorAll('.act-checkbox').forEach(cb => {
                        cb.addEventListener('change', function() {
                            calculateTotals(selected);
                        });
                    });

                } else {
                    // MODE PAKET: Render Bullet Points <ul><li>...</li></ul>
                    titleApaSaja.classList.remove('d-none'); // Munculkan teks biru "Apa saja yang dilakukan"

                    if (relatedKegiatan.length > 0) {
                        const ul = document.createElement('ul');
                        ul.className = 'mb-0 ps-3'; // Margin 0, Padding Start 3
                        ul.style.fontSize = '13.5px';

                        relatedKegiatan.forEach(k => {
                            const li = document.createElement('li');
                            li.className = 'mb-1';
                            li.style.color = '#1e293b'; // Warna teks hitam soft
                            li.textContent = k.nama_kegiatan;
                            ul.appendChild(li);
                        });
                        activitiesContainer.appendChild(ul);
                    } else {
                        activitiesContainer.innerHTML = '<span class="text-muted small">Belum ada rincian kegiatan untuk paket ini.</span>';
                    }
                }

                // Kalkulasi total
                calculateTotals(selected);
            }
        });
    });
</script>