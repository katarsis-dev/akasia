<?php if (isset($_SESSION['user_id'])): ?>
    <div class="bottom-nav d-flex d-lg-none py-1">
        <a href="?page=home" class="nav-item-bottom <?= $page == 'home' ? 'active' : '' ?> flex-fill">
            <i class="bi bi-house-door"></i>
            <span>Beranda</span>
        </a>
        <a href="?page=dashboard" class="nav-item-bottom <?= $page == 'dashboard' ? 'active' : '' ?> flex-fill">
            <i class="bi bi-speedometer2"></i>
            <span>Monitoring Status Servis</span>
        </a>
        <a href="?page=booking" class="nav-item-bottom <?= $page == 'booking' ? 'active' : '' ?> flex-fill">
            <i class="bi bi-calendar-plus"></i>
            <span>Reservasi Servis</span>
        </a>
        <a href="?page=notifikasi" class="nav-item-bottom <?= $page == 'notifikasi' ? 'active' : '' ?> flex-fill">
            <i class="bi bi-bell"></i>
            <span>Notif</span>
        </a>
        <a href="?page=profil" class="nav-item-bottom <?= $page == 'profil' ? 'active' : '' ?> flex-fill">
            <i class="bi bi-person-badge"></i>
            <span>Akun</span>
        </a>
    </div>
<?php endif; ?>
<footer class="text-center text-muted py-4 mt-auto border-top bg-white">
    <div class="container">
        <small>&copy; <?= date('Y') ?> Bengkel Akasia Motor. All rights reserved.</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>