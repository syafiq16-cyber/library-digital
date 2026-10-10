<?php
/**
 * Master Data Buku
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Buku.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas']);

$pageTitle = 'Master Data Buku';
$bukuModel = new Buku();
$masterModel = new Master();

$kategoriList = $masterModel->getKategori();
$rakList = $masterModel->getRak();

$pageNo = max(1, (int)($_GET['p'] ?? 1));
$limit = 15;
$offset = ($pageNo - 1) * $limit;

$filters = [
    'search'      => trim($_GET['search'] ?? ''),
    'kategori_id' => trim($_GET['kategori_id'] ?? ''),
    'rak_id'      => trim($_GET['rak_id'] ?? ''),
    'status'      => trim($_GET['status'] ?? '')
];

$books = $bukuModel->getAll($filters, $limit, $offset);
$totalRows = $bukuModel->countAll($filters);
$totalPages = ceil($totalRows / $limit);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-book-half text-primary me-2"></i>Master Data Buku</h1>
                <div class="page-subtitle">Kelola katalog pustaka, ketersediaan stok, dan pelacakan eksemplar fisik</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('index.php?page=cetak_label_massal') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-upc-scan me-1"></i>Cetak Label Barcode
                </a>
                <a href="<?= base_url('index.php?page=import') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i>Import Excel
                </a>
                <a href="<?= base_url('index.php?page=buku_create') ?>" class="btn btn-primary-custom">
                    <i class="bi bi-plus-circle"></i> Tambah Buku Baru
                </a>
            </div>
        </div>

        <!-- Filter & Pencarian -->
        <div class="card-custom mb-4">
            <div class="card-custom-body py-3">
                <form method="GET" action="<?= base_url('index.php') ?>" class="row g-2 align-items-center">
                    <input type="hidden" name="page" value="buku">

                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Cari judul, pengarang, kode, ISBN..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <select name="kategori_id" class="form-select form-select-sm">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kategoriList as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= ($filters['kategori_id'] == $k['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kategori'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="rak_id" class="form-select form-select-sm">
                            <option value="">Semua Rak</option>
                            <?php foreach ($rakList as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= ($filters['rak_id'] == $r['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['kode_rak'] . ' - ' . $r['nama_rak'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            <option value="Tersedia" <?= ($filters['status'] === 'Tersedia') ? 'selected' : '' ?>>Tersedia</option>
                            <option value="Habis Dipinjam" <?= ($filters['status'] === 'Habis Dipinjam') ? 'selected' : '' ?>>Habis Dipinjam</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <a href="<?= base_url('index.php?page=buku') ?>" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Buku -->
        <div class="card-custom">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Cover</th>
                            <th>Kode & ISBN</th>
                            <th>Judul Buku</th>
                            <th>Pengarang / Penerbit</th>
                            <th>Kategori</th>
                            <th>Rak</th>
                            <th>Stok</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($books)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-2 d-block mb-2 opacity-50"></i>
                                Tidak ada data buku ditemukan.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($books as $idx => $b): ?>
                            <?php
                                $coverUrl = !empty($b['cover']) && file_exists(UPLOAD_COVER_PATH . '/' . $b['cover'])
                                    ? base_url('uploads/covers/' . $b['cover'])
                                    : asset_url('images/default_book.png');
                            ?>
                            <tr>
                                <td><?= $offset + $idx + 1 ?></td>
                                <td>
                                    <img src="<?= $coverUrl ?>" alt="Cover" class="rounded border shadow-sm" style="width: 38px; height: 52px; object-fit: cover;">
                                </td>
                                <td>
                                    <span class="fw-bold font-monospace text-primary"><?= htmlspecialchars($b['kode_buku'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="text-muted small" style="font-size: 11px;"><?= htmlspecialchars($b['isbn'] ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <a href="<?= base_url('index.php?page=buku_detail&id=' . $b['id']) ?>" class="fw-bold text-decoration-none text-main">
                                        <?= htmlspecialchars($b['judul'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <div class="text-muted small"><?= htmlspecialchars($b['edisi'] ?? 'Cetakan 1', ENT_QUOTES, 'UTF-8') ?> &bull; <?= $b['tahun_terbit'] ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($b['nama_pengarang'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($b['nama_penerbit'] ?? '-', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($b['nama_kategori'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><small class="text-muted font-monospace"><?= htmlspecialchars($b['kode_rak'] ?? '-', ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td>
                                    <?php if ($b['stok_tersedia'] > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                                            <?= $b['stok_tersedia'] ?> / <?= $b['stok_total'] ?> Tersedia
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">
                                            0 / <?= $b['stok_total'] ?> Habis
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('index.php?page=buku_detail&id=' . $b['id']) ?>" class="btn btn-outline-primary" title="Detail & Eksemplar">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('index.php?page=buku_edit&id=' . $b['id']) ?>" class="btn btn-outline-secondary" title="Edit Buku">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= base_url('controllers/BukuController.php?action=delete&id=' . $b['id']) ?>" class="btn btn-outline-danger" title="Hapus Buku" onclick="return confirm('Apakah Anda yakin ingin menghapus buku ini beserta seluruh eksemplarnya?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-custom-header border-top d-flex justify-content-between align-items-center py-2">
                <small class="text-muted">Menampilkan <?= count($books) ?> dari total <?= $totalRows ?> judul buku</small>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $pageNo) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= base_url('index.php?page=buku&p=' . $i . '&' . http_build_query($filters)) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
