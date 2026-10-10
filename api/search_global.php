<?php
/**
 * API Global Search Navbar
 * Endpoint: /api/search_global.php?q=keyword
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

if (!is_logged_in()) {
    echo json_encode(['results' => []]);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$db = get_db();
$results = [];

// 1. Cari Buku
$stmtB = $db->prepare("
    SELECT id, judul, kode_buku, isbn 
    FROM buku 
    WHERE judul LIKE :q OR kode_buku LIKE :q OR isbn LIKE :q 
    LIMIT 4
");
$stmtB->execute([':q' => "%$q%"]);
while ($row = $stmtB->fetch()) {
    $results[] = [
        'title'    => $row['judul'],
        'category' => 'Buku',
        'subtitle' => $row['kode_buku'],
        'url'      => base_url('index.php?page=buku_detail&id=' . $row['id']),
        'icon'     => 'bi-book'
    ];
}

// 2. Cari Anggota
if (has_role(['Administrator', 'Petugas'])) {
    $stmtA = $db->prepare("
        SELECT id, nama, nomor_anggota, identitas_no 
        FROM anggota 
        WHERE nama LIKE :q OR nomor_anggota LIKE :q OR identitas_no LIKE :q 
        LIMIT 4
    ");
    $stmtA->execute([':q' => "%$q%"]);
    while ($row = $stmtA->fetch()) {
        $results[] = [
            'title'    => $row['nama'],
            'category' => 'Anggota',
            'subtitle' => $row['nomor_anggota'] . ' (' . $row['identitas_no'] . ')',
            'url'      => base_url('index.php?page=anggota_edit&id=' . $row['id']),
            'icon'     => 'bi-person-badge'
        ];
    }
}

// 3. Cari Transaksi Peminjaman
if (has_role(['Administrator', 'Petugas'])) {
    $stmtP = $db->prepare("
        SELECT p.id, p.nomor_transaksi, a.nama AS nama_anggota 
        FROM peminjaman p 
        JOIN anggota a ON p.anggota_id = a.id 
        WHERE p.nomor_transaksi LIKE :q 
        LIMIT 3
    ");
    $stmtP->execute([':q' => "%$q%"]);
    while ($row = $stmtP->fetch()) {
        $results[] = [
            'title'    => $row['nomor_transaksi'],
            'category' => 'Peminjaman',
            'subtitle' => 'Peminjam: ' . $row['nama_anggota'],
            'url'      => base_url('index.php?page=peminjaman_detail&id=' . $row['id']),
            'icon'     => 'bi-arrow-left-right'
        ];
    }
}

echo json_encode(['results' => $results]);
