<?php
/**
 * Cetak Struk / Bukti Peminjaman Buku
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Peminjaman.php';

require_role(['Administrator', 'Petugas']);

$id = (int)($_GET['id'] ?? 0);
$peminjamanModel = new Peminjaman();
$loan = $peminjamanModel->getById($id);

if (!$loan) {
    die("Transaksi tidak ditemukan.");
}

$details = $peminjamanModel->getDetails($id);
$schoolName = get_setting('nama_sekolah', 'SMK Negeri 2 Sumbawa Besar');
$libName = get_setting('nama_perpustakaan', 'PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR');
$schoolAddress = get_setting('alamat', 'Jl. Lingkar Luar No. 02, Sumbawa Besar');
$dendaPerHari = (float)get_setting('denda_per_hari', '1000');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Peminjaman - <?= htmlspecialchars($loan['nomor_transaksi'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= asset_url('vendor/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/print.css') ?>">
    <style>
        body {
            background-color: #f1f5f9;
            padding: 30px 10px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }
        .receipt-card {
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .receipt-title {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .receipt-sub {
            font-size: 10.5px;
            text-align: center;
            color: #64748b;
            margin-bottom: 15px;
            border-bottom: 1.5px dashed #cbd5e1;
            padding-bottom: 10px;
        }
        @media print {
            body { background: #fff !important; padding: 0 !important; }
            .receipt-card { border: none !important; box-shadow: none !important; width: 100% !important; max-width: 100% !important; padding: 0 !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <div class="text-center mb-2 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-sm rounded-pill px-3">
            Cetak Struk Ini
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 ms-2">
            Tutup
        </button>
    </div>

    <div class="receipt-title"><?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="receipt-sub">
        <strong><?= htmlspecialchars($libName, ENT_QUOTES, 'UTF-8') ?></strong><br>
        <?= htmlspecialchars($schoolAddress, ENT_QUOTES, 'UTF-8') ?>
    </div>

    <div class="d-flex justify-content-between small mb-2">
        <span class="text-muted">No. Transaksi:</span>
        <span class="fw-bold font-monospace"><?= htmlspecialchars($loan['nomor_transaksi'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="d-flex justify-content-between small mb-2">
        <span class="text-muted">Nama Anggota:</span>
        <span class="fw-semibold"><?= htmlspecialchars($loan['nama_anggota'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="d-flex justify-content-between small mb-2">
        <span class="text-muted">No. Anggota / NIS:</span>
        <span><?= htmlspecialchars($loan['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($loan['identitas_no'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="d-flex justify-content-between small mb-2">
        <span class="text-muted">Kelas / Jurusan:</span>
        <span><?= htmlspecialchars(($loan['nama_kelas'] ?? '-') . ' (' . ($loan['kode_jurusan'] ?? '-') . ')', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="d-flex justify-content-between small mb-2">
        <span class="text-muted">Tanggal Pinjam:</span>
        <span><?= date('d/m/Y', strtotime($loan['tanggal_pinjam'])) ?></span>
    </div>
    <div class="d-flex justify-content-between small mb-3 p-2 bg-light rounded border">
        <span class="text-danger fw-bold">Jatuh Tempo:</span>
        <span class="text-danger fw-bold font-monospace fs-6"><?= date('d/m/Y', strtotime($loan['tanggal_jatuh_tempo'])) ?></span>
    </div>

    <table class="table table-sm small border-top border-bottom mb-3">
        <thead>
            <tr class="text-muted">
                <th>Judul Buku</th>
                <th class="text-end">Kode</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $d): ?>
            <tr>
                <td>
                    <div class="fw-semibold"><?= htmlspecialchars($d['judul'], ENT_QUOTES, 'UTF-8') ?></div>
                    <small class="text-muted">Rak: <?= htmlspecialchars($d['kode_rak'] ?? '-', ENT_QUOTES, 'UTF-8') ?></small>
                </td>
                <td class="text-end font-monospace"><?= htmlspecialchars($d['kode_eksemplar'] ?: $d['kode_buku'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="p-2 mb-3 bg-light rounded border text-muted" style="font-size: 10px; line-height: 1.4;">
        <strong>Perhatian:</strong>
        <ul class="mb-0 ps-3">
            <li>Harap mengembalikan buku tepat waktu sebelum tanggal jatuh tempo.</li>
            <li>Keterlambatan dikenakan denda <?= format_rupiah($dendaPerHari) ?> per hari per buku.</li>
            <li>Jagalah kebersihan dan keutuhan buku selama peminjaman.</li>
        </ul>
    </div>

    <div class="d-flex justify-content-between text-center small mt-4" style="font-size: 11px;">
        <div style="width: 150px;">
            <span>Peminjam,</span>
            <div style="height: 45px;"></div>
            <strong>( <?= htmlspecialchars($loan['nama_anggota'], ENT_QUOTES, 'UTF-8') ?> )</strong>
        </div>
        <div style="width: 150px;">
            <span>Petugas Layanan,</span>
            <div style="height: 45px;"></div>
            <strong>( <?= htmlspecialchars($loan['nama_petugas'], ENT_QUOTES, 'UTF-8') ?> )</strong>
        </div>
    </div>
</div>

</body>
</html>
