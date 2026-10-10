<?php
/**
 * Import Massal Data Buku & Anggota dari Excel (.xlsx, .xls) & CSV
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

$pageTitle = 'Import Data';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/qrcode_helper.php';
require_once __DIR__ . '/../../config/excel_helper.php';
require_once __DIR__ . '/../../models/Buku.php';
require_once __DIR__ . '/../../models/Anggota.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas']);

$currentUser = current_user();
$masterModel = new Master();
$kategoriList = $masterModel->getKategori();
$pengarangList = $masterModel->getPengarang();
$penerbitList  = $masterModel->getPenerbit();
$rakList       = $masterModel->getRak();
$kelasList     = $masterModel->getKelas();
$jurusanList   = $masterModel->getJurusan();

// ===================== DOWNLOAD TEMPLATE EXCEL / CSV =====================
if (isset($_GET['download'])) {
    $dl = $_GET['download'];

    if ($dl === 'template_buku_xlsx') {
        $headers = ['ISBN', 'Judul', 'Pengarang ID', 'Penerbit ID', 'Kategori ID', 'Rak ID', 'Tahun Terbit', 'Stok Total'];
        $sample = [
            ['978-602-0123-45-6', 'Pemrograman Web Berbasis PHP & MySQL', '1', '1', '1', '1', '2024', '5'],
            ['978-602-0987-65-4', 'Dasar Jaringan Komputer & Telekomunikasi', '2', '2', '2', '2', '2023', '3'],
            ['', 'Matematika Terapan untuk SMK', '1', '2', '3', '1', '2024', '10']
        ];
        download_template_xlsx('template_import_buku.xlsx', $headers, $sample);
    } elseif ($dl === 'template_anggota_xlsx') {
        $headers = ['Nama', 'NIS/NISN', 'Jenis Kelamin (L/P)', 'Kelas ID', 'Jurusan ID', 'No HP', 'Jenis Anggota'];
        $sample = [
            ['Ahmad Fauzi', '2024001', 'L', '1', '1', '081234567890', 'Siswa'],
            ['Siti Rahayu', '2024002', 'P', '2', '2', '085234567891', 'Siswa'],
            ['Drs. Bambang Sudiro', '197501012005011002', 'L', '', '1', '087812345678', 'Guru']
        ];
        download_template_xlsx('template_import_anggota.xlsx', $headers, $sample);
    }
}

$tab = $_GET['tab'] ?? 'buku';
$result = ['success' => 0, 'skip' => 0, 'error' => 0, 'messages' => []];

// ===================== PROSES IMPORT =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=import&tab=' . $tab));
        exit;
    }

    $importType = $_POST['import_type'] ?? 'buku';
    $file = $_FILES['excel_file'] ?? $_FILES['csv_file'] ?? null;

    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        set_flash('danger', 'Silakan pilih file Excel (.xlsx, .xls) atau CSV (.csv) yang valid.');
        header('Location: ' . base_url('index.php?page=import&tab=' . $importType));
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'])) {
        set_flash('danger', 'Format file .' . htmlspecialchars($ext) . ' tidak didukung. Harap unggah file Excel (.xlsx, .xls) atau CSV.');
        header('Location: ' . base_url('index.php?page=import&tab=' . $importType));
        exit;
    }

    try {
        $rows = parse_spreadsheet_file($file['tmp_name'], $file['name']);
    } catch (Exception $e) {
        set_flash('danger', 'Gagal memproses file: ' . $e->getMessage());
        header('Location: ' . base_url('index.php?page=import&tab=' . $importType));
        exit;
    }

    if (empty($rows) || count($rows) < 2) {
        set_flash('danger', 'File tidak berisi baris data atau hanya berisi baris judul header.');
        header('Location: ' . base_url('index.php?page=import&tab=' . $importType));
        exit;
    }

    // Buang baris header
    array_shift($rows);
    $rowNum = 1;

    if ($importType === 'buku') {
        $bukuModel = new Buku();
        foreach ($rows as $row) {
            $rowNum++;
            if (count($row) < 6) {
                $result['error']++;
                $result['messages'][] = "Baris $rowNum: Kolom tidak cukup (minimal 6 kolom diperlukan).";
                continue;
            }

            // ISBN, Judul, Pengarang ID, Penerbit ID, Kategori ID, Rak ID, Tahun, Stok
            $isbn        = trim((string)($row[0] ?? ''));
            $judul       = trim((string)($row[1] ?? ''));
            $pengarangId = trim((string)($row[2] ?? ''));
            $penerbitId  = trim((string)($row[3] ?? ''));
            $kategoriId  = trim((string)($row[4] ?? ''));
            $rakId       = trim((string)($row[5] ?? ''));
            $tahun       = isset($row[6]) && is_numeric(trim((string)$row[6])) ? (int)trim((string)$row[6]) : (int)date('Y');
            $stok        = isset($row[7]) && is_numeric(trim((string)$row[7])) ? max(1, (int)trim((string)$row[7])) : 1;

            if (empty($judul)) {
                $result['error']++;
                $result['messages'][] = "Baris $rowNum: Judul buku wajib diisi.";
                continue;
            }

            try {
                $bukuModel->create([
                    'isbn'          => !empty($isbn) ? $isbn : null,
                    'judul'         => $judul,
                    'pengarang_id'  => (int)$pengarangId,
                    'penerbit_id'   => (int)$penerbitId,
                    'kategori_id'   => (int)$kategoriId,
                    'rak_id'        => (int)$rakId,
                    'tahun_terbit'  => $tahun,
                    'stok_total'    => $stok,
                    'stok_tersedia' => $stok,
                ]);
                $result['success']++;
            } catch (Exception $e) {
                $result['error']++;
                $result['messages'][] = "Baris $rowNum ($judul): " . $e->getMessage();
            }
        }
    } elseif ($importType === 'anggota') {
        $anggotaModel = new Anggota();
        foreach ($rows as $row) {
            $rowNum++;
            if (count($row) < 4) {
                $result['error']++;
                $result['messages'][] = "Baris $rowNum: Kolom tidak cukup (minimal 4 kolom diperlukan).";
                continue;
            }

            // Nama, Identitas No (NIS/NISN/NIP), Jenis Kelamin, Kelas ID, Jurusan ID, No HP, Jenis Anggota
            $nama         = trim((string)($row[0] ?? ''));
            $identitasNo  = trim((string)($row[1] ?? ''));
            $jenisKelamin = strtoupper(trim((string)($row[2] ?? 'L')));
            $kelasId      = trim((string)($row[3] ?? ''));
            $jurusanId    = isset($row[4]) && is_numeric(trim((string)$row[4])) ? (int)trim((string)$row[4]) : null;
            $noHp         = isset($row[5]) ? trim((string)$row[5]) : null;
            $jenisAnggota = isset($row[6]) && !empty(trim((string)$row[6])) ? trim((string)$row[6]) : 'Siswa';

            if (empty($nama) || empty($identitasNo)) {
                $result['error']++;
                $result['messages'][] = "Baris $rowNum: Nama dan No. Identitas wajib diisi.";
                continue;
            }

            if (!in_array($jenisKelamin, ['L', 'P'])) {
                $jenisKelamin = 'L';
            }

            // Cek duplikat identitas
            $existing = $anggotaModel->getByIdentitas($identitasNo);
            if ($existing) {
                $result['skip']++;
                $result['messages'][] = "Baris $rowNum ($nama): Identitas $identitasNo sudah terdaftar (dilewati).";
                continue;
            }

            try {
                $anggotaModel->create([
                    'nama'          => $nama,
                    'identitas_no'  => $identitasNo,
                    'jenis_kelamin' => $jenisKelamin,
                    'kelas_id'      => (!empty($kelasId) && is_numeric($kelasId)) ? (int)$kelasId : null,
                    'jurusan_id'    => $jurusanId ?: null,
                    'nomor_hp'      => $noHp,
                    'jenis_anggota' => $jenisAnggota ?: 'Siswa',
                    'status_aktif'  => 1,
                ]);
                $result['success']++;
            } catch (Exception $e) {
                $result['error']++;
                $result['messages'][] = "Baris $rowNum ($nama): " . $e->getMessage();
            }
        }
    }

    log_activity($currentUser['id'], 'Import', 'Import ' . ucfirst($importType), 
        "Import {$importType} via Excel/CSV: {$result['success']} berhasil, {$result['skip']} dilewati, {$result['error']} gagal");

    $msg = "Import data selesai: <strong>{$result['success']}</strong> berhasil ditambahkan, <strong>{$result['skip']}</strong> dilewati (duplikat), <strong>{$result['error']}</strong> gagal.";
    set_flash($result['error'] > 0 ? 'warning' : 'success', $msg);

    if (!empty($result['messages'])) {
        $_SESSION['import_messages'] = $result['messages'];
    }

    header('Location: ' . base_url('index.php?page=import&tab=' . $importType));
    exit;
}

$importMessages = $_SESSION['import_messages'] ?? [];
unset($_SESSION['import_messages']);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">

    <!-- Flash Messages -->
    <?php if ($flash = get_flash()): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : ($flash['type'] === 'warning' ? 'exclamation-triangle-fill' : 'exclamation-circle-fill') ?> me-2"></i>
            <?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php if (!empty($importMessages)): ?>
            <div class="alert alert-secondary small shadow-sm">
                <strong><i class="bi bi-info-circle me-1"></i> Rincian Proses Import:</strong>
                <ul class="mb-0 mt-1 ps-3" style="max-height: 180px; overflow-y: auto;">
                    <?php foreach (array_slice($importMessages, 0, 30) as $msg): ?>
                        <li><?= htmlspecialchars($msg) ?></li>
                    <?php endforeach; ?>
                    <?php if (count($importMessages) > 30): ?>
                        <li class="fw-bold">... dan <?= count($importMessages) - 30 ?> catatan lainnya.</li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="page-title"><i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>Import Data Massal</h1>
            <p class="text-muted mb-0 small">Mendukung format Microsoft Excel (.xlsx, .xls) dan CSV dengan pembuatan nomor & QR Code otomatis</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> Format Excel (.xlsx/.xls) Aktif
            </span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                <i class="bi bi-file-earmark-csv me-1"></i> Format CSV Aktif
            </span>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'buku' ? 'active fw-bold' : '' ?>" href="<?= base_url('index.php?page=import&tab=buku') ?>">
                <i class="bi bi-book-fill me-2 text-primary"></i>Import Buku
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'anggota' ? 'active fw-bold' : '' ?>" href="<?= base_url('index.php?page=import&tab=anggota') ?>">
                <i class="bi bi-people-fill me-2 text-success"></i>Import Anggota
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'referensi' ? 'active fw-bold' : '' ?>" href="<?= base_url('index.php?page=import&tab=referensi') ?>">
                <i class="bi bi-table me-2 text-info"></i>Tabel Referensi ID
            </a>
        </li>
    </ul>

    <!-- TAB: IMPORT BUKU -->
    <?php if ($tab === 'buku'): ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card card-custom">
                <div class="card-header bg-primary text-white py-3 d-flex align-items-center justify-content-between">
                    <strong><i class="bi bi-file-earmark-arrow-up-fill me-2"></i>Upload Excel / CSV - Data Buku</strong>
                    <span class="badge bg-white text-primary">Buku & Stok</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info small py-2 mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Kode buku, QR Code eksemplar fisik, dan barcode akan <strong>di-generate otomatis</strong> oleh sistem. 
                        Gunakan ID relasi dari tab <a href="<?= base_url('index.php?page=import&tab=referensi') ?>" class="alert-link fw-bold">Tabel Referensi</a>.
                    </div>

                    <form method="POST" action="<?= base_url('index.php?page=import') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="import_type" value="buku">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih File Excel / CSV Buku <span class="text-danger">*</span></label>
                            <input type="file" name="excel_file" accept=".xlsx,.xls,.csv,.txt" class="form-control form-control-lg" required id="bukuFileInput">
                            <div class="form-text small">
                                <i class="bi bi-check-circle text-success me-1"></i>Dukungan: <strong>Excel (.xlsx, .xls)</strong> dan <strong>CSV (.csv)</strong>.
                            </div>
                        </div>

                        <!-- Status File Terpilih -->
                        <div id="fileStatusBuku" class="alert alert-success d-none py-2 px-3 small mb-3">
                            <i class="bi bi-file-earmark-check-fill me-1"></i>
                            <span id="fileStatusBukuText"></span>
                        </div>

                        <!-- Preview area (khusus CSV) -->
                        <div id="csvPreviewBuku" class="d-none mb-3">
                            <label class="form-label fw-medium small">Pratinjau CSV (10 baris pertama):</label>
                            <div class="table-responsive border rounded" style="max-height:220px;overflow-y:auto;">
                                <table class="table table-sm table-bordered mb-0" id="previewTableBuku">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>ISBN</th><th>Judul</th><th>Pengarang ID</th><th>Penerbit ID</th>
                                            <th>Kategori ID</th><th>Rak ID</th><th>Tahun</th><th>Stok</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewBodyBuku"></tbody>
                                </table>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-custom px-4 py-2">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i> Mulai Import Data Buku
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card card-custom mb-4">
                <div class="card-header py-3 bg-light">
                    <strong><i class="bi bi-download me-2 text-success"></i>Download Template Buku</strong>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Gunakan template resmi agar susunan kolom sesuai dengan pembaca otomatis sistem:</p>
                    
                    <div class="d-flex flex-column gap-2 mb-3">
                        <a href="<?= base_url('index.php?page=import&download=template_buku_xlsx') ?>" class="btn btn-success d-flex align-items-center justify-content-center gap-2 py-2">
                            <i class="bi bi-file-earmark-excel-fill fs-5"></i>
                            <div class="text-start">
                                <div class="fw-bold">Download Template Excel (.xlsx)</div>
                                <small style="font-size: 11px; opacity: 0.9;">Format Excel modern (Rekomendasi)</small>
                            </div>
                        </a>
                        <a href="#" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2" onclick="downloadTemplate('buku'); return false;">
                            <i class="bi bi-file-earmark-csv fs-5"></i>
                            <span>Download Template CSV (.csv)</span>
                        </a>
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        <h6 class="fw-bold small mb-2"><i class="bi bi-list-check me-1"></i>Urutan Kolom yang Diperlukan:</h6>
                        <ol class="small text-muted ps-3 mb-0" style="line-height: 1.6;">
                            <li><strong>ISBN</strong> - Nomor ISBN (opsional, boleh kosong)</li>
                            <li><strong>Judul</strong> - Judul buku <span class="text-danger">*</span></li>
                            <li><strong>Pengarang ID</strong> - Angka ID pengarang <span class="text-danger">*</span></li>
                            <li><strong>Penerbit ID</strong> - Angka ID penerbit <span class="text-danger">*</span></li>
                            <li><strong>Kategori ID</strong> - Angka ID kategori <span class="text-danger">*</span></li>
                            <li><strong>Rak ID</strong> - Angka ID rak lokasi <span class="text-danger">*</span></li>
                            <li><strong>Tahun Terbit</strong> - Contoh: 2024</li>
                            <li><strong>Stok Total</strong> - Jumlah buku fisik (default: 1)</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: IMPORT ANGGOTA -->
    <?php elseif ($tab === 'anggota'): ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card card-custom">
                <div class="card-header bg-success text-white py-3 d-flex align-items-center justify-content-between">
                    <strong><i class="bi bi-file-earmark-arrow-up-fill me-2"></i>Upload Excel / CSV - Data Anggota</strong>
                    <span class="badge bg-white text-success">Siswa & Guru</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info small py-2 mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Nomor anggota unik dan token QR Code akan <strong>di-generate otomatis</strong>. 
                        Data dengan NIS/NISN yang sudah terdaftar akan otomatis <strong>dilewati</strong> sehingga tidak terjadi duplikat.
                    </div>

                    <form method="POST" action="<?= base_url('index.php?page=import') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="import_type" value="anggota">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih File Excel / CSV Anggota <span class="text-danger">*</span></label>
                            <input type="file" name="excel_file" accept=".xlsx,.xls,.csv,.txt" class="form-control form-control-lg" required id="anggotaFileInput">
                            <div class="form-text small">
                                <i class="bi bi-check-circle text-success me-1"></i>Dukungan: <strong>Excel (.xlsx, .xls)</strong> dan <strong>CSV (.csv)</strong>.
                            </div>
                        </div>

                        <!-- Status File Terpilih -->
                        <div id="fileStatusAnggota" class="alert alert-success d-none py-2 px-3 small mb-3">
                            <i class="bi bi-file-earmark-check-fill me-1"></i>
                            <span id="fileStatusAnggotaText"></span>
                        </div>

                        <!-- Preview area (khusus CSV) -->
                        <div id="csvPreviewAnggota" class="d-none mb-3">
                            <label class="form-label fw-medium small">Pratinjau CSV (10 baris pertama):</label>
                            <div class="table-responsive border rounded" style="max-height:220px;overflow-y:auto;">
                                <table class="table table-sm table-bordered mb-0" id="previewTableAnggota">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>Nama</th><th>NIS/NISN</th><th>JK</th><th>Kelas ID</th>
                                            <th>Jurusan ID</th><th>No HP</th><th>Jenis</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewBodyAnggota"></tbody>
                                </table>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success px-4 py-2">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i> Mulai Import Data Anggota
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card card-custom mb-4">
                <div class="card-header py-3 bg-light">
                    <strong><i class="bi bi-download me-2 text-success"></i>Template Anggota</strong>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Download template resmi untuk memastikan format kolom anggota benar:</p>
                    
                    <div class="d-flex flex-column gap-2 mb-3">
                        <a href="<?= base_url('index.php?page=import&download=template_anggota_xlsx') ?>" class="btn btn-success d-flex align-items-center justify-content-center gap-2 py-2">
                            <i class="bi bi-file-earmark-excel-fill fs-5"></i>
                            <div class="text-start">
                                <div class="fw-bold">Download Template Excel (.xlsx)</div>
                                <small style="font-size: 11px; opacity: 0.9;">Format Excel modern (Rekomendasi)</small>
                            </div>
                        </a>
                        <a href="#" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2" onclick="downloadTemplate('anggota'); return false;">
                            <i class="bi bi-file-earmark-csv fs-5"></i>
                            <span>Download Template CSV (.csv)</span>
                        </a>
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        <h6 class="fw-bold small mb-2"><i class="bi bi-list-check me-1"></i>Urutan Kolom yang Diperlukan:</h6>
                        <ol class="small text-muted ps-3 mb-0" style="line-height: 1.6;">
                            <li><strong>Nama</strong> - Nama lengkap siswa / guru <span class="text-danger">*</span></li>
                            <li><strong>NIS/NISN</strong> - Nomor identitas unik / NISN / NIP <span class="text-danger">*</span></li>
                            <li><strong>Jenis Kelamin</strong> - <code>L</code> (Laki-laki) atau <code>P</code> (Perempuan)</li>
                            <li><strong>Kelas ID</strong> - ID dari tabel kelas (kosongkan jika guru)</li>
                            <li><strong>Jurusan ID</strong> - ID dari tabel jurusan</li>
                            <li><strong>No HP</strong> - Nomor WhatsApp/HP aktif (opsional)</li>
                            <li><strong>Jenis Anggota</strong> - <code>Siswa</code> atau <code>Guru</code> (default: Siswa)</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: REFERENSI ID -->
    <?php elseif ($tab === 'referensi'): ?>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card card-custom">
                <div class="card-header py-3 bg-light"><strong><i class="bi bi-tag-fill text-primary me-2"></i>Kategori Buku</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 280px;">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="table-dark"><tr><th>ID</th><th>Nama Kategori</th><th>Kode DDC</th></tr></thead>
                            <tbody>
                                <?php foreach ($kategoriList as $k): ?>
                                    <tr><td><strong><?= $k['id'] ?></strong></td><td><?= htmlspecialchars($k['nama_kategori']) ?></td><td class="text-muted"><?= htmlspecialchars($k['kode_ddc'] ?? '-') ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-custom">
                <div class="card-header py-3 bg-light"><strong><i class="bi bi-inbox-fill text-primary me-2"></i>Rak Buku</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 280px;">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="table-dark"><tr><th>ID</th><th>Nama Rak</th><th>Lokasi</th></tr></thead>
                            <tbody>
                                <?php foreach ($rakList as $r): ?>
                                    <tr><td><strong><?= $r['id'] ?></strong></td><td><?= htmlspecialchars($r['nama_rak']) ?></td><td class="text-muted"><?= htmlspecialchars($r['lokasi'] ?? '-') ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-custom">
                <div class="card-header py-3 bg-light"><strong><i class="bi bi-pencil-fill text-success me-2"></i>Pengarang</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 280px;">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="table-dark"><tr><th>ID</th><th>Nama Pengarang</th></tr></thead>
                            <tbody>
                                <?php foreach ($pengarangList as $p): ?>
                                    <tr><td><strong><?= $p['id'] ?></strong></td><td><?= htmlspecialchars($p['nama_pengarang']) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-custom">
                <div class="card-header py-3 bg-light"><strong><i class="bi bi-building text-success me-2"></i>Penerbit</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 280px;">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="table-dark"><tr><th>ID</th><th>Nama Penerbit</th><th>Kota</th></tr></thead>
                            <tbody>
                                <?php foreach ($penerbitList as $p): ?>
                                    <tr><td><strong><?= $p['id'] ?></strong></td><td><?= htmlspecialchars($p['nama_penerbit']) ?></td><td class="text-muted"><?= htmlspecialchars($p['kota'] ?? '-') ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-custom">
                <div class="card-header py-3 bg-light"><strong><i class="bi bi-mortarboard-fill text-warning me-2"></i>Kelas</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 280px;">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="table-dark"><tr><th>ID</th><th>Nama Kelas</th><th>Tingkat</th></tr></thead>
                            <tbody>
                                <?php foreach ($kelasList as $k): ?>
                                    <tr><td><strong><?= $k['id'] ?></strong></td><td><?= htmlspecialchars($k['nama_kelas']) ?></td><td><?= htmlspecialchars($k['tingkat']) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-custom">
                <div class="card-header py-3 bg-light"><strong><i class="bi bi-gear-wide-connected text-warning me-2"></i>Jurusan / Kompetensi Keahlian</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 280px;">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="table-dark"><tr><th>ID</th><th>Kode</th><th>Nama Jurusan</th></tr></thead>
                            <tbody>
                                <?php foreach ($jurusanList as $j): ?>
                                    <tr><td><strong><?= $j['id'] ?></strong></td><td><code><?= htmlspecialchars($j['kode_jurusan']) ?></code></td><td><?= htmlspecialchars($j['nama_jurusan']) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    </div>

<script>
function handleFileInputChange(input, statusDiv, statusText, previewDiv, previewBody, maxCols) {
    var file = input.files[0];
    if (!file) return;

    var name = file.name;
    var ext = name.split('.').pop().toLowerCase();

    if (ext === 'xlsx' || ext === 'xls') {
        previewDiv.classList.add('d-none');
        statusDiv.classList.remove('d-none');
        statusText.innerHTML = 'File Excel <strong>' + name + '</strong> (' + (file.size / 1024).toFixed(1) + ' KB) siap diimpor. Klik tombol di bawah.';
    } else if (ext === 'csv' || ext === 'txt') {
        statusDiv.classList.remove('d-none');
        statusText.innerHTML = 'File CSV <strong>' + name + '</strong> (' + (file.size / 1024).toFixed(1) + ' KB) dipilih.';
        previewCsv(input, previewDiv, previewBody, maxCols);
    } else {
        statusDiv.classList.add('d-none');
        previewDiv.classList.add('d-none');
    }
}

// CSV Preview
function previewCsv(fileInput, previewContainer, previewBody, maxCols) {
    var file = fileInput.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var lines = e.target.result.split('\n').slice(1, 11); // skip header, max 10 rows
        previewBody.innerHTML = '';
        lines.forEach(function(line) {
            if (!line.trim()) return;
            var cols = line.split(',');
            var tr = document.createElement('tr');
            for (var i = 0; i < maxCols; i++) {
                var td = document.createElement('td');
                td.textContent = cols[i] ? cols[i].trim() : '';
                tr.appendChild(td);
            }
            previewBody.appendChild(tr);
        });
        previewContainer.classList.remove('d-none');
    };
    reader.readAsText(file);
}

var bukuInput = document.getElementById('bukuFileInput');
if (bukuInput) {
    bukuInput.addEventListener('change', function() {
        handleFileInputChange(
            this,
            document.getElementById('fileStatusBuku'),
            document.getElementById('fileStatusBukuText'),
            document.getElementById('csvPreviewBuku'), 
            document.getElementById('previewBodyBuku'), 8
        );
    });
}

var anggotaInput = document.getElementById('anggotaFileInput');
if (anggotaInput) {
    anggotaInput.addEventListener('change', function() {
        handleFileInputChange(
            this,
            document.getElementById('fileStatusAnggota'),
            document.getElementById('fileStatusAnggotaText'),
            document.getElementById('csvPreviewAnggota'), 
            document.getElementById('previewBodyAnggota'), 7
        );
    });
}

// Download template CSV
function downloadTemplate(type) {
    var content = '';
    if (type === 'buku') {
        content = 'ISBN,Judul,Pengarang ID,Penerbit ID,Kategori ID,Rak ID,Tahun Terbit,Stok Total\n';
        content += '978-602-0123-45-6,Pemrograman Web Berbasis PHP & MySQL,1,1,1,1,2024,5\n';
        content += ',Matematika Terapan untuk SMK,2,2,2,2,2023,2\n';
    } else {
        content = 'Nama,NIS/NISN,Jenis Kelamin (L/P),Kelas ID,Jurusan ID,No HP,Jenis Anggota\n';
        content += 'Ahmad Fauzi,2024001,L,1,1,081234567890,Siswa\n';
        content += 'Siti Rahayu,2024002,P,2,2,,Siswa\n';
    }

    var blob = new Blob(['\uFEFF' + content], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', 'template_import_' + type + '.csv');
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
