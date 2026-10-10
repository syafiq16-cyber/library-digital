<?php
/**
 * API Handler Pemrosesan Dua QR Code
 * Endpoint: /api/scan_handler.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/qrcode_helper.php';
require_once __DIR__ . '/../models/Anggota.php';
require_once __DIR__ . '/../models/Buku.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Autentikasi diperlukan']);
    exit;
}

$rawQr = trim($_POST['qr_data'] ?? $_GET['qr_data'] ?? '');
$expectedType = trim($_POST['expected_type'] ?? $_GET['expected_type'] ?? ''); // 'MEMBER', 'BOOK', or empty
$action = trim($_POST['action'] ?? $_GET['action'] ?? 'Scan Universal');

if (empty($rawQr)) {
    echo json_encode(['success' => false, 'message' => 'Data QR Code kosong']);
    exit;
}

$parse = parse_and_validate_qr($rawQr);
$user = current_user();

// Jika bukan QR perpustakaan
if (!$parse['valid']) {
    log_scan('UNKNOWN', $rawQr, $user['id'], $action, 'Format QR tidak dikenali');
    echo json_encode([
        'success' => false,
        'type'    => 'UNKNOWN',
        'message' => 'QR Code tidak dikenali sebagai QR Perpustakaan SMKN 2 Sumbawa Besar.'
    ]);
    exit;
}

// Validasi silang: jika konteks sedang memilih anggota tapi yang discan adalah buku
if ($expectedType === 'MEMBER' && $parse['type'] !== 'MEMBER') {
    log_scan('BOOK', $rawQr, $user['id'], $action, 'Salah scan: QR Buku terdeteksi saat memilih Anggota');
    echo json_encode([
        'success'      => false,
        'type'         => 'BOOK',
        'is_mismatch'  => true,
        'message'      => 'QR Code Buku terdeteksi. Silakan scan QR Code Anggota.'
    ]);
    exit;
}

// Validasi silang: jika konteks sedang memilih buku tapi yang discan adalah anggota
if ($expectedType === 'BOOK' && $parse['type'] !== 'BOOK') {
    log_scan('MEMBER', $rawQr, $user['id'], $action, 'Salah scan: QR Anggota terdeteksi saat memilih Buku');
    echo json_encode([
        'success'      => false,
        'type'         => 'MEMBER',
        'is_mismatch'  => true,
        'message'      => 'QR Code Anggota terdeteksi. Silakan scan QR Code Buku.'
    ]);
    exit;
}

// 1. TIPE QR ANGGOTA
if ($parse['type'] === 'MEMBER') {
    $anggotaModel = new Anggota();
    $nomorAnggota = $parse['identifier'];

    $anggota = $anggotaModel->getByNomorAnggota($nomorAnggota);
    if (!$anggota) {
        $anggota = $anggotaModel->getByQrToken($rawQr);
    }

    if (!$anggota) {
        log_scan('MEMBER', $rawQr, $user['id'], $action, 'Anggota tidak ditemukan di database');
        echo json_encode([
            'success' => false,
            'type'    => 'MEMBER',
            'message' => "Anggota dengan nomor {$nomorAnggota} tidak terdaftar di database."
        ]);
        exit;
    }

    // Ambil daftar pinjaman aktif
    $activeLoans = $anggotaModel->getActiveLoans($anggota['id']);
    $maxPinjam = (int)get_setting('maksimal_buku_pinjam', '3');
    $canBorrow = count($activeLoans) < $maxPinjam && (int)$anggota['status_aktif'] === 1;

    $photoUrl = !empty($anggota['foto']) && file_exists(UPLOAD_ANGGOTA_PATH . '/' . $anggota['foto'])
        ? base_url('uploads/anggota/' . $anggota['foto'])
        : asset_url('images/' . ($anggota['jenis_kelamin'] === 'P' ? 'default_avatar_f.png' : 'default_avatar_m.png'));

    log_scan('MEMBER', $rawQr, $user['id'], $action, "Ditemukan: {$anggota['nama']} ({$anggota['nomor_anggota']})");

    echo json_encode([
        'success'      => true,
        'type'         => 'MEMBER',
        'data'         => [
            'id'             => $anggota['id'],
            'nomor_anggota'  => $anggota['nomor_anggota'],
            'identitas_no'   => $anggota['identitas_no'],
            'nama'           => $anggota['nama'],
            'jenis_anggota'  => $anggota['jenis_anggota'],
            'jenis_kelamin'  => $anggota['jenis_kelamin'],
            'nama_kelas'     => $anggota['nama_kelas'] ?? '-',
            'kode_jurusan'   => $anggota['kode_jurusan'] ?? '-',
            'nomor_hp'       => $anggota['nomor_hp'] ?? '-',
            'alamat'         => $anggota['alamat'] ?? '-',
            'foto_url'       => $photoUrl,
            'status_aktif'   => (int)$anggota['status_aktif'],
            'active_loans'   => $activeLoans,
            'total_pinjam'   => count($activeLoans),
            'max_pinjam'     => $maxPinjam,
            'can_borrow'     => $canBorrow
        ],
        'message'      => "Data anggota {$anggota['nama']} berhasil dimuat."
    ]);
    exit;
}

// 2. TIPE QR BUKU
if ($parse['type'] === 'BOOK') {
    $bukuModel = new Buku();
    $identifier = $parse['identifier'];

    // Cek apakah berupa eksemplar tertentu (misal BK-00001-01) atau kode buku judul (misal BK-00001)
    $eksemplar = $bukuModel->findEksemplar($identifier);
    $buku = null;

    if ($eksemplar) {
        $buku = $bukuModel->getById($eksemplar['buku_id']);
    } else {
        $buku = $bukuModel->getByKode($identifier);
        if (!$buku) {
            $buku = $bukuModel->getByQrToken($rawQr);
        }
    }

    if (!$buku) {
        log_scan('BOOK', $rawQr, $user['id'], $action, 'Buku tidak ditemukan di database');
        echo json_encode([
            'success' => false,
            'type'    => 'BOOK',
            'message' => "Buku dengan kode {$identifier} tidak ditemukan di katalog."
        ]);
        exit;
    }

    $coverUrl = !empty($buku['cover']) && file_exists(UPLOAD_COVER_PATH . '/' . $buku['cover'])
        ? base_url('uploads/covers/' . $buku['cover'])
        : asset_url('images/default_book.png');

    log_scan('BOOK', $rawQr, $user['id'], $action, "Ditemukan: {$buku['judul']} ({$buku['kode_buku']})");

    echo json_encode([
        'success'      => true,
        'type'         => 'BOOK',
        'data'         => [
            'id'             => $buku['id'],
            'kode_buku'      => $buku['kode_buku'],
            'isbn'           => $buku['isbn'] ?? '-',
            'judul'          => $buku['judul'],
            'nama_pengarang' => $buku['nama_pengarang'] ?? '-',
            'nama_kategori'  => $buku['nama_kategori'] ?? '-',
            'nama_rak'       => $buku['nama_rak'] ?? '-',
            'kode_rak'       => $buku['kode_rak'] ?? '-',
            'tahun_terbit'   => $buku['tahun_terbit'],
            'stok_total'     => (int)$buku['stok_total'],
            'stok_tersedia'  => (int)$buku['stok_tersedia'],
            'cover_url'      => $coverUrl,
            'status'         => $buku['status'],
            'is_available'   => (int)$buku['stok_tersedia'] > 0,
            'eksemplar_info' => $eksemplar ? [
                'id'             => $eksemplar['id'],
                'kode_eksemplar' => $eksemplar['kode_eksemplar'],
                'kondisi'        => $eksemplar['kondisi'],
                'status'         => $eksemplar['status']
            ] : null
        ],
        'message'      => "Data buku {$buku['judul']} berhasil dimuat."
    ]);
    exit;
}
