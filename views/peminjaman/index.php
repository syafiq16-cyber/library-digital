<?php
/**
 * Daftar Riwayat Transaksi Peminjaman
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Peminjaman.php';

require_role(['Administrator', 'Petugas']);

$pageTitle = 'Data Peminjaman Buku';
$peminjamanModel = new Peminjaman();

$pageNo = max(1, (int)($_GET['p'] ?? 1));
$limit = 15;
$offset = ($pageNo - 1) * $limit;

$filters = [
    'search'    => trim($_GET['search'] ?? ''),
    'status'    => trim($_GET['status'] ?? ''),
    'tgl_awal'  => trim($_GET['tgl_awal'] ?? ''),
    'tgl_akhir' => trim($_GET['tgl_akhir'] ?? '')
];

$loans = $peminjamanModel->getAll($filters, $limit, $offset);
$totalRows = $peminjamanModel->countAll($filters);
$totalPages = ceil($totalRows / $limit);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-arrow-up-right-square-fill text-primary me-2"></i>Transaksi Peminjaman</h1>
                <div class="page-subtitle">Kelola dan pantau seluruh transaksi peminjaman buku perpustakaan</div>
            </div>
            <div>
                <a href="<?= base_url('index.php?page=peminjaman_create') ?>" class="btn btn-primary-custom">
                    <i class="bi bi-plus-circle"></i> Tambah Peminjaman Baru
                </a>
            </div>
        </div>

        <!-- Filter & Pencarian -->
        <div class="card-custom mb-4">
            <div class="card-custom-body py-3">
                <form method="GET" action="<?= base_url('index.php') ?>" class="row g-2 align-items-center">
                    <input type="hidden" name="page" value="peminjaman">

                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Cari no. transaksi / peminjam..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            <option value="Dipinjam" <?= ($filters['status'] === 'Dipinjam') ? 'selected' : '' ?>>Dipinjam</option>
                            <option value="Terlambat" <?= ($filters['status'] === 'Terlambat') ? 'selected' : '' ?>>Terlambat</option>
                            <option value="Selesai" <?= ($filters['status'] === 'Selesai') ? 'selected' : '' ?>>Selesai (Kembali)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <input type="date" name="tgl_awal" class="form-control form-control-sm" title="Tanggal Mulai" value="<?= htmlspecialchars($filters['tgl_awal'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-2">
                        <input type="date" name="tgl_akhir" class="form-control form-control-sm" title="Tanggal Selesai" value="<?= htmlspecialchars($filters['tgl_akhir'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="bi bi-filter"></i> Filter Data
                        </button>
                        <a href="<?= base_url('index.php?page=peminjaman') ?>" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Peminjaman -->
        <div class="card-custom">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>No. Transaksi</th>
                            <th>Nama Anggota</th>
                            <th>Kelas / Identitas</th>
                            <th>Tgl Pinjam</th>
                            <th>Jatuh Tempo</th>
                            <th>Total Buku</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($loans)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                Tidak ada data transaksi peminjaman yang cocok.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($loans as $idx => $row): ?>
                            <tr>
                                <td><?= $offset + $idx + 1 ?></td>
                                <td>
                                    <a href="<?= base_url('index.php?page=peminjaman_detail&id=' . $row['id']) ?>" class="fw-bold text-primary text-decoration-none">
                                        <?= htmlspecialchars($row['nomor_transaksi'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($row['nama_anggota'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($row['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['nama_kelas'] ?? $row['identitas_no'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                <td>
                                    <?php 
                                    $isOver = ($row['status'] === 'Terlambat' || ($row['status'] === 'Dipinjam' && strtotime($row['tanggal_jatuh_tempo']) < time()));
                                    ?>
                                    <span class="<?= $isOver ? 'text-danger fw-bold' : '' ?>">
                                        <?= date('d/m/Y', strtotime($row['tanggal_jatuh_tempo'])) ?>
                                    </span>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $row['total_buku'] ?> Buku</span></td>
                                <td>
                                    <?php if ($row['status'] === 'Dipinjam'): ?>
                                        <span class="badge-status badge-status-warning"><i class="bi bi-hourglass-split"></i> Dipinjam</span>
                                    <?php elseif ($row['status'] === 'Selesai'): ?>
                                        <span class="badge-status badge-status-success"><i class="bi bi-check2"></i> Selesai</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-status-danger"><i class="bi bi-exclamation-octagon"></i> Terlambat</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('index.php?page=peminjaman_detail&id=' . $row['id']) ?>" class="btn btn-outline-primary" title="Detail Transaksi">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('index.php?page=peminjaman_cetak&id=' . $row['id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Cetak Bukti Struk">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <?php if ($row['status'] !== 'Selesai'): ?>
                                        <a href="<?= base_url('index.php?page=pengembalian_proses&pinjam_id=' . $row['id']) ?>" class="btn btn-outline-success" title="Kembalikan Buku">
                                            <i class="bi bi-arrow-down-left-square"></i>
                                        </a>
                                        <?php endif; ?>
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
                <small class="text-muted">Menampilkan <?= count($loans) ?> dari total <?= $totalRows ?> transaksi</small>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $pageNo) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= base_url('index.php?page=peminjaman&p=' . $i . '&' . http_build_query($filters)) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
