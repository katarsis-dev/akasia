<?php
// Pastikan hanya bisa diakses login
if (!isset($_SESSION['user_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Ambil semua data notifikasi/riwayat pesan berdasarkan user_id yang sedang login
   $sql = "SELECT n.*, r.no_antrian, r.jenis_kendaraan
        FROM notifikasi_wa n
        JOIN reservasi r ON n.reservasi_id = r.id
        WHERE r.user_id = :uid
        AND n.jenis IN (
            'Konfirmasi Reservasi',
            'Perubahan Status',
            'Antrian Dipanggil',
            'Callback Pending',
            'Proses Servis Dimulai',
            'Servis Selesai',
            'Reminder Jadwal Servis'
        )
        ORDER BY n.sent_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $notifikasi = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error database: " . $e->getMessage());
}

// Fungsi untuk menentukan ikon berdasarkan jenis notifikasi
function getNotifIcon($jenis)
{
    switch ($jenis) {
        case 'Konfirmasi Reservasi':
            return '<i class="bi bi-calendar-check fs-4 text-warning"></i>';
        case 'Perubahan Status':
            return '<i class="bi bi-arrow-repeat fs-4 text-primary"></i>';
        case 'Antrian Dipanggil':
            return '<i class="bi bi-megaphone fs-4 text-info"></i>';
        case 'Callback Pending':
            return '<i class="bi bi-telephone-inbound fs-4 text-danger"></i>';
        case 'Proses Servis Dimulai':
            return '<i class="bi bi-wrench-adjustable fs-4 text-primary"></i>';
        case 'Servis Selesai':
            return '<i class="bi bi-check2-circle fs-4 text-success"></i>';
        default:
            return '<i class="bi bi-bell fs-4 text-secondary"></i>';
    }
}

function isQueueNumberChangedNotification(array $notif): bool
{
    $pesan = strtolower((string) ($notif['pesan'] ?? ''));

    return ($notif['jenis'] ?? '') === 'Perubahan Status'
        && str_contains($pesan, 'maju dari')
        && str_contains($pesan, 'menjadi');
}
?>

<div class="mb-4">
    <h3 class="fw-bold text-primary"><i class="bi bi-bell"></i> Kotak Notifikasi</h3>
    <p class="text-muted">Pemberitahuan dan pembaruan status servis kendaraan Anda.</p>
</div>

<div class="row">
    <div class="col-md-10 col-lg-8">
        <?php if (empty($notifikasi)): ?>
            <div class="card border-0 shadow-sm text-center p-5">
                <div class="mb-3">
                    <div class="d-inline-block bg-light rounded-circle p-4">
                        <i class="bi bi-bell-slash fs-1 text-muted"></i>
                    </div>
                </div>
                <h5 class="text-muted fw-bold">Belum ada notifikasi</h5>
                <p class="small text-muted mb-0">Semua pembaruan status kendaraan Anda akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <div class="list-group shadow-sm border-0">
                <?php foreach ($notifikasi as $notif): ?>
                    <?php
                    $isQueueChanged = isQueueNumberChangedNotification($notif);
                    $notifTitle = $isQueueChanged ? 'Perubahan Nomor Antrean' : (string) $notif['jenis'];
                    $notifMeta = $isQueueChanged
                        ? 'Antrean terbaru: ' . (string) $notif['no_antrian']
                        : 'Antrean: ' . (string) $notif['no_antrian'];
                    ?>
                    <div class="list-group-item list-group-item-action p-4 border-0 mb-2 rounded-3 bg-white shadow-sm">
                        <div class="d-flex gap-3">
                            <!-- Ikon Notifikasi -->
                            <div class="flex-shrink-0 mt-1 bg-light rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <?= getNotifIcon($notif['jenis']) ?>
                            </div>

                            <!-- Konten Notifikasi -->
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($notifTitle) ?></h6>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-clock"></i> <?= date('d M Y, H:i', strtotime($notif['sent_at'])) ?>
                                    </small>
                                </div>

                                <p class="mb-2 small text-secondary">
                                    <span class="fw-semibold text-primary"><?= htmlspecialchars($notifMeta) ?></span>
                                    &bull; <?= htmlspecialchars($notif['jenis_kendaraan'] ?? 'Kendaraan Anda') ?>
                                </p>

                                <!-- Isi Pesan (Dibungkus agar rapi) -->
                                <div class="bg-light p-3 rounded border text-muted small fst-italic" style="white-space: pre-wrap;"><?= htmlspecialchars($notif['pesan']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
