<?php
if (!isset($_SESSION['user_id'])) {
    exit;
}

date_default_timezone_set('Asia/Jakarta');

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getActiveReservationBlockingMessage')) {
    function getActiveReservationBlockingMessage(array $reservasi): string
    {
        $status = (string) ($reservasi['status'] ?? '');
        $antrean = (string) ($reservasi['no_antrian'] ?? '-');
        $tanggal = !empty($reservasi['tanggal_servis']) ? date('d-m-Y', strtotime((string) $reservasi['tanggal_servis'])) : '-';

        return match ($status) {
            'menunggu_konfirmasi' => "Anda masih memiliki reservasi dengan nomor antrean {$antrean} pada tanggal {$tanggal} yang sedang menunggu konfirmasi admin. Selesaikan reservasi tersebut terlebih dahulu sebelum membuat reservasi baru.",
            'dikonfirmasi' => "Anda masih memiliki reservasi dengan nomor antrean {$antrean} pada tanggal {$tanggal} yang sudah dikonfirmasi. Selesaikan reservasi tersebut terlebih dahulu sebelum membuat reservasi baru.",
            'menunggu_antrean' => "Anda masih memiliki reservasi dengan nomor antrean {$antrean} pada tanggal {$tanggal} yang sedang menunggu antrean servis. Selesaikan reservasi tersebut terlebih dahulu sebelum membuat reservasi baru.",
            'diproses' => "Anda masih memiliki reservasi dengan nomor antrean {$antrean} pada tanggal {$tanggal} yang sedang diproses. Selesaikan reservasi tersebut terlebih dahulu sebelum membuat reservasi baru.",
            'pending' => "Anda masih memiliki reservasi dengan nomor antrean {$antrean} pada tanggal {$tanggal} yang berstatus pending. Selesaikan reservasi tersebut terlebih dahulu sebelum membuat reservasi baru.",
            default => "Anda masih memiliki reservasi aktif dengan nomor antrean {$antrean} pada tanggal {$tanggal}. Selesaikan reservasi tersebut terlebih dahulu sebelum membuat reservasi baru.",
        };
    }
}

$success = '';
$error = '';
$user_id = (int) $_SESSION['user_id'];

// Ambil LIST kendaraan milik user
$stmt_vehicle = $pdo->prepare("SELECT * FROM kendaraan WHERE user_id = :id");
$stmt_vehicle->execute([':id' => $user_id]);
$kendaraanList = $stmt_vehicle->fetchAll(PDO::FETCH_ASSOC);

// JIKA 0 Kendaraan, lempar ke form tambah kendaraan
if (count($kendaraanList) === 0) {
    header("Location: ?page=kendaraan&action=tambah&return_url=booking");
    exit;
}

$stmt_pengaturan = $pdo->query("SELECT jam_buka, jam_tutup, jam_istirahat_mulai, jam_istirahat_selesai, hari_operasional FROM pengaturan LIMIT 1");
$pengaturan = $stmt_pengaturan->fetch(PDO::FETCH_ASSOC) ?: [];

$jam_buka = $pengaturan['jam_buka'] ?? '08:00:00';
$jam_tutup = $pengaturan['jam_tutup'] ?? '17:00:00';
$jam_istirahat_mulai = $pengaturan['jam_istirahat_mulai'] ?? '12:00:00';
$jam_istirahat_selesai = $pengaturan['jam_istirahat_selesai'] ?? '13:00:00';
$hari_operasional = trim((string) ($pengaturan['hari_operasional'] ?? 'Setiap Hari'));

$today = date('Y-m-d');
$currentTimestamp = time();
$closingTimestampToday = strtotime($today . ' ' . $jam_tutup);

$min_date = ($closingTimestampToday !== false && $currentTimestamp >= $closingTimestampToday)
    ? date('Y-m-d', strtotime('+1 day'))
    : $today;

$jam_buka_label = date('H:i', strtotime($jam_buka));
$jam_tutup_label = date('H:i', strtotime($jam_tutup));
$jam_istirahat_mulai_label = date('H:i', strtotime($jam_istirahat_mulai));
$jam_istirahat_selesai_label = date('H:i', strtotime($jam_istirahat_selesai));

$stmt_user = $pdo->prepare("SELECT nama, no_whatsapp, alamat FROM users WHERE id = :id");
$stmt_user->execute([':id' => $user_id]);
$currentUser = $stmt_user->fetch(PDO::FETCH_ASSOC) ?: [];

$stmt_layanan = $pdo->query("SELECT id, nama, deskripsi, estimasi_durasi, estimasi_biaya_jasa, is_custom FROM jenis_layanan WHERE is_active = 1 ORDER BY id ASC");
$layanan = $stmt_layanan->fetchAll(PDO::FETCH_ASSOC);

