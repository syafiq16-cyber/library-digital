<?php
/**
 * Riwayat Transaksi Pengembalian Buku
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Pengembalian.php';

require_role(['Administrator', 'Petugas']);

$pageTitle = 'Data Pengembalian & Denda';
$pengembalianModel = new Pengembalian();

$pageNo = max(1, (int)($_GET['p'] ?? 1));
$limit = 15;
$offset = ($pageNo - 1) * $limit;

$filters = [
    'search'       => trim($_GET['search'] ?? ''),
    'status_denda' => trim($_GET['status_denda'] ?? ''),
    'tgl_awal'     => trim($_GET['tgl_awal'] ?? ''),
    'tgl_akhir'    => trim($_GET['tgl_akhir'] ?? '')
];

$returns = $pengembalianModel->getAll($filters, $limit, $offset);
$totalRows = $pengembalianModel->countAll($filters);
$totalPages = ceil($totalRows / $limit);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-arrow-down-left-square-fill text-success me-2"></i>Transaksi Pengembalian</h1>
                <div class="page-subtitle">Riwayat pengembalian buku, kalkulasi keterlambatan, dan penerimaan denda</div>
            </div>
            <div>
                <a href="<?= base_url('index.php?page=pengembalian_proses') ?>" class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i>Proses Pengembalian Baru
                </a>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="card-custom mb-4">
            <div class="card-custom-body py-3">
                <form method="GET" action="<?= base_url('index.php') ?>" class="row g-2 align-items-center">
                    <input type="hidden" name="page" value="pengembalian">

                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="No. kembali / pinjam / anggota..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <select name="status_denda" class="form-select form-select-sm">
                            <option value="">Semua Status Denda</option>
                            <option value="Tidak Ada" <?= ($filters['status_denda'] === 'Tidak Ada') ? 'selected' : '' ?>>Tidak Ada Denda</option>
                            <option value="Lunas" <?= ($filters['status_denda'] === 'Lunas') ? 'selected' : '' ?>>Lunas</option>
                            <option value="Belum Lunas" <?= ($filters['status_denda'] === 'Belum Lunas') ? 'selected' : '' ?>>Belum Lunas</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <input type="date" name="tgl_awal" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['tgl_awal'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-2">
                        <input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['tgl_akhir'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="bi bi-filter"></i> Filter Data
                        </button>
                        <a href="<?= base_url('index.php?page=pengembalian') ?>" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Pengembalian -->
        <div class="card-custom">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>No. Pengembalian</th>
                            <th>No. Peminjaman</th>
                            <th>Nama Anggota</th>
                            <th>Tgl Kembali</th>
                            <th>Terlambat</th>
                            <th>Total Denda</th>
                            <th>Status Denda</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($returns)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                Belum ada data pengembalian buku.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($returns as $idx => $r): ?>
                            <tr>
                                <td><?= $offset + $idx + 1 ?></td>
                                <td>
                                    <a href="<?= base_url('index.php?page=pengembalian_detail&id=' . $r['id']) ?>" class="fw-bold text-success text-decoration-none">
                                        <?= htmlspecialchars($r['nomor_pengembalian'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?= base_url('index.php?page=peminjaman_detail&id=' . $r['peminjaman_id']) ?>" class="font-monospace text-primary text-decoration-none">
                                        <?= htmlspecialchars($r['nomor_transaksi'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($r['nama_anggota'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($r['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td><?= date('d/m/Y', strtotime($r['tanggal_kembali'])) ?></td>
                                <td>
                                    <?php if ($r['terlambat_hari'] > 0): ?>
                                        <span class="badge bg-danger text-white"><?= $r['terlambat_hari'] ?> Hari</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Tepat Waktu</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold <?= ($r['total_denda'] > 0) ? 'text-danger' : 'text-muted' ?>">
                                    <?= format_rupiah($r['total_denda']) ?>
                                </td>
                                <td>
                                    <?php if ($r['status_denda'] === 'Lunas'): ?>
                                        <span class="badge-status badge-status-success"><i class="bi bi-check2"></i> Lunas</span>
                                    <?php elseif ($r['status_denda'] === 'Belum Lunas'): ?>
                                        <span class="badge-status badge-status-danger"><i class="bi bi-clock-history"></i> Belum Lunas</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-status-secondary">Tidak Ada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('index.php?page=pengembalian_detail&id=' . $r['id']) ?>" class="btn btn-sm btn-outline-primary" title="Detail Pengembalian">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
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
                <small class="text-muted">Menampilkan <?= count($returns) ?> dari total <?= $totalRows ?> pengembalian</small>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $pageNo) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= base_url('index.php?page=pengembalian&p=' . $i . '&' . http_build_query($filters)) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
