<?php

function redirectTo(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function formatDateIndonesia(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    if (!$timestamp) {
        return $value;
    }

    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    return date('d', $timestamp) . ' ' . ($months[(int) date('n', $timestamp)] ?? date('m', $timestamp)) . ' ' . date('Y', $timestamp);
}

function formatDateTimeIndonesia(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    if (!$timestamp) {
        return $value;
    }

    return formatDateIndonesia($value) . ' ' . date('H:i', $timestamp);
}

function getWorkshopSchedule(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT jam_buka, jam_tutup, jam_istirahat_mulai, jam_istirahat_selesai, hari_operasional FROM pengaturan LIMIT 1");
    $pengaturan = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'jam_buka' => $pengaturan['jam_buka'] ?? '08:00:00',
        'jam_tutup' => $pengaturan['jam_tutup'] ?? '17:00:00',
        'jam_istirahat_mulai' => $pengaturan['jam_istirahat_mulai'] ?? null,
        'jam_istirahat_selesai' => $pengaturan['jam_istirahat_selesai'] ?? null,
        'hari_operasional' => trim((string) ($pengaturan['hari_operasional'] ?? 'Setiap Hari')),
    ];
}

function getOperationalDayNumbers(?string $hariOperasional): array
{
    $hariOperasional = strtolower(trim((string) $hariOperasional));
    $map = [
        'senin' => 1,
        'selasa' => 2,
        'rabu' => 3,
        'kamis' => 4,
        'jumat' => 5,
        'sabtu' => 6,
        'minggu' => 7,
    ];

    if ($hariOperasional === '' || str_contains($hariOperasional, 'setiap hari')) {
        return [1, 2, 3, 4, 5, 6, 7];
    }

    $normalized = str_replace(['s.d.', 's/d', 'sd'], '-', $hariOperasional);
    $days = [];

    if (preg_match('/(senin|selasa|rabu|kamis|jumat|sabtu|minggu)\s*-\s*(senin|selasa|rabu|kamis|jumat|sabtu|minggu)/', $normalized, $matches)) {
        $start = $map[$matches[1]] ?? null;
        $end = $map[$matches[2]] ?? null;
        if ($start !== null && $end !== null) {
            if ($start <= $end) {
                for ($day = $start; $day <= $end; $day++) {
                    $days[] = $day;
                }
            } else {
                for ($day = $start; $day <= 7; $day++) {
                    $days[] = $day;
                }
                for ($day = 1; $day <= $end; $day++) {
                    $days[] = $day;
                }
            }
        }
    } else {
        preg_match_all('/senin|selasa|rabu|kamis|jumat|sabtu|minggu/', $normalized, $matches);
        foreach ($matches[0] as $namaHari) {
            if (isset($map[$namaHari])) {
                $days[] = $map[$namaHari];
            }
        }
    }

    $days = array_values(array_unique($days));

    return !empty($days) ? $days : [1, 2, 3, 4, 5, 6, 7];
}

function createWorkshopDateTime(DateTimeZone $tz, string $date, ?string $time): ?DateTimeImmutable
{
    if (!$time) {
        return null;
    }

    $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time, $tz);

    return $dateTime ?: null;
}

function moveToNextOperationalStart(DateTimeImmutable $moment, array $schedule, DateTimeZone $tz): DateTimeImmutable
{
    $operationalDays = getOperationalDayNumbers($schedule['hari_operasional'] ?? '');
    $candidate = $moment;

    for ($i = 0; $i < 14; $i++) {
        $dayNumber = (int) $candidate->format('N');
        $date = $candidate->format('Y-m-d');
        $open = createWorkshopDateTime($tz, $date, $schedule['jam_buka'] ?? '08:00:00');
        $close = createWorkshopDateTime($tz, $date, $schedule['jam_tutup'] ?? '17:00:00');

        if ($open && $close && in_array($dayNumber, $operationalDays, true)) {
            if ($close <= $open) {
                $close = $close->modify('+1 day');
            }

            if ($candidate <= $open) {
                return $open;
            }

            if ($candidate < $close) {
                $breakStart = createWorkshopDateTime($tz, $date, $schedule['jam_istirahat_mulai'] ?? null);
                $breakEnd = createWorkshopDateTime($tz, $date, $schedule['jam_istirahat_selesai'] ?? null);
                if ($breakStart && $breakEnd && $breakEnd <= $breakStart) {
                    $breakEnd = $breakEnd->modify('+1 day');
                }

                if ($breakStart && $breakEnd && $candidate >= $breakStart && $candidate < $breakEnd) {
                    return $breakEnd;
                }

                return $candidate;
            }
        }

        $candidate = $candidate->modify('+1 day')->setTime(0, 0, 0);
    }

    return $moment;
}

