<?php
$stages = getReservasiStages();
$sidebarCounts = [];
$scQuery = $pdo->query("SELECT status, COUNT(*) as total FROM reservasi GROUP BY status");
while ($r = $scQuery->fetch()) {
    $sidebarCounts[$r['status']] = (int) $r['total'];
}

// Ambil role user saat ini (default ke admin jika kosong)
$userRole = $adminUser['role'] ?? 'admin';
?>
<nav class="sidebar-nav">

    <?php if ($userRole === 'owner'): ?>
        <a href="index.php?page=owner_dashboard" class="nav-item <?= $currentPage === 'owner_dashboard' ? 'active' : '' ?>">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Dashboard Statistik Reservasi Servis</span>
        </a>

    <?php else: ?>
        <a href="index.php?page=dashboard" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
        </a>
        <a href="index.php?page=reservasi_walkin" class="nav-item <?= $currentPage === 'reservasi_walkin' ? 'active' : '' ?>">
            <i class="bi bi-person-plus"></i>
            <span>Pendaftaran Servis Walk-In</span>
        </a>
        <a href="index.php?page=status_servis" class="nav-item <?= $currentPage === 'status_servis' ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i>
            <span>Kelola Reservasi Servis</span>
        </a>

        <div class="nav-group">
            <div class="nav-group-title">
                <span>Data Master</span>
            </div>
            <div class="nav-submenu">
                <a href="index.php?page=jenis_layanan" class="nav-item nav-subitem <?= $currentPage === 'jenis_layanan' ? 'active' : '' ?>">
                    <i class="bi bi-card-checklist"></i>
                    <span>Kelola Jenis Layanan Servis</span>
                </a>
                <a href="index.php?page=kegiatan_servis" class="nav-item nav-subitem <?= $currentPage === 'kegiatan_servis' ? 'active' : '' ?>">
                    <i class="bi bi-list-task"></i>
                    <span>Kelola Kegiatan Servis</span>
                </a>
                <a href="index.php?page=mekanik" class="nav-item nav-subitem <?= $currentPage === 'mekanik' ? 'active' : '' ?>">
                    <i class="bi bi-person-gear"></i>
                    <span>Kelola Data Mekanik</span>
                </a>

                <a href="index.php?page=template_notifikasi" class="nav-item nav-subitem <?= $currentPage === 'template_notifikasi' ? 'active' : '' ?>">
                    <i class="bi bi-chat-left-text"></i>
                    <span>Kelola Template WhatsApp</span>
                </a>
            </div>
        </div>

        <div class="nav-group">
            <div class="nav-group-title">
                <span>Riwayat</span>
            </div>
            <div class="nav-submenu">
                <a href="index.php?page=notifikasi" class="nav-item nav-subitem <?= $currentPage === 'notifikasi' ? 'active' : '' ?>">
                    <i class="bi bi-bell"></i>
                    <span>Riwayat Notifikasi</span>
                </a>
            </div>
        </div>

        <div class="nav-group">
            <div class="nav-group-title">
                <span>Pengaturan</span>
            </div>
            <div class="nav-submenu">
                <a href="index.php?page=pengaturan" class="nav-item nav-subitem <?= $currentPage === 'pengaturan' ? 'active' : '' ?>">
                    <i class="bi bi-sliders"></i>
                    <span>Kelola Profil Bengkel</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <a href="logout.php" class="nav-item nav-logout mt-auto">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </a>
</nav>
</aside>

<main class="content-area">
    <main class="content-area">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" id="sidebarToggle" style="border-radius: 12px; padding: 6px 12px;">
                    <i class="bi bi-list fs-3"></i>
                </button>
                <div>
                    <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Menu') ?></h1>
                    <div class="page-subtitle">Sistem administrasi bengkel Akasia Motor</div>
                </div>
            </div>
            <div class="topbar-user">
                <div class="avatar-circle"><?= htmlspecialchars(strtoupper(substr($adminUser['nama'] ?? 'U', 0, 1))) ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($adminUser['nama'] ?? 'User') ?></div>
                    <div class="user-role">
                        <span class="badge <?= $userRole === 'owner' ? 'bg-primary' : 'bg-success' ?> text-uppercase me-1" style="font-size: 10px;">
                            <?= htmlspecialchars($userRole) ?>
                        </span>
                        <?= htmlspecialchars($adminUser['email'] ?? '') ?>
                    </div>
                </div>
            </div>
        </header>
        <section class="page-body">
            <?php if (!empty($flash)): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>