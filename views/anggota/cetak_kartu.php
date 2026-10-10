<?php
/**
 * Modul Cetak Kartu Anggota Perpustakaan (Standar ID Card Modern)
 * Format QR Code: LIBRARY-MEMBER:{nomor_anggota}
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/qrcode_helper.php';
require_once __DIR__ . '/../../models/Anggota.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas']);

$anggotaModel = new Anggota();
$masterModel = new Master();

$singleId = (int)($_GET['anggota_id'] ?? 0);
$selectedKelas = $_GET['kelas_id'] ?? '';
$selectedJurusan = $_GET['jurusan_id'] ?? '';

if ($singleId > 0) {
    $members = [$anggotaModel->getById($singleId)];
} elseif (!empty($selectedKelas) || !empty($selectedJurusan)) {
    $members = $anggotaModel->getAll(['kelas_id' => $selectedKelas, 'jurusan_id' => $selectedJurusan, 'status_aktif' => 1], 100, 0);
} else {
    $members = $anggotaModel->getAll(['status_aktif' => 1], 100, 0);
}

$schoolName = get_setting('nama_sekolah', 'SMK Negeri 2 Sumbawa Besar');
$libName = get_setting('nama_perpustakaan', 'PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR');
$schoolAddress = get_setting('alamat', 'Jl. Lingkar Luar No. 02, Sumbawa Besar');
$kelasList = $masterModel->getKelas();
$jurusanList = $masterModel->getJurusan();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu Anggota - <?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= asset_url('vendor/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/print.css') ?>">
    <!-- Offline QR Code Library -->
    <script src="<?= asset_url('vendor/qrcode.min.js') ?>"></script>
    <style>
        body {
            background-color: #f8fafc;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }
        .control-bar {
            background: #fff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .member-card {
            width: 85.6mm;
            height: 54mm;
            border: 1.5px solid #1e3a8a;
            border-radius: 8px;
            padding: 3mm 4mm;
            background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
            display: inline-block;
            margin: 6mm;
            vertical-align: top;
        }
        .member-card-header {
            display: flex;
            align-items: center;
            gap: 2mm;
            border-bottom: 1.5px solid #1e3a8a;
            padding-bottom: 1.5mm;
            margin-bottom: 2mm;
        }
        .member-card-header img {
            width: 9mm;
            height: 9mm;
            object-fit: contain;
        }
        .member-card-header-text h6 {
            font-size: 7.5pt;
            font-weight: 800;
            margin: 0;
            color: #1e3a8a;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .member-card-header-text small {
            font-size: 5.5pt;
            color: #475569;
            display: block;
            line-height: 1.1;
        }
        .member-card-body {
            display: flex;
            gap: 3mm;
        }
        .member-card-photo {
            width: 18mm;
            height: 24mm;
            object-fit: cover;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            background: #e2e8f0;
        }
        .member-card-info {
            flex-grow: 1;
            font-size: 6.5pt;
            line-height: 1.35;
        }
        .member-card-info table {
            width: 100%;
        }
        .member-card-info td {
            padding: 0.3mm 0;
            vertical-align: top;
        }
        .member-card-qr {
            position: absolute;
            bottom: 2.5mm;
            right: 3mm;
            text-align: center;
        }
        .member-card-qr-box {
            background: #fff;
            padding: 1.5mm;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            display: inline-block;
        }
        @media print {
            body { background: #fff !important; padding: 0 !important; }
            .control-bar, .no-print { display: none !important; }
            .member-card { box-shadow: none !important; margin: 4mm 2mm !important; page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <!-- Panel Filter & Print Controls -->
    <div class="control-bar no-print d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <input type="hidden" name="page" value="cetak_kartu_massal">
            <span class="small fw-bold text-muted">Filter Cetak:</span>
            <select name="kelas_id" class="form-select form-select-sm" style="width: 160px;">
                <option value="">Semua Kelas</option>
                <?php foreach ($kelasList as $k): ?>
                <option value="<?= $k['id'] ?>" <?= ($selectedKelas == $k['id']) ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <select name="jurusan_id" class="form-select form-select-sm" style="width: 160px;">
                <option value="">Semua Jurusan</option>
                <?php foreach ($jurusanList as $j): ?>
                <option value="<?= $j['id'] ?>" <?= ($selectedJurusan == $j['id']) ? 'selected' : '' ?>><?= htmlspecialchars($j['kode_jurusan'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">Terapkan</button>
            <a href="<?= base_url('index.php?page=cetak_kartu_massal') ?>" class="btn btn-sm btn-outline-secondary">Tampilkan Semua</a>
        </form>

        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-primary px-3">
                <i class="bi bi-printer me-1"></i>Cetak Kartu (<?= count($members) ?> Anggota)
            </button>
            <a href="<?= base_url('index.php?page=anggota') ?>" class="btn btn-sm btn-outline-secondary">
                Kembali
            </a>
        </div>
    </div>

    <!-- Grid Kartu Anggota -->
    <div class="text-center">
        <?php foreach ($members as $m): ?>
        <?php
            $photoUrl = !empty($m['foto']) && file_exists(UPLOAD_ANGGOTA_PATH . '/' . $m['foto'])
                ? base_url('uploads/anggota/' . $m['foto'])
                : asset_url('images/' . ($m['jenis_kelamin'] === 'P' ? 'default_avatar_f.png' : 'default_avatar_m.png'));
            $cardUid = 'qr_card_' . md5($m['qr_token']);
        ?>
        <div class="member-card text-start">
            <div class="member-card-header">
                <img src="<?= asset_url('images/logo_smkn2.png') ?>" alt="Logo">
                <div class="member-card-header-text">
                    <h6><?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></h6>
                    <small>KARTU ANGGOTA PERPUSTAKAAN DIGITAL</small>
                </div>
            </div>

            <div class="member-card-body">
                <img src="<?= $photoUrl ?>" alt="Foto" class="member-card-photo">
                <div class="member-card-info">
                    <table>
                        <tr>
                            <td class="text-muted" style="width: 14mm;">Nama:</td>
                            <td class="fw-bold text-dark text-truncate" style="max-width: 32mm;"><?= htmlspecialchars($m['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">No. Anggota:</td>
                            <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($m['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">NISN / NIP:</td>
                            <td><?= htmlspecialchars($m['identitas_no'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status:</td>
                            <td><?= htmlspecialchars($m['jenis_anggota'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kelas / Dept:</td>
                            <td><?= htmlspecialchars($m['nama_kelas'] ?? $m['kode_jurusan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- QR Code Anggota: LIBRARY-MEMBER:{nomor_anggota} -->
            <div class="member-card-qr">
                <div class="member-card-qr-box">
                    <div id="<?= $cardUid ?>" class="qr-canvas-holder" data-qr-text="<?= htmlspecialchars($m['qr_token'], ENT_QUOTES, 'UTF-8') ?>" data-qr-size="58"></div>
                </div>
                <div style="font-size: 5pt; font-family: monospace; color: #1e3a8a; margin-top: 1px;">
                    <?= htmlspecialchars($m['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.qr-canvas-holder').forEach(el => {
        const text = el.getAttribute('data-qr-text');
        const size = parseInt(el.getAttribute('data-qr-size') || '58', 10);
        if (text && typeof QRCode !== 'undefined') {
            new QRCode(el, {
                text: text,
                width: size,
                height: size,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    });
});
</script>

</body>
</html>
