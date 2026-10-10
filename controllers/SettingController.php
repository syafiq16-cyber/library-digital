<?php
/**
 * Controller Pengaturan Sistem & Profil Sekolah
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=setting'));
        exit;
    }

    $user = current_user();

    $settingsToUpdate = [
        'nama_sekolah'         => trim($_POST['nama_sekolah'] ?? ''),
        'nama_perpustakaan'     => trim($_POST['nama_perpustakaan'] ?? ''),
        'npsn'                 => trim($_POST['npsn'] ?? ''),
        'alamat'               => trim($_POST['alamat'] ?? ''),
        'email'                => trim($_POST['email'] ?? ''),
        'nomor_telepon'        => trim($_POST['nomor_telepon'] ?? ''),
        'kepala_sekolah'       => trim($_POST['kepala_sekolah'] ?? ''),
        'nip_kepala_sekolah'   => trim($_POST['nip_kepala_sekolah'] ?? ''),
        'kepala_perpustakaan'  => trim($_POST['kepala_perpustakaan'] ?? ''),
        'nip_kepala_perpustakaan' => trim($_POST['nip_kepala_perpustakaan'] ?? ''),
        'lama_pinjam_hari'     => max(1, (int)($_POST['lama_pinjam_hari'] ?? 7)),
        'maksimal_buku_pinjam' => max(1, (int)($_POST['maksimal_buku_pinjam'] ?? 3)),
        'denda_per_hari'       => max(0, (float)($_POST['denda_per_hari'] ?? 1000)),
        'denda_rusak_ringan'   => max(0, (float)($_POST['denda_rusak_ringan'] ?? 15000)),
        'denda_rusak_berat'    => max(0, (float)($_POST['denda_rusak_berat'] ?? 50000)),
        'denda_hilang'         => max(0, (float)($_POST['denda_hilang'] ?? 100000)),
        'hari_operasional'     => trim($_POST['hari_operasional'] ?? '')
    ];

    foreach ($settingsToUpdate as $key => $val) {
        update_setting($key, (string)$val);
    }

    // Logo upload
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/png', 'image/jpeg', 'image/webp'];
        $mime = mime_content_type($_FILES['logo']['tmp_name']);
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));

        if (in_array($mime, $allowed) && in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
            $logoName = 'logo_smkn2.' . $ext;
            copy($_FILES['logo']['tmp_name'], __DIR__ . '/../assets/images/' . $logoName);
            update_setting('logo', $logoName);
        }
    }

    log_activity($user['id'], 'Pengaturan', 'Update', 'Memperbarui konfigurasi sistem dan identitas perpustakaan');
    set_flash('success', 'Pengaturan sistem perpustakaan berhasil diperbarui.');
    header('Location: ' . base_url('index.php?page=setting'));
    exit;
}
