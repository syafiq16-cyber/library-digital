<?php
/**
 * Controller Transaksi Peminjaman, Pengembalian, & Perpanjangan
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Peminjaman.php';
require_once __DIR__ . '/../models/Pengembalian.php';
require_once __DIR__ . '/../models/Anggota.php';
require_once __DIR__ . '/../models/Buku.php';

require_role(['Administrator', 'Petugas']);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$peminjamanModel = new Peminjaman();
$pengembalianModel = new Pengembalian();
$user = current_user();

// 1. PROSES SIMPAN PEMINJAMAN
if ($action === 'pinjam_store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=peminjaman_create'));
        exit;
    }

    $anggotaId = (int)($_POST['anggota_id'] ?? 0);
    $bukuIds = $_POST['buku_id'] ?? [];
    $eksemplarIds = $_POST['eksemplar_id'] ?? [];

    if (!$anggotaId || empty($bukuIds)) {
        set_flash('danger', 'Silakan pilih anggota dan minimal satu buku yang akan dipinjam.');
        header('Location: ' . base_url('index.php?page=peminjaman_create'));
        exit;
    }

    $anggotaModel = new Anggota();
    $anggota = $anggotaModel->getById($anggotaId);
    if (!$anggota || (int)$anggota['status_aktif'] !== 1) {
        set_flash('danger', 'Anggota tidak valid atau statusnya dinonaktifkan.');
        header('Location: ' . base_url('index.php?page=peminjaman_create'));
        exit;
    }

    // Validasi kuota pinjam
    $activeLoans = $anggotaModel->getActiveLoans($anggotaId);
    $maxPinjam = (int)get_setting('maksimal_buku_pinjam', '3');
    if ((count($activeLoans) + count($bukuIds)) > $maxPinjam) {
        set_flash('danger', "Batas maksimal peminjaman adalah {$maxPinjam} buku. Anggota saat ini sedang meminjam " . count($activeLoans) . " buku.");
        header('Location: ' . base_url('index.php?page=peminjaman_create'));
        exit;
    }

    // Siapkan array item buku
    $bukuItems = [];
    $bukuModel = new Buku();
    foreach ($bukuIds as $idx => $bId) {
        $bId = (int)$bId;
        $book = $bukuModel->getById($bId);
        if (!$book || $book['stok_tersedia'] <= 0) {
            set_flash('danger', "Stok buku '" . ($book['judul'] ?? "ID $bId") . "' tidak mencukupi untuk dipinjam.");
            header('Location: ' . base_url('index.php?page=peminjaman_create'));
            exit;
        }

        $exId = !empty($eksemplarIds[$idx]) ? (int)$eksemplarIds[$idx] : null;
        $bukuItems[] = [
            'buku_id'      => $bId,
            'eksemplar_id' => $exId
        ];
    }

    $durasiPinjamHari = (int)get_setting('lama_pinjam_hari', '7');
    $tglPinjam = $_POST['tanggal_pinjam'] ?? date('Y-m-d');
    $tglTempo = !empty($_POST['tanggal_jatuh_tempo']) 
        ? $_POST['tanggal_jatuh_tempo'] 
        : date('Y-m-d', strtotime("+$durasiPinjamHari days", strtotime($tglPinjam)));

    try {
        $dataPinjam = [
            'nomor_transaksi'     => $_POST['nomor_transaksi'] ?? '',
            'anggota_id'          => $anggotaId,
            'petugas_id'          => $user['id'],
            'tanggal_pinjam'      => $tglPinjam,
            'tanggal_jatuh_tempo' => $tglTempo,
            'catatan'             => trim($_POST['catatan'] ?? '')
        ];

        $pId = $peminjamanModel->create($dataPinjam, $bukuItems);
        log_activity($user['id'], 'Peminjaman', 'Tambah', "Membuat transaksi peminjaman ID: $pId untuk {$anggota['nama']} (" . count($bukuItems) . " buku)");
        set_flash('success', "Transaksi peminjaman berhasil disimpan! Stok buku berhasil disesuaikan.");
        header('Location: ' . base_url('index.php?page=peminjaman_detail&id=' . $pId));
        exit;
    } catch (Exception $e) {
        set_flash('danger', 'Gagal memproses peminjaman: ' . $e->getMessage());
        header('Location: ' . base_url('index.php?page=peminjaman_create'));
        exit;
    }
}

// 2. PROSES PENGEMBALIAN BUKU
if ($action === 'kembali_store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=pengembalian'));
        exit;
    }

    $peminjamanId = (int)($_POST['peminjaman_id'] ?? 0);
    $tglKembali = $_POST['tanggal_kembali'] ?? date('Y-m-d');
    $dendaDibayar = (float)($_POST['denda_dibayar'] ?? 0);
    $selectedDetails = $_POST['selected_detail_id'] ?? [];
    $kondisiMap = $_POST['kondisi_buku'] ?? [];
    $catatanMap = $_POST['catatan_detail'] ?? [];

    if (!$peminjamanId || empty($selectedDetails)) {
        set_flash('danger', 'Pilih minimal satu buku yang dikembalikan.');
        header('Location: ' . base_url('index.php?page=pengembalian_proses&pinjam_id=' . $peminjamanId));
        exit;
    }

    // Ambil detail pinjaman yang ada
    $allDetails = $peminjamanModel->getDetails($peminjamanId);
    $detailLookup = [];
    foreach ($allDetails as $d) {
        $detailLookup[$d['id']] = $d;
    }

    $returnedItems = [];
    foreach ($selectedDetails as $detId) {
        $detId = (int)$detId;
        if (isset($detailLookup[$detId])) {
            $d = $detailLookup[$detId];
            $kondisi = $kondisiMap[$detId] ?? 'Baik';
            $cat = $catatanMap[$detId] ?? '';

            $returnedItems[] = [
                'peminjaman_detail_id' => $detId,
                'buku_id'              => $d['buku_id'],
                'eksemplar_id'         => $d['eksemplar_id'],
                'kondisi_buku'         => $kondisi,
                'catatan'              => $cat
            ];
        }
    }

    try {
        $kId = $pengembalianModel->processReturn(
            $peminjamanId,
            $user['id'],
            $tglKembali,
            $returnedItems,
            $dendaDibayar,
            trim($_POST['catatan_umum'] ?? '')
        );

        log_activity($user['id'], 'Pengembalian', 'Proses', "Memproses pengembalian ID: $kId untuk peminjaman ID: $peminjamanId (" . count($returnedItems) . " buku)");
        set_flash('success', "Pengembalian buku berhasil diproses! Stok dan status transaksi telah diperbarui.");
        header('Location: ' . base_url('index.php?page=pengembalian_detail&id=' . $kId));
        exit;
    } catch (Exception $e) {
        set_flash('danger', 'Gagal memproses pengembalian: ' . $e->getMessage());
        header('Location: ' . base_url('index.php?page=pengembalian_proses&pinjam_id=' . $peminjamanId));
        exit;
    }
}

// 3. PROSES PERPANJANGAN PEMINJAMAN
if ($action === 'extend_loan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=peminjaman'));
        exit;
    }

    $peminjamanId = (int)($_POST['peminjaman_id'] ?? 0);
    $tambahHari = max(1, (int)($_POST['tambah_hari'] ?? get_setting('lama_pinjam_hari', '7')));
    $catatan = trim($_POST['catatan'] ?? '');

    try {
        $pengembalianModel->extendLoan($peminjamanId, $user['id'], $tambahHari, $catatan);
        log_activity($user['id'], 'Perpanjangan', 'Tambah', "Memperpanjang masa pinjam transaksi ID: $peminjamanId selama $tambahHari hari");
        set_flash('success', "Masa peminjaman berhasil diperpanjang {$tambahHari} hari ke depan.");
    } catch (Exception $e) {
        set_flash('danger', 'Gagal memperpanjang masa pinjam: ' . $e->getMessage());
    }

    header('Location: ' . base_url('index.php?page=peminjaman_detail&id=' . $peminjamanId));
    exit;
}