function calculateOperationalEndTime(PDO $pdo, DateTimeInterface $startTime, int $durationMinutes): DateTimeImmutable
{
    $tz = new DateTimeZone('Asia/Jakarta');
    $schedule = getWorkshopSchedule($pdo);
    $remainingMinutes = max(0, $durationMinutes);
    $current = DateTimeImmutable::createFromInterface($startTime)->setTimezone($tz);

    if ($remainingMinutes === 0) {
        return moveToNextOperationalStart($current, $schedule, $tz);
    }

    while ($remainingMinutes > 0) {
        $current = moveToNextOperationalStart($current, $schedule, $tz);
        $date = $current->format('Y-m-d');
        $close = createWorkshopDateTime($tz, $date, $schedule['jam_tutup'] ?? '17:00:00');
        if (!$close) {
            return $current;
        }
        if ($close <= $current) {
            $close = $close->modify('+1 day');
        }

        $breakStart = createWorkshopDateTime($tz, $date, $schedule['jam_istirahat_mulai'] ?? null);
        $breakEnd = createWorkshopDateTime($tz, $date, $schedule['jam_istirahat_selesai'] ?? null);
        if ($breakStart && $breakEnd && $breakEnd <= $breakStart) {
            $breakEnd = $breakEnd->modify('+1 day');
        }

        $segmentEnd = $close;
        if ($breakStart && $breakEnd && $current < $breakStart) {
            $segmentEnd = min($close, $breakStart);
        }

        $availableMinutes = (int) floor(($segmentEnd->getTimestamp() - $current->getTimestamp()) / 60);
        if ($availableMinutes <= 0) {
            if ($breakStart && $breakEnd && $current >= $breakStart && $current < $breakEnd) {
                $current = $breakEnd;
                continue;
            }

            $current = $close->modify('+1 minute');
            continue;
        }

        if ($remainingMinutes <= $availableMinutes) {
            return $current->modify('+' . $remainingMinutes . ' minutes');
        }

        $remainingMinutes -= $availableMinutes;
        $current = $segmentEnd;

        if ($breakStart && $breakEnd && $segmentEnd == $breakStart) {
            $current = $breakEnd;
        } else {
            $current = $close->modify('+1 minute');
        }
    }

    return $current;
}

function getReservasiStages(): array
{
    return [
        'menunggu_konfirmasi' => ['label' => 'Konfirmasi Reservasi', 'icon' => 'bi-hourglass-split', 'badge' => 'warning'],
        'dikonfirmasi' => ['label' => 'Konfirmasi Kehadiran', 'icon' => 'bi-patch-check', 'badge' => 'primary'],
        'menunggu_antrean' => ['label' => 'Antrean Servis', 'icon' => 'bi-people', 'badge' => 'info'],
        'diproses' => ['label' => 'Servis Berlangsung', 'icon' => 'bi-wrench-adjustable-circle', 'badge' => 'warning'],
        'pending' => ['label' => 'Pending Kehadiran', 'icon' => 'bi-clock-history', 'badge' => 'secondary'],
        'selesai' => ['label' => 'Servis Selesai', 'icon' => 'bi-check-circle', 'badge' => 'success'],
    ];
}

