<?php
/**
 * Pusat Laporan - 12 Jenis Laporan Komprehensif
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

$pageTitle = 'Laporan Perpustakaan';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Laporan.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);

$laporanModel = new Laporan();
$masterModel  = new Master();

$kategoriList  = $masterModel->getKategori();
$kelasList     = $masterModel->getKelas();
$jurusanList   = $masterModel->getJurusan();
$rakList       = $masterModel->getRak();

// Baca filter & tipe laporan dari URL
$tipe    = $_GET['tipe'] ?? 'peminjaman';
$tglAwal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tglAkhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

$filters = [
    'tgl_awal'       => $tglAwal,
    'tgl_akhir'      => $tglAkhir,
    'kategori_id'    => (int)($_GET['kategori_id'] ?? 0) ?: null,
    'kelas_id'       => (int)($_GET['kelas_id'] ?? 0) ?: null,
    'jurusan_id'     => (int)($_GET['jurusan_id'] ?? 0) ?: null,
    'rak_id'         => (int)($_GET['rak_id'] ?? 0) ?: null,
    'status'         => $_GET['status'] ?? '',
    'status_denda'   => $_GET['status_denda'] ?? '',
    'status_bayar'   => $_GET['status_bayar'] ?? '',
    'jenis_anggota'  => $_GET['jenis_anggota'] ?? '',
    'jenis_pengunjung' => $_GET['jenis_pengunjung'] ?? '',
    'tahun'          => (int)($_GET['tahun'] ?? 0) ?: null,
];

$data = [];
switch ($tipe) {
    case 'buku':           $data = $laporanModel->laporanBuku($filters); break;
    case 'anggota':        $data = $laporanModel->laporanAnggota($filters); break;
    case 'peminjaman':     $data = $laporanModel->laporanPeminjaman($filters); break;
    case 'pengembalian':   $data = $laporanModel->laporanPengembalian($filters); break;
    case 'keterlambatan':  $data = $laporanModel->laporanKeterlambatan(); break;
    case 'denda':          $data = $laporanModel->laporanDenda($filters); break;
    case 'rusak':          $data = $laporanModel->laporanBukuRusak(); break;
    case 'hilang':         $data = $laporanModel->laporanBukuHilang(); break;
    case 'pengunjung':     $data = $laporanModel->laporanPengunjung($filters); break;
    case 'populer':        $data = $laporanModel->laporanBukuTerpopuler(30); break;
    case 'aktif':          $data = $laporanModel->laporanAnggotaAktif(30); break;
    case 'statistik':      $data = $laporanModel->laporanStatistikUmum(); break;
    default:               $data = $laporanModel->laporanPeminjaman($filters);
}

// Daftar tipe laporan
$reportTypes = [
    'peminjaman'   => ['icon' => 'bi-arrow-up-circle-fill',  'label' => 'Peminjaman',          'color' => 'primary'],
    'pengembalian' => ['icon' => 'bi-arrow-down-circle-fill','label' => 'Pengembalian',         'color' => 'success'],
    'keterlambatan'=> ['icon' => 'bi-alarm-fill',            'label' => 'Keterlambatan',        'color' => 'warning'],
    'denda'        => ['icon' => 'bi-cash-stack',            'label' => 'Denda',                'color' => 'danger'],
    'buku'         => ['icon' => 'bi-book-half',             'label' => 'Data Buku',            'color' => 'info'],
    'rusak'        => ['icon' => 'bi-exclamation-triangle-fill','label' => 'Buku Rusak',        'color' => 'warning'],
    'hilang'       => ['icon' => 'bi-question-circle-fill',  'label' => 'Buku Hilang',          'color' => 'danger'],
    'anggota'      => ['icon' => 'bi-people-fill',           'label' => 'Data Anggota',         'color' => 'primary'],
    'pengunjung'   => ['icon' => 'bi-person-check-fill',     'label' => 'Pengunjung',           'color' => 'secondary'],
    'populer'      => ['icon' => 'bi-star-fill',             'label' => 'Buku Terpopuler',      'color' => 'warning'],
    'aktif'        => ['icon' => 'bi-trophy-fill',           'label' => 'Anggota Paling Aktif', 'color' => 'success'],
    'statistik'    => ['icon' => 'bi-bar-chart-fill',        'label' => 'Ringkasan Statistik',  'color' => 'info'],
];

$currentReport = $reportTypes[$tipe] ?? $reportTypes['peminjaman'];
$buildExportUrl = function(string $format) use ($tipe, $filters) {
    $q = http_build_query(array_merge(['tipe' => $tipe, 'format' => $format], array_filter($filters)));
    return base_url('index.php?page=laporan_export&' . $q);
};
$buildPrintUrl = function() use ($tipe, $filters) {
    $q = http_build_query(array_merge(['tipe' => $tipe], array_filter($filters)));
    return base_url('index.php?page=laporan_cetak&' . $q);
};

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">

    <!-- Page Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="page-title"><i class="bi bi-file-earmark-bar-graph-fill text-primary me-2"></i>Laporan Perpustakaan</h1>
            <p class="text-muted mb-0 small">12 jenis laporan komprehensif — cetak & ekspor ke Excel/CSV</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= $buildExportUrl('csv') ?>" class="btn btn-success btn-sm px-3">
                <i class="bi bi-filetype-csv me-1"></i> Export CSV
            </a>
            <a href="<?= $buildPrintUrl() ?>" target="_blank" class="btn btn-warning btn-sm px-3 text-dark">
                <i class="bi bi-printer-fill me-1"></i> Cetak Laporan
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar Pilih Jenis Laporan -->
        <div class="col-lg-3">
            <div class="card card-custom h-100">
                <div class="card-body p-0">
                    <div class="p-3 border-bottom">
                        <span class="fw-semibold text-muted small text-uppercase">Pilih Jenis Laporan</span>
                    </div>
                    <div class="list-group list-group-flush" id="report-type-list">
                        <?php foreach ($reportTypes as $key => $rep): ?>
                            <a href="<?= base_url('index.php?page=laporan&tipe=' . $key) ?>"
                               class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2 px-3 <?= $tipe === $key ? 'active' : '' ?>"
                               id="report-link-<?= $key ?>">
                                <i class="<?= $rep['icon'] ?> text-<?= $rep['color'] ?>"></i>
                                <span class="small fw-medium"><?= $rep['label'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Report Area -->
        <div class="col-lg-9">

            <!-- Filter Card -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <form method="GET" action="<?= base_url('index.php') ?>" class="row g-2 align-items-end">
                        <input type="hidden" name="page" value="laporan">
                        <input type="hidden" name="tipe" value="<?= htmlspecialchars($tipe) ?>">

                        <?php if (!in_array($tipe, ['keterlambatan', 'rusak', 'hilang', 'populer', 'aktif', 'statistik'])): ?>
                            <div class="col-sm-6 col-md-3">
                                <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                                <input type="date" name="tgl_awal" class="form-control form-control-sm" value="<?= htmlspecialchars($tglAwal) ?>">
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                                <input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?= htmlspecialchars($tglAkhir) ?>">
                            </div>
                        <?php endif; ?>

                        <?php if ($tipe === 'buku'): ?>
                            <div class="col-sm-6 col-md-3">
                                <label class="form-label small text-muted mb-1">Kategori</label>
                                <select name="kategori_id" class="form-select form-select-sm">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($kategoriList as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= ($filters['kategori_id'] == $k['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($k['nama_kategori']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($tipe === 'anggota'): ?>
                            <div class="col-sm-6 col-md-3">
                                <label class="form-label small text-muted mb-1">Jenis Anggota</label>
                                <select name="jenis_anggota" class="form-select form-select-sm">
                                    <option value="">Semua Jenis</option>
                                    <option value="Siswa" <?= $filters['jenis_anggota'] === 'Siswa' ? 'selected' : '' ?>>Siswa</option>
                                    <option value="Guru" <?= $filters['jenis_anggota'] === 'Guru' ? 'selected' : '' ?>>Guru/Staf</option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label class="form-label small text-muted mb-1">Jurusan</label>
                                <select name="jurusan_id" class="form-select form-select-sm">
                                    <option value="">Semua Jurusan</option>
                                    <?php foreach ($jurusanList as $j): ?>
                                        <option value="<?= $j['id'] ?>" <?= ($filters['jurusan_id'] == $j['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($j['kode_jurusan'] . ' - ' . $j['nama_jurusan']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($tipe === 'peminjaman'): ?>
                            <div class="col-sm-6 col-md-3">
                                <label class="form-label small text-muted mb-1">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Semua Status</option>
                                    <option value="Dipinjam" <?= $filters['status'] === 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                                    <option value="Terlambat" <?= $filters['status'] === 'Terlambat' ? 'selected' : '' ?>>Terlambat</option>
                                    <option value="Dikembalikan" <?= $filters['status'] === 'Dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($tipe === 'denda'): ?>
                            <div class="col-sm-6 col-md-3">
                                <label class="form-label small text-muted mb-1">Status Pembayaran</label>
                                <select name="status_bayar" class="form-select form-select-sm">
                                    <option value="">Semua Status</option>
                                    <option value="Belum Dibayar" <?= $filters['status_bayar'] === 'Belum Dibayar' ? 'selected' : '' ?>>Belum Dibayar</option>
                                    <option value="Sudah Dibayar" <?= $filters['status_bayar'] === 'Sudah Dibayar' ? 'selected' : '' ?>>Sudah Dibayar</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="bi bi-funnel-fill me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-auto">
                            <a href="<?= base_url('index.php?page=laporan&tipe=' . $tipe) ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-circle me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Table/Content Card -->
            <div class="card card-custom">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="<?= $currentReport['icon'] ?> text-<?= $currentReport['color'] ?> fs-5"></i>
                        <strong><?= $currentReport['label'] ?></strong>
                        <?php if ($tipe !== 'statistik'): ?>
                            <span class="badge bg-secondary ms-1"><?= is_array($data) ? count($data) : 0 ?> data</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-muted small">
                        <?= date('d M Y', strtotime($tglAwal)) ?> — <?= date('d M Y', strtotime($tglAkhir)) ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if ($tipe === 'statistik'): ?>
                        <!-- Statistik Umum -->
                        <?php $kpi = $data['kpi'] ?? []; ?>
                        <div class="p-4">
                            <div class="row g-3 mb-4">
                                <?php
                                $statItems = [
                                    ['icon' => 'bi-book-fill',         'label' => 'Total Judul Buku',  'val' => number_format($kpi['total_judul'] ?? 0),  'color' => 'primary'],
                                    ['icon' => 'bi-people-fill',        'label' => 'Anggota Aktif',     'val' => number_format($kpi['total_anggota'] ?? 0), 'color' => 'success'],
                                    ['icon' => 'bi-arrow-up-circle',    'label' => 'Sedang Dipinjam',   'val' => number_format($kpi['total_dipinjam'] ?? 0),'color' => 'warning'],
                                    ['icon' => 'bi-cash-stack',         'label' => 'Total Denda',       'val' => format_rupiah($kpi['total_denda'] ?? 0),   'color' => 'danger'],
                                ];
                                foreach ($statItems as $s): ?>
                                    <div class="col-sm-6 col-md-3">
                                        <div class="card border-0 bg-light text-center p-3 rounded-3">
                                            <i class="<?= $s['icon'] ?> text-<?= $s['color'] ?> fs-3 mb-1"></i>
                                            <div class="fw-bold fs-5"><?= $s['val'] ?></div>
                                            <div class="text-muted small"><?= $s['label'] ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <h6 class="fw-bold mb-3"><i class="bi bi-star-fill text-warning me-2"></i>5 Buku Terpopuler</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-hover table-bordered align-middle">
                                    <thead class="table-dark"><tr><th>No</th><th>Kode</th><th>Judul</th><th>Kategori</th><th>Total Dipinjam</th></tr></thead>
                                    <tbody>
                                        <?php foreach (($data['populer_buku'] ?? []) as $i => $pb): ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td><code><?= htmlspecialchars($pb['kode_buku']) ?></code></td>
                                                <td><?= htmlspecialchars($pb['judul']) ?></td>
                                                <td><?= htmlspecialchars($pb['nama_kategori'] ?? '-') ?></td>
                                                <td><span class="badge bg-warning text-dark"><?= $pb['total_dipinjam'] ?>x</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="fw-bold mb-3"><i class="bi bi-trophy-fill text-success me-2"></i>5 Anggota Paling Aktif</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover table-bordered align-middle">
                                    <thead class="table-dark"><tr><th>No</th><th>No. Anggota</th><th>Nama</th><th>Kelas</th><th>Total Peminjaman</th></tr></thead>
                                    <tbody>
                                        <?php foreach (($data['anggota_top'] ?? []) as $i => $at): ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td><code><?= htmlspecialchars($at['nomor_anggota']) ?></code></td>
                                                <td><?= htmlspecialchars($at['nama']) ?></td>
                                                <td><?= htmlspecialchars($at['nama_kelas'] ?? '-') ?></td>
                                                <td><span class="badge bg-success"><?= $at['total_buku_dipinjam'] ?> buku</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php elseif (empty($data)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Tidak ada data untuk filter yang dipilih.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0 align-middle" id="report-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <?php if ($tipe === 'peminjaman'): ?>
                                            <th>No. Transaksi</th><th>Anggota</th><th>Kelas</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Jml Buku</th><th>Status</th>
                                        <?php elseif ($tipe === 'pengembalian'): ?>
                                            <th>No. Kembali</th><th>No. Peminjaman</th><th>Anggota</th><th>Tgl Kembali</th><th>Denda</th><th>Status Denda</th>
                                        <?php elseif ($tipe === 'keterlambatan'): ?>
                                            <th>No. Transaksi</th><th>Anggota</th><th>Kelas</th><th>Jatuh Tempo</th><th>Hari Telat</th><th>Perkiraan Denda</th>
                                        <?php elseif ($tipe === 'denda'): ?>
                                            <th>No. Kembali</th><th>Anggota</th><th>Jenis Denda</th><th>Jumlah</th><th>Status</th><th>Tgl</th>
                                        <?php elseif ($tipe === 'buku'): ?>
                                            <th>Kode Buku</th><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Tahun</th><th>Stok Total</th><th>Tersedia</th>
                                        <?php elseif ($tipe === 'rusak'): ?>
                                            <th>Kode Eksemplar</th><th>Judul Buku</th><th>Kategori</th><th>Rak</th><th>Kondisi</th><th>Status</th>
                                        <?php elseif ($tipe === 'hilang'): ?>
                                            <th>Kode Eksemplar</th><th>Judul Buku</th><th>Kategori</th><th>Rak</th><th>Harga Buku</th><th>Status</th>
                                        <?php elseif ($tipe === 'anggota'): ?>
                                            <th>No. Anggota</th><th>Nama</th><th>Identitas</th><th>Kelas</th><th>Jurusan</th><th>Status</th>
                                        <?php elseif ($tipe === 'pengunjung'): ?>
                                            <th>Nama</th><th>Jenis</th><th>Kelas</th><th>Keperluan</th><th>Tanggal</th><th>Waktu Masuk</th>
                                        <?php elseif ($tipe === 'populer'): ?>
                                            <th>Kode</th><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Total Dipinjam</th>
                                        <?php elseif ($tipe === 'aktif'): ?>
                                            <th>No. Anggota</th><th>Nama</th><th>Kelas</th><th>Jurusan</th><th>Total Transaksi</th><th>Total Buku</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $i => $row): ?>
                                        <tr>
                                            <td class="text-muted small"><?= $i + 1 ?></td>
                                            <?php if ($tipe === 'peminjaman'): ?>
                                                <td><code class="small"><?= htmlspecialchars($row['nomor_transaksi']) ?></code></td>
                                                <td>
                                                    <div class="fw-semibold small"><?= htmlspecialchars($row['nama_anggota']) ?></div>
                                                    <div class="text-muted" style="font-size:0.72rem"><?= htmlspecialchars($row['nomor_anggota']) ?></div>
                                                </td>
                                                <td class="small"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?> <span class="text-muted"><?= htmlspecialchars($row['kode_jurusan'] ?? '') ?></span></td>
                                                <td class="small"><?= format_date($row['tanggal_pinjam']) ?></td>
                                                <td class="small <?= (strtotime($row['tanggal_jatuh_tempo']) < time() && $row['status'] !== 'Dikembalikan') ? 'text-danger fw-bold' : '' ?>">
                                                    <?= format_date($row['tanggal_jatuh_tempo']) ?>
                                                </td>
                                                <td class="text-center"><?= (int)$row['total_buku'] ?></td>
                                                <td>
                                                    <?php
                                                    $sBadge = ['Dipinjam' => 'warning text-dark', 'Dikembalikan' => 'success', 'Terlambat' => 'danger', 'Diperpanjang' => 'info'];
                                                    $bc = $sBadge[$row['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $bc ?> small"><?= htmlspecialchars($row['status']) ?></span>
                                                </td>
                                            <?php elseif ($tipe === 'pengembalian'): ?>
                                                <td><code class="small"><?= htmlspecialchars($row['nomor_pengembalian'] ?? '-') ?></code></td>
                                                <td><code class="small"><?= htmlspecialchars($row['nomor_transaksi'] ?? '-') ?></code></td>
                                                <td class="small fw-semibold"><?= htmlspecialchars($row['nama_anggota']) ?></td>
                                                <td class="small"><?= format_date($row['tanggal_kembali']) ?></td>
                                                <td class="small fw-bold text-danger"><?= format_rupiah($row['denda_dibayar'] ?? 0) ?></td>
                                                <td>
                                                    <?php $sd = $row['status_denda'] ?? 'Lunas'; ?>
                                                    <span class="badge bg-<?= $sd === 'Lunas' ? 'success' : 'danger' ?>"><?= htmlspecialchars($sd) ?></span>
                                                </td>
                                            <?php elseif ($tipe === 'keterlambatan'): ?>
                                                <td><code class="small"><?= htmlspecialchars($row['nomor_transaksi']) ?></code></td>
                                                <td class="small fw-semibold"><?= htmlspecialchars($row['nama_anggota']) ?></td>
                                                <td class="small"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                                <td class="small text-danger"><?= format_date($row['tanggal_jatuh_tempo']) ?></td>
                                                <td><span class="badge bg-danger"><?= (int)$row['telat_hari'] ?> hari</span></td>
                                                <td class="small fw-bold text-danger"><?= format_rupiah((int)$row['telat_hari'] * (float)get_setting('denda_per_hari', 1000)) ?></td>
                                            <?php elseif ($tipe === 'denda'): ?>
                                                <td><code class="small"><?= htmlspecialchars($row['nomor_pengembalian'] ?? '-') ?></code></td>
                                                <td class="small fw-semibold"><?= htmlspecialchars($row['nama_anggota']) ?></td>
                                                <td class="small"><?= htmlspecialchars($row['jenis_denda'] ?? '-') ?></td>
                                                <td class="fw-bold text-danger"><?= format_rupiah($row['jumlah_denda'] ?? 0) ?></td>
                                                <td>
                                                    <?php $sb = $row['status_bayar'] ?? 'Belum Dibayar'; ?>
                                                    <span class="badge bg-<?= $sb === 'Sudah Dibayar' ? 'success' : 'danger' ?> small"><?= htmlspecialchars($sb) ?></span>
                                                </td>
                                                <td class="small text-muted"><?= format_date(substr($row['created_at'] ?? date('Y-m-d'), 0, 10)) ?></td>
                                            <?php elseif ($tipe === 'buku'): ?>
                                                <td><code class="small"><?= htmlspecialchars($row['kode_buku']) ?></code></td>
                                                <td class="small fw-semibold"><?= htmlspecialchars($row['judul']) ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($row['nama_pengarang'] ?? '-') ?></td>
                                                <td class="small"><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                                                <td class="small"><?= (int)$row['tahun_terbit'] ?></td>
                                                <td class="text-center"><?= (int)$row['stok_total'] ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?= $row['stok_tersedia'] > 0 ? 'success' : 'danger' ?>"><?= (int)$row['stok_tersedia'] ?></span>
                                                </td>
                                            <?php elseif ($tipe === 'rusak'): ?>
                                                <td><code class="small"><?= htmlspecialchars($row['kode_eksemplar']) ?></code></td>
                                                <td class="small fw-semibold"><?= htmlspecialchars($row['judul']) ?></td>
                                                <td class="small"><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                                                <td class="small"><?= htmlspecialchars($row['nama_rak'] ?? '-') ?></td>
                                                <td><span class="badge bg-warning text-dark small"><?= htmlspecialchars($row['kondisi']) ?></span></td>
                                                <td class="small"><?= htmlspecialchars($row['status']) ?></td>
                                            <?php elseif ($tipe === 'hilang'): ?>
                                                <td><code class="small"><?= htmlspecialchars($row['kode_eksemplar']) ?></code></td>
                                                <td class="small fw-semibold"><?= htmlspecialchars($row['judul']) ?></td>
                                                <td class="small"><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                                                <td class="small"><?= htmlspecialchars($row['nama_rak'] ?? '-') ?></td>
                                                <td class="small fw-bold text-danger"><?= format_rupiah($row['harga'] ?? 0) ?></td>
                                                <td><span class="badge bg-danger small"><?= htmlspecialchars($row['status'] ?? 'Hilang') ?></span></td>
                                            <?php elseif ($tipe === 'anggota'): ?>
                                                <td><code class="small"><?= htmlspecialchars($row['nomor_anggota']) ?></code></td>
                                                <td class="small fw-semibold"><?= htmlspecialchars($row['nama']) ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($row['identitas_no']) ?></td>
                                                <td class="small"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                                <td class="small"><?= htmlspecialchars($row['kode_jurusan'] ?? '-') ?> <span class="text-muted"><?= htmlspecialchars($row['nama_jurusan'] ?? '') ?></span></td>
                                                <td><span class="badge bg-<?= $row['status_aktif'] ? 'success' : 'secondary' ?> small"><?= $row['status_aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                                            <?php elseif ($tipe === 'pengunjung'): ?>
                                                <td class="small fw-semibold"><?= htmlspecialchars($row['nama_pengunjung'] ?? $row['nama'] ?? '-') ?></td>
                                                <td class="small"><?= htmlspecialchars($row['jenis_pengunjung']) ?></td>
                                                <td class="small"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                                <td class="small"><?= htmlspecialchars($row['keperluan'] ?? '-') ?></td>
                                                <td class="small"><?= format_date($row['tanggal']) ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars(substr($row['waktu_masuk'] ?? '', 0, 5)) ?></td>
                                            <?php elseif ($tipe === 'populer'): ?>
                                                <td><code class="small"><?= htmlspecialchars($row['kode_buku']) ?></code></td>
                                                <td class="small fw-semibold"><?= htmlspecialchars($row['judul']) ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($row['nama_pengarang'] ?? '-') ?></td>
                                                <td class="small"><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                                                <td class="text-center"><span class="badge bg-warning text-dark"><?= (int)$row['total_dipinjam'] ?>x</span></td>
                                            <?php elseif ($tipe === 'aktif'): ?>
                                                <td><code class="small"><?= htmlspecialchars($row['nomor_anggota']) ?></code></td>
                                                <td class="small fw-semibold"><?= htmlspecialchars($row['nama']) ?></td>
                                                <td class="small"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                                <td class="small"><?= htmlspecialchars($row['kode_jurusan'] ?? '-') ?></td>
                                                <td class="text-center"><span class="badge bg-primary"><?= (int)$row['total_transaksi'] ?>x</span></td>
                                                <td class="text-center"><span class="badge bg-success"><?= (int)$row['total_buku_dipinjam'] ?> buku</span></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
