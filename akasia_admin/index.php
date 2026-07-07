<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

$page = $_GET['page'] ?? 'dashboard';
$allowedPages = [
    'login',
    'dashboard',
    'reservasi',
    'reservasi_walkin',
    'detail_reservasi',
    'status_servis',
    'mekanik',
    'rekap_mekanik',
    'jenis_layanan',
    'kegiatan_servis',
    'notifikasi',
    'pengaturan',
    'template_notifikasi',
    'owner_dashboard',
];
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

function findReservasi(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT 
            r.*, 
            u.nama AS pelanggan, 
            u.no_whatsapp, 
            u.alamat,
            k.merk_kendaraan AS kendaraan_merk,
            k.jenis_kendaraan AS kendaraan_jenis,
            k.model_kendaraan AS kendaraan_model,
            k.tahun_kendaraan AS kendaraan_tahun,
            k.no_plat AS kendaraan_no_plat,
            k.no_rangka AS kendaraan_no_rangka,
            k.no_mesin AS kendaraan_no_mesin,
            jl.nama AS layanan, 
            jl.is_custom, -- TAMBAHKAN BARIS INI
            m.nama AS mekanik_nama
        FROM reservasi r
        INNER JOIN users u ON u.id = r.user_id
        LEFT JOIN kendaraan k ON k.user_id = r.user_id
        INNER JOIN jenis_layanan jl ON jl.id = r.jenis_layanan_id
        LEFT JOIN mekanik m ON m.id = r.mekanik_id
        WHERE r.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);

    return $stmt->fetch() ?: null;
}

function updateMekanikStatus(PDO $pdo, ?int $mekanikId, string $status): void
{
    if (!$mekanikId) {
        return;
    }

    $stmt = $pdo->prepare("UPDATE mekanik SET status = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':id' => $mekanikId,
    ]);
}