function getOperationalMinutes(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT jam_buka, jam_tutup, jam_istirahat_mulai, jam_istirahat_selesai FROM pengaturan LIMIT 1");
    $pengaturan = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $jamBuka = $pengaturan['jam_buka'] ?? '08:00:00';
    $jamTutup = $pengaturan['jam_tutup'] ?? '17:00:00';
    $jamIstirahatMulai = $pengaturan['jam_istirahat_mulai'] ?? null;
    $jamIstirahatSelesai = $pengaturan['jam_istirahat_selesai'] ?? null;

    $today = date('Y-m-d');
    $start = strtotime($today . ' ' . $jamBuka);
    $end = strtotime($today . ' ' . $jamTutup);

    if ($start === false || $end === false) {
        return 0;
    }

    if ($end <= $start) {
        $end += 86400;
    }

    $workMinutes = (int) ceil(($end - $start) / 60);

    if ($jamIstirahatMulai && $jamIstirahatSelesai) {
        $breakStart = strtotime($today . ' ' . $jamIstirahatMulai);
        $breakEnd = strtotime($today . ' ' . $jamIstirahatSelesai);

        if ($breakStart !== false && $breakEnd !== false) {
            if ($breakEnd <= $breakStart) {
                $breakEnd += 86400;
            }

            $overlapStart = max($start, $breakStart);
            $overlapEnd = min($end, $breakEnd);
            if ($overlapEnd > $overlapStart) {
                $workMinutes -= (int) ceil(($overlapEnd - $overlapStart) / 60);
            }
        }
    }

    return max(0, $workMinutes);
}

function getBookedMinutesByDate(PDO $pdo, string $tanggal): int
{
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(COALESCE(NULLIF(r.estimasi_durasi, 0), jl.estimasi_durasi, 0)), 0)
        FROM reservasi r
        LEFT JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
        WHERE r.tanggal_servis = :tgl
          AND r.status IN ('menunggu_konfirmasi', 'dikonfirmasi', 'menunggu_antrean', 'diproses', 'pending', 'selesai')
    ");
    $stmt->execute([':tgl' => $tanggal]);

    return (int) $stmt->fetchColumn();
}

function getCapacityInfo(PDO $pdo, string $tanggal): array
{
    $workMinutesPerMechanic = getOperationalMinutes($pdo);

    $stmtMekanik = $pdo->query("SELECT COUNT(id) AS total_mekanik FROM mekanik WHERE status != 'libur'");
    $rowMekanik = $stmtMekanik->fetch(PDO::FETCH_ASSOC);
    $jumlahMekanik = (int) ($rowMekanik['total_mekanik'] ?? 1);

    if ($jumlahMekanik <= 0) {
        $jumlahMekanik = 1;
    }

    $capacity = $workMinutesPerMechanic * $jumlahMekanik;

    $used = getBookedMinutesByDate($pdo, $tanggal);

    return [
        'capacity' => $capacity,
        'used' => $used,
        'remaining' => max(0, $capacity - $used),
        'allowed' => $capacity > 0 && $used < $capacity,
    ];
}
function getStageMeta(string $status): array
{
    $stages = getReservasiStages();

    return $stages[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'icon' => 'bi-circle', 'badge' => 'dark'];
}

function getStatusBadgeClass(string $status): string
{
    return match ($status) {
        'menunggu_konfirmasi' => 'text-bg-warning',
        'dikonfirmasi' => 'text-bg-primary',
        'menunggu_antrean' => 'text-bg-info',
        'diproses' => 'text-bg-warning',
        'pending' => 'text-bg-secondary',
        'selesai' => 'text-bg-success',
        'dibatalkan' => 'text-bg-danger',
        default => 'text-bg-dark',
    };
}

function getKehadiranBadgeClass(string $status): string
{
    return $status === 'Hadir' ? 'text-bg-success' : 'text-bg-secondary';
}

function getMekanikBadgeClass(string $status): string
{
    return match ($status) {
        'sibuk' => 'text-bg-warning',
        'libur' => 'text-bg-secondary',
        default => 'text-bg-success',
    };
}

