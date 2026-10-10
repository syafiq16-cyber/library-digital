<?php
/**
 * Controller Manajemen Data Anggota
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Anggota.php';

require_role(['Administrator', 'Petugas']);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$anggotaModel = new Anggota();
$user = current_user();

// 1. TAMBAH ANGGOTA
if ($action === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=anggota_create'));
        exit;
    }

    $nama = trim($_POST['nama'] ?? '');
    $identitasNo = trim($_POST['identitas_no'] ?? '');

    if (empty($nama) || empty($identitasNo)) {
        set_flash('danger', 'Nama lengkap dan NIS/NIP wajib diisi.');
        header('Location: ' . base_url('index.php?page=anggota_create'));
        exit;
    }

    // Cek duplikat identitas_no
    if ($anggotaModel->getByIdentitas($identitasNo)) {
        set_flash('danger', "Nomor identitas (NIS/NIP) '{$identitasNo}' sudah terdaftar pada sistem.");
        header('Location: ' . base_url('index.php?page=anggota_create'));
        exit;
    }

    $jk = $_POST['jenis_kelamin'] ?? 'L';
    $fotoFilename = ($jk === 'P') ? 'default_avatar_f.png' : 'default_avatar_m.png';

    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $fileMime = mime_content_type($_FILES['foto']['tmp_name']);
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (in_array($fileMime, $allowedTypes) && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $newFilename = 'agt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], UPLOAD_ANGGOTA_PATH . '/' . $newFilename)) {
                $fotoFilename = $newFilename;
            }
        }
    }

    try {
        $data = [
            'nomor_anggota'      => trim($_POST['nomor_anggota'] ?? ''),
            'identitas_no'       => $identitasNo,
            'nama'               => $nama,
            'jenis_anggota'      => $_POST['jenis_anggota'] ?? 'Siswa',
            'jenis_kelamin'      => $jk,
            'kelas_id'           => !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : null,
            'jurusan_id'         => !empty($_POST['jurusan_id']) ? (int)$_POST['jurusan_id'] : null,
            'nomor_hp'           => trim($_POST['nomor_hp'] ?? ''),
            'alamat'             => trim($_POST['alamat'] ?? ''),
            'foto'               => $fotoFilename,
            'status_aktif'       => isset($_POST['status_aktif']) ? 1 : 0,
            'tanggal_registrasi' => $_POST['tanggal_registrasi'] ?? date('Y-m-d')
        ];

        $agtId = $anggotaModel->create($data);
        log_activity($user['id'], 'Anggota', 'Tambah', "Mendaftarkan anggota baru: {$nama} ({$identitasNo})");
        set_flash('success', "Anggota '{$nama}' berhasil didaftarkan dengan QR Code otomatis.");
        header('Location: ' . base_url('index.php?page=anggota'));
        exit;
    } catch (Exception $e) {
        set_flash('danger', 'Gagal mendaftarkan anggota: ' . $e->getMessage());
        header('Location: ' . base_url('index.php?page=anggota_create'));
        exit;
    }
}

// 2. EDIT ANGGOTA
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=anggota'));
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $existing = $anggotaModel->getById($id);
    if (!$existing) {
        set_flash('danger', 'Data anggota tidak ditemukan.');
        header('Location: ' . base_url('index.php?page=anggota'));
        exit;
    }

    $fotoFilename = $existing['foto'];
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $fileMime = mime_content_type($_FILES['foto']['tmp_name']);
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (in_array($fileMime, $allowedTypes) && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $newFilename = 'agt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], UPLOAD_ANGGOTA_PATH . '/' . $newFilename)) {
                $fotoFilename = $newFilename;
            }
        }
    }

    $data = [
        'identitas_no'  => trim($_POST['identitas_no'] ?? ''),
        'nama'          => trim($_POST['nama'] ?? ''),
        'jenis_anggota' => $_POST['jenis_anggota'] ?? 'Siswa',
        'jenis_kelamin' => $_POST['jenis_kelamin'] ?? 'L',
        'kelas_id'      => !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : null,
        'jurusan_id'    => !empty($_POST['jurusan_id']) ? (int)$_POST['jurusan_id'] : null,
        'nomor_hp'      => trim($_POST['nomor_hp'] ?? ''),
        'alamat'        => trim($_POST['alamat'] ?? ''),
        'foto'          => $fotoFilename,
        'status_aktif'  => isset($_POST['status_aktif']) ? 1 : 0
    ];

    if ($anggotaModel->update($id, $data)) {
        log_activity($user['id'], 'Anggota', 'Edit', "Memperbarui data anggota: {$data['nama']} (ID: $id)");
        set_flash('success', "Data anggota '{$data['nama']}' berhasil diperbarui.");
    } else {
        set_flash('danger', 'Gagal memperbarui data anggota.');
    }

    header('Location: ' . base_url('index.php?page=anggota'));
    exit;
}

// 3. HAPUS ANGGOTA
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $existing = $anggotaModel->getById($id);
    if ($existing) {
        $activeLoans = $anggotaModel->getActiveLoans($id);
        if (!empty($activeLoans)) {
            set_flash('danger', "Tidak dapat menghapus '{$existing['nama']}' karena masih memiliki buku pinjaman aktif.");
        } else {
            if ($anggotaModel->delete($id)) {
                log_activity($user['id'], 'Anggota', 'Hapus', "Menghapus data anggota: {$existing['nama']} (ID: $id)");
                set_flash('success', "Data anggota '{$existing['nama']}' berhasil dihapus.");
            } else {
                set_flash('danger', 'Gagal menghapus anggota.');
            }
        }
    } else {
        set_flash('danger', 'Anggota tidak ditemukan.');
    }
    header('Location: ' . base_url('index.php?page=anggota'));
    exit;
}
