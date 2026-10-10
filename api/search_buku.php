<?php
/**
 * API Pencarian Buku Real-time untuk Transaksi & Katalog
 * Endpoint: /api/search_buku.php?q=keyword
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Buku.php';

$q = trim($_GET['q'] ?? '');
$bukuModel = new Buku();

$filters = ['search' => $q];
if (isset($_GET['available_only']) && $_GET['available_only'] == '1') {
    $filters['status'] = 'Tersedia';
}

$books = $bukuModel->getAll($filters, 15, 0);
$results = [];

foreach ($books as $b) {
    $coverUrl = !empty($b['cover']) && file_exists(UPLOAD_COVER_PATH . '/' . $b['cover'])
        ? base_url('uploads/covers/' . $b['cover'])
        : asset_url('images/default_book.png');

    $results[] = [
        'id'             => $b['id'],
        'kode_buku'      => $b['kode_buku'],
        'isbn'           => $b['isbn'] ?? '-',
        'judul'          => $b['judul'],
        'nama_pengarang' => $b['nama_pengarang'] ?? '-',
        'nama_kategori'  => $b['nama_kategori'] ?? '-',
        'nama_rak'       => $b['nama_rak'] ?? '-',
        'stok_tersedia'  => (int)$b['stok_tersedia'],
        'cover_url'      => $coverUrl,
        'qr_token'       => $b['qr_token']
    ];
}

echo json_encode(['results' => $results]);