function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin']) && is_array($_SESSION['admin']);
}

function requireAdminAuth(): void
{
    if (!isAdminLoggedIn()) {
        redirectTo('index.php?page=login');
    }
}

function currentAdmin(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function normalizePhone(?string $phone): string
{
    $phone = trim((string) $phone);
    if ($phone === '') {
        return '';
    }

    return preg_replace('/\D+/', '', $phone);
}

function displayPhone(?string $phone): string
{
    $phone = normalizePhone($phone);
    if ($phone === '') {
        return '-';
    }

    if (str_starts_with($phone, '62')) {
        return $phone;
    }

    if (str_starts_with($phone, '0')) {
        return '62' . substr($phone, 1);
    }

    return $phone;
}

function isValidPhone(?string $phone): bool
{
    $phone = normalizePhone($phone);

    if ($phone === '') {
        return true;
    }

    return preg_match('/^(62|0)[0-9]{9,14}$/', $phone) === 1;
}

function isValidDateValue(?string $date): bool
{
    if (!$date) {
        return false;
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
}

function generatePelangganEmail(PDO $pdo, string $nama, string $whatsapp): string
{
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '.', $nama), '.'));
    if ($slug === '') {
        $slug = 'pelanggan';
    }

    $phonePart = preg_replace('/\D+/', '', $whatsapp);
    if ($phonePart === '') {
        $phonePart = (string) time();
    }

    $base = $slug . '.' . $phonePart;
    $email = $base . '@walkin.akasia.local';
    $counter = 1;

    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);

        if (!$stmt->fetch()) {
            return $email;
        }

        $counter++;
        $email = $base . $counter . '@walkin.akasia.local';
    }
}

function parseQueueNumber(?string $queueNumber): int
{
    $queueNumber = strtoupper(trim((string) $queueNumber));
    if (!preg_match('/^A(\d+)$/', $queueNumber, $matches)) {
        return 0;
    }

    return (int) $matches[1];
}

function formatQueueNumber(int $queueNumber): string
{
    return 'A' . str_pad((string) $queueNumber, 3, '0', STR_PAD_LEFT);
}

if (!function_exists('generateQueueNumber')) {
    function generateQueueNumber(PDO $pdo, string $tanggalServis): string
    {
        // Ambil angka tertinggi dari no_antrian (format A001 -> ambil 001)
        // yang statusnya bukan 'dibatalkan' pada tanggal tersebut
        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(no_antrian, 2) AS UNSIGNED)) AS max_nomor
            FROM reservasi
            WHERE tanggal_servis = :tanggal 
             AND status != 'dibatalkan'
             AND no_antrian LIKE 'A%'
        ");

        $stmt->execute([
            ':tanggal' => $tanggalServis
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Jika ada nomor sebelumnya, tambah 1. Jika belum ada (atau semua dibatalkan), mulai dari 1.
        if ($row && $row['max_nomor'] !== null) {
            $nomorBaru = (int)$row['max_nomor'] + 1;
        } else {
            $nomorBaru = 1;
        }

        // Format kembali menjadi A001, A002, dst.
        return 'A' . str_pad($nomorBaru, 3, '0', STR_PAD_LEFT);
    }
}

function buildPageUrl(array $params): string
{
    return 'index.php?' . http_build_query(array_filter($params, static fn($value) => $value !== null && $value !== ''));
}

