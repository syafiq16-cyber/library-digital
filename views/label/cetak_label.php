<?php
/**
 * Modul Cetak Label Punggung & Barcode/QR Buku
 * Format QR Code: LIBRARY-BOOK:{kode_buku} atau LIBRARY-BOOK:{kode_eksemplar}
 * Standar Dimensi: Stiker Punggung Buku (Call Number + QR Code)
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/qrcode_helper.php';
require_once __DIR__ . '/../../models/Buku.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas']);

$bukuModel = new Buku();
$masterModel = new Master();

$bukuId = (int)($_GET['buku_id'] ?? 0);
$kategoriId = (int)($_GET['kategori_id'] ?? 0);
$rakId = (int)($_GET['rak_id'] ?? 0);
$mode = $_GET['mode'] ?? 'eksemplar'; // 'eksemplar' atau 'judul'
$copies = max(1, (int)($_GET['copies'] ?? 1));

$kategoriList = $masterModel->getKategori();
$rakList = $masterModel->getRak();

// Build items to print
$labelItems = [];

if ($bukuId > 0) {
    $book = $bukuModel->getById($bukuId);
    if ($book) {
        if ($mode === 'eksemplar') {
            $eksemplars = $bukuModel->getEksemplarByBukuId($bukuId);
            foreach ($eksemplars as $ex) {
                $labelItems[] = [
                    'buku_id'        => $book['id'],
                    'judul'          => $book['judul'],
                    'pengarang'      => $book['nama_pengarang'] ?? 'Anonim',
                    'kategori'       => $book['nama_kategori'] ?? '-',
                    'subkategori'    => $book['subkategori'] ?? '',
                    'kode_rak'       => $book['kode_rak'] ?? 'RAK-01',
                    'tahun'          => $book['tahun_terbit'],
                    'kode_cetak'     => $ex['kode_eksemplar'],
                    'qr_token'       => $ex['qr_token'] ?: make_book_qr_token($ex['kode_eksemplar']),
                    'tipe'           => 'Eksemplar'
                ];
            }
        } else {
            for ($i = 0; $i < $copies; $i++) {
                $labelItems[] = [
                    'buku_id'        => $book['id'],
                    'judul'          => $book['judul'],
                    'pengarang'      => $book['nama_pengarang'] ?? 'Anonim',
                    'kategori'       => $book['nama_kategori'] ?? '-',
                    'subkategori'    => $book['subkategori'] ?? '',
                    'kode_rak'       => $book['kode_rak'] ?? 'RAK-01',
                    'tahun'          => $book['tahun_terbit'],
                    'kode_cetak'     => $book['kode_buku'],
                    'qr_token'       => $book['qr_token'] ?: make_book_qr_token($book['kode_buku']),
                    'tipe'           => 'Master Buku'
                ];
            }
        }
    }
} else {
    // Filtered or bulk
    $filters = [];
    if ($kategoriId > 0) $filters['kategori_id'] = $kategoriId;
    if ($rakId > 0) $filters['rak_id'] = $rakId;

    $books = $bukuModel->getAll($filters, 50, 0);
    foreach ($books as $bk) {
        if ($mode === 'eksemplar') {
            $eksemplars = $bukuModel->getEksemplarByBukuId($bk['id']);
            foreach ($eksemplars as $ex) {
                $labelItems[] = [
                    'buku_id'        => $bk['id'],
                    'judul'          => $bk['judul'],
                    'pengarang'      => $bk['nama_pengarang'] ?? 'Anonim',
                    'kategori'       => $bk['nama_kategori'] ?? '-',
                    'subkategori'    => $bk['subkategori'] ?? '',
                    'kode_rak'       => $bk['kode_rak'] ?? 'RAK-01',
                    'tahun'          => $bk['tahun_terbit'],
                    'kode_cetak'     => $ex['kode_eksemplar'],
                    'qr_token'       => $ex['qr_token'] ?: make_book_qr_token($ex['kode_eksemplar']),
                    'tipe'           => 'Eksemplar'
                ];
            }
        } else {
            $labelItems[] = [
                'buku_id'        => $bk['id'],
                'judul'          => $bk['judul'],
                'pengarang'      => $bk['nama_pengarang'] ?? 'Anonim',
                'kategori'       => $bk['nama_kategori'] ?? '-',
                'subkategori'    => $bk['subkategori'] ?? '',
                'kode_rak'       => $bk['kode_rak'] ?? 'RAK-01',
                'tahun'          => $bk['tahun_terbit'],
                'kode_cetak'     => $bk['kode_buku'],
                'qr_token'       => $bk['qr_token'] ?: make_book_qr_token($bk['kode_buku']),
                'tipe'           => 'Master Buku'
            ];
        }
    }
}

// Function to build standard library call number
function formatCallNumber($item) {
    // 3 digit DDC / Subkategori code
    $ddc = !empty($item['subkategori']) ? $item['subkategori'] : substr(preg_replace('/[^0-9]/', '', $item['kode_cetak']), 0, 3);
    if (empty($ddc)) $ddc = '000';
    
    // 3 first letters of author surname
    $words = explode(' ', trim($item['pengarang']));
    $lastName = end($words);
    $authorCode = strtoupper(substr($lastName, 0, 3));

    // First letter of title (lowercase)
    $titleCode = strtolower(substr(trim($item['judul']), 0, 1));

    return [
        'ddc'         => $ddc,
        'author_code' => $authorCode,
        'title_code'  => $titleCode,
        'rak'         => $item['kode_rak']
    ];
}

$schoolName = get_setting('nama_sekolah', 'SMKN 2 SUMBAWA BESAR');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Buku - <?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= asset_url('vendor/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/print.css') ?>">
    <script src="<?= asset_url('vendor/qrcode.min.js') ?>"></script>
    <style>
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            padding: 20px;
        }
        .control-bar {
            background: #ffffff;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .label-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 5mm;
            justify-content: flex-start;
        }
        /* Standar Ukuran Stiker Punggung Buku: 55mm x 35mm */
        .book-label {
            width: 55mm;
            height: 38mm;
            border: 1.5px solid #0f172a;
            border-radius: 6px;
            padding: 2mm 3mm;
            background: #ffffff;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            page-break-inside: avoid;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .label-header {
            text-align: center;
            border-bottom: 1px solid #0f172a;
            padding-bottom: 1mm;
            margin-bottom: 1mm;
        }
        .label-header h6 {
            font-size: 6.5pt;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            color: #0f172a;
            line-height: 1.1;
        }
        .label-header small {
            font-size: 5pt;
            color: #475569;
            display: block;
        }
        .label-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-grow: 1;
            gap: 2mm;
        }
        .call-number-box {
            border: 1px solid #334155;
            padding: 1.5mm;
            border-radius: 4px;
            text-align: center;
            background: #f8fafc;
            min-width: 20mm;
            line-height: 1.15;
        }
        .call-number-box .line-ddc {
            font-size: 7.5pt;
            font-weight: 800;
            color: #0f172a;
        }
        .call-number-box .line-author {
            font-size: 7.5pt;
            font-weight: 700;
            color: #1e293b;
        }
        .call-number-box .line-title {
            font-size: 7pt;
            font-weight: 700;
            color: #334155;
        }
        .call-number-box .line-rak {
            font-size: 5.5pt;
            color: #64748b;
            border-top: 0.5px dashed #cbd5e1;
            margin-top: 1mm;
            padding-top: 0.5mm;
        }
        .qr-box {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .qr-canvas-container {
            width: 22mm;
            height: 22mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-code-text {
            font-family: monospace;
            font-size: 5.5pt;
            font-weight: 700;
            color: #0f172a;
            margin-top: 0.5mm;
        }
        .label-footer {
            border-top: 0.5px solid #cbd5e1;
            padding-top: 0.5mm;
            font-size: 5.5pt;
            color: #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
            font-weight: 600;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
                margin: 0;
            }
            .control-bar {
                display: none !important;
            }
            .label-grid {
                gap: 3mm;
                margin: 0;
                padding: 5mm;
            }
            .book-label {
                box-shadow: none;
                border: 1px solid #000;
            }
        }
    </style>
</head>
<body>

    <!-- Control Bar (Disembunyikan saat dicetak) -->
    <div class="control-bar d-print-none">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('index.php?page=buku') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Buku
                </a>
                <h5 class="mb-0 fw-bold text-dark ms-2">
                    <i class="bi bi-tags-fill text-primary me-2"></i>Cetak Label Punggung Buku & QR Code
                </h5>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button onclick="window.print()" class="btn btn-primary btn-sm px-3 shadow-sm">
                    <i class="bi bi-printer-fill me-1"></i> Cetak Label Sekarang
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="<?= base_url('index.php') ?>" class="row g-2 mt-2 pt-2 border-top">
            <input type="hidden" name="page" value="cetak_label_massal">
            <?php if ($bukuId > 0): ?>
                <input type="hidden" name="buku_id" value="<?= $bukuId ?>">
            <?php endif; ?>

            <?php if ($bukuId === 0): ?>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Kategori Buku</label>
                    <select name="kategori_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategoriList as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $kategoriId == $k['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kategori'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Lokasi Rak</label>
                    <select name="rak_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Rak</option>
                        <?php foreach ($rakList as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $rakId == $r['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['kode_rak'] . ' - ' . $r['nama_rak'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Mode Cetak Label</label>
                <select name="mode" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="eksemplar" <?= $mode === 'eksemplar' ? 'selected' : '' ?>>Per Eksemplar Fisik (QR Unik)</option>
                    <option value="judul" <?= $mode === 'judul' ? 'selected' : '' ?>>Per Judul / Master Buku</option>
                </select>
            </div>

            <?php if ($mode === 'judul'): ?>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Jumlah Salinan</label>
                    <input type="number" name="copies" class="form-control form-select-sm" min="1" max="50" value="<?= $copies ?>" onchange="this.form.submit()">
                </div>
            <?php endif; ?>
        </form>

        <div class="mt-2 text-muted small">
            Total Label Siap Cetak: <span class="badge bg-primary text-white"><?= count($labelItems) ?> label</span>
            <?php if ($bukuId > 0 && isset($book)): ?>
                | Buku Terpilih: <strong><?= htmlspecialchars($book['judul'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($book['kode_buku'], ENT_QUOTES, 'UTF-8') ?>)
            <?php endif; ?>
        </div>
    </div>

    <!-- Container Grid Label Stiker -->
    <?php if (empty($labelItems)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="mt-2 text-muted">Tidak ada data buku atau eksemplar yang ditemukan untuk dicetak.</p>
        </div>
    <?php else: ?>
        <div class="label-grid">
            <?php foreach ($labelItems as $idx => $item): 
                $call = formatCallNumber($item);
                $qrId = "label-qr-" . $idx;
            ?>
                <div class="book-label">
                    <div class="label-header">
                        <h6>PERPUSTAKAAN SMKN 2 SUMBAWA</h6>
                        <small>NO. PANGGIL &amp; QR IDENTITAS</small>
                    </div>

                    <div class="label-body">
                        <!-- Nomor Panggil (Call Number) -->
                        <div class="call-number-box">
                            <div class="line-ddc"><?= htmlspecialchars($call['ddc'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="line-author"><?= htmlspecialchars($call['author_code'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="line-title"><?= htmlspecialchars($call['title_code'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="line-rak"><?= htmlspecialchars($call['rak'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <!-- QR Code Buku -->
                        <div class="qr-box">
                            <div class="qr-canvas-container" id="<?= $qrId ?>"></div>
                            <div class="qr-code-text"><?= htmlspecialchars($item['kode_cetak'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>

                    <div class="label-footer" title="<?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Render QR Code Client-Side Offline -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php foreach ($labelItems as $idx => $item): 
                $qrId = "label-qr-" . $idx;
                $token = $item['qr_token'];
            ?>
            (function() {
                var container = document.getElementById("<?= $qrId ?>");
                if (container && typeof QRCode !== 'undefined') {
                    new QRCode(container, {
                        text: "<?= addslashes($token) ?>",
                        width: 72,
                        height: 72,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.M
                    });
                }
            })();
            <?php endforeach; ?>
        });
    </script>
</body>
</html>
