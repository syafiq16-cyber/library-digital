<?php
/**
 * Layout Topbar Navbar
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */
$user = current_user();
$roleName = current_role_name();
$flash = get_flash();
?>
<header class="topbar">
    <div class="topbar-left">
        <button id="sidebarToggleBtn" class="btn-sidebar-toggle" title="Toggle Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>

        <!-- Global Search Bar -->
        <div class="global-search-container">
            <i class="bi bi-search global-search-icon"></i>
            <input type="text" id="globalSearchInput" class="global-search-input" placeholder="Cari buku, anggota, atau transaksi..." autocomplete="off">
        </div>
    </div>

    <div class="topbar-right">
        <!-- Quick Scanner Button -->
        <?php if (has_role(['Administrator', 'Petugas'])): ?>
        <a href="<?= base_url('index.php?page=scanner') ?>" class="btn btn-sm btn-outline-primary rounded-pill d-none d-md-inline-flex align-items-center gap-1">
            <i class="bi bi-qr-code-scan"></i>
            <span>Scan QR</span>
        </a>
        <?php endif; ?>

        <!-- Theme Toggle (Light / Dark Mode) -->
        <button id="themeToggleBtn" class="theme-toggle-btn" title="Ganti Tema Gelap/Terang">
            <i id="themeIcon" class="bi bi-sun-fill text-warning"></i>
            <span id="themeText" class="d-none d-sm-inline">Light</span>
        </button>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <div class="user-profile-badge dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-profile-avatar">
                    <?= strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="user-profile-info d-none d-md-flex">
                    <span class="user-profile-name"><?= htmlspecialchars($user['nama_lengkap'] ?? 'Pengguna', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="user-profile-role"><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2 mt-2" style="border-radius:12px; min-width:200px;">
                <li class="px-3 py-2 border-bottom">
                    <div class="fw-bold text-truncate"><?= htmlspecialchars($user['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <small class="text-muted"><?= htmlspecialchars($user['email'] ?? $user['username'], ENT_QUOTES, 'UTF-8') ?></small>
                </li>
                <?php if (has_role('Administrator')): ?>
                <li><a class="dropdown-item py-2" href="<?= base_url('index.php?page=setting') ?>"><i class="bi bi-sliders me-2 text-primary"></i>Pengaturan Sistem</a></li>
                <li><a class="dropdown-item py-2" href="<?= base_url('index.php?page=log') ?>"><i class="bi bi-shield-check me-2 text-info"></i>Log Aktivitas</a></li>
                <?php endif; ?>
                <li><a class="dropdown-item py-2 text-danger" href="<?= base_url('logout.php') ?>" onclick="return confirm('Keluar dari sesi ini?');"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
            </ul>
        </div>
    </div>
</header>

<!-- Flash Toast Notifications -->
<?php if ($flash): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        showToast('<?= $flash['type'] ?>', '<?= addslashes($flash['message']) ?>');
    });
</script>
<?php endif; ?>