$layananById = [];
foreach ($layanan as $item) {
    $layananById[(int) $item['id']] = $item;
}

$stmt_kegiatan = $pdo->query("SELECT id, jenis_layanan_id, nama_kegiatan, estimasi_durasi, estimasi_biaya FROM kegiatan_servis WHERE is_active = 1 ORDER BY jenis_layanan_id ASC, id ASC");
$kegiatanServisList = $stmt_kegiatan->fetchAll(PDO::FETCH_ASSOC);

$kegiatanByLayanan = [];
$kegiatanById = [];
foreach ($kegiatanServisList as $kegiatan) {
    $kegiatanByLayanan[(int) $kegiatan['jenis_layanan_id']][] = $kegiatan;
    $kegiatanById[(int) $kegiatan['id']] = $kegiatan;
}

if (!function_exists('getOperationalMinutes')) {
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
}

if (!function_exists('getOperationalDayNumbers')) {
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
}

if (!function_exists('createWorkshopDateTime')) {
    function createWorkshopDateTime(DateTimeZone $tz, string $date, ?string $time): ?DateTimeImmutable
    {
        if (!$time) {
            return null;
        }

        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time, $tz);

        return $dateTime ?: null;
    }
}

if (!function_exists('moveToNextOperationalStart')) {
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
}

if (!function_exists('calculateOperationalEndTime')) {
    function calculateOperationalEndTime(array $schedule, DateTimeInterface $startTime, int $durationMinutes): DateTimeImmutable
    {
        $tz = new DateTimeZone('Asia/Jakarta');
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
}

if (!function_exists('getBookedMinutesByDate')) {
    function getBookedMinutesByDate(PDO $pdo, string $tanggal): int
    {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(COALESCE(NULLIF(r.estimasi_durasi, 0), jl.estimasi_durasi, 0)), 0)
            FROM reservasi r
            LEFT JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
            WHERE r.tanggal_servis = :tgl
              AND r.status IN ('menunggu_konfirmasi', 'dikonfirmasi', 'menunggu_antrean', 'diproses', 'pending')
        ");
        $stmt->execute([':tgl' => $tanggal]);

        return (int) $stmt->fetchColumn();
    }
}

if (!function_exists('getFullyBookedDates')) {
    function getFullyBookedDates(PDO $pdo, string $startDate, string $endDate, int $capacityMinutes): array
    {
        if ($capacityMinutes <= 0) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT tanggal_servis, COALESCE(SUM(estimasi_durasi), 0) AS used_minutes
            FROM reservasi
            WHERE tanggal_servis BETWEEN :start_date AND :end_date
              AND status IN ('menunggu_konfirmasi', 'dikonfirmasi', 'menunggu_antrean', 'diproses', 'pending')
            GROUP BY tanggal_servis
        ");
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);

        $fullDates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ((int) $row['used_minutes'] >= $capacityMinutes) {
                $fullDates[] = $row['tanggal_servis'];
            }
        }

        return $fullDates;
    }
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

// 1. Ambil Waktu Operasional untuk 1 Mekanik
$operationalMinutes = getOperationalMinutes($pdo);

// 2. Ambil Jumlah Mekanik yang sedang tidak libur
$stmtMekanik = $pdo->query("SELECT COUNT(id) AS total_mekanik FROM mekanik WHERE status != 'libur'");
$rowMekanik = $stmtMekanik->fetch(PDO::FETCH_ASSOC);
$jumlahMekanik = (int) ($rowMekanik['total_mekanik'] ?? 1);
if ($jumlahMekanik <= 0) $jumlahMekanik = 1;

// 3. Kalikan untuk mendapatkan Total Kapasitas Aktual (Misal: 480 x 4 = 1920)
$totalCapacityMinutes = $operationalMinutes * $jumlahMekanik;

$bookingCapacityInfo = null;
$bookingAdjustedEstimate = null;
$activeReservationNotice = '';

$selectedTanggalServis = trim($_POST['tanggal_servis'] ?? '');
$bookingHorizonDays = 90;
// Gunakan $totalCapacityMinutes untuk mengecek tanggal yang penuh
$bookingFullDates = getFullyBookedDates($pdo, $today, date('Y-m-d', strtotime('+' . $bookingHorizonDays . ' days')), $totalCapacityMinutes);

