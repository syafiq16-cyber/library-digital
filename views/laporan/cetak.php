<?php
/**
 * Laporan Cetak - Format Resmi dengan Kop Surat SMKN 2 Sumbawa Besar
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Laporan.php';

require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);

$laporanModel = new Laporan();

$tipe    = $_GET['tipe'] ?? 'peminjaman';
$tglAwal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tglAkhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

$filters = [
    'tgl_awal'       => $tglAwal,
    'tgl_akhir'      => $tglAkhir,
    'kategori_id'    => (int)($_GET['kategori_id'] ?? 0) ?: null,
    'kelas_id'       => (int)($_GET['kelas_id'] ?? 0) ?: null,
    'jurusan_id'     => (int)($_GET['jurusan_id'] ?? 0) ?: null,
    'status'         => $_GET['status'] ?? '',
    'status_denda'   => $_GET['status_denda'] ?? '',
    'status_bayar'   => $_GET['status_bayar'] ?? '',
    'jenis_anggota'  => $_GET['jenis_anggota'] ?? '',
    'jenis_pengunjung' => $_GET['jenis_pengunjung'] ?? '',
];

$data = [];
$reportTitles = [
    'buku'          => 'Laporan Data Koleksi Buku',
    'anggota'       => 'Laporan Data Anggota Perpustakaan',
    'peminjaman'    => 'Laporan Transaksi Peminjaman',
    'pengembalian'  => 'Laporan Transaksi Pengembalian',
    'keterlambatan' => 'Laporan Keterlambatan Pengembalian',
    'denda'         => 'Laporan Denda Perpustakaan',
    'rusak'         => 'Laporan Buku Rusak',
    'hilang'        => 'Laporan Buku Hilang',
    'pengunjung'    => 'Laporan Pengunjung Perpustakaan',
    'populer'       => 'Laporan Buku Terpopuler',
    'aktif'         => 'Laporan Anggota Paling Aktif',
    'statistik'     => 'Laporan Ringkasan Statistik Perpustakaan',
];

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

$reportTitle = $reportTitles[$tipe] ?? 'Laporan Perpustakaan';

$namaSekolah      = get_setting('nama_sekolah', 'SMK Negeri 2 Sumbawa Besar');
$namaPerpus       = get_setting('nama_perpustakaan', 'PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR');
$npsn             = get_setting('npsn', '');
$alamat           = get_setting('alamat', 'Jl. Lingkar Luar No. 02, Sumbawa Besar, NTB');
$telp             = get_setting('nomor_telepon', '');
$email            = get_setting('email', '');
$kepalaSekolah    = get_setting('kepala_sekolah', '');
$nipKepSek        = get_setting('nip_kepala_sekolah', '');
$kepalaPerp       = get_setting('kepala_perpustakaan', '');
$nipKepPerp       = get_setting('nip_kepala_perpustakaan', '');
$logo             = get_setting('logo', 'logo_smkn2.png');
$logoPath         = asset_url('images/' . $logo);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($reportTitle) ?> - <?= htmlspecialchars($namaSekolah) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #000;
            background: #fff;
        }
        /* KOP SURAT */
        .kop-surat {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #1a237e;
            margin-bottom: 5px;
        }
        .kop-logo img {
            width: 75px;
            height: 75px;
            object-fit: contain;
        }
        .kop-text h2 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #1a237e;
            margin-bottom: 2px;
        }
        .kop-text h3 {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #1a237e;
            margin-bottom: 4px;
        }
        .kop-text p {
            font-size: 9pt;
            color: #333;
            line-height: 1.4;
        }
        .kop-divider {
            border: none;
            border-top: 1px solid #1a237e;
            margin: 4px 0 15px;
        }

        /* JUDUL LAPORAN */
        .report-title-block {
            text-align: center;
            margin-bottom: 15px;
        }
        .report-title-block h1 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 4px;
        }
        .report-title-block p {
            font-size: 10pt;
            color: #444;
        }

        /* TABEL */
        table { border-collapse: collapse; width: 100%; font-size: 9pt; }
        thead { background-color: #1a237e; color: white; }
        th, td { border: 0.5px solid #555; padding: 4px 7px; vertical-align: top; }
        tbody tr:nth-child(even) { background-color: #f4f6fb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        code { font-family: monospace; font-size: 8pt; }

        /* TANDA TANGAN */
        .ttd-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .ttd-box {
            text-align: center;
            width: 200px;
        }
        .ttd-box .ttd-label { font-size: 9.5pt; margin-bottom: 55px; }
        .ttd-box .ttd-name { font-weight: bold; font-size: 10pt; text-decoration: underline; }
        .ttd-box .ttd-nip { font-size: 9pt; }

        /* Tombol print */
        .print-controls {
            position: fixed;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 8px;
            z-index: 9999;
        }
        .print-controls button, .print-controls a {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-print { background: #1a237e; color: #fff; }
        .btn-close-pr { background: #e0e0e0; color: #333; }

        @media print {
            .print-controls { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <!-- Print Controls -->
    <div class="print-controls">
        <a href="javascript:window.close();" class="btn-close-pr">&#x2715; Tutup</a>
        <button onclick="window.print()" class="btn-print">&#128438; Cetak</button>
    </div>

    <!-- KOP SURAT -->
    <div class="kop-surat">
        <div class="kop-logo">
            <img src="<?= $logoPath ?>" alt="Logo SMKN 2">
        </div>
        <div class="kop-text">
            <h2><?= htmlspecialchars($namaSekolah) ?></h2>
            <h3><?= htmlspecialchars($namaPerpus) ?></h3>
            <p>
                Alamat: <?= htmlspecialchars($alamat) ?>
                <?php if ($npsn): ?> | NPSN: <?= htmlspecialchars($npsn) ?><?php endif; ?>
                <?php if ($telp): ?> | Telp: <?= htmlspecialchars($telp) ?><?php endif; ?>
                <?php if ($email): ?> | Email: <?= htmlspecialchars($email) ?><?php endif; ?>
            </p>
        </div>
    </div>
    <hr class="kop-divider">

    <!-- JUDUL LAPORAN -->
    <div class="report-title-block">
        <h1><?= htmlspecialchars($reportTitle) ?></h1>
        <?php if (!in_array($tipe, ['keterlambatan', 'rusak', 'hilang', 'populer', 'aktif', 'statistik'])): ?>
            <p>Periode: <?= date('d F Y', strtotime($tglAwal)) ?> &ndash; <?= date('d F Y', strtotime($tglAkhir)) ?></p>
        <?php else: ?>
            <p>Dicetak Tanggal: <?= date('d F Y') ?></p>
        <?php endif; ?>
    </div>

    <!-- ISI LAPORAN -->
    <?php if ($tipe === 'statistik'): 
        $kpi = $data['kpi'] ?? []; ?>
        <table>
            <thead><tr><th>Indikator</th><th>Nilai</th></tr></thead>
            <tbody>
                <tr><td>Total Judul Buku</td><td><?= number_format($kpi['total_judul'] ?? 0) ?> judul</td></tr>
                <tr><td>Total Eksemplar Buku</td><td><?= number_format($kpi['total_buku'] ?? 0) ?> eksemplar</td></tr>
                <tr><td>Buku Tersedia</td><td><?= number_format($kpi['total_tersedia'] ?? 0) ?> eksemplar</td></tr>
                <tr><td>Anggota Aktif</td><td><?= number_format($kpi['total_anggota'] ?? 0) ?> anggota</td></tr>
                <tr><td>Buku Sedang Dipinjam</td><td><?= number_format($kpi['total_dipinjam'] ?? 0) ?> buku</td></tr>
                <tr><td>Peminjaman Terlambat</td><td><?= number_format($kpi['total_terlambat'] ?? 0) ?> transaksi</td></tr>
                <tr><td>Peminjaman Hari Ini</td><td><?= number_format($kpi['pinjam_hari_ini'] ?? 0) ?> transaksi</td></tr>
                <tr><td>Total Denda Terkumpul</td><td><?= format_rupiah($kpi['total_denda'] ?? 0) ?></td></tr>
            </tbody>
        </table>
    <?php elseif (empty($data)): ?>
        <p style="text-align:center; color:#888; margin-top:20px;">Tidak ada data untuk periode yang dipilih.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width:30px">No</th>
                    <?php if ($tipe === 'peminjaman'): ?>
                        <th>No. Transaksi</th><th>Anggota</th><th>Kelas</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Jml Buku</th><th>Status</th>
                    <?php elseif ($tipe === 'pengembalian'): ?>
                        <th>No. Kembali</th><th>No. Pinjam</th><th>Anggota</th><th>Tgl Kembali</th><th>Denda</th><th>Status Denda</th>
                    <?php elseif ($tipe === 'keterlambatan'): ?>
                        <th>No. Transaksi</th><th>Nama Anggota</th><th>No. Anggota</th><th>Kelas</th><th>Jatuh Tempo</th><th>Hari Telat</th><th>Est. Denda</th>
                    <?php elseif ($tipe === 'denda'): ?>
                        <th>No. Kembali</th><th>Nama Anggota</th><th>Jenis Denda</th><th>Jumlah</th><th>Status Bayar</th><th>Tanggal</th>
                    <?php elseif ($tipe === 'buku'): ?>
                        <th>Kode Buku</th><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Tahun</th><th class="text-center">Stok</th><th class="text-center">Tersedia</th>
                    <?php elseif ($tipe === 'rusak'): ?>
                        <th>Kode Eksemplar</th><th>Judul Buku</th><th>Kategori</th><th>Rak</th><th>Kondisi</th><th>Status</th>
                    <?php elseif ($tipe === 'hilang'): ?>
                        <th>Kode Eksemplar</th><th>Judul Buku</th><th>Kategori</th><th>Rak</th><th class="text-right">Harga Buku</th><th>Status</th>
                    <?php elseif ($tipe === 'anggota'): ?>
                        <th>No. Anggota</th><th>Nama Lengkap</th><th>Identitas</th><th>Kelas</th><th>Jurusan</th><th>Status</th>
                    <?php elseif ($tipe === 'pengunjung'): ?>
                        <th>Nama</th><th>Jenis</th><th>Kelas</th><th>Keperluan</th><th>Tanggal</th><th>Masuk</th>
                    <?php elseif ($tipe === 'populer'): ?>
                        <th>Kode Buku</th><th>Judul</th><th>Pengarang</th><th>Kategori</th><th class="text-center">Total Dipinjam</th>
                    <?php elseif ($tipe === 'aktif'): ?>
                        <th>No. Anggota</th><th>Nama</th><th>Kelas</th><th>Jurusan</th><th class="text-center">Total Transaksi</th><th class="text-center">Total Buku</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $i => $row): ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <?php if ($tipe === 'peminjaman'): ?>
                            <td><code><?= htmlspecialchars($row['nomor_transaksi']) ?></code></td>
                            <td><?= htmlspecialchars($row['nama_anggota']) ?> <br><small>(<?= htmlspecialchars($row['nomor_anggota']) ?>)</small></td>
                            <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                            <td><?= format_date($row['tanggal_pinjam']) ?></td>
                            <td><?= format_date($row['tanggal_jatuh_tempo']) ?></td>
                            <td class="text-center"><?= (int)$row['total_buku'] ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                        <?php elseif ($tipe === 'pengembalian'): ?>
                            <td><code><?= htmlspecialchars($row['nomor_pengembalian'] ?? '-') ?></code></td>
                            <td><code><?= htmlspecialchars($row['nomor_transaksi'] ?? '-') ?></code></td>
                            <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
                            <td><?= format_date($row['tanggal_kembali']) ?></td>
                            <td class="text-right"><?= format_rupiah($row['denda_dibayar'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($row['status_denda'] ?? 'Lunas') ?></td>
                        <?php elseif ($tipe === 'keterlambatan'): ?>
                            <td><code><?= htmlspecialchars($row['nomor_transaksi']) ?></code></td>
                            <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
                            <td><?= htmlspecialchars($row['nomor_anggota']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                            <td><?= format_date($row['tanggal_jatuh_tempo']) ?></td>
                            <td class="text-center"><?= (int)$row['telat_hari'] ?> hari</td>
                            <td class="text-right"><?= format_rupiah((int)$row['telat_hari'] * (float)get_setting('denda_per_hari', 1000)) ?></td>
                        <?php elseif ($tipe === 'denda'): ?>
                            <td><code><?= htmlspecialchars($row['nomor_pengembalian'] ?? '-') ?></code></td>
                            <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
                            <td><?= htmlspecialchars($row['jenis_denda'] ?? '-') ?></td>
                            <td class="text-right"><?= format_rupiah($row['jumlah_denda'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($row['status_bayar'] ?? '-') ?></td>
                            <td><?= format_date(substr($row['created_at'] ?? date('Y-m-d'), 0, 10)) ?></td>
                        <?php elseif ($tipe === 'buku'): ?>
                            <td><code><?= htmlspecialchars($row['kode_buku']) ?></code></td>
                            <td><?= htmlspecialchars($row['judul']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pengarang'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                            <td class="text-center"><?= (int)$row['tahun_terbit'] ?></td>
                            <td class="text-center"><?= (int)$row['stok_total'] ?></td>
                            <td class="text-center"><?= (int)$row['stok_tersedia'] ?></td>
                        <?php elseif ($tipe === 'rusak'): ?>
                            <td><code><?= htmlspecialchars($row['kode_eksemplar']) ?></code></td>
                            <td><?= htmlspecialchars($row['judul']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_rak'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['kondisi']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                        <?php elseif ($tipe === 'hilang'): ?>
                            <td><code><?= htmlspecialchars($row['kode_eksemplar']) ?></code></td>
                            <td><?= htmlspecialchars($row['judul']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_rak'] ?? '-') ?></td>
                            <td class="text-right"><?= format_rupiah($row['harga'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($row['status'] ?? 'Hilang') ?></td>
                        <?php elseif ($tipe === 'anggota'): ?>
                            <td><code><?= htmlspecialchars($row['nomor_anggota']) ?></code></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['identitas_no']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['kode_jurusan'] ?? '-') ?></td>
                            <td><?= $row['status_aktif'] ? 'Aktif' : 'Nonaktif' ?></td>
                        <?php elseif ($tipe === 'pengunjung'): ?>
                            <td><?= htmlspecialchars($row['nama_pengunjung'] ?? $row['nama'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['jenis_pengunjung']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['keperluan'] ?? '-') ?></td>
                            <td><?= format_date($row['tanggal']) ?></td>
                            <td><?= htmlspecialchars(substr($row['waktu_masuk'] ?? '', 0, 5)) ?></td>
                        <?php elseif ($tipe === 'populer'): ?>
                            <td><code><?= htmlspecialchars($row['kode_buku']) ?></code></td>
                            <td><?= htmlspecialchars($row['judul']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pengarang'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                            <td class="text-center"><?= (int)$row['total_dipinjam'] ?>x</td>
                        <?php elseif ($tipe === 'aktif'): ?>
                            <td><code><?= htmlspecialchars($row['nomor_anggota']) ?></code></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['kode_jurusan'] ?? '-') ?></td>
                            <td class="text-center"><?= (int)$row['total_transaksi'] ?></td>
                            <td class="text-center"><?= (int)$row['total_buku_dipinjam'] ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:10px; font-size:9pt; color:#444;">
            <strong>Total Data:</strong> <?= count($data) ?> baris
        </div>
    <?php endif; ?>

    <!-- TANDA TANGAN -->
    <div class="ttd-section">
        <div class="ttd-box">
            <div class="ttd-label">Mengetahui,<br>Kepala <?= htmlspecialchars($namaSekolah) ?></div>
            <div class="ttd-name"><?= htmlspecialchars($kepalaSekolah ?: '__________________________') ?></div>
            <?php if ($nipKepSek): ?>
                <div class="ttd-nip">NIP. <?= htmlspecialchars($nipKepSek) ?></div>
            <?php endif; ?>
        </div>
        <div class="ttd-box" style="text-align:right;">
            <div class="ttd-label">Sumbawa Besar, <?= date('d F Y') ?><br>Kepala Perpustakaan</div>
            <div class="ttd-name"><?= htmlspecialchars($kepalaPerp ?: '__________________________') ?></div>
            <?php if ($nipKepPerp): ?>
                <div class="ttd-nip">NIP. <?= htmlspecialchars($nipKepPerp) ?></div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
