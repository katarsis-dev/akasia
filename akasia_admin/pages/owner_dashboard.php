<?php
// --- 0. Filter Periode Waktu ---
$periode = trim($_GET['periode'] ?? 'bulan_ini');
$validPeriode = ['hari_ini', 'minggu_ini', 'bulan_ini', 'tahun_ini', 'custom'];
if (!in_array($periode, $validPeriode, true)) {
    $periode = 'bulan_ini';
}

$customDari = trim($_GET['dari'] ?? '');
$customSampai = trim($_GET['sampai'] ?? '');

switch ($periode) {
    case 'hari_ini':
        $tanggalMulai = date('Y-m-d');
        $tanggalAkhir = date('Y-m-d');
        break;
    case 'minggu_ini':
        $tanggalMulai = date('Y-m-d', strtotime('monday this week'));
        $tanggalAkhir = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'tahun_ini':
        $tanggalMulai = date('Y-01-01');
        $tanggalAkhir = date('Y-12-31');
        break;
    case 'custom':
        $tanggalMulai = $customDari !== '' ? $customDari : date('Y-m-01');
        $tanggalAkhir = $customSampai !== '' ? $customSampai : date('Y-m-d');
        break;
    case 'bulan_ini':
    default:
        $tanggalMulai = date('Y-m-01');
        $tanggalAkhir = date('Y-m-t');
        break;
}

// Jaga-jaga kalau dari > sampai pada custom range, tukar otomatis
if (strtotime($tanggalMulai) > strtotime($tanggalAkhir)) {
    [$tanggalMulai, $tanggalAkhir] = [$tanggalAkhir, $tanggalMulai];
}

$tanggalMulaiFull = $tanggalMulai . ' 00:00:00';
$tanggalAkhirFull = $tanggalAkhir . ' 23:59:59';

// Tentukan apakah rentang dianggap "pendek" (<= 31 hari) untuk granularitas chart tren
$jumlahHariRentang = (strtotime($tanggalAkhir) - strtotime($tanggalMulai)) / 86400 + 1;
$groupHarian = $jumlahHariRentang <= 31;