$stmt_active_reservasi = $pdo->prepare("
    SELECT id, no_antrian, status, tanggal_servis
    FROM reservasi
    WHERE user_id = :uid
      AND status NOT IN ('selesai', 'dibatalkan')
    ORDER BY created_at ASC, id ASC
    LIMIT 1
");
$stmt_active_reservasi->execute([':uid' => $user_id]);
$activeReservasi = $stmt_active_reservasi->fetch(PDO::FETCH_ASSOC) ?: null;
$bookingFormDisabled = $activeReservasi !== null;
if ($bookingFormDisabled) {
    $activeReservationNotice = getActiveReservationBlockingMessage($activeReservasi);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal_servis = $selectedTanggalServis;
    $jenis_layanan_id = (int) ($_POST['jenis_layanan_id'] ?? 0);
    $kendaraan_id = (int) ($_POST['kendaraan_id'] ?? 0);
    $keluhan = trim($_POST['keluhan'] ?? '');

    // Validasi Kendaraan
    $stmt_vk = $pdo->prepare("SELECT * FROM kendaraan WHERE id = ? AND user_id = ?");
    $stmt_vk->execute([$kendaraan_id, $user_id]);
    $dipilih = $stmt_vk->fetch(PDO::FETCH_ASSOC);

    $selectedLayanan = null;
    foreach ($layanan as $item) {
        if ((int) $item['id'] === $jenis_layanan_id) {
            $selectedLayanan = $item;
            break;
        }
    }

    $selectedKegiatanIds = array_values(array_filter(array_map('intval', $_POST['kegiatan_ids'] ?? [])));
    $isServisUmum = $selectedLayanan ? (bool) $selectedLayanan['is_custom'] : false;

    $bookingDuration = 0;

    if (!$dipilih) {
        $error = 'Kendaraan yang dipilih tidak valid.';
    } elseif ($activeReservasi) {
        $error = getActiveReservationBlockingMessage($activeReservasi);
    } elseif (empty($tanggal_servis) || $jenis_layanan_id <= 0) {
        $error = 'Semua field yang bertanda * wajib diisi.';
    } elseif ($tanggal_servis < $min_date) {
        $error = 'Tanggal servis tidak valid. Silakan pilih tanggal yang masih tersedia.';
    } elseif (!$selectedLayanan) {
        $error = 'Jenis layanan servis tidak valid. Silakan pilih ulang.';
    } else {
        try {
            $kegiatanTerpilih = [];

            if ($isServisUmum) {
                $availableKegiatan = $kegiatanByLayanan[$jenis_layanan_id] ?? [];
                if (empty($availableKegiatan)) {
                    $availableKegiatan = $kegiatanServisList;
                }

                $availableIds = array_map(static fn($item) => (int) $item['id'], $availableKegiatan);
                $selectedKegiatanIds = array_values(array_intersect($selectedKegiatanIds, $availableIds));

                if (empty($selectedKegiatanIds)) {
                    $error = 'Silakan pilih minimal satu kegiatan servis untuk Servis Umum.';
                } else {
                    foreach ($selectedKegiatanIds as $kegiatanId) {
                        if (isset($kegiatanById[$kegiatanId])) {
                            $kegiatanTerpilih[] = $kegiatanById[$kegiatanId];
                        }
                    }
                }
            } else {
                // Paket Servis Ringan/Sedang/Berat/Super Berat
                $kegiatanTerpilih = $kegiatanByLayanan[$jenis_layanan_id] ?? [];
                $selectedKegiatanIds = array_map(
                    static fn($item) => (int) $item['id'],
                    $kegiatanTerpilih
                );

                $estimasi_durasi = (int) $selectedLayanan['estimasi_durasi'];
                $biaya_jasa = (float) $selectedLayanan['estimasi_biaya_jasa'];
            }

            if ($isServisUmum) {
                $estimasi_durasi = array_sum(
                    array_map(
                        static fn($item) => (int) $item['estimasi_durasi'],
                        $kegiatanTerpilih
                    )
                );
                $biaya_jasa = array_sum(
                    array_map(
                        static fn($item) => (float) $item['estimasi_biaya'],
                        $kegiatanTerpilih
                    )
                );
            }

            $bookingDuration = (int) $estimasi_durasi;
            $usedMinutes = 0;
            $remainingMinutes = $totalCapacityMinutes;

            // Validasi sisa kapasitas menggunakan $totalCapacityMinutes
            if (empty($error) && $totalCapacityMinutes > 0) {
                $usedMinutes = getBookedMinutesByDate($pdo, $tanggal_servis);
                $remainingMinutes = max(0, $totalCapacityMinutes - $usedMinutes);

                $bookingCapacityInfo = [
                    'tanggal' => $tanggal_servis,
                    'used' => $usedMinutes,
                    'remaining' => $remainingMinutes,
                    'capacity' => $totalCapacityMinutes,
                    'next_total' => $usedMinutes + $bookingDuration,
                    'allowed' => ($usedMinutes + $bookingDuration) <= $totalCapacityMinutes,
                ];

                if (!$bookingCapacityInfo['allowed']) {
                    $error = 'Kuota reservasi harian untuk tanggal ' . date('d-m-Y', strtotime($tanggal_servis)) . ' sudah penuh. Sisa kapasitas hanya ' . $remainingMinutes . ' menit dari total ' . $totalCapacityMinutes . ' menit pada jam operasional bengkel hari ini.';
                }
            }

            if (empty($error) && $bookingDuration > 0 && $tanggal_servis !== '') {
                $schedule = [
                    'jam_buka' => $jam_buka,
                    'jam_tutup' => $jam_tutup,
                    'jam_istirahat_mulai' => $jam_istirahat_mulai,
                    'jam_istirahat_selesai' => $jam_istirahat_selesai,
                    'hari_operasional' => $hari_operasional,
                ];
                $startEstimate = ($tanggal_servis === $today)
                    ? new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'))
                    : new DateTimeImmutable($tanggal_servis . ' ' . $jam_buka, new DateTimeZone('Asia/Jakarta'));
                $bookingAdjustedEstimate = calculateOperationalEndTime($schedule, $startEstimate, $bookingDuration);
            }

            if (empty($error)) {
                $pdo->beginTransaction();

                $no_antrian = generateQueueNumber($pdo, $tanggal_servis);

                // Ambil data kendaraan yang dipilih dari tabel kendaraan
                $no_plat = $dipilih['no_plat'];
                $jenis_kendaraan = trim(($dipilih['merk_kendaraan'] ?? '') . ' - ' . ($dipilih['jenis_kendaraan'] ?? ''), ' -');
                $tipe_model = $dipilih['model_kendaraan'];
                $tahun = !empty($dipilih['tahun_kendaraan']) ? (int) $dipilih['tahun_kendaraan'] : null;

                $sql_res = "INSERT INTO reservasi (
                    no_antrian,
                    user_id,
                    mekanik_id,
                    jenis_layanan_id,
                    jenis_reservasi,
                    no_plat,
                    jenis_kendaraan,
                    tipe_model,
                    tahun,
                    keluhan,
                    catatan_tambahan,
                    biaya_jasa,
                    total_biaya,
                    estimasi_durasi,
                    status,
                    tanggal_servis
                ) VALUES (
                    :no_a,
                    :uid,
                    :mekanik_id,
                    :jasa,
                    'Online',
                    :plat,
                    :kendaraan,
                    :tipe,
                    :tahun,
                    :keluhan,
                    :catatan_tambahan,
                    :biaya_jasa,
                    :total_biaya,
                    :estimasi_durasi,
                    'menunggu_konfirmasi',
                    :tgl
                )";

                $stmt_res = $pdo->prepare($sql_res);
                $stmt_res->execute([
                    ':no_a' => $no_antrian,
                    ':uid' => $user_id,
                    ':mekanik_id' => null,
                    ':jasa' => $jenis_layanan_id,
                    ':plat' => $no_plat,
                    ':kendaraan' => $jenis_kendaraan,
                    ':tipe' => $tipe_model,
                    ':tahun' => $tahun,
                    ':keluhan' => $keluhan,
                    ':catatan_tambahan' => $keluhan,
                    ':biaya_jasa' => $biaya_jasa,
                    ':total_biaya' => $biaya_jasa,
                    ':estimasi_durasi' => $estimasi_durasi,
                    ':tgl' => $tanggal_servis
                ]);

                $reservasi_id = (int) $pdo->lastInsertId();

                if (!empty($selectedKegiatanIds)) {
                    $stmt_keg = $pdo->prepare("INSERT INTO reservasi_kegiatan (reservasi_id, kegiatan_servis_id) VALUES (:res_id, :keg_id)");
                    foreach ($selectedKegiatanIds as $keg_id) {
                        $stmt_keg->execute([
                            ':res_id' => $reservasi_id,
                            ':keg_id' => (int) $keg_id
                        ]);
                    }
                }

                $pdo->commit();

                $success = "Reservasi sukses! Nomor antrean Anda: <strong>" . e($no_antrian) . "</strong>";
                if ($bookingAdjustedEstimate instanceof DateTimeImmutable) {
                    $success .= "<br>Estimasi selesai operasional: <strong>" . e(formatTanggal($bookingAdjustedEstimate->format('Y-m-d')) . ' ' . $bookingAdjustedEstimate->format('H:i') . ' WIB') . "</strong>";
                }

                try {
                    $stmt_pengaturan = $pdo->query("SELECT no_whatsapp, token_fonnte FROM pengaturan LIMIT 1");
                    $pengaturan = $stmt_pengaturan->fetch(PDO::FETCH_ASSOC) ?: [];
                    $adminPhone = $pengaturan['no_whatsapp'] ?? '';
                    $tokenFonnte = $pengaturan['token_fonnte'] ?? '';

                    $stmt_tpl = $pdo->query("SELECT isi_pesan FROM template_whatsapp WHERE kode_template = 'notif_admin_reservasi_baru' AND is_active = 1 LIMIT 1");
                    $template = $stmt_tpl->fetchColumn();

                    if ($adminPhone && $tokenFonnte && $template) {
                        $namaPelanggan = $currentUser['nama'] ?? 'Pelanggan';
                        $kendaraan = $tipe_model !== '' ? $tipe_model : 'Kendaraan';
                        $tglFormat = date('d-m-Y', strtotime($tanggal_servis));
                        $host = 'akasia-motor.my.id';
                        $linkKonfirmasi = 'https://' . $host . '/admin/index.php?page=status_servis&stage=menunggu_konfirmasi&id=' . $reservasi_id;

                        $msgAdmin = str_replace(
                            ['{nama}', '{kendaraan}', '{no_plat}', '{layanan}', '{tanggal}', '{link}'],
                            [$namaPelanggan, $kendaraan, $no_plat, $selectedLayanan['nama'], $tglFormat, $linkKonfirmasi],
                            $template
                        );

                        $ch = curl_init();
                        curl_setopt_array($ch, [
                            CURLOPT_URL => 'https://api.fonnte.com/send',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => true,
                            CURLOPT_HTTPHEADER => ['Authorization: ' . $tokenFonnte],
                            CURLOPT_POSTFIELDS => [
                                'target' => $adminPhone,
                                'message' => $msgAdmin,
                                'countryCode' => '62'
                            ],
                            CURLOPT_TIMEOUT => 10,
                            CURLOPT_SSL_VERIFYPEER => false
                        ]);

                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        $statusLog = ($httpCode == 200) ? 'Terkirim' : 'Gagal';
                        $stmt_log = $pdo->prepare("INSERT INTO notifikasi_wa (reservasi_id, no_tujuan, pesan, jenis, status) VALUES (:res_id, :tujuan, :pesan, 'Notifikasi Admin', :status)");
                        $stmt_log->execute([
                            ':res_id' => $reservasi_id,
                            ':tujuan' => $adminPhone,
                            ':pesan' => $msgAdmin,
                            ':status' => $statusLog
                        ]);
                    }
                } catch (Exception $e) {
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Terjadi kesalahan database: ' . $e->getMessage();
        }
    }
}

$selectedLayananId = (int) ($_POST['jenis_layanan_id'] ?? 0);
$selectedKendaraanId = (int) ($_POST['kendaraan_id'] ?? 0);
?>

<style>
    .hidden-kegiatan {
        display: none !important;
    }

    .layanan-detail {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.85rem;
        padding: 1rem;
        margin-top: 0.75rem;
    }

    .layanan-detail ul {
        margin-bottom: 0;
        padding-left: 1.1rem;
    }
</style>

<div class="mb-4">
    <h3 class="fw-bold text-primary"><i class="bi bi-calendar-plus"></i> Reservasi Servis</h3>
    <p class="text-muted">Lengkapi formulir di bawah ini untuk mengatur jadwal servis kendaraan Anda.</p>
</div>

<div class="row">
    <div class="col-md-10 col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 bg-white rounded-3">

                <?php if ($error): ?>
                    <div class="alert alert-danger p-3 small"><i class="bi bi-exclamation-triangle"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success p-3 small">
                        <i class="bi bi-check-circle"></i> <?= $success ?>
                        <br><br>
                        <a href="?page=dashboard" class="btn btn-sm btn-success rounded-pill">Lihat Dasbor</a>
                    </div>
                <?php endif; ?>

                <?php if (!$success && $bookingFormDisabled && $activeReservationNotice !== ''): ?>
                    <div class="alert alert-warning p-3 small">
                        <i class="bi bi-info-circle"></i> <?= e($activeReservationNotice) ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h6 class="fw-bold text-secondary mb-0">Data Pelanggan</h6>
                    <a href="?page=profil" class="btn btn-sm btn-outline-primary rounded-pill py-0" style="font-size: 0.8rem;">
                        <i class="bi bi-pencil-square"></i> Ubah Data
                    </a>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-semibold">Nama Pelanggan</label>
                        <input type="text" class="form-control bg-light text-muted" value="<?= e($currentUser['nama'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-semibold">Nomor WhatsApp</label>
                        <input type="text" class="form-control bg-light text-muted" value="<?= e($currentUser['no_whatsapp'] ?? '') ?>" readonly>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Alamat Lengkap</label>
                    <textarea class="form-control bg-light text-muted" rows="2" readonly><?= e($currentUser['alamat'] ?? '') ?></textarea>
                    <div class="form-text small text-primary"><i class="bi bi-info-circle"></i> Data diambil dari akun Anda. Klik <strong>Ubah Data</strong> di atas jika ada perubahan.</div>
                </div>

                <a href="?page=profil" class="btn btn-sm btn-outline-primary rounded-pill py-0" style="font-size: 0.8rem;">
                    <i class="bi bi-person-badge"></i> Lihat Detail Akun
                </a>

                <?php if (!$success): ?>
                    <form method="POST" action="">
                        <fieldset <?= $bookingFormDisabled ? 'disabled' : '' ?>>
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <div class="col-12 mt-4">
                                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-2">Data Kendaraan</h6>
                                <small class="text-muted">
                                    <i class="bi bi-info-square-fill text-primary me-1"></i> Akasia Motor saat ini hanya melayani servis dan perawatan sepeda motor berbahan bakar bensin. Servis sepeda motor listrik belum tersedia.
                                </small>
                            </div>
                        </div>

                        <h6 class="fw-bold border-bottom pb-2 mb-3 mt-4 text-secondary">Pilih Kendaraan Anda</h6>
                        <div class="mb-4">
                            <select name="kendaraan_id" class="form-select form-select-lg" required>
                                <option value="">-- Pilih Kendaraan yang Akan Diservis --</option>
                                <?php foreach ($kendaraanList as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= $selectedKendaraanId === (int)$k['id'] ? 'selected' : '' ?>>
                                        <?= e($k['merk_kendaraan'] . ' ' . $k['model_kendaraan'] . ' - ' . $k['no_plat']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small mt-2">
                                Ingin servis kendaraan lain? <a href="?page=kendaraan&action=tambah&return_url=booking" class="fw-bold text-decoration-none">Tambah kendaraan baru di sini</a>.
                            </div>
                        </div>

                        <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">Data Reservasi</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-semibold">Tanggal Servis *</label>
                                <input type="date" name="tanggal_servis" id="tanggal_servis" class="form-control" required min="<?= e($min_date) ?>" value="<?= e($_POST['tanggal_servis'] ?? '') ?>" data-full-dates='<?= e(json_encode($bookingFullDates)) ?>' data-capacity-minutes="<?= (int) $totalCapacityMinutes ?>">
                                <div class="form-text small">
                                    Silakan pilih tanggal kedatangan.
                                    <span class="text-muted d-block mt-1">Kapasitas dihitung dari total estimasi antrean aktif pada tanggal yang dipilih.</span>
                                    <span class="text-muted d-block mt-1">Jam operasional: <?= e($jam_buka_label) ?> - <?= e($jam_tutup_label) ?></span>
                                    <span class="text-muted d-block mt-1">Istirahat: <?= e($jam_istirahat_mulai_label) ?> - <?= e($jam_istirahat_selesai_label) ?></span>
                                    <?php if ($min_date !== date('Y-m-d')): ?>
                                        <span class="text-danger fw-semibold d-block mt-1">Pemesanan untuk hari ini sudah ditutup karena jam operasional telah berakhir.</span>
                                    <?php endif; ?>
                                </div>
                                <div id="tanggal-servis-status" class="small mt-2 d-none"></div>

                                <?php if (!empty($bookingCapacityInfo) && !empty($bookingCapacityInfo['tanggal'])): ?>
                                    <div class="alert <?= !empty($bookingCapacityInfo['allowed']) ? 'alert-info' : 'alert-warning' ?> p-3 small mt-3 mb-0">
                                        <div class="fw-semibold mb-1">Kapasitas antrean tanggal <?= e(date('d-m-Y', strtotime($bookingCapacityInfo['tanggal']))) ?></div>
                                        <div>Total kapasitas: <?= (int) $bookingCapacityInfo['capacity'] ?> menit</div>
                                        <div>Terpakai: <?= (int) $bookingCapacityInfo['used'] ?> menit</div>
                                        <div>Sisa kapasitas: <?= (int) $bookingCapacityInfo['remaining'] ?> menit</div>
                                        <?php if ($bookingAdjustedEstimate instanceof DateTimeImmutable): ?>
                                            <div>Estimasi selesai operasional: <?= e(formatTanggal($bookingAdjustedEstimate->format('Y-m-d')) . ' ' . $bookingAdjustedEstimate->format('H:i') . ' WIB') ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($bookingCapacityInfo['allowed'])): ?>
                                            <div class="text-success fw-semibold mt-1">Reservasi masih bisa diterima untuk tanggal ini.</div>
                                        <?php else: ?>
                                            <div class="text-danger fw-semibold mt-1">Tanggal ini sudah penuh untuk estimasi servis yang dipilih.</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">Layanan Servis</h6>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Jenis Layanan Servis *</label>
                            <select name="jenis_layanan_id" class="form-select layanan-selector" required>
                                <option value="">-- Pilih Jenis Servis --</option>
                                <?php foreach ($layanan as $item): ?>
                                    <option value="<?= (int) $item['id'] ?>" <?= $selectedLayananId === (int) $item['id'] ? 'selected' : '' ?>>
                                        <?= e($item['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">Setelah dipilih, rincian pekerjaan dari master kegiatan servis akan muncul di bawah.</div>
                        </div>

                        <div id="layanan-detail-list">
                            <?php foreach ($layanan as $item): ?>
                                <?php
                                $kegiatanList = $kegiatanByLayanan[(int) $item['id']] ?? [];
                                $isUmum = (int)$item['is_custom'] === 1;
                                $selectedCheckboxIds = array_map('intval', $_POST['kegiatan_ids'] ?? []);
                                $initialDurasi = 0;
                                $initialBiaya = 0;

                                if ($isUmum) {
                                    foreach ($kegiatanList as $kegiatan) {
                                        if (in_array((int) $kegiatan['id'], $selectedCheckboxIds, true)) {
                                            $initialDurasi += (int) $kegiatan['estimasi_durasi'];
                                            $initialBiaya += (float) $kegiatan['estimasi_biaya'];
                                        }
                                    }
                                }
                                ?>

                                <div class="layanan-detail hidden-kegiatan" data-layanan-id="<?= (int) $item['id'] ?>" data-is-umum="<?= $isUmum ? 'true' : 'false' ?>">
                                    <div class="d-flex align-items-start gap-2 mb-2">
                                        <i class="bi bi-tools text-primary"></i>
                                        <div>
                                            <h6 class="mb-1 fw-bold text-secondary"><?= e($item['nama']) ?></h6>
                                            <?php if (!empty($item['deskripsi'])): ?>
                                                <p class="mb-0 small text-muted"><?= e($item['deskripsi']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($isUmum): ?>
                                        <div class="small fw-semibold text-primary mb-2">Pilih kegiatan yang dibutuhkan:</div>
                                        <?php if (!empty($kegiatanList)): ?>
                                            <div class="checklist-box">
                                                <div class="row">
                                                    <?php foreach ($kegiatanList as $kegiatan): ?>
                                                        <div class="col-md-6 mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input js-kegiatan-checkbox"
                                                                    type="checkbox"
                                                                    name="kegiatan_ids[]"
                                                                    value="<?= (int) $kegiatan['id'] ?>"
                                                                    id="keg_<?= (int) $kegiatan['id'] ?>"
                                                                    data-durasi="<?= (int) $kegiatan['estimasi_durasi'] ?>"
                                                                    data-biaya="<?= (float) $kegiatan['estimasi_biaya'] ?>"
                                                                    <?= in_array((int) $kegiatan['id'], $selectedCheckboxIds, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label small" for="keg_<?= (int) $kegiatan['id'] ?>">
                                                                    <?= e($kegiatan['nama_kegiatan']) ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                            <div class="estimasi-panel">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <div class="small fw-semibold text-secondary mb-1">Estimasi Durasi</div>
                                                        <div class="fw-bold text-dark"><span class="js-total-durasi"><?= (int) $initialDurasi ?></span> Menit</div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="text-secondary small mb-1 fw-semibold">Estimasi Biaya Jasa</div>
                                                        <div class="fw-bold fs-4 text-primary">Rp <span class="js-total-biaya">0</span></div>
                                                        <small class="text-muted d-block" style="font-size: 11px; max-width: 220px;">
                                                            Estimasi biaya diatas hanyalah estimasi biaya jasa saja, belum termasuk biaya part jika diperlukan pergantian part.
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-text small mt-2">Estimasi di atas akan mengikuti checklist yang dipilih.</div>
                                        <?php else: ?>
                                            <div class="small text-muted">Belum ada data kegiatan servis umum.</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="small fw-semibold text-primary mb-2">Apa saja yang dilakukan:</div>
                                        <?php if (!empty($kegiatanList)): ?>
                                            <ul class="small text-dark">
                                                <?php foreach ($kegiatanList as $kegiatan): ?>
                                                    <li><?= e($kegiatan['nama_kegiatan']) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <div class="estimasi-panel mt-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <div class="small fw-semibold text-secondary mb-1">Estimasi Durasi</div>
                                                        <div class="fw-bold text-dark"><?= (int) $item['estimasi_durasi'] ?> Menit</div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="small fw-semibold text-secondary mb-1">Estimasi Biaya Jasa</div>
                                                        <div class="fw-bold text-primary fs-5">Rp <?= number_format((float) $item['estimasi_biaya_jasa'], 0, ',', '.') ?></div>
                                                        <small class="text-muted d-block" style="font-size: 11px; max-width: 220px;">
                                                            Estimasi biaya diatas hanyalah estimasi biaya jasa saja, belum termasuk biaya part jika diperlukan pergantian part.
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="small text-muted">Belum ada data kegiatan servis untuk layanan ini.</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-4 mt-3">
                            <label class="form-label small fw-semibold">Catatan Tambahan / Keluhan</label>
                            <textarea name="keluhan" class="form-control" rows="4" placeholder="Jelaskan kendala kendaraan Anda, suara aneh, getaran, atau hal lain yang perlu diperiksa..."><?= e($_POST['keluhan'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm py-2" <?= $bookingFormDisabled ? 'disabled' : '' ?>>
                            Buat Reservasi
                        </button>
                        </fieldset>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const layananSelector = document.querySelector('.layanan-selector');
        const tanggalInput = document.getElementById('tanggal_servis');
        const tanggalStatus = document.getElementById('tanggal-servis-status');
        const fullDates = tanggalInput ? JSON.parse(tanggalInput.getAttribute('data-full-dates') || '[]') : [];
        const fullDateSet = new Set(fullDates);

        function validateTanggalServis() {
            if (!tanggalInput) {
                return true;
            }
            const selectedDate = tanggalInput.value;
            const isFull = selectedDate && fullDateSet.has(selectedDate);

            if (isFull) {
                tanggalInput.setCustomValidity('Tanggal ini sudah penuh. Silakan pilih tanggal lain.');
                tanggalInput.classList.add('is-invalid');
                tanggalInput.title = 'Tanggal ini sudah penuh. Silakan pilih tanggal lain.';
                if (tanggalStatus) {
                    tanggalStatus.textContent = 'Tanggal ini sudah penuh. Silakan pilih tanggal lain.';
                    tanggalStatus.classList.remove('d-none');
                    tanggalStatus.classList.remove('text-success');
                    tanggalStatus.classList.add('text-danger', 'fw-semibold');
                }
                return false;
            }

            tanggalInput.setCustomValidity('');
            tanggalInput.classList.remove('is-invalid');
            tanggalInput.removeAttribute('title');
            if (tanggalStatus) {
                tanggalStatus.textContent = '';
                tanggalStatus.classList.add('d-none');
                tanggalStatus.classList.remove('text-danger', 'fw-semibold');
            }
            return true;
        }

        function toggleLayananDetail() {
            const selectedValue = layananSelector ? layananSelector.value : '';
            document.querySelectorAll('.layanan-detail').forEach(function(detail) {
                const layananId = detail.getAttribute('data-layanan-id');
                const isUmum = detail.getAttribute('data-is-umum') === 'true';

                if (selectedValue !== '' && layananId === selectedValue) {
                    detail.classList.remove('hidden-kegiatan');
                    if (isUmum) {
                        const updateTotal = function() {
                            calculateGeneralServiceTotal(detail);
                        };
                        detail.querySelectorAll('.js-kegiatan-checkbox').forEach(function(checkbox) {
                            if (!checkbox.dataset.totalBound) {
                                checkbox.addEventListener('change', updateTotal);
                                checkbox.dataset.totalBound = '1';
                            }
                        });
                        calculateGeneralServiceTotal(detail);
                    }
                } else {
                    detail.classList.add('hidden-kegiatan');
                }
            });
        }

        function calculateGeneralServiceTotal(detailElement) {
            if (!detailElement) {
                return;
            }
            let totalDurasi = 0;
            let totalBiaya = 0;

            detailElement.querySelectorAll('.js-kegiatan-checkbox').forEach(function(checkbox) {
                if (checkbox.checked) {
                    totalDurasi += parseInt(checkbox.getAttribute('data-durasi') || 0, 10);
                    totalBiaya += parseFloat(checkbox.getAttribute('data-biaya') || 0);
                }
            });

            const durasiElement = detailElement.querySelector('.js-total-durasi');
            const biayaElement = detailElement.querySelector('.js-total-biaya');

            if (durasiElement) {
                durasiElement.textContent = totalDurasi;
            }
            if (biayaElement) {
                biayaElement.textContent = new Intl.NumberFormat('id-ID').format(totalBiaya);
            }
        }

        if (layananSelector) {
            layananSelector.addEventListener('change', toggleLayananDetail);
            toggleLayananDetail();
        }

        if (tanggalInput) {
            tanggalInput.addEventListener('change', validateTanggalServis);
            tanggalInput.addEventListener('input', validateTanggalServis);
            validateTanggalServis();
        }

        const bookingForm = document.querySelector('form[method="POST"]');
        if (bookingForm) {
            bookingForm.addEventListener('submit', function(event) {
                if (!validateTanggalServis()) {
                    event.preventDefault();
                    if (tanggalInput) {
                        tanggalInput.reportValidity();
                    }
                }
            });
        }
    });
</script>
