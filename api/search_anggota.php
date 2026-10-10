<?php
/**
 * API Pencarian Anggota Real-time
 * Endpoint: /api/search_anggota.php?q=keyword
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Anggota.php';

if (!is_logged_in()) {
    echo json_encode(['results' => []]);
    exit;
}

$q = trim($_GET['q'] ?? '');
$anggotaModel = new Anggota();

$members = $anggotaModel->getAll(['search' => $q], 15, 0);
$results = [];

foreach ($members as $a) {
    $activeLoans = $anggotaModel->getActiveLoans($a['id']);
    $maxPinjam = (int)get_setting('maksimal_buku_pinjam', '3');
    $canBorrow = count($activeLoans) < $maxPinjam && (int)$a['status_aktif'] === 1;

    $photoUrl = !empty($a['foto']) && file_exists(UPLOAD_ANGGOTA_PATH . '/' . $a['foto'])
        ? base_url('uploads/anggota/' . $a['foto'])
        : asset_url('images/' . ($a['jenis_kelamin'] === 'P' ? 'default_avatar_f.png' : 'default_avatar_m.png'));

    $results[] = [
        'id'            => $a['id'],
        'nomor_anggota' => $a['nomor_anggota'],
        'identitas_no'  => $a['identitas_no'],
        'nama'          => $a['nama'],
        'jenis_anggota' => $a['jenis_anggota'],
        'nama_kelas'    => $a['nama_kelas'] ?? '-',
        'kode_jurusan'  => $a['kode_jurusan'] ?? '-',
        'foto_url'      => $photoUrl,
        'status_aktif'  => (int)$a['status_aktif'],
        'total_pinjam'  => count($activeLoans),
        'max_pinjam'    => $maxPinjam,
        'can_borrow'    => $canBorrow,
        'qr_token'      => $a['qr_token']
    ];
}

echo json_encode(['results' => $results]);