function formatExceptionMessage(Throwable $e): string
{
    $message = trim($e->getMessage());
    if ($message === '') {
        return 'Terjadi kesalahan sistem.';
    }
    return $message;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_active_jenis_layanan') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['is_active'] ?? 0);

        $stmt = $pdo->prepare("UPDATE jenis_layanan SET is_active = :is_active WHERE id = :id");
        $stmt->execute([':is_active' => $status, ':id' => $id]);

        setFlash('success', $status === 0 ? 'Paket layanan berhasil dinonaktifkan dari form reservasi.' : 'Paket layanan berhasil diaktifkan kembali.');
        redirectTo('index.php?page=jenis_layanan');
    }

    if ($action === 'toggle_active_kegiatan_servis') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['is_active'] ?? 0);

        $stmt = $pdo->prepare("UPDATE kegiatan_servis SET is_active = :is_active WHERE id = :id");
        $stmt->execute([':is_active' => $status, ':id' => $id]);

        setFlash('success', $status === 0 ? 'Kegiatan servis berhasil dinonaktifkan dari form reservasi.' : 'Kegiatan servis berhasil diaktifkan kembali.');
        redirectTo('index.php?page=kegiatan_servis');
    }
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("
            SELECT id, nama, email, password, role
            FROM users
            WHERE email = :email AND role IN ('admin', 'owner')
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin'] = [
                'id' => (int) $admin['id'],
                'nama' => $admin['nama'],
                'email' => $admin['email'],
                'role' => $admin['role'],
            ];
            setFlash('success', 'Login berhasil.');
            if ($admin['role'] === 'owner') {
                redirectTo('index.php?page=owner_dashboard');
            }
            redirectTo('index.php?page=dashboard');
        }

        setFlash('danger', 'Email atau password tidak valid.');
        redirectTo('index.php?page=login');
    }
    // --- 1. Aksi Meminta OTP untuk Reset Password ---
    if ($action === 'request_otp_reset') {
        $email = trim($_POST['email'] ?? '');

        // Cari user admin/owner
        $stmt = $pdo->prepare("
            SELECT id, nama, no_whatsapp, role 
            FROM users 
            WHERE email = :email AND role IN ('admin', 'owner') 
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && !empty($user['no_whatsapp'])) {
            // Generate OTP 6 digit angka
            $otp = rand(100000, 999999);

            // Simpan data di Session untuk verifikasi nanti
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp'] = $otp;

            // Format pesan OTP
            $pesan = "Halo *{$user['nama']}*,\n\nKami menerima permintaan untuk mengatur ulang password akun " . ucfirst($user['role']) . " Anda di Akasia Motor.\n\nKode OTP Anda adalah: *{$otp}*\n\n_Kode ini bersifat rahasia. Jangan berikan kepada siapapun, termasuk pihak bengkel._";

            // Kirim via Fonnte dan tangkap hasilnya
            $sendResult = sendWhatsApp($pdo, $user['no_whatsapp'], $pesan);

            // Cek apakah berhasil terkirim dari API Fonnte
            if ($sendResult['success'] === true) {
                setFlash('success', 'Kode OTP telah dikirim ke WhatsApp Anda.');
                redirectTo('index.php?page=login&step=verify_otp');
            } else {
                // Tampilkan pesan error langsung dari Fonnte agar kita tahu penyebabnya
                setFlash('danger', 'Gagal mengirim OTP WhatsApp. Sistem Fonnte merespons: ' . $sendResult['reason']);
                redirectTo('index.php?page=login');
            }
        } else {
            setFlash('danger', 'Email tidak ditemukan atau nomor WhatsApp belum diatur di profil Anda.');
            redirectTo('index.php?page=login');
        }
    }

    // --- 2. Aksi Memproses OTP dan Password Baru ---
    if ($action === 'process_reset_password') {
        $email = $_SESSION['reset_email'] ?? '';
        $sessionOtp = $_SESSION['reset_otp'] ?? '';
        $inputOtp = trim($_POST['otp'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';

        // Cek apakah sesi valid
        if (!$email || !$sessionOtp) {
            setFlash('danger', 'Sesi reset password tidak valid atau sudah kadaluarsa. Silakan ulangi.');
            redirectTo('index.php?page=login');
        }

        // Verifikasi kecocokan OTP
        if ($inputOtp == $sessionOtp) {
            // Hash password baru
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update ke database
            $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email AND role IN ('admin', 'owner')");
            $updateStmt->execute([':password' => $hashedPassword, ':email' => $email]);

            // Bersihkan Session OTP
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_otp']);

            setFlash('success', 'Password Anda berhasil diubah! Silakan login dengan password baru.');
            redirectTo('index.php?page=login');
        } else {
            setFlash('danger', 'Kode OTP yang Anda masukkan salah.');
            // Kembalikan ke form OTP
            redirectTo('index.php?page=login&step=verify_otp');
        }
    }

    requireAdminAuth();

    if ($action === 'save_walkin') {
        $nama = trim($_POST['nama'] ?? '');
        $noWhatsapp = normalizePhone($_POST['no_whatsapp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $noPlat = trim($_POST['no_plat'] ?? '');

        $merk = trim($_POST['merk_kendaraan'] ?? '');
        if ($merk === 'Lainnya') {
            $merk = trim($_POST['merk_kendaraan_manual'] ?? '');
        }
        $jenis = trim($_POST['jenis_kendaraan'] ?? '');
        $jenisKendaraan = ($merk !== '' && $jenis !== '') ? $merk . ' - ' . $jenis : $jenis;

        $tipeModel = trim($_POST['model_kendaraan'] ?? '');
        if ($tipeModel === 'Lainnya') {
            $tipeModel = trim($_POST['model_kendaraan_manual'] ?? '');
        }

        $tahun = trim($_POST['tahun_kendaraan'] ?? '');

        $warna = trim($_POST['warna'] ?? '');
        $keluhan = trim($_POST['keluhan'] ?? '');
        $jenisLayananId = (int) ($_POST['jenis_layanan_id'] ?? 0);
        $tanggalServis = trim($_POST['tanggal_servis'] ?? date('Y-m-d'));
        $kegiatanIds = $_POST['kegiatan_ids'] ?? [];

        if ($nama === '' || $noPlat === '' || $jenisLayananId <= 0 || !isValidDateValue($tanggalServis)) {
            setFlash('danger', 'Data Servis walk-in belum lengkap.');
            redirectTo('index.php?page=reservasi_walkin');;
        }
        if (!isValidPhone($noWhatsapp)) {
            setFlash('danger', 'Nomor WhatsApp pelanggan tidak valid.');
            redirectTo('index.php?page=reservasi_walkin');;
        }

        try {
            $layananStmt = $pdo->prepare("SELECT id, nama, estimasi_durasi, estimasi_biaya_jasa, is_custom FROM jenis_layanan WHERE id = :id LIMIT 1");
            $layananStmt->execute([':id' => $jenisLayananId]);
            $selectedLayanan = $layananStmt->fetch(PDO::FETCH_ASSOC);

            if (!$selectedLayanan) {
                setFlash('danger', 'Jenis layanan tidak valid.');
                redirectTo('index.php?page=reservasi_walkin');;
            }
            $isServisUmum = (bool) $selectedLayanan['is_custom'];
            $selectedKegiatanIds = array_values(array_filter(array_map('intval', (array) $kegiatanIds)));
            $estimasiDurasi = 0;
            $biayaJasa = 0.0;

            if ($isServisUmum) {
                $availableKegiatanStmt = $pdo->prepare("SELECT id, estimasi_durasi, estimasi_biaya FROM kegiatan_servis WHERE jenis_layanan_id = :jenis_layanan_id ORDER BY id ASC");
                $availableKegiatanStmt->execute([':jenis_layanan_id' => $jenisLayananId]);
                $availableKegiatan = $availableKegiatanStmt->fetchAll(PDO::FETCH_ASSOC);
                $availableIds = array_map(static fn($item) => (int) $item['id'], $availableKegiatan);
                $selectedKegiatanIds = array_values(array_intersect($selectedKegiatanIds, $availableIds));

                if (empty($selectedKegiatanIds)) {
                    setFlash('danger', 'Silakan pilih minimal satu kegiatan servis untuk Servis Umum.');
                    redirectTo('index.php?page=reservasi_walkin');;
                }

                $selectedItems = [];
                foreach ($availableKegiatan as $item) {
                    if (in_array((int) $item['id'], $selectedKegiatanIds, true)) {
                        $selectedItems[] = $item;
                    }
                }

                $estimasiDurasi = array_sum(array_map(static fn($item) => (int) $item['estimasi_durasi'], $selectedItems));
                $biayaJasa = array_sum(array_map(static fn($item) => (float) $item['estimasi_biaya'], $selectedItems));
            } else {
                $estimasiDurasi = (int) $selectedLayanan['estimasi_durasi'];
                $biayaJasa = (float) $selectedLayanan['estimasi_biaya_jasa'];
            }

            $capacityInfo = getCapacityInfo($pdo, $tanggalServis);
            if (!$capacityInfo['allowed'] || ($capacityInfo['used'] + $estimasiDurasi) > $capacityInfo['capacity']) {
                $sisa = max(0, $capacityInfo['capacity'] - $capacityInfo['used']);
                setFlash(
                    'danger',
                    'Kuota reservasi harian untuk tanggal ' . formatDateIndonesia($tanggalServis) . ' sudah penuh. Sisa kapasitas hanya ' . $sisa . ' menit.'
                );
                redirectTo('index.php?page=reservasi_walkin');;
            }

            $pdo->beginTransaction();
            $userStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'pelanggan' AND nama = :nama AND COALESCE(no_whatsapp, '') = :no_whatsapp LIMIT 1");
            $userStmt->execute([':nama' => $nama, ':no_whatsapp' => $noWhatsapp]);
            $existingUser = $userStmt->fetch();

            if ($existingUser) {
                $userId = (int) $existingUser['id'];
                $updateUserStmt = $pdo->prepare("UPDATE users SET alamat = :alamat WHERE id = :id");
                $updateUserStmt->execute([':alamat' => $alamat !== '' ? $alamat : null, ':id' => $userId]);
            } else {
                $insertUserStmt = $pdo->prepare("
                    INSERT INTO users (nama, email, password, no_whatsapp, alamat, role)
                    VALUES (:nama, :email, :password, :no_whatsapp, :alamat, 'pelanggan')
                ");
                $insertUserStmt->execute([
                    ':nama' => $nama,
                    ':email' => generatePelangganEmail($pdo, $nama, $noWhatsapp),
                    ':password' => password_hash('walkin123', PASSWORD_DEFAULT),
                    ':no_whatsapp' => $noWhatsapp !== '' ? $noWhatsapp : null,
                    ':alamat' => $alamat !== '' ? $alamat : null,
                ]);
                $userId = (int) $pdo->lastInsertId();
            }

            $noAntrian = generateQueueNumber($pdo, $tanggalServis);


            $insertReservasiStmt = $pdo->prepare("
                INSERT INTO reservasi (
                    no_antrian, user_id, mekanik_id, jenis_layanan_id, jenis_reservasi,
                    no_plat, jenis_kendaraan, tipe_model, tahun, warna, keluhan,
                    kehadiran, status, tanggal_servis, estimasi_durasi, biaya_jasa, total_biaya
                ) VALUES (
                    :no_antrian, :user_id, NULL, :jenis_layanan_id, 'Walk-in',
                    :no_plat, :jenis_kendaraan, :tipe_model, :tahun, :warna, :keluhan,
                    'Hadir', 'menunggu_antrean', :tanggal_servis, :estimasi_durasi, :biaya_jasa, :total_biaya
                )
            ");
            $insertReservasiStmt->execute([
                ':no_antrian' => $noAntrian,
                ':user_id' => $userId,
                ':jenis_layanan_id' => $jenisLayananId,
                ':no_plat' => $noPlat,
                ':jenis_kendaraan' => $jenisKendaraan !== '' ? $jenisKendaraan : null,
                ':tipe_model' => $tipeModel !== '' ? $tipeModel : null,
                ':tahun' => $tahun !== '' ? (int) $tahun : null,
                ':warna' => $warna !== '' ? $warna : null,
                ':keluhan' => $keluhan !== '' ? $keluhan : null,
                ':tanggal_servis' => $tanggalServis,
                ':estimasi_durasi' => $estimasiDurasi,
                ':biaya_jasa' => $biayaJasa,
                ':total_biaya' => $biayaJasa,
            ]);

            $reservasiId = (int) $pdo->lastInsertId();
            if ($isServisUmum && !empty($selectedKegiatanIds)) {
                $insertKegiatanStmt = $pdo->prepare("INSERT INTO reservasi_kegiatan (reservasi_id, kegiatan_servis_id) VALUES (:reservasi_id, :kegiatan_servis_id)");
                foreach ($selectedKegiatanIds as $kegiatanId) {
                    $kegiatanId = (int) $kegiatanId;
                    if ($kegiatanId > 0) {
                        $insertKegiatanStmt->execute([':reservasi_id' => $reservasiId, ':kegiatan_servis_id' => $kegiatanId]);
                    }
                }
            }

            // ==== KIRIM NOTIFIKASI KE ADMIN (JIKA ADA RESERVASI BARU) ====
            $pengaturanStmt = $pdo->query("SELECT no_whatsapp FROM pengaturan LIMIT 1");
            $pengaturan = $pengaturanStmt->fetch();
            $adminPhone = $pengaturan['no_whatsapp'] ?? '';

            if ($adminPhone) {
                $templateAdmin = getWhatsAppTemplate($pdo, 'notif_admin_reservasi_baru');
                if ($templateAdmin) {

                    $layananStmt = $pdo->prepare("SELECT nama FROM jenis_layanan WHERE id = :id");
                    $layananStmt->execute([':id' => $jenisLayananId]);
                    $namaLayanan = $layananStmt->fetchColumn() ?: '-';

                    // $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                    // $host = $_SERVER['HTTP_HOST'];
                    // $linkKonfirmasi = $protocol . "://" . $host . "/akasia_admin/index.php?page=status_servis&stage=menunggu_konfirmasi&id=" . $reservasiId;
                    $host = 'https://akasia-motor.my.id';
                    $linkKonfirmasi = $host . "/admin/index.php?page=status_servis&stage=menunggu_konfirmasi&id=" . $reservasiId;
                    $msgAdmin = renderTemplateMessage($templateAdmin, [
                        'nama' => $nama,
                        'kendaraan' => ($tipeModel !== '') ? $tipeModel : 'Kendaraan',
                        'no_plat' => $noPlat,
                        'layanan' => $namaLayanan,
                        'tanggal' => formatDateIndonesia($tanggalServis),
                        'link' => $linkKonfirmasi
                    ]);

                    // Kirim dan Catat Log
                    sendAndLogWhatsApp($pdo, $reservasiId, $adminPhone, $msgAdmin, 'Notifikasi Admin');
                }
            }

            $pdo->commit();
            $workshopSchedule = getWorkshopSchedule($pdo);
            $startEstimate = ($tanggalServis === date('Y-m-d'))
                ? new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'))
                : new DateTimeImmutable($tanggalServis . ' ' . ($workshopSchedule['jam_buka'] ?? '08:00:00'), new DateTimeZone('Asia/Jakarta'));
            $estimasiSelesai = calculateOperationalEndTime($pdo, $startEstimate, $estimasiDurasi);
            setFlash(
                'success',
                'Reservasi berhasil dibuat. Antrean: ' . $noAntrian . '. Estimasi selesai operasional: ' . formatDateTimeIndonesia($estimasiSelesai->format('Y-m-d H:i:s')) . '. Notifikasi terkirim ke Admin.'
            );
        } catch (Throwable $e) {
            $pdo->rollBack();
            setFlash('danger', 'Reservasi gagal disimpan. Penyebab: ' . formatExceptionMessage($e));
            redirectTo('index.php?page=reservasi_walkin');;
        }
        redirectTo($_POST['redirect_to'] ?? 'index.php?page=status_servis&stage=menunggu_antrean');
    }

    if ($action === 'confirm_reservasi' || $action === 'cancel_reservasi' || $action === 'alihkan_reservasi' || $action === 'confirm_kehadiran' || $action === 'mark_pending' || $action === 'callback_pending') {
        $id = (int) ($_POST['id'] ?? 0);
        $reservasi = findReservasi($pdo, $id);

        if (!$reservasi) {
            setFlash('danger', 'Data reservasi tidak ditemukan.');
            redirectTo($_POST['redirect_to'] ?? 'index.php?page=status_servis');
        }

        $pdo->beginTransaction();
        try {
            if ($action === 'confirm_reservasi') {
                $stmt = $pdo->prepare("UPDATE reservasi SET status = 'dikonfirmasi', waktu_konfirmasi = NOW() WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $reservasi['status'] = 'dikonfirmasi';
                $reservasi['id'] = $id;
                buildAndSendTemplate($pdo, $reservasi, 'reservasi_dikonfirmasi', 'Konfirmasi Reservasi', [
                    'nama' => $reservasi['pelanggan'],
                    'antrean' => $reservasi['no_antrian'],
                    'tanggal' => formatDateIndonesia($reservasi['tanggal_servis']),
                ]);
            }

            if ($action === 'alihkan_reservasi') {
                $tanggalBaru = trim($_POST['tanggal_baru'] ?? '');
                $alasan = trim($_POST['alasan'] ?? '');

                if ($tanggalBaru === '' || $alasan === '') {
                    throw new RuntimeException('Tanggal baru dan alasan pengalihan wajib diisi.');
                }
                if (!isValidDateValue($tanggalBaru)) {
                    throw new RuntimeException('Format tanggal tidak valid.');
                }

                $durasiReservasi = (int) ($reservasi['estimasi_durasi'] ?? 0);
                if ($durasiReservasi <= 0) {
                    $durasiStmt = $pdo->prepare("SELECT estimasi_durasi FROM jenis_layanan WHERE id = :id LIMIT 1");
                    $durasiStmt->execute([':id' => (int) $reservasi['jenis_layanan_id']]);
                    $durasiReservasi = (int) ($durasiStmt->fetchColumn() ?: 0);
                }

                $capacityInfo = getCapacityInfo($pdo, $tanggalBaru);
                if (!$capacityInfo['allowed'] || ($capacityInfo['used'] + $durasiReservasi) > $capacityInfo['capacity']) {
                    $sisa = max(0, $capacityInfo['capacity'] - $capacityInfo['used']);
                    throw new RuntimeException(
                        'Tanggal tujuan sudah penuh. Sisa kapasitas hanya ' . $sisa . ' menit.'
                    );
                }
                $noAntrianLama = $reservasi['no_antrian'];
                $noAntrianBaru = generateQueueNumber($pdo, $tanggalBaru);
                $stmt = $pdo->prepare("
    UPDATE reservasi
    SET status = 'pending',
        tanggal_servis = :tanggal_baru,
        no_antrian = :no_antrian,
        alasan_status = :alasan,
        waktu_pending = NOW()
    WHERE id = :id
");
                $stmt->execute([
                    ':tanggal_baru' => $tanggalBaru,
                    ':no_antrian' => $noAntrianBaru,
                    ':alasan' => $alasan,
                    ':id' => $id
                ]);

                $reservasi['tanggal_servis'] = $tanggalBaru;
                $reservasi['no_antrian'] = $noAntrianBaru;

                buildAndSendTemplate($pdo, $reservasi, 'reservasi_dialihkan', 'Perubahan Status', [
                    'nama' => $reservasi['pelanggan'],
                    'alasan' => $alasan,
                    'tanggal_baru' => formatDateIndonesia($tanggalBaru),
                    'antrean' => $noAntrianLama,
                    'antrean_baru' => $noAntrianBaru
                ]);
            }

            if ($action === 'cancel_reservasi') {
                $alasan = trim($_POST['alasan'] ?? '');
                if ($alasan === '') {
                    throw new RuntimeException('Alasan pembatalan wajib diisi.');
                }

                if (!empty($reservasi['mekanik_id'])) {
                    updateMekanikStatus($pdo, (int) $reservasi['mekanik_id'], 'tersedia');
                }
                $stmt = $pdo->prepare("UPDATE reservasi SET alasan_status = :alasan WHERE id = :id");
                $stmt->execute([
                    ':id' => $id,
                    ':alasan' => $alasan
                ]);
                shiftQueueAfterCancellation($pdo, $reservasi);
                buildAndSendTemplate($pdo, $reservasi, 'reservasi_dibatalkan', 'Perubahan Status', [
                    'nama' => $reservasi['pelanggan'],
                    'antrean' => $reservasi['no_antrian'],
                    'alasan' => $alasan
                ]);
            }

            if ($action === 'confirm_kehadiran') {
                $stmt = $pdo->prepare("UPDATE reservasi SET status = 'menunggu_antrean', kehadiran = 'Hadir', waktu_hadir = NOW() WHERE id = :id");
                $stmt->execute([':id' => $id]);
                buildAndSendTemplate($pdo, $reservasi, 'kehadiran_dikonfirmasi', 'Perubahan Status', [
                    'nama' => $reservasi['pelanggan'],
                ]);
            }

            if ($action === 'mark_pending') {
                $stmt = $pdo->prepare("UPDATE reservasi SET status = 'pending', kehadiran = 'Belum Hadir', waktu_pending = NOW() WHERE id = :id");
                $stmt->execute([':id' => $id]);
                buildAndSendTemplate($pdo, $reservasi, 'pending_kehadiran', 'Callback Pending', [
                    'nama' => $reservasi['pelanggan'],
                    'antrean' => $reservasi['no_antrian'],
                ]);
            }

            if ($action === 'callback_pending') {
                buildAndSendTemplate($pdo, $reservasi, 'callback_antrean', 'Callback Pending', [
                    'nama' => $reservasi['pelanggan'],
                ]);
            }

            $pdo->commit();
            setFlash('success', 'Perubahan data berhasil disimpan.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            setFlash('danger', 'Perubahan data gagal disimpan. Penyebab: ' . formatExceptionMessage($e));
        }

        redirectTo($_POST['redirect_to'] ?? 'index.php?page=status_servis');
    }

    if ($action === 'start_service') {
        $id = (int) ($_POST['id'] ?? 0);
        $mekanikId = (int) ($_POST['mekanik_id'] ?? 0);
        $reservasi = findReservasi($pdo, $id);

        if (!$reservasi || $mekanikId <= 0) {
            setFlash('danger', 'Data proses servis belum lengkap.');
            redirectTo($_POST['redirect_to'] ?? 'index.php?page=status_servis&stage=menunggu_antrean');
        }

        $pdo->beginTransaction();
        try {
            $mekanikStmt = $pdo->prepare("SELECT id, nama FROM mekanik WHERE id = :id AND LOWER(TRIM(status)) = 'tersedia' LIMIT 1");
            $mekanikStmt->execute([':id' => $mekanikId]);
            $mekanik = $mekanikStmt->fetch();

            if (!$mekanik) {
                throw new RuntimeException('Mekanik tidak tersedia.');
            }

            $stmt = $pdo->prepare("UPDATE reservasi SET status = 'diproses', mekanik_id = :mekanik_id, waktu_mulai_servis = NOW() WHERE id = :id");
            $stmt->execute([':mekanik_id' => $mekanikId, ':id' => $id]);
            updateMekanikStatus($pdo, $mekanikId, 'sibuk');

            $reservasi['id'] = $id;
            $reservasi['no_whatsapp'] = $reservasi['no_whatsapp'] ?? '';
            buildAndSendTemplate($pdo, $reservasi, 'proses_servis_dimulai', 'Perubahan Status', [
                'nama' => $reservasi['pelanggan'],
                'antrean' => $reservasi['no_antrian'],
                'mekanik' => $mekanik['nama'],
            ]);

            $pdo->commit();
            setFlash('success', 'Servis berhasil dimulai.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            setFlash('danger', 'Servis gagal dimulai. Penyebab: ' . formatExceptionMessage($e));
        }
        redirectTo($_POST['redirect_to'] ?? 'index.php?page=status_servis&stage=menunggu_antrean');
    }

    if ($action === 'complete_service') {
        $id = (int) ($_POST['id'] ?? 0);
        $hasilServis = trim($_POST['hasil_servis'] ?? '');
        $biayaJasa = (float) ($_POST['biaya_jasa'] ?? 0);
        $catatanTambahan = trim($_POST['catatan_tambahan'] ?? '');
        $sparepartNames = $_POST['sparepart_nama'] ?? [];
        $sparepartQty = $_POST['sparepart_qty'] ?? [];
        $sparepartHarga = $_POST['sparepart_harga'] ?? [];
        $reservasi = findReservasi($pdo, $id);

        if (!$reservasi) {
            setFlash('danger', 'Data servis tidak ditemukan.');
            redirectTo($_POST['redirect_to'] ?? 'index.php?page=status_servis&stage=diproses');
        }

        $totalSparepart = 0;
        $spareparts = [];
        foreach ($sparepartNames as $index => $name) {
            $name = trim((string) $name);
            $qty = max(1, (int) ($sparepartQty[$index] ?? 1));
            $harga = (float) ($sparepartHarga[$index] ?? 0);
            if ($name === '') {
                continue;
            }
            $subtotal = $qty * $harga;
            $totalSparepart += $subtotal;
            $spareparts[] = ['nama' => $name, 'qty' => $qty, 'harga' => $harga, 'subtotal' => $subtotal];
        }

        $totalBiaya = $biayaJasa + $totalSparepart;

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                UPDATE reservasi
                SET status = 'selesai', hasil_servis = :hasil_servis, biaya_jasa = :biaya_jasa, total_biaya = :total_biaya, catatan_tambahan = :catatan_tambahan, waktu_selesai = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':hasil_servis' => $hasilServis !== '' ? $hasilServis : null,
                ':biaya_jasa' => $biayaJasa,
                ':total_biaya' => $totalBiaya,
                ':catatan_tambahan' => $catatanTambahan !== '' ? $catatanTambahan : null,
                ':id' => $id,
            ]);

            $deleteStmt = $pdo->prepare("DELETE FROM reservasi_sparepart WHERE reservasi_id = :reservasi_id");
            $deleteStmt->execute([':reservasi_id' => $id]);

            if ($spareparts) {
                $insertStmt = $pdo->prepare("INSERT INTO reservasi_sparepart (reservasi_id, nama_item, qty, harga, subtotal) VALUES (:reservasi_id, :nama_item, :qty, :harga, :subtotal)");
                foreach ($spareparts as $item) {
                    $insertStmt->execute([':reservasi_id' => $id, ':nama_item' => $item['nama'], ':qty' => $item['qty'], ':harga' => $item['harga'], ':subtotal' => $item['subtotal']]);
                }
            }

            updateMekanikStatus($pdo, $reservasi['mekanik_id'] ? (int) $reservasi['mekanik_id'] : null, 'tersedia');
            buildAndSendTemplate($pdo, $reservasi, 'servis_selesai', 'Servis Selesai', [
                'nama' => $reservasi['pelanggan'],
                'antrean' => $reservasi['no_antrian'],
                'total' => number_format($totalBiaya, 0, ',', '.'),
            ]);

            $pdo->commit();
            setFlash('success', 'Data penyelesaian servis berhasil disimpan.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            setFlash('danger', 'Data penyelesaian servis gagal disimpan. Penyebab: ' . formatExceptionMessage($e));
        }
        redirectTo($_POST['redirect_to'] ?? 'index.php?page=status_servis&stage=selesai');
    }

    if ($action === 'save_mekanik') {
        $id = (int) ($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $noHp = normalizePhone($_POST['no_hp'] ?? '');
        $status = $_POST['status_mekanik'] ?? 'tersedia';

        if ($nama === '' || !isValidPhone($noHp)) {
            setFlash('danger', 'Data mekanik belum valid.');
            redirectTo('index.php?page=mekanik');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE mekanik SET nama = :nama, no_hp = :no_hp, status = :status WHERE id = :id");
            $stmt->execute([':nama' => $nama, ':no_hp' => $noHp !== '' ? $noHp : null, ':status' => $status, ':id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO mekanik (nama, no_hp, status) VALUES (:nama, :no_hp, :status)");
            $stmt->execute([':nama' => $nama, ':no_hp' => $noHp !== '' ? $noHp : null, ':status' => $status]);
        }
        setFlash('success', 'Data mekanik berhasil disimpan.');
        redirectTo('index.php?page=mekanik');
    }

    if ($action === 'delete_mekanik') {
        $stmt = $pdo->prepare("DELETE FROM mekanik WHERE id = :id");
        $stmt->execute([':id' => (int) ($_POST['id'] ?? 0)]);
        setFlash('success', 'Data mekanik berhasil dihapus.');
        redirectTo('index.php?page=mekanik');
    }

    if ($action === 'save_jenis_layanan') {
        $id = (int) ($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $estimasiDurasi = (int) ($_POST['estimasi_durasi'] ?? 0);
        $estimasiBiayaJasa = (float) ($_POST['estimasi_biaya_jasa'] ?? 0);
        $isCustom = isset($_POST['is_custom']) ? 1 : 0;

        if ($nama === '') {
            setFlash('danger', 'Nama jenis layanan wajib diisi.');
            redirectTo('index.php?page=jenis_layanan');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE jenis_layanan SET nama = :nama, deskripsi = :deskripsi, estimasi_durasi = :estimasi_durasi, estimasi_biaya_jasa = :estimasi_biaya_jasa WHERE id = :id");
            $stmt->execute([':nama' => $nama, ':deskripsi' => $deskripsi !== '' ? $deskripsi : null, ':estimasi_durasi' => $estimasiDurasi, ':estimasi_biaya_jasa' => $estimasiBiayaJasa, ':id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO jenis_layanan (nama, deskripsi, estimasi_durasi, estimasi_biaya_jasa) VALUES (:nama, :deskripsi, :estimasi_durasi, :estimasi_biaya_jasa)");
            $stmt->execute([':nama' => $nama, ':deskripsi' => $deskripsi !== '' ? $deskripsi : null, ':estimasi_durasi' => $estimasiDurasi, ':estimasi_biaya_jasa' => $estimasiBiayaJasa]);
        }
        setFlash('success', 'Paket Layanan berhasil disimpan.');
        redirectTo('index.php?page=jenis_layanan');
    }

    if ($action === 'delete_jenis_layanan') {
        $stmt = $pdo->prepare("DELETE FROM jenis_layanan WHERE id = :id");
        $stmt->execute([':id' => (int) ($_POST['id'] ?? 0)]);
        setFlash('success', 'Paket Layanan berhasil dihapus.');
        redirectTo('index.php?page=jenis_layanan');
    }

    if ($action === 'save_kegiatan_servis') {
        $id = (int) ($_POST['id'] ?? 0);
        $jenisLayananId = (int) ($_POST['jenis_layanan_id'] ?? 0);
        $namaKegiatan = trim($_POST['nama_kegiatan'] ?? '');
        $estimasiDurasi = (int) ($_POST['estimasi_durasi'] ?? 0);
        $estimasiBiaya = (float) ($_POST['estimasi_biaya'] ?? 0);

        if ($jenisLayananId <= 0 || $namaKegiatan === '') {
            setFlash('danger', 'Data kegiatan servis wajib dilengkapi.');
            redirectTo('index.php?page=kegiatan_servis');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE kegiatan_servis SET jenis_layanan_id = :jenis_layanan_id, nama_kegiatan = :nama_kegiatan, estimasi_durasi = :estimasi_durasi, estimasi_biaya = :estimasi_biaya WHERE id = :id");
            $stmt->execute([':jenis_layanan_id' => $jenisLayananId, ':nama_kegiatan' => $namaKegiatan, ':estimasi_durasi' => $estimasiDurasi, ':estimasi_biaya' => $estimasiBiaya, ':id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO kegiatan_servis (jenis_layanan_id, nama_kegiatan, estimasi_durasi, estimasi_biaya) VALUES (:jenis_layanan_id, :nama_kegiatan, :estimasi_durasi, :estimasi_biaya)");
            $stmt->execute([':jenis_layanan_id' => $jenisLayananId, ':nama_kegiatan' => $namaKegiatan, ':estimasi_durasi' => $estimasiDurasi, ':estimasi_biaya' => $estimasiBiaya]);
        }
        setFlash('success', 'Kegiatan servis berhasil disimpan.');
        redirectTo('index.php?page=kegiatan_servis');
    }

    if ($action === 'delete_kegiatan_servis') {
        $stmt = $pdo->prepare("DELETE FROM kegiatan_servis WHERE id = :id");
        $stmt->execute([':id' => (int) ($_POST['id'] ?? 0)]);
        setFlash('success', 'Kegiatan servis berhasil dihapus.');
        redirectTo('index.php?page=kegiatan_servis');
    }

    if ($action === 'update_pengaturan') {
        $id = (int) ($_POST['id'] ?? 0);
        $noWhatsapp = normalizePhone($_POST['no_whatsapp'] ?? '');
        $tokenFonnte = trim($_POST['token_fonnte'] ?? '');

        if (!isValidPhone($noWhatsapp)) {
            setFlash('danger', 'Nomor WhatsApp bengkel tidak valid.');
            redirectTo('index.php?page=pengaturan');
        }

        // 1. Update tabel pengaturan
        $stmt = $pdo->prepare("
            UPDATE pengaturan
            SET nama_bengkel = :nama_bengkel, alamat = :alamat, no_whatsapp = :no_whatsapp, jam_buka = :jam_buka, jam_tutup = :jam_tutup, jam_istirahat_mulai = :jam_istirahat_mulai, jam_istirahat_selesai = :jam_istirahat_selesai, hari_operasional = :hari_operasional, token_fonnte = :token_fonnte
            WHERE id = :id
        ");
        $stmt->execute([
            ':nama_bengkel' => trim($_POST['nama_bengkel'] ?? ''),
            ':alamat' => trim($_POST['alamat'] ?? ''),
            ':no_whatsapp' => $noWhatsapp !== '' ? $noWhatsapp : null,
            ':jam_buka' => $_POST['jam_buka'] ?? null,
            ':jam_tutup' => $_POST['jam_tutup'] ?? null,
            ':jam_istirahat_mulai' => $_POST['jam_istirahat_mulai'] ?? null,
            ':jam_istirahat_selesai' => $_POST['jam_istirahat_selesai'] ?? null,
            ':hari_operasional' => trim($_POST['hari_operasional'] ?? ''),
            ':token_fonnte' => $tokenFonnte,
            ':id' => $id,
        ]);

        // 2. TAMBAHAN: Sinkronisasi nomor WhatsApp ke tabel users untuk role admin & owner
        $updateUsersStmt = $pdo->prepare("
            UPDATE users 
            SET no_whatsapp = :no_whatsapp 
            WHERE role IN ('admin', 'owner')
        ");
        $updateUsersStmt->execute([
            ':no_whatsapp' => $noWhatsapp !== '' ? $noWhatsapp : null
        ]);

        // Update pesan sukses agar lebih informatif
        setFlash('success', 'Pengaturan bengkel dan nomor WhatsApp Admin/Owner berhasil disinkronkan dan diperbarui.');
        redirectTo('index.php?page=pengaturan');
    }

    if ($action === 'save_template') {
        $id = (int) ($_POST['id'] ?? 0);
        $namTemplate = trim($_POST['nama_template'] ?? '');
        $isiPesan = trim($_POST['isi_pesan'] ?? '');
        $isActive = (int) ($_POST['is_active'] ?? 1);

        if ($namTemplate === '' || $isiPesan === '') {
            setFlash('danger', 'Nama template dan isi pesan wajib diisi.');
            redirectTo('index.php?page=template_notifikasi&edit=' . $id);
        }

        $stmt = $pdo->prepare("UPDATE template_whatsapp SET nama_template = :nama_template, isi_pesan = :isi_pesan, is_active = :is_active WHERE id = :id");
        $stmt->execute([':nama_template' => $namTemplate, ':isi_pesan' => $isiPesan, ':is_active' => $isActive, ':id' => $id]);

        setFlash('success', 'Template notifikasi berhasil disimpan.');
        redirectTo('index.php?page=template_notifikasi');
    }
}

if ($page === 'login' && isAdminLoggedIn()) {
    redirectTo('index.php?page=dashboard');
}

if ($page === 'reservasi') {
    $query = $_GET;
    unset($query['page']);
    $query['page'] = 'status_servis';
    $query['stage'] = 'menunggu_konfirmasi';
    redirectTo(buildPageUrl($query));
}

$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($pageFile)) {
    $page = 'dashboard';
    $pageFile = __DIR__ . '/pages/dashboard.php';
}

$pageTitleMap = [
    'login' => 'Login Admin',
    'dashboard' => 'Dashboard Admin',
    'reservasi' => 'Menunggu Konfirmasi',
    'reservasi_walkin' => 'Pendaftaran Servis Walk-in',
    'detail_reservasi' => 'Detail Reservasi',
    'status_servis' => 'Kelola Reservasi Servis',
    'mekanik' => 'Kelola Data Mekanik',
    'rekap_mekanik' => 'Rekap Kerja Mekanik', // <-- INI YANG DITAMBAHKAN
    'jenis_layanan' => 'Kelola Jenis Layanan Servis',
    'kegiatan_servis' => 'Kelola Kegiatan Servis',
    'notifikasi' => 'Riwayat Notifikasi',
    'pengaturan' => 'Kelola Profil Bengkel',
    'template_notifikasi' => 'Kelola Template WhatsApp',
];

$pageTitle = $pageTitleMap[$page] ?? 'Akasia Motor Admin';
$flash = getFlash();
$adminUser = currentAdmin();

if ($page !== 'login') {
    requireAdminAuth();
    if ($adminUser['role'] === 'owner') {
        $allowedOwnerPages = ['owner_dashboard', 'detail_reservasi', 'rekap_mekanik'];
        if (!in_array($page, $allowedOwnerPages, true)) {
            redirectTo('index.php?page=owner_dashboard');
        }
    } elseif ($adminUser['role'] === 'admin') {
        if ($page === 'owner_dashboard') {
            redirectTo('index.php?page=dashboard');
        }
    }
}

if ($page === 'login') {
    require $pageFile;
    exit;
}

require __DIR__ . '/layout/header.php';
require __DIR__ . '/layout/sidebar.php';
require $pageFile;
require __DIR__ . '/layout/footer.php';
