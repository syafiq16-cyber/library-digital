<?php
/**
 * Controller Manajemen Data Buku
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Buku.php';

require_role(['Administrator', 'Petugas']);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$bukuModel = new Buku();
$user = current_user();

// 1. TAMBAH BUKU
if ($action === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=buku_create'));
        exit;
    }

    $judul = trim($_POST['judul'] ?? '');
    if (empty($judul)) {
        set_flash('danger', 'Judul buku wajib diisi.');
        header('Location: ' . base_url('index.php?page=buku_create'));
        exit;
    }

    $coverFilename = 'default_book.png';
    if (!empty($_FILES['cover']['name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $fileMime = mime_content_type($_FILES['cover']['tmp_name']);
        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));

        if (in_array($fileMime, $allowedTypes) && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $newFilename = 'cover_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['cover']['tmp_name'], UPLOAD_COVER_PATH . '/' . $newFilename)) {
                $coverFilename = $newFilename;
            }
        }
    }

    try {
        $stokTotal = max(1, (int)($_POST['stok_total'] ?? 1));
        $data = [
            'kode_buku'      => trim($_POST['kode_buku'] ?? ''),
            'isbn'           => trim($_POST['isbn'] ?? ''),
            'judul'          => $judul,
            'pengarang_id'   => (int)$_POST['pengarang_id'],
            'penerbit_id'    => (int)$_POST['penerbit_id'],
            'kategori_id'    => (int)$_POST['kategori_id'],
            'subkategori'    => trim($_POST['subkategori'] ?? ''),
            'rak_id'         => (int)$_POST['rak_id'],
            'tahun_terbit'   => (int)$_POST['tahun_terbit'],
            'edisi'          => trim($_POST['edisi'] ?? 'Cetakan 1'),
            'bahasa'         => trim($_POST['bahasa'] ?? 'Indonesia'),
            'jumlah_halaman' => (int)($_POST['jumlah_halaman'] ?? 100),
            'sumber_buku'    => $_POST['sumber_buku'] ?? 'BOS',
            'harga'          => (float)($_POST['harga'] ?? 0),
            'stok_total'     => $stokTotal,
            'stok_tersedia'  => $stokTotal,
            'cover'          => $coverFilename,
            'deskripsi'      => trim($_POST['deskripsi'] ?? '')
        ];

        $bukuId = $bukuModel->create($data);
        log_activity($user['id'], 'Buku', 'Tambah', "Menambahkan buku baru: {$judul} (ID: $bukuId) dengan $stokTotal eksemplar");
        set_flash('success', "Buku '{$judul}' dan {$stokTotal} eksemplar QR Code berhasil ditambahkan.");
        header('Location: ' . base_url('index.php?page=buku'));
        exit;
    } catch (Exception $e) {
        set_flash('danger', 'Gagal menambahkan buku: ' . $e->getMessage());
        header('Location: ' . base_url('index.php?page=buku_create'));
        exit;
    }
}

// 2. EDIT BUKU
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=buku'));
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $existing = $bukuModel->getById($id);
    if (!$existing) {
        set_flash('danger', 'Data buku tidak ditemukan.');
        header('Location: ' . base_url('index.php?page=buku'));
        exit;
    }

    $coverFilename = $existing['cover'];
    if (!empty($_FILES['cover']['name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $fileMime = mime_content_type($_FILES['cover']['tmp_name']);
        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));

        if (in_array($fileMime, $allowedTypes) && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $newFilename = 'cover_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['cover']['tmp_name'], UPLOAD_COVER_PATH . '/' . $newFilename)) {
                $coverFilename = $newFilename;
            }
        }
    }

    $data = [
        'isbn'           => trim($_POST['isbn'] ?? ''),
        'judul'          => trim($_POST['judul'] ?? ''),
        'pengarang_id'   => (int)$_POST['pengarang_id'],
        'penerbit_id'    => (int)$_POST['penerbit_id'],
        'kategori_id'    => (int)$_POST['kategori_id'],
        'subkategori'    => trim($_POST['subkategori'] ?? ''),
        'rak_id'         => (int)$_POST['rak_id'],
        'tahun_terbit'   => (int)$_POST['tahun_terbit'],
        'edisi'          => trim($_POST['edisi'] ?? 'Cetakan 1'),
        'bahasa'         => trim($_POST['bahasa'] ?? 'Indonesia'),
        'jumlah_halaman' => (int)($_POST['jumlah_halaman'] ?? 100),
        'sumber_buku'    => $_POST['sumber_buku'] ?? 'BOS',
        'harga'          => (float)($_POST['harga'] ?? 0),
        'cover'          => $coverFilename,
        'deskripsi'      => trim($_POST['deskripsi'] ?? ''),
        'status'         => $_POST['status'] ?? 'Tersedia'
    ];

    if ($bukuModel->update($id, $data)) {
        log_activity($user['id'], 'Buku', 'Edit', "Memperbarui data buku: {$data['judul']} (ID: $id)");
        set_flash('success', "Data buku '{$data['judul']}' berhasil diperbarui.");
    } else {
        set_flash('danger', 'Gagal memperbarui data buku.');
    }

    header('Location: ' . base_url('index.php?page=buku'));
    exit;
}

// 3. HAPUS BUKU
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $existing = $bukuModel->getById($id);
    if ($existing) {
        if ($bukuModel->delete($id)) {
            log_activity($user['id'], 'Buku', 'Hapus', "Menghapus data buku: {$existing['judul']} (ID: $id)");
            set_flash('success', "Buku '{$existing['judul']}' berhasil dihapus.");
        } else {
            set_flash('danger', 'Gagal menghapus buku. Buku mungkin sedang terkait dengan transaksi aktif.');
        }
    } else {
        set_flash('danger', 'Buku tidak ditemukan.');
    }
    header('Location: ' . base_url('index.php?page=buku'));
    exit;
}
