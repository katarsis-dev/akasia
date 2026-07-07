<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akasia Motor - Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-bottom: 80px;
            /* Jarak untuk bottom nav di mobile */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Hapus jarak bawah untuk Desktop (xl) karena tidak pakai bottom-nav */
        @media (min-width: 1200px) {
            body {
                padding-bottom: 0;
            }
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            background-color: #ffffff;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
            z-index: 1000;
        }

        .nav-item-bottom {
            flex: 1 1 0;
            text-align: center;
            padding: 10px 0 11px;
            color: #6c757d;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s;
        }

        .nav-item-bottom.active {
            color: #0d6efd;
            font-weight: 600;
        }

        .nav-item-bottom i {
            font-size: 1.4rem;
            display: block;
            margin-bottom: 2px;
        }

        .hero {
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            color: white;
            padding: 40px 0;
            border-radius: 0 0 20px 20px;
            margin-top: -20px;
            margin-bottom: 20px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
            margin-bottom: 15px;
        }

        .container-main {
            margin-top: 20px;
            margin-bottom: 20px;
            min-height: 70vh;
        }

        /* Style tambahan untuk navbar desktop */
        .navbar-nav .nav-link {
            font-weight: 500;
            white-space: nowrap;
            /* Mencegah teks menu patah ke bawah */
            padding-left: 0.8rem !important;
            padding-right: 0.8rem !important;
        }

        .navbar-nav .nav-link.active {
            font-weight: 700;
            color: #fff !important;
        }

        .offcanvas .nav-link {
            padding: 0.5rem 0;
        }

        .offcanvas .nav-link.active {
            font-weight: 700;
        }

        .brand-logo {
            height: 32px;
            width: auto;
            object-fit: contain;
            background: linear-gradient(180deg, rgba(248, 251, 255, 0.98) 0%, rgba(227, 236, 250, 0.98) 100%);
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid rgba(13, 110, 253, 0.28);
            box-shadow: 0 4px 14px rgba(3, 24, 70, 0.22);
        }

        @media (max-width: 576px) {
            .brand-logo {
                height: 28px;
                padding: 3px 8px;
            }
        }
    </style>
</head>

<body>
    <!-- Diubah ke navbar-expand-xl dan menggunakan container-xl agar lebih lega -->
    <nav class="navbar navbar-expand-xl navbar-dark bg-primary sticky-top shadow-sm py-3">
        <div class="container-xl">
            <a class="navbar-brand fw-bold me-4" href="?page=home">
                <img src="./assets/images/logo.png" alt="Akasia Motor" class="brand-logo me-2">
                <span class="align-middle">Akasia Motor</span>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="btn btn-outline-light btn-sm rounded-pill d-xl-none ms-auto me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavMenu" aria-controls="mobileNavMenu" aria-label="Buka menu">
                    <i class="bi bi-list"></i>
                </button>

                <div class="collapse navbar-collapse d-none d-xl-flex">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'home' ? 'active' : '' ?>" href="?page=home"><i class="bi bi-house-door me-1"></i> Beranda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>" href="?page=dashboard"><i class="bi bi-speedometer2 me-1"></i> Monitoring Status Servis</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'booking' ? 'active' : '' ?>" href="?page=booking"><i class="bi bi-calendar-plus me-1"></i> Reservasi Servis</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'riwayat' ? 'active' : '' ?>" href="?page=riwayat"><i class="bi bi-clock-history me-1"></i> Riwayat Servis</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'kendaraan' ? 'active' : '' ?>" href="?page=kendaraan"><i class="bi bi-bicycle me-1"></i> Kelola Data Kendaraan</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'profil' ? 'active' : '' ?>" href="?page=profil"><i class="bi bi-person-badge me-1"></i> Profil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'notifikasi' ? 'active' : '' ?>" href="?page=notifikasi"><i class="bi bi-bell me-1"></i> Notif</a>
                        </li>
                    </ul>
                </div>

                <div class="d-none d-xl-flex align-items-center ms-3">
                    <div class="text-white me-3 small lh-sm text-end">
                        <span class="d-block text-white-50" style="font-size: 0.7rem;">Halo,</span>
                        <strong class="d-inline-block text-truncate" style="max-width: 120px;"><?= htmlspecialchars($_SESSION['nama']) ?></strong>
                    </div>
                    <a href="?page=logout" class="btn btn-outline-light btn-sm rounded-pill px-3">Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="offcanvas offcanvas-end bg-primary text-white d-xl-none" tabindex="-1" id="mobileNavMenu" aria-labelledby="mobileNavMenuLabel">
            <div class="offcanvas-header border-bottom border-light border-opacity-25">
                <h5 class="offcanvas-title fw-bold" id="mobileNavMenuLabel">Menu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column gap-2">
                <a class="nav-link text-white <?= $page == 'home' ? 'active' : '' ?>" href="?page=home"><i class="bi bi-house-door me-2"></i> Beranda</a>
                <a class="nav-link text-white <?= $page == 'dashboard' ? 'active' : '' ?>" href="?page=dashboard"><i class="bi bi-speedometer2 me-2"></i> Monitoring Status Servis</a>
                <a class="nav-link text-white <?= $page == 'booking' ? 'active' : '' ?>" href="?page=booking"><i class="bi bi-calendar-plus me-2"></i> Reservasi Servis</a>
                <a class="nav-link text-white <?= $page == 'riwayat' ? 'active' : '' ?>" href="?page=riwayat"><i class="bi bi-clock-history me-2"></i> Riwayat Servis</a>
                <a class="nav-link text-white <?= $page == 'kendaraan' ? 'active' : '' ?>" href="?page=kendaraan"><i class="bi bi-bicycle me-2"></i> Kelola Data Kendaraan</a>
                <a class="nav-link text-white <?= $page == 'profil' ? 'active' : '' ?>" href="?page=profil"><i class="bi bi-person-badge me-2"></i> Profil Saya</a>
                <a class="nav-link text-white <?= $page == 'notifikasi' ? 'active' : '' ?>" href="?page=notifikasi"><i class="bi bi-bell me-2"></i> Notifikasi</a>

                <div class="mt-auto pt-3 border-top border-light border-opacity-25">
                    <div class="mb-3 text-center">
                        <small class="text-white-50">Masuk sebagai</small><br>
                        <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>
                    </div>
                    <a href="?page=logout" class="btn btn-outline-light rounded-pill w-100">Logout</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="container container-main">