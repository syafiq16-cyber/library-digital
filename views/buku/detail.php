<?php
/**
 * Detail Buku & Manajemen Eksemplar Fisik
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/qrcode_helper.php';
require_once __DIR__ . '/../../models/Buku.php';

$id = (int)($_GET['id'] ?? 0);
$bukuModel = new Buku();
$book = $bukuModel->getById($id);

if (!$book) {
    set_flash('danger', 'Buku tidak ditemukan.');
    header('Location: ' . base_url('index.php?page=buku'));
    exit;
}

$pageTitle = 'Detail Buku - ' . $book['judul'];
$eksemplarList = $bukuModel->getEksemplarByBukuId($id);

$coverUrl = !empty($book['cover']) && file_exists(UPLOAD_COVER_PATH . '/' . $book['cover'])
    ? base_url('uploads/covers/' . $book['cover'])
    : asset_url('images/default_book.png');

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-book text-primary me-2"></i><?= htmlspecialchars($book['judul'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="page-subtitle">Detail katalog buku dan status peminjaman eksemplar fisik</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('index.php?page=cetak_label_massal&buku_id=' . $book['id']) ?>" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-upc-scan me-1"></i>Cetak Label Stiker QR
                </a>
                <?php if (has_role(['Administrator', 'Petugas'])): ?>
                <a href="<?= base_url('index.php?page=buku_edit&id=' . $book['id']) ?>" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit Buku
                </a>
                <?php endif; ?>
                <a href="<?= base_url('index.php?page=buku') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Kolom Cover & QR Code Buku -->
            <div class="col-lg-4">
                <div class="card-custom text-center mb-4">
                    <div class="card-custom-body p-4">
                        <img src="<?= $coverUrl ?>" alt="Cover" class="img-fluid rounded-3 shadow border mb-3" style="max-height: 280px; object-fit: cover;">

                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($book['judul'], ENT_QUOTES, 'UTF-8') ?></h5>
                        <div class="text-muted small mb-3"><?= htmlspecialchars($book['nama_pengarang'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>

                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                                <?= $book['stok_tersedia'] ?> dari <?= $book['stok_total'] ?> Eksemplar Tersedia
                            </span>
                        </div>

                        <hr>

                        <!-- Render QR Code Buku Induk (LIBRARY-BOOK:{kode_buku}) -->
                        <div class="p-2 bg-light rounded border d-inline-block">
                            <div class="small fw-bold text-muted mb-1"><i class="bi bi-qr-code me-1"></i>QR Code Buku:</div>
                            <?= render_qr_code_html($book['qr_token'], 140) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Data Spesifikasi Buku -->
            <div class="col-lg-8">
                <div class="card-custom mb-4">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title"><i class="bi bi-info-circle text-primary"></i><span>Spesifikasi & Informasi Lengkap</span></h2>
                    </div>
                    <div class="card-custom-body">
                        <table class="table table-borderless table-sm mb-3">
                            <tr><th class="text-muted" style="width: 170px;">Kode Buku:</th><td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($book['kode_buku'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Nomor ISBN:</th><td><?= htmlspecialchars($book['isbn'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Kategori:</th><td><span class="badge bg-light text-dark border"><?= htmlspecialchars($book['nama_kategori'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td></tr>
                            <tr><th class="text-muted">Subkategori:</th><td><?= htmlspecialchars($book['subkategori'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Pengarang:</th><td><?= htmlspecialchars($book['nama_pengarang'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Penerbit:</th><td><?= htmlspecialchars($book['nama_penerbit'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Tahun Terbit / Edisi:</th><td><?= $book['tahun_terbit'] ?> &bull; <?= htmlspecialchars($book['edisi'] ?? 'Cetakan 1', ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Bahasa / Halaman:</th><td><?= htmlspecialchars($book['bahasa'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= $book['jumlah_halaman'] ?> Halaman</td></tr>
                            <tr><th class="text-muted">Lokasi Penempatan:</th><td class="fw-bold text-success"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars(($book['nama_rak'] ?? '') . ' (' . ($book['kode_rak'] ?? '') . ') - ' . ($book['lokasi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Sumber Pengadaan:</th><td><?= htmlspecialchars($book['sumber_buku'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Estimasi Nilai / Harga:</th><td><?= format_rupiah($book['harga']) ?></td></tr>
                        </table>

                        <h6 class="fw-bold text-main mb-2">Sinopsis / Deskripsi:</h6>
                        <p class="text-muted small p-3 bg-light rounded border">
                            <?= nl2br(htmlspecialchars($book['deskripsi'] ?: 'Tidak ada sinopsis atau deskripsi tambahan untuk buku ini.', ENT_QUOTES, 'UTF-8')) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Eksemplar Fisik Buku -->
        <div class="card-custom">
            <div class="card-custom-header">
                <h2 class="card-custom-title">
                    <i class="bi bi-collection text-success"></i>
                    <span>Daftar Eksemplar Fisik & QR Code (<?= count($eksemplarList) ?> Eksemplar)</span>
                </h2>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode Eksemplar</th>
                            <th>Nomor Barcode</th>
                            <th>Kondisi Fisik</th>
                            <th>Status Sirkulasi</th>
                            <th class="text-center">QR Code Eksemplar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eksemplarList)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada eksemplar terdaftar.</td></tr>
                        <?php else: ?>
                            <?php foreach ($eksemplarList as $idx => $ex): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="fw-bold font-monospace text-primary"><?= htmlspecialchars($ex['kode_eksemplar'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><span class="text-muted font-monospace"><?= htmlspecialchars($ex['nomor_barkod'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td>
                                    <?php if ($ex['kondisi'] === 'Baik'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Baik</span>
                                    <?php elseif ($ex['kondisi'] === 'Rusak Ringan'): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Rusak Ringan</span>
                                    <?php elseif ($ex['kondisi'] === 'Rusak Berat'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Rusak Berat</span>
                                    <?php else: ?>
                                        <span class="badge bg-dark text-white">Hilang</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($ex['status'] === 'Tersedia'): ?>
                                        <span class="badge-status badge-status-success"><i class="bi bi-check-circle"></i> Tersedia di Rak</span>
                                    <?php elseif ($ex['status'] === 'Dipinjam'): ?>
                                        <span class="badge-status badge-status-warning"><i class="bi bi-hourglass-split"></i> Sedang Dipinjam</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-status-danger"><i class="bi bi-tools"></i> <?= $ex['status'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center py-2">
                                    <?= render_qr_code_html($ex['qr_token'], 75) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