// --- 1. Kueri Grafik Tren Reservasi (mengikuti filter periode) ---
if ($groupHarian) {
    $chartReservasiStmt = $pdo->prepare("
        SELECT DATE(created_at) as label_periode, COUNT(*) as total
        FROM reservasi
        WHERE created_at BETWEEN :mulai AND :akhir
        GROUP BY DATE(created_at)
        ORDER BY label_periode ASC
    ");
} else {
    $chartReservasiStmt = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as label_periode, COUNT(*) as total
        FROM reservasi
        WHERE created_at BETWEEN :mulai AND :akhir
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY label_periode ASC
    ");
}
$chartReservasiStmt->execute([':mulai' => $tanggalMulaiFull, ':akhir' => $tanggalAkhirFull]);
$chartReservasi = $chartReservasiStmt->fetchAll(PDO::FETCH_ASSOC);

// --- 2. Kueri Grafik Pendapatan Bulanan (mengikuti filter periode, berbasis waktu_selesai) ---
$chartPendapatanStmt = $pdo->prepare("
    SELECT DATE_FORMAT(waktu_selesai, '%Y-%m') as bulan_key, DATE_FORMAT(waktu_selesai, '%b %Y') as bulan, SUM(total_biaya) as total
    FROM reservasi
    WHERE status = 'selesai' AND waktu_selesai BETWEEN :mulai AND :akhir
    GROUP BY bulan_key, bulan
    ORDER BY bulan_key ASC
");
$chartPendapatanStmt->execute([':mulai' => $tanggalMulaiFull, ':akhir' => $tanggalAkhirFull]);
$chartPendapatan = $chartPendapatanStmt->fetchAll(PDO::FETCH_ASSOC);

// --- 3. Kueri Grafik Layanan Terlaris (mengikuti filter periode) ---
$chartLayananStmt = $pdo->prepare("
    SELECT jl.nama, COUNT(r.id) as total
    FROM reservasi r
    JOIN jenis_layanan jl ON r.jenis_layanan_id = jl.id
    WHERE r.created_at BETWEEN :mulai AND :akhir
    GROUP BY r.jenis_layanan_id
    ORDER BY total DESC
    LIMIT 5
");
$chartLayananStmt->execute([':mulai' => $tanggalMulaiFull, ':akhir' => $tanggalAkhirFull]);
$chartLayanan = $chartLayananStmt->fetchAll(PDO::FETCH_ASSOC);

// --- 4. Ringkasan KPI untuk dashboard owner ---
$summaryStmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS total_servis_selesai,
        SUM(CASE WHEN status = 'selesai' THEN total_biaya ELSE 0 END) AS total_pendapatan,
        SUM(CASE WHEN status = 'dibatalkan' THEN 1 ELSE 0 END) AS total_servis_dibatalkan
    FROM reservasi
    WHERE created_at BETWEEN :mulai AND :akhir
");
$summaryStmt->execute([':mulai' => $tanggalMulaiFull, ':akhir' => $tanggalAkhirFull]);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$totalServisSelesai = (int) ($summary['total_servis_selesai'] ?? 0);
$totalPendapatan = (float) ($summary['total_pendapatan'] ?? 0);
$totalServisDibatalkan = (int) ($summary['total_servis_dibatalkan'] ?? 0);

$layananTerlarisNama = $chartLayanan[0]['nama'] ?? '-';
$layananTerlarisTotal = (int) ($chartLayanan[0]['total'] ?? 0);

// Label periode untuk ditampilkan di UI
$labelPeriode = match ($periode) {
    'hari_ini' => 'Hari Ini',
    'minggu_ini' => 'Minggu Ini',
    'tahun_ini' => 'Tahun Ini',
    'custom' => 'Periode Custom',
    default => 'Bulan Ini',
};
?>

<div class="container-fluid px-0 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h3 class="mb-1 fw-bold text-dark page-title">Dashboard Statistik Reservasi Servis</h3>
            <p class="text-muted mb-0 page-subtitle">Ringkasan performa bengkel dan tren pendapatan.</p>
        </div>
    </div>

    <div class="card shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="get" action="index.php" class="d-flex align-items-end flex-wrap gap-3">
                <input type="hidden" name="page" value="owner_dashboard">

                <div>
                    <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px;">Periode</label>
                    <select name="periode" id="periode-select" class="form-select" style="width: 180px;" onchange="toggleCustomRange(this.value)">
                        <option value="hari_ini" <?= $periode === 'hari_ini' ? 'selected' : '' ?>>Hari Ini</option>
                        <option value="minggu_ini" <?= $periode === 'minggu_ini' ? 'selected' : '' ?>>Minggu Ini</option>
                        <option value="bulan_ini" <?= $periode === 'bulan_ini' ? 'selected' : '' ?>>Bulan Ini</option>
                        <option value="tahun_ini" <?= $periode === 'tahun_ini' ? 'selected' : '' ?>>Tahun Ini</option>
                        <option value="custom" <?= $periode === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>

                <div id="custom-range-fields" class="d-flex gap-3" style="<?= $periode === 'custom' ? '' : 'display: none;' ?>">
                    <div>
                        <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px;">Dari Tanggal</label>
                        <input type="date" name="dari" value="<?= htmlspecialchars($tanggalMulai) ?>" class="form-control" style="width: 160px;">
                    </div>
                    <div>
                        <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px;">Sampai Tanggal</label>
                        <input type="date" name="sampai" value="<?= htmlspecialchars($tanggalAkhir) ?>" class="form-control" style="width: 160px;">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-funnel-fill me-1"></i> Terapkan
                </button>

                <div class="ms-auto text-muted small">
                    Menampilkan data: <strong><?= htmlspecialchars($labelPeriode) ?></strong>
                    (<?= htmlspecialchars(formatDateIndonesia($tanggalMulai)) ?> &ndash; <?= htmlspecialchars(formatDateIndonesia($tanggalAkhir)) ?>)
                </div>
            </form>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-4 mb-4">
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted fw-semibold">Total Servis Selesai</span>
                        <i class="bi bi-wrench-adjustable-circle fs-4 text-primary"></i>
                    </div>
                    <div class="fw-bolder text-dark" style="font-size: 2rem; line-height: 1;"><?= number_format($totalServisSelesai, 0, ',', '.') ?></div>
                    <div class="text-muted small mt-2">Total servis berstatus selesai pada periode ini</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted fw-semibold">Total Pendapatan</span>
                        <i class="bi bi-cash-stack fs-4 text-success"></i>
                    </div>
                    <div class="fw-bolder text-dark" style="font-size: 2rem; line-height: 1;">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></div>
                    <div class="text-muted small mt-2">Akumulasi servis selesai pada periode ini</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted fw-semibold">Layanan Terlaris</span>
                        <i class="bi bi-award fs-4 text-warning"></i>
                    </div>
                    <div class="fw-bolder text-dark text-truncate" style="font-size: 1.35rem; line-height: 1.2;"><?= htmlspecialchars($layananTerlarisNama) ?></div>
                    <div class="text-muted small mt-2"><?= number_format($layananTerlarisTotal, 0, ',', '.') ?> transaksi pada periode ini</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted fw-semibold">Servis Dibatalkan</span>
                        <i class="bi bi-x-circle fs-4" style="color: #e11d48;"></i>
                    </div>
                    <div class="fw-bolder text-dark" style="font-size: 2rem; line-height: 1;"><?= number_format($totalServisDibatalkan, 0, ',', '.') ?></div>
                    <div class="text-muted small mt-2">Total reservasi dengan status dibatalkan pada periode ini</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <i class="bi bi-graph-up fs-5 text-primary"></i>
                        <h5 class="mb-0 fw-bold">Tren Reservasi (<?= htmlspecialchars($labelPeriode) ?>)</h5>
                    </div>
                    <canvas id="reservasiChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <i class="bi bi-bar-chart fs-5 text-success"></i>
                        <h5 class="mb-0 fw-bold">Pendapatan Bulanan (<?= htmlspecialchars($labelPeriode) ?>)</h5>
                    </div>
                    <canvas id="pendapatanChart" height="320"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center g-4 mb-4">
        <div class="col-12 col-md-10 col-lg-8 col-xl-5">
            <div class="card h-100 shadow-sm" style="min-height: 420px;">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <i class="bi bi-pie-chart fs-5 text-warning"></i>
                        <h5 class="mb-0 fw-bold">Layanan Terlaris</h5>
                    </div>
                    <canvas id="layananChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function toggleCustomRange(value) {
        var fields = document.getElementById('custom-range-fields');
        fields.style.display = (value === 'custom') ? 'flex' : 'none';
    }

    // Config Chart 1: Reservasi (Line)
    const dataReservasi = <?= json_encode($chartReservasi) ?>;
    new Chart(document.getElementById('reservasiChart'), {
        type: 'line',
        data: {
            labels: dataReservasi.map(item => item.label_periode),
            datasets: [{
                label: 'Jumlah Reservasi',
                data: dataReservasi.map(item => item.total),
                borderColor: '#2258d8',
                backgroundColor: 'rgba(34, 88, 216, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#2258d8'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Config Chart 2: Pendapatan (Bar)
    const dataPendapatan = <?= json_encode($chartPendapatan) ?>;
    new Chart(document.getElementById('pendapatanChart'), {
        type: 'bar',
        data: {
            labels: dataPendapatan.map(item => item.bulan),
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: dataPendapatan.map(item => item.total),
                backgroundColor: '#10b981',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Config Chart 3: Layanan Terlaris (Doughnut)
    const dataLayanan = <?= json_encode($chartLayanan) ?>;
    new Chart(document.getElementById('layananChart'), {
        type: 'doughnut',
        data: {
            labels: dataLayanan.map(item => item.nama),
            datasets: [{
                data: dataLayanan.map(item => item.total),
                backgroundColor: ['#2258d8', '#10b981', '#f59e0b', '#e11d48', '#6c7a92'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });
</script>