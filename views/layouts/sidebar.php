<?php
/**
 * Layout Sidebar Navigasi Bertingkat & Responsif
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */
$currentPage = $_GET['page'] ?? 'dashboard';
$user = current_user();
$roleName = current_role_name();
$sidebarSchoolName = get_setting('nama_sekolah', 'SMK Negeri 2 Sumbawa Besar');
?>
<aside class="sidebar">
    <!-- Brand -->
    <a href="<?= base_url('index.php?page=dashboard') ?>" class="sidebar-brand">
        <img src="<?= asset_url('images/logo_smkn2.png') ?>" alt="Logo <?= htmlspecialchars($sidebarSchoolName, ENT_QUOTES, 'UTF-8') ?>">
        <div class="sidebar-brand-text">
            <span class="sidebar-brand-title" style="font-size: 0.85rem; white-space: normal; line-height: 1.2; padding-right: 5px;"><?= htmlspecialchars($sidebarSchoolName, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="sidebar-brand-sub">PERPUSTAKAAN DIGITAL</span>
        </div>
    </a>

    <!-- Menu Navigation -->
    <ul class="sidebar-menu">
        <li class="sidebar-heading">Menu Utama</li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=dashboard') ?>" class="sidebar-link <?= ($currentPage === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Scanner Terpadu Dua QR (Admin & Petugas) -->
        <?php if (has_role(['Administrator', 'Petugas'])): ?>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=scanner') ?>" class="sidebar-link <?= ($currentPage === 'scanner') ? 'active' : '' ?>">
                <i class="bi bi-qr-code-scan"></i>
                <span>Scanner Dua QR</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Master Data (Admin & Petugas) -->
        <?php if (has_role(['Administrator', 'Petugas'])): ?>
        <li class="sidebar-heading">Master Data</li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=buku') ?>" class="sidebar-link <?= ($currentPage === 'buku' || $currentPage === 'buku_create' || $currentPage === 'buku_edit' || $currentPage === 'buku_detail') ? 'active' : '' ?>">
                <i class="bi bi-book-half"></i>
                <span>Data Buku & Stok</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=anggota') ?>" class="sidebar-link <?= ($currentPage === 'anggota' || $currentPage === 'anggota_create' || $currentPage === 'anggota_edit') ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>Data Anggota / Siswa</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=master_kategori') ?>" class="sidebar-link <?= ($currentPage === 'master_kategori') ? 'active' : '' ?>">
                <i class="bi bi-tags-fill"></i>
                <span>Kategori Buku</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=master_pengarang') ?>" class="sidebar-link <?= ($currentPage === 'master_pengarang') ? 'active' : '' ?>">
                <i class="bi bi-person-lines-fill"></i>
                <span>Pengarang</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=master_penerbit') ?>" class="sidebar-link <?= ($currentPage === 'master_penerbit') ? 'active' : '' ?>">
                <i class="bi bi-building"></i>
                <span>Penerbit</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=master_rak') ?>" class="sidebar-link <?= ($currentPage === 'master_rak') ? 'active' : '' ?>">
                <i class="bi bi-inboxes-fill"></i>
                <span>Lokasi Rak</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=master_kelas') ?>" class="sidebar-link <?= ($currentPage === 'master_kelas') ? 'active' : '' ?>">
                <i class="bi bi-mortarboard-fill"></i>
                <span>Tingkat & Rombel</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=master_jurusan') ?>" class="sidebar-link <?= ($currentPage === 'master_jurusan') ? 'active' : '' ?>">
                <i class="bi bi-gear-wide-connected"></i>
                <span>Kompetensi Keahlian</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Transaksi Sirkulasi (Admin & Petugas) -->
        <?php if (has_role(['Administrator', 'Petugas'])): ?>
        <li class="sidebar-heading">Sirkulasi & Transaksi</li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=peminjaman') ?>" class="sidebar-link <?= ($currentPage === 'peminjaman' || $currentPage === 'peminjaman_create') ? 'active' : '' ?>">
                <i class="bi bi-arrow-up-right-square-fill"></i>
                <span>Peminjaman Buku</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=pengembalian') ?>" class="sidebar-link <?= ($currentPage === 'pengembalian' || $currentPage === 'pengembalian_proses') ? 'active' : '' ?>">
                <i class="bi bi-arrow-down-left-square-fill"></i>
                <span>Pengembalian & Denda</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Layanan Perpustakaan (Semua Role) -->
        <li class="sidebar-heading">Layanan & Katalog</li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=katalog') ?>" class="sidebar-link <?= ($currentPage === 'katalog') ? 'active' : '' ?>">
                <i class="bi bi-journals"></i>
                <span>Katalog Buku Digital</span>
            </a>
        </li>
        <?php if (has_role(['Administrator', 'Petugas', 'Kepala Perpustakaan'])): ?>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=pengunjung') ?>" class="sidebar-link <?= ($currentPage === 'pengunjung') ? 'active' : '' ?>">
                <i class="bi bi-person-check-fill"></i>
                <span>Buku Tamu / Pengunjung</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=jurnal') ?>" class="sidebar-link <?= ($currentPage === 'jurnal') ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i>
                <span>Jurnal Kegiatan Harian</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (has_role('Anggota')): ?>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=riwayat_saya') ?>" class="sidebar-link <?= ($currentPage === 'riwayat_saya') ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i>
                <span>Riwayat Pinjaman Saya</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Cetak Massal & Label (Admin & Petugas) -->
        <?php if (has_role(['Administrator', 'Petugas'])): ?>
        <li class="sidebar-heading">Cetak & Barcode</li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=cetak_kartu_massal') ?>" class="sidebar-link <?= ($currentPage === 'cetak_kartu_massal') ? 'active' : '' ?>">
                <i class="bi bi-card-heading"></i>
                <span>Cetak Kartu Anggota</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=cetak_label_massal') ?>" class="sidebar-link <?= ($currentPage === 'cetak_label_massal') ? 'active' : '' ?>">
                <i class="bi bi-upc-scan"></i>
                <span>Cetak Label & QR Buku</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=import') ?>" class="sidebar-link <?= ($currentPage === 'import') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                <span>Import Data Excel/CSV</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Laporan (Admin, Petugas, Kepala Perpustakaan) -->
        <?php if (has_role(['Administrator', 'Petugas', 'Kepala Perpustakaan'])): ?>
        <li class="sidebar-heading">Laporan & Statistik</li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=laporan') ?>" class="sidebar-link <?= ($currentPage === 'laporan') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>Pusat 12 Laporan</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Administrasi Sistem (Hanya Administrator) -->
        <?php if (has_role('Administrator')): ?>
        <li class="sidebar-heading">Administrasi</li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=users') ?>" class="sidebar-link <?= ($currentPage === 'users') ? 'active' : '' ?>">
                <i class="bi bi-person-badge-fill"></i>
                <span>Manajemen User</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=log') ?>" class="sidebar-link <?= ($currentPage === 'log') ? 'active' : '' ?>">
                <i class="bi bi-shield-check"></i>
                <span>Log Aktivitas</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=backup') ?>" class="sidebar-link <?= ($currentPage === 'backup') ? 'active' : '' ?>">
                <i class="bi bi-database-down"></i>
                <span>Backup & Restore DB</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= base_url('index.php?page=setting') ?>" class="sidebar-link <?= ($currentPage === 'setting') ? 'active' : '' ?>">
                <i class="bi bi-sliders"></i>
                <span>Pengaturan Sistem</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Logout -->
        <li class="sidebar-item mt-3 mb-4">
            <a href="<?= base_url('logout.php') ?>" class="sidebar-link text-danger hover-danger" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
                <i class="bi bi-box-arrow-right text-danger"></i>
                <span>Keluar (Logout)</span>
            </a>
        </li>
    </ul>
</aside>