function renderPagination(int $currentPage, int $totalPages, array $baseParams): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav class="pagination-wrap" aria-label="Pagination"><ul class="pagination mb-0">';

    $prevParams = $baseParams;
    $prevParams['p'] = max(1, $currentPage - 1);
    $html .= '<li class="page-item' . ($currentPage <= 1 ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . htmlspecialchars(buildPageUrl($prevParams)) . '"><i class="bi bi-chevron-left"></i></a></li>';

    for ($page = 1; $page <= $totalPages; $page++) {
        $pageParams = $baseParams;
        $pageParams['p'] = $page;
        $html .= '<li class="page-item' . ($page === $currentPage ? ' active' : '') . '">';
        $html .= '<a class="page-link" href="' . htmlspecialchars(buildPageUrl($pageParams)) . '">' . $page . '</a></li>';
    }

    $nextParams = $baseParams;
    $nextParams['p'] = min($totalPages, $currentPage + 1);
    $html .= '<li class="page-item' . ($currentPage >= $totalPages ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . htmlspecialchars(buildPageUrl($nextParams)) . '"><i class="bi bi-chevron-right"></i></a></li>';
    $html .= '</ul></nav>';

    return $html;
}

function getWhatsAppTemplate(PDO $pdo, string $kode): ?string
{
    $stmt = $pdo->prepare("SELECT isi_pesan FROM template_whatsapp WHERE kode_template = :kode AND is_active = 1 LIMIT 1");
    $stmt->execute([':kode' => $kode]);
    $row = $stmt->fetch();

    return $row['isi_pesan'] ?? null;
}

function renderTemplateMessage(string $template, array $data): string
{
    foreach ($data as $key => $value) {
        $template = str_replace('{' . $key . '}', (string) $value, $template);
    }

    return $template;
}

// Tambahkan parameter PDO $pdo ke dalam fungsi
function sendWhatsApp(PDO $pdo, string $phone, string $message): array
{
    // Ambil token secara dinamis dari database
    $stmt = $pdo->query("SELECT token_fonnte FROM pengaturan LIMIT 1");
    $pengaturan = $stmt->fetch();
    $token = $pengaturan['token_fonnte'] ?? '';

    if ($token === '' || $token === 'TOKEN_FONNTE_ANDA') {
        return ['success' => false, 'reason' => 'Token Fonnte belum dikonfigurasi di Menu Pengaturan.'];
    }

    $phone = displayPhone($phone);

    if ($phone === '-') {
        return ['success' => false, 'reason' => 'Nomor WhatsApp kosong.'];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: ' . $token],
        CURLOPT_POSTFIELDS => [
            'target' => $phone,
            'message' => $message,
            'countryCode' => '62',
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError !== '') {
        return ['success' => false, 'reason' => 'Curl error: ' . $curlError];
    }

    $data = json_decode((string) $response, true);

    if ($httpCode === 200 && isset($data['status']) && $data['status'] === true) {
        return ['success' => true, 'reason' => 'Pesan berhasil dikirim.'];
    }

    return ['success' => false, 'reason' => $data['reason'] ?? ('HTTP ' . $httpCode)];
}

function sendAndLogWhatsApp(PDO $pdo, int $reservasiId, string $phone, string $message, string $jenis, bool $failHard = true): bool
{
    // Pastikan variabel $pdo ikut dikirimkan di sini
    $result = sendWhatsApp($pdo, $phone, $message);

    if ($result['success'] === false && $failHard) {
        die("
            <div style='background: #fee2e2; border: 2px solid #ef4444; padding: 20px; margin: 20px; font-family: sans-serif; border-radius: 8px;'>
                <h2 style='color: #b91c1c; margin-top: 0;'>PENGIRIMAN WHATSAPP GAGAL!</h2>
                <p><strong>Alasan / Pesan Error:</strong><br> <span style='font-size: 18px; color: #7f1d1d;'>" . htmlspecialchars($result['reason']) . "</span></p>
                <hr style='border-color: #fca5a5;'>
                <p><em>*Silakan copy/foto pesan error di atas. Jika kamu menggunakan tombol back/kembali, data status mungkin tidak terupdate.</em></p>
            </div>
        ");
    }

    $status = $result['success'] ? 'Terkirim' : 'Gagal';

    $stmt = $pdo->prepare("
        INSERT INTO notifikasi_wa (reservasi_id, no_tujuan, pesan, jenis, status)
        VALUES (:reservasi_id, :no_tujuan, :pesan, :jenis, :status)
    ");
    $stmt->execute([
        ':reservasi_id' => $reservasiId,
        ':no_tujuan' => displayPhone($phone),
        ':pesan' => $message,
        ':jenis' => $jenis,
        ':status' => $status,
    ]);

    return $result['success'];
}

function buildAndSendTemplate(PDO $pdo, array $reservasi, string $kodeTemplate, string $jenisLog, array $data): bool
{
    $template = getWhatsAppTemplate($pdo, $kodeTemplate);
    if (!$template) {
        return false;
    }

    $message = renderTemplateMessage($template, $data);

    return sendAndLogWhatsApp($pdo, (int) $reservasi['id'], (string) ($reservasi['no_whatsapp'] ?? ''), $message, $jenisLog);
}

function shiftQueueAfterCancellation(PDO $pdo, array $reservasiDibatalkan): void
{
    $queueNumberLama = (string) ($reservasiDibatalkan['no_antrian'] ?? '');
    $tanggalServis = (string) ($reservasiDibatalkan['tanggal_servis'] ?? '');
    $queueValueLama = parseQueueNumber($queueNumberLama);
    $reservasiId = (int) ($reservasiDibatalkan['id'] ?? 0);

    if ($queueValueLama <= 0 || $tanggalServis === '' || $reservasiId <= 0) {
        return;
    }

    $nomorPengganti = 'X-' . $reservasiId;
    $releaseStmt = $pdo->prepare("
        UPDATE reservasi
        SET no_antrian = :no_antrian, status = 'dibatalkan'
        WHERE id = :id
    ");
    $releaseStmt->execute([
        ':no_antrian' => $nomorPengganti,
        ':id' => $reservasiId,
    ]);

    $shiftStmt = $pdo->prepare("
        SELECT 
            r.id,
            r.no_antrian,
            u.nama AS pelanggan,
            u.no_whatsapp
        FROM reservasi r
        INNER JOIN users u ON u.id = r.user_id
        WHERE r.tanggal_servis = :tanggal_servis
          AND r.status != 'dibatalkan'
          AND r.no_antrian LIKE 'A%'
          AND CAST(SUBSTRING(r.no_antrian, 2) AS UNSIGNED) > :queue_number
        ORDER BY CAST(SUBSTRING(r.no_antrian, 2) AS UNSIGNED) ASC
    ");
    $shiftStmt->execute([
        ':tanggal_servis' => $tanggalServis,
        ':queue_number' => $queueValueLama,
    ]);
    $antreanSesudahnya = $shiftStmt->fetchAll(PDO::FETCH_ASSOC);

    $updateStmt = $pdo->prepare("UPDATE reservasi SET no_antrian = :no_antrian WHERE id = :id");

    foreach ($antreanSesudahnya as $item) {
        $nomorLama = (string) ($item['no_antrian'] ?? '');
        $nilaiNomorLama = parseQueueNumber($nomorLama);
        if ($nilaiNomorLama <= 0) {
            continue;
        }

        $nomorBaru = formatQueueNumber($nilaiNomorLama - 1);
        $updateStmt->execute([
            ':no_antrian' => $nomorBaru,
            ':id' => (int) $item['id'],
        ]);

        $template = getWhatsAppTemplate($pdo, 'perubahan_nomor_antrean');
        $pesan = $template
            ? renderTemplateMessage($template, [
                'nama' => (string) ($item['pelanggan'] ?? 'Pelanggan'),
                'tanggal' => formatDateIndonesia($tanggalServis),
                'antrean_lama' => $nomorLama,
                'antrean_baru' => $nomorBaru,
            ])
            : "Kabar Baik! Antrean Anda maju dari {$nomorLama} menjadi {$nomorBaru}. Silakan cek antrean terbaru Anda di bengkel.";
        $terkirim = sendAndLogWhatsApp(
            $pdo,
            (int) $item['id'],
            (string) ($item['no_whatsapp'] ?? ''),
            $pesan,
            'Perubahan Status',
            false
        );

        if (!$terkirim) {
            throw new RuntimeException('Gagal mengirim notifikasi pergeseran antrean untuk pelanggan ' . ($item['pelanggan'] ?? '-'));
        }
    }
}

function getReminderTargetDates(PDO $pdo, ?DateTimeInterface $now = null): array
{
    $tz = new DateTimeZone('Asia/Jakarta');
    $moment = $now ? DateTimeImmutable::createFromInterface($now) : new DateTimeImmutable('now', $tz);
    $currentTime = $moment->format('H:i:s');

    // Ambil jam operasional (jam buka dan jam tutup) dari database
    $stmt = $pdo->query("SELECT jam_buka, jam_tutup FROM pengaturan LIMIT 1");
    $pengaturan = $stmt->fetch(PDO::FETCH_ASSOC);

    // Default jika kosong
    $jamBuka = $pengaturan['jam_buka'] ?? '08:00:00';
    $jamTutup = $pengaturan['jam_tutup'] ?? '17:00:00';

    $targets = [];

    // 1. Logika H-1: Jika waktu sekarang sudah melewati jam tutup, tarik jadwal besok
    if ($currentTime >= $jamTutup) {
        $targets[] = $moment->modify('+1 day')->format('Y-m-d');
    }

    // 2. Logika Hari H: Jika waktu sekarang sudah melewati jam buka, tarik jadwal hari ini
    if ($currentTime >= $jamBuka) {
        $targets[] = $moment->format('Y-m-d');
    }

    return $targets;
}

function processReminderJadwalServis(PDO $pdo, ?string $targetDate = null): array
{
    // Gunakan parameter atau ambil array target berdasarkan jam operasional
    $targetDates = $targetDate ? [$targetDate] : getReminderTargetDates($pdo);

    // Jika belum masuk jam operasional pengiriman (misal jam 6 pagi), hentikan proses
    if (empty($targetDates)) {
        return [
            'success' => false,
            'reason' => 'belum_waktu_pengiriman',
            'target_dates' => [],
            'sent' => 0,
            'failed' => 0,
        ];
    }

    // Buat placeholder query ( ?, ? ) sesuai jumlah tanggal
    $inQuery = implode(',', array_fill(0, count($targetDates), '?'));

    // Ambil format pesan dari database atau gunakan format fallback (sesuai Revisi 5)
    $template = getWhatsAppTemplate($pdo, 'reminder_jadwal_servis');
    if (!$template) {
        $template = "Halo Bapak/Ibu {nama},\n\nKami mengingatkan bahwa Anda memiliki jadwal servis kendaraan di Bengkel Akasia Motor.\n\nTanggal Servis: {tanggal}\nNomor Antrean: {antrean}\n\nMohon hadir sesuai jadwal yang telah ditentukan.\n\nTerima kasih.";
    }

    // Cari pelanggan di tanggal target yang belum dikirimi WA
    $sql = "
        SELECT r.id, r.no_antrian, r.tanggal_servis, u.nama, u.no_whatsapp, jl.nama AS layanan
        FROM reservasi r
        INNER JOIN users u ON u.id = r.user_id
        INNER JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
        WHERE r.tanggal_servis IN ($inQuery)
          AND r.status IN ('dikonfirmasi', 'menunggu_antrean', 'pending')
          AND COALESCE(r.reminder_status, 'belum_dikirim') = 'belum_dikirim'
        ORDER BY r.id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($targetDates);

    $sent = 0;
    $failed = 0;
    $processed = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $message = renderTemplateMessage($template, [
            'nama' => $row['nama'] ?? 'Pelanggan',
            'antrean' => $row['no_antrian'] ?? '-',
            'tanggal' => formatDateIndonesia($row['tanggal_servis'] ?? null),
            'layanan' => $row['layanan'] ?? '-',
        ]);

        // Proses kirim WhatsApp
        $ok = sendAndLogWhatsApp($pdo, (int) $row['id'], (string) ($row['no_whatsapp'] ?? ''), $message, 'Reminder Jadwal Servis', false);

        if ($ok) {
            $update = $pdo->prepare("
                UPDATE reservasi
                SET reminder_status = 'sudah_dikirim',
                    reminder_sent_at = NOW()
                WHERE id = :id
            ");
            $update->execute([':id' => (int) $row['id']]);
            $sent++;
        } else {
            $failed++;
        }

        $processed[] = [
            'id' => (int) $row['id'],
            'no_antrian' => (string) ($row['no_antrian'] ?? ''),
        ];
    }

    return [
        'success' => true,
        'reason' => 'processed',
        'target_dates' => $targetDates,
        'sent' => $sent,
        'failed' => $failed,
        'processed' => $processed,
    ];
}

function hitungEstimasiGiliranDinamis(PDO $pdo): array
{
    // 1. Ambil jumlah mekanik aktif (yang tidak sedang libur)
    $stmtMekanik = $pdo->query("SELECT COUNT(*) FROM mekanik WHERE status != 'libur'");
    $jumlahMekanik = (int) $stmtMekanik->fetchColumn();
    if ($jumlahMekanik <= 0) {
        $jumlahMekanik = 1; // Fallback jika data mekanik kosong
    }

    // 2. Ambil semua reservasi yang saat ini sedang dikerjakan (status = diproses)
    $stmtDiproses = $pdo->query("SELECT waktu_mulai_servis, estimasi_durasi FROM reservasi WHERE status = 'diproses' ORDER BY waktu_mulai_servis ASC");
    $listDiproses = $stmtDiproses->fetchAll(PDO::FETCH_ASSOC);

    $now = time();
    $waktuSelesaiMekanik = [];

    // Hitung sisa waktu pengerjaan untuk masing-masing mekanik yang sedang sibuk
    foreach ($listDiproses as $row) {
        $waktuMulai = strtotime($row['waktu_mulai_servis']);
        $durasiMenit = (int) $row['estimasi_durasi'];
        $waktuSelesai = $waktuMulai + ($durasiMenit * 60);

        $sisaMenit = (int) ceil(($waktuSelesai - $now) / 60);
        // Jika pengerjaan lewat dari estimasi tapi belum klik selesai, anggap sisa 0 menit (sebentar lagi selesai)
        if ($sisaMenit < 0) {
            $sisaMenit = 0;
        }

        $waktuSelesaiMekanik[] = $sisaMenit;
    }

    // Jika ada mekanik yang menganggur (jumlah mekanik aktif > jumlah yang sedang kerja), isi slot kosong dengan 0 menit
    while (count($waktuSelesaiMekanik) < $jumlahMekanik) {
        $waktuSelesaiMekanik[] = 0;
    }

    // Urutkan sisa waktu dari yang paling cepat selesai
    sort($waktuSelesaiMekanik);

    // 3. Ambil semua reservasi yang sedang mengantre (status = menunggu_antrean) berdasarkan urutan hadir/daftar
    $stmtAntrean = $pdo->query("
        SELECT id, estimasi_durasi 
        FROM reservasi 
        WHERE status = 'menunggu_antrean' 
        ORDER BY COALESCE(waktu_hadir, created_at) ASC, id ASC
    ");
    $listAntrean = $stmtAntrean->fetchAll(PDO::FETCH_ASSOC);

    $estimasiWaktuPerId = [];

    // Alokasikan antrean ke mekanik yang akan senggang paling cepat
    foreach ($listAntrean as $antrean) {
        // Reservasi ini harus menunggu selama waktu luang dari mekanik yang paling cepat selesai (indeks 0)
        $waktuTunggu = $waktuSelesaiMekanik[0];
        $estimasiWaktuPerId[$antrean['id']] = $waktuTunggu;

        // Setelah reservasi ini masuk ke mekanik tersebut, waktu luang mekanik tersebut bertambah sebesar durasi servis baru ini
        $waktuSelesaiMekanik[0] += (int) $antrean['estimasi_durasi'];

        // Urutkan kembali agar indeks 0 tetap menjadi mekanik yang paling cepat selesai berikutnya
        sort($waktuSelesaiMekanik);
    }

    return $estimasiWaktuPerId;
}
