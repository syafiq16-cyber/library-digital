<?php
/**
 * Dashboard Utama Sistem
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Laporan.php';
require_once __DIR__ . '/../../models/Peminjaman.php';
require_once __DIR__ . '/../../models/LogAktivitas.php';

require_login();

$pageTitle = 'Dashboard Eksekutif';
$laporanModel = new Laporan();
$peminjamanModel = new Peminjaman();
$logModel = new LogAktivitas();

$kpi = $laporanModel->getDashboardKPI();
$monthlyChart = $laporanModel->getMonthlyChartData();
$categoryChart = $laporanModel->getCategoryChartData();

$appName = get_setting('nama_perpustakaan', 'PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR');

// Transaksi terbaru
$recentLoans = $peminjamanModel->getAll([], 6, 0);

// Pinjaman terlambat
$overdueLoans = $laporanModel->laporanKeterlambatan();
$overdueLoans = array_slice($overdueLoans, 0, 5);

// Scan log terakhir
$recentScans = $logModel->getRecentScans(5);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <!-- Header Page -->
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard Perpustakaan</h1>
                <div class="page-subtitle">Sistem Informasi <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Ringkasan Eksekutif Real-time</div>
            </div>
            <div class="d-flex gap-2">
                <?php if (has_role(['Administrator', 'Petugas'])): ?>
                <a href="<?= base_url('index.php?page=scanner') ?>" class="btn btn-primary-custom">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>Scanner Dua QR</span>
                </a>
                <a href="<?= base_url('index.php?page=peminjaman_create') ?>" class="btn btn-outline-primary rounded-pill d-inline-flex align-items-center gap-1">
                    <i class="bi bi-plus-circle"></i>
                    <span>Peminjaman Baru</span>
                </a>
                <?php endif; ?>
                <a href="<?= base_url('index.php?page=katalog') ?>" class="btn btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-1">
                    <i class="bi bi-journals"></i>
                    <span>Katalog</span>
                </a>
            </div>
        </div>

        <!-- Row 1: KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-primary">
                        <i class="bi bi-book-half"></i>
                    </div>
                    <div>
                        <div class="kpi-value"><?= number_format($kpi['total_buku']) ?></div>
                        <div class="kpi-label">Total Buku (<?= $kpi['total_judul'] ?> Judul)</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-success">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="kpi-value"><?= number_format($kpi['total_anggota']) ?></div>
                        <div class="kpi-label">Anggota Perpustakaan</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-warning">
                        <i class="bi bi-arrow-up-right-circle-fill"></i>
                    </div>
                    <div>
                        <div class="kpi-value"><?= number_format($kpi['total_dipinjam']) ?></div>
                        <div class="kpi-label">Buku Sedang Dipinjam</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-danger">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="kpi-value"><?= number_format($kpi['total_terlambat']) ?></div>
                        <div class="kpi-label">Peminjaman Terlambat</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Secondary KPI Cards & QR Stats -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-sm-6">
                <div class="p-3 bg-surface border rounded-3 d-flex align-items-center justify-content-between shadow-sm">
                    <div>
                        <small class="text-muted d-block">Peminjaman Bulan Ini</small>
                        <strong class="fs-5 text-primary"><?= $kpi['pinjam_bulan_ini'] ?> Transaksi</strong>
                    </div>
                    <i class="bi bi-calendar-check text-primary fs-3 opacity-50"></i>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="p-3 bg-surface border rounded-3 d-flex align-items-center justify-content-between shadow-sm">
                    <div>
                        <small class="text-muted d-block">Pengembalian Bulan Ini</small>
                        <strong class="fs-5 text-success"><?= $kpi['kembali_bulan_ini'] ?> Selesai</strong>
                    </div>
                    <i class="bi bi-arrow-down-left-circle text-success fs-3 opacity-50"></i>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="p-3 bg-surface border rounded-3 d-flex align-items-center justify-content-between shadow-sm">
                    <div>
                        <small class="text-muted d-block">Total Denda Terkumpul</small>
                        <strong class="fs-5 text-warning"><?= format_rupiah($kpi['total_denda']) ?></strong>
                    </div>
                    <i class="bi bi-cash-coin text-warning fs-3 opacity-50"></i>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="p-3 bg-surface border rounded-3 d-flex align-items-center justify-content-between shadow-sm">
                    <div>
                        <small class="text-muted d-block">Sistem Dua QR Code</small>
                        <strong class="fs-6 text-info"><?= $kpi['total_qr_anggota'] ?> Agt &bull; <?= $kpi['total_qr_buku'] ?> Buku</strong>
                        <div style="font-size:10.5px;" class="text-muted mt-1"><i class="bi bi-camera me-1"></i><?= $kpi['scan_hari_ini'] ?> Scan Hari Ini</div>
                    </div>
                    <i class="bi bi-qr-code text-info fs-3 opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Row 3: Charts -->
        <div class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="card-custom h-100 mb-0">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title">
                            <i class="bi bi-graph-up text-primary"></i>
                            <span>Tren Sirkulasi Buku (12 Bulan Terakhir)</span>
                        </h2>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Peminjaman vs Pengembalian</span>
                    </div>
                    <div class="card-custom-body" style="height: 320px;">
                        <canvas id="circulationChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card-custom h-100 mb-0">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title">
                            <i class="bi bi-pie-chart text-info"></i>
                            <span>Distribusi Kategori Buku</span>
                        </h2>
                    </div>
                    <div class="card-custom-body d-flex align-items-center justify-content-center" style="height: 320px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 4: Tables (Recent Transactions & Overdue Warnings) -->
        <div class="row g-4 mb-4">
            <!-- Transaksi Terbaru -->
            <div class="col-xl-7">
                <div class="card-custom mb-0">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title">
                            <i class="bi bi-arrow-left-right text-success"></i>
                            <span>Transaksi Peminjaman Terbaru</span>
                        </h2>
                        <a href="<?= base_url('index.php?page=peminjaman') ?>" class="small text-primary text-decoration-none fw-semibold">
                            Lihat Semua <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Anggota / Peminjam</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentLoans)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi peminjaman.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($recentLoans as $row): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= base_url('index.php?page=peminjaman_detail&id=' . $row['id']) ?>" class="fw-bold text-decoration-none text-primary">
                                                <?= htmlspecialchars($row['nomor_transaksi'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($row['nama_anggota'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['nama_kelas'] ?? $row['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_jatuh_tempo'])) ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'Dipinjam'): ?>
                                                <span class="badge-status badge-status-warning"><i class="bi bi-hourglass-split"></i> Dipinjam</span>
                                            <?php elseif ($row['status'] === 'Selesai'): ?>
                                                <span class="badge-status badge-status-success"><i class="bi bi-check2"></i> Selesai</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-status-danger"><i class="bi bi-exclamation-circle"></i> Terlambat</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Peringatan Keterlambatan -->
            <div class="col-xl-5">
                <div class="card-custom mb-0">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title text-danger">
                            <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                            <span>Peringatan Keterlambatan</span>
                        </h2>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">
                            <?= count($overdueLoans) ?> Terlambat
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Anggota</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Telat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($overdueLoans)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-success">
                                        <i class="bi bi-check-circle fs-4 d-block mb-1"></i>
                                        Tidak ada peminjaman terlambat saat ini!
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($overdueLoans as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($item['nama_anggota'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($item['nama_kelas'] ?? $item['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td class="text-danger fw-semibold"><?= date('d/m/Y', strtotime($item['tanggal_jatuh_tempo'])) ?></td>
                                        <td><span class="badge bg-danger text-white"><?= $item['telat_hari'] ?> hari</span></td>
                                        <td>
                                            <a href="<?= base_url('index.php?page=pengembalian_proses&pinjam_id=' . $item['id']) ?>" class="btn btn-sm btn-outline-danger" title="Proses Pengembalian">
                                                <i class="bi bi-arrow-down-left-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 5: Log Pemindaian QR Code Terakhir -->
        <div class="card-custom">
            <div class="card-custom-header">
                <h2 class="card-custom-title">
                    <i class="bi bi-upc-scan text-primary"></i>
                    <span>Log Aktivitas Pemindaian Dua QR Terkini</span>
                </h2>
                <a href="<?= base_url('index.php?page=scanner') ?>" class="btn btn-sm btn-primary-custom py-1 px-3">
                    <i class="bi bi-camera"></i> Buka Scanner Kamera
                </a>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Waktu Scan</th>
                            <th>Tipe QR</th>
                            <th>Kode Token QR</th>
                            <th>Aktivitas</th>
                            <th>Hasil Pemindaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentScans)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">Belum ada aktivitas pemindaian hari ini.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentScans as $sc): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i:s', strtotime($sc['created_at'])) ?> WITA</td>
                                <td>
                                    <?php if ($sc['qr_type'] === 'MEMBER'): ?>
                                        <span class="badge bg-primary text-white"><i class="bi bi-person-badge me-1"></i>QR Anggota</span>
                                    <?php elseif ($sc['qr_type'] === 'BOOK'): ?>
                                        <span class="badge bg-success text-white"><i class="bi bi-book me-1"></i>QR Buku</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary text-white">Unknown</span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= htmlspecialchars($sc['qr_token'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= htmlspecialchars($sc['action'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($sc['result_info'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div> <!-- /page-container -->

<script>
    window.DASHBOARD_CHART_DATA = {
        circulation: <?= json_encode($monthlyChart) ?>,
        category: <?= json_encode($categoryChart) ?>
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof initDashboardCharts === 'function') {
            initDashboardCharts(window.DASHBOARD_CHART_DATA.circulation, window.DASHBOARD_CHART_DATA.category);
        }
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
