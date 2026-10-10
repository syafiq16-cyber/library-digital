<?php
/**
 * Export Laporan ke CSV/Excel
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Laporan.php';

require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);

$laporanModel = new Laporan();

$tipe    = $_GET['tipe'] ?? 'peminjaman';
$format  = $_GET['format'] ?? 'csv';
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

// Ambil data
$data = [];
$headers = [];
$filename = 'laporan_' . $tipe . '_' . date('Ymd');

switch ($tipe) {
    case 'buku':
        $data = $laporanModel->laporanBuku($filters);
        $headers = ['Kode Buku', 'Judul', 'Pengarang', 'Penerbit', 'Kategori', 'Rak', 'Tahun Terbit', 'Stok Total', 'Stok Tersedia', 'Status'];
        break;
    case 'anggota':
        $data = $laporanModel->laporanAnggota($filters);
        $headers = ['No. Anggota', 'Nama', 'No. Identitas', 'Jenis Anggota', 'Jenis Kelamin', 'Kelas', 'Jurusan', 'No. HP', 'Tanggal Registrasi', 'Status Aktif'];
        break;
    case 'peminjaman':
        $data = $laporanModel->laporanPeminjaman($filters);
        $headers = ['No. Transaksi', 'Nama Anggota', 'No. Anggota', 'Kelas', 'Jurusan', 'Tgl Pinjam', 'Jatuh Tempo', 'Jumlah Buku', 'Status', 'Petugas'];
        break;
    case 'pengembalian':
        $data = $laporanModel->laporanPengembalian($filters);
        $headers = ['No. Pengembalian', 'No. Peminjaman', 'Nama Anggota', 'Tgl Kembali', 'Denda Dibayar', 'Status Denda', 'Petugas'];
        break;
    case 'keterlambatan':
        $data = $laporanModel->laporanKeterlambatan();
        $headers = ['No. Transaksi', 'Nama Anggota', 'No. Anggota', 'Kelas', 'No. HP', 'Jatuh Tempo', 'Hari Telat', 'Estimasi Denda'];
        break;
    case 'denda':
        $data = $laporanModel->laporanDenda($filters);
        $headers = ['No. Pengembalian', 'Nama Anggota', 'No. Anggota', 'Jenis Denda', 'Jumlah Denda', 'Status Bayar', 'Tanggal'];
        break;
    case 'rusak':
        $data = $laporanModel->laporanBukuRusak();
        $headers = ['Kode Eksemplar', 'Kode Buku', 'Judul Buku', 'Kategori', 'Rak', 'Kondisi', 'Status'];
        break;
    case 'hilang':
        $data = $laporanModel->laporanBukuHilang();
        $headers = ['Kode Eksemplar', 'Kode Buku', 'Judul Buku', 'Kategori', 'Rak', 'Harga Buku', 'Status'];
        break;
    case 'pengunjung':
        $data = $laporanModel->laporanPengunjung($filters);
        $headers = ['Nama Pengunjung', 'Jenis Pengunjung', 'Kelas', 'Jurusan', 'Keperluan', 'Tanggal', 'Waktu Masuk'];
        break;
    case 'populer':
        $data = $laporanModel->laporanBukuTerpopuler(30);
        $headers = ['Kode Buku', 'Judul', 'Pengarang', 'Penerbit', 'Kategori', 'Total Dipinjam'];
        break;
    case 'aktif':
        $data = $laporanModel->laporanAnggotaAktif(30);
        $headers = ['No. Anggota', 'Nama', 'Kelas', 'Jurusan', 'Total Transaksi', 'Total Buku Dipinjam'];
        break;
    default:
        $data = $laporanModel->laporanPeminjaman($filters);
        $headers = ['No. Transaksi', 'Nama Anggota', 'No. Anggota', 'Kelas', 'Jurusan', 'Tgl Pinjam', 'Jatuh Tempo', 'Jumlah Buku', 'Status', 'Petugas'];
}

// Set HTTP headers untuk download
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output UTF-8 BOM agar Excel bisa membaca karakter Indonesia
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Baris judul laporan
$namaSekolah = get_setting('nama_sekolah', 'SMK Negeri 2 Sumbawa Besar');
$namaPerpus  = get_setting('nama_perpustakaan', 'Perpustakaan Digital SMKN 2 Sumbawa Besar');
$reportTitles = [
    'buku' => 'Laporan Data Buku', 'anggota' => 'Laporan Data Anggota',
    'peminjaman' => 'Laporan Peminjaman', 'pengembalian' => 'Laporan Pengembalian',
    'keterlambatan' => 'Laporan Keterlambatan', 'denda' => 'Laporan Denda',
    'rusak' => 'Laporan Buku Rusak', 'hilang' => 'Laporan Buku Hilang',
    'pengunjung' => 'Laporan Pengunjung', 'populer' => 'Buku Terpopuler',
    'aktif' => 'Anggota Paling Aktif',
];

fputcsv($output, [$namaSekolah]);
fputcsv($output, [$namaPerpus]);
fputcsv($output, [$reportTitles[$tipe] ?? 'Laporan Perpustakaan']);
fputcsv($output, ['Periode: ' . date('d/m/Y', strtotime($tglAwal)) . ' s/d ' . date('d/m/Y', strtotime($tglAkhir))]);
fputcsv($output, ['Diekspor Tanggal: ' . date('d/m/Y H:i')]);
fputcsv($output, []); // baris kosong

// Header kolom
fputcsv($output, array_merge(['No.'], $headers));

// Data baris
foreach ($data as $idx => $row) {
    $line = [$idx + 1];

    switch ($tipe) {
        case 'buku':
            $line = array_merge($line, [
                $row['kode_buku'], $row['judul'], $row['nama_pengarang'] ?? '-',
                $row['nama_penerbit'] ?? '-', $row['nama_kategori'] ?? '-',
                $row['nama_rak'] ?? '-', $row['tahun_terbit'],
                $row['stok_total'], $row['stok_tersedia'], $row['status']
            ]);
            break;
        case 'anggota':
            $line = array_merge($line, [
                $row['nomor_anggota'], $row['nama'], $row['identitas_no'],
                $row['jenis_anggota'], $row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan',
                $row['nama_kelas'] ?? '-', $row['kode_jurusan'] ?? '-',
                $row['nomor_hp'] ?? '-', $row['tanggal_registrasi'],
                $row['status_aktif'] ? 'Aktif' : 'Nonaktif'
            ]);
            break;
        case 'peminjaman':
            $line = array_merge($line, [
                $row['nomor_transaksi'], $row['nama_anggota'], $row['nomor_anggota'],
                $row['nama_kelas'] ?? '-', $row['kode_jurusan'] ?? '-',
                $row['tanggal_pinjam'], $row['tanggal_jatuh_tempo'],
                $row['total_buku'], $row['status'], $row['nama_petugas'] ?? '-'
            ]);
            break;
        case 'pengembalian':
            $line = array_merge($line, [
                $row['nomor_pengembalian'] ?? '-', $row['nomor_transaksi'] ?? '-',
                $row['nama_anggota'], $row['tanggal_kembali'],
                $row['denda_dibayar'] ?? 0, $row['status_denda'] ?? 'Lunas', $row['nama_petugas'] ?? '-'
            ]);
            break;
        case 'keterlambatan':
            $line = array_merge($line, [
                $row['nomor_transaksi'], $row['nama_anggota'], $row['nomor_anggota'],
                $row['nama_kelas'] ?? '-', $row['nomor_hp'] ?? '-',
                $row['tanggal_jatuh_tempo'], $row['telat_hari'],
                (int)$row['telat_hari'] * (float)get_setting('denda_per_hari', 1000)
            ]);
            break;
        case 'denda':
            $line = array_merge($line, [
                $row['nomor_pengembalian'] ?? '-', $row['nama_anggota'], $row['nomor_anggota'],
                $row['jenis_denda'] ?? '-', $row['jumlah_denda'] ?? 0,
                $row['status_bayar'] ?? '-', substr($row['created_at'] ?? '', 0, 10)
            ]);
            break;
        case 'rusak':
            $line = array_merge($line, [
                $row['kode_eksemplar'], $row['kode_buku'], $row['judul'],
                $row['nama_kategori'] ?? '-', $row['nama_rak'] ?? '-',
                $row['kondisi'], $row['status']
            ]);
            break;
        case 'hilang':
            $line = array_merge($line, [
                $row['kode_eksemplar'], $row['kode_buku'], $row['judul'],
                $row['nama_kategori'] ?? '-', $row['nama_rak'] ?? '-',
                $row['harga'] ?? 0, $row['status'] ?? 'Hilang'
            ]);
            break;
        case 'pengunjung':
            $line = array_merge($line, [
                $row['nama_pengunjung'] ?? $row['nama'] ?? '-',
                $row['jenis_pengunjung'], $row['nama_kelas'] ?? '-',
                $row['kode_jurusan'] ?? '-', $row['keperluan'] ?? '-',
                $row['tanggal'], substr($row['waktu_masuk'] ?? '', 0, 5)
            ]);
            break;
        case 'populer':
            $line = array_merge($line, [
                $row['kode_buku'], $row['judul'], $row['nama_pengarang'] ?? '-',
                $row['nama_penerbit'] ?? '-', $row['nama_kategori'] ?? '-',
                $row['total_dipinjam']
            ]);
            break;
        case 'aktif':
            $line = array_merge($line, [
                $row['nomor_anggota'], $row['nama'],
                $row['nama_kelas'] ?? '-', $row['kode_jurusan'] ?? '-',
                $row['total_transaksi'], $row['total_buku_dipinjam']
            ]);
            break;
    }

    fputcsv($output, $line);
}

fclose($output);
exit;
