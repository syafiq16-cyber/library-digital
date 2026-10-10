<?php
/**
 * Front Controller & Routing Utama
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';

$page = $_GET['page'] ?? 'dashboard';

// Rute Publik (Katalog dapat diakses publik atau harus login jika diperlukan)
if ($page === 'katalog_publik') {
    require_once __DIR__ . '/views/katalog/index.php';
    exit;
}

// Seluruh rute lain mewajibkan autentikasi login
require_login();

switch ($page) {
    case 'dashboard':
        require_once __DIR__ . '/views/dashboard/index.php';
        break;

    // Scanner Dua QR
    case 'scanner':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/scanner/index.php';
        break;

    // Master Buku
    case 'buku':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/buku/index.php';
        break;
    case 'buku_create':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/buku/create.php';
        break;
    case 'buku_edit':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/buku/edit.php';
        break;
    case 'buku_detail':
        require_once __DIR__ . '/views/buku/detail.php';
        break;

    // Master Anggota
    case 'anggota':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/anggota/index.php';
        break;
    case 'anggota_create':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/anggota/create.php';
        break;
    case 'anggota_edit':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/anggota/edit.php';
        break;
    case 'riwayat_saya':
        require_once __DIR__ . '/views/anggota/riwayat_saya.php';
        break;

    // Master Data Lainnya
    case 'master_kategori':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/master/kategori.php';
        break;
    case 'master_pengarang':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/master/pengarang.php';
        break;
    case 'master_penerbit':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/master/penerbit.php';
        break;
    case 'master_rak':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/master/rak.php';
        break;
    case 'master_kelas':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/master/kelas.php';
        break;
    case 'master_jurusan':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/master/jurusan.php';
        break;

    // Transaksi Peminjaman
    case 'peminjaman':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/peminjaman/index.php';
        break;
    case 'peminjaman_create':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/peminjaman/create.php';
        break;
    case 'peminjaman_detail':
        require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);
        require_once __DIR__ . '/views/peminjaman/detail.php';
        break;
    case 'peminjaman_cetak':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/peminjaman/cetak_bukti.php';
        break;

    // Transaksi Pengembalian & Denda
    case 'pengembalian':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/pengembalian/index.php';
        break;
    case 'pengembalian_proses':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/pengembalian/proses.php';
        break;
    case 'pengembalian_detail':
        require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);
        require_once __DIR__ . '/views/pengembalian/detail.php';
        break;

    // Layanan Perpustakaan
    case 'katalog':
        require_once __DIR__ . '/views/katalog/index.php';
        break;
    case 'pengunjung':
        require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);
        require_once __DIR__ . '/views/pengunjung/index.php';
        break;
    case 'jurnal':
        require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);
        require_once __DIR__ . '/views/jurnal/index.php';
        break;

    // Cetak Massal Kartu & Label
    case 'cetak_kartu_massal':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/anggota/cetak_kartu.php';
        break;
    case 'cetak_label_massal':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/label/cetak_label.php';
        break;

    // Import / Export
    case 'import':
        require_role(['Administrator', 'Petugas']);
        require_once __DIR__ . '/views/import/index.php';
        break;

    // Laporan
    case 'laporan':
        require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);
        require_once __DIR__ . '/views/laporan/index.php';
        break;
    case 'laporan_cetak':
        require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);
        require_once __DIR__ . '/views/laporan/cetak.php';
        break;
    case 'laporan_export':
        require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);
        require_once __DIR__ . '/views/laporan/export_excel.php';
        break;

    // Administrasi & Pengaturan
    case 'users':
        require_role('Administrator');
        require_once __DIR__ . '/views/users/index.php';
        break;
    case 'log':
        require_role('Administrator');
        require_once __DIR__ . '/views/log/index.php';
        break;
    case 'backup':
        require_role('Administrator');
        require_once __DIR__ . '/views/backup/index.php';
        break;
    case 'setting':
        require_role('Administrator');
        require_once __DIR__ . '/views/setting/index.php';
        break;

    default:
        http_response_code(404);
        require_once __DIR__ . '/views/layouts/header.php';
        require_once __DIR__ . '/views/layouts/sidebar.php';
        echo '<div class="main-content"><div class="page-container text-center py-5">';
        echo '<h1 class="display-4 fw-bold text-muted">404</h1>';
        echo '<p class="lead">Halaman yang Anda tuju tidak ditemukan.</p>';
        echo '<a href="' . base_url('index.php?page=dashboard') . '" class="btn btn-primary-custom">Kembali ke Dashboard</a>';
        echo '</div></div>';
        require_once __DIR__ . '/views/layouts/footer.php';
        break;
}
