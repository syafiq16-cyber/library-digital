<?php
/**
 * Detail Pengembalian Buku & Rincian Denda
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Pengembalian.php';

require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);

$id = (int)($_GET['id'] ?? 0);
$pengembalianModel = new Pengembalian();
$ret = $pengembalianModel->getById($id);

if (!$ret) {
    set_flash('danger', 'Data pengembalian tidak ditemukan.');
    header('Location: ' . base_url('index.php?page=pengembalian'));
    exit;
}

$pageTitle = 'Detail Pengembalian ' . $ret['nomor_pengembalian'];
$details = $pengembalianModel->getDetails($id);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-file-earmark-check text-success me-2"></i><?= htmlspecialchars($ret['nomor_pengembalian'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="page-subtitle">Detail tanda terima pengembalian buku dan status penyelesaian denda</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('index.php?page=pengembalian') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Riwayat
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Informasi Pengembalian -->
            <div class="col-lg-6">
                <div class="card-custom h-100 mb-0">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title text-success">
                            <i class="bi bi-info-circle"></i>
                            <span>Ringkasan Pengembalian</span>
                        </h2>
                        <?php if ($ret['status_denda'] === 'Lunas'): ?>
                            <span class="badge-status badge-status-success"><i class="bi bi-check2"></i> Denda Lunas</span>
                        <?php elseif ($ret['status_denda'] === 'Belum Lunas'): ?>
                            <span class="badge-status badge-status-danger"><i class="bi bi-clock-history"></i> Denda Belum Lunas</span>
                        <?php else: ?>
                            <span class="badge-status badge-status-secondary">Tidak Ada Denda</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-custom-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="text-muted" style="width: 170px;">No. Pengembalian:</th><td class="fw-bold font-monospace text-success"><?= htmlspecialchars($ret['nomor_pengembalian'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">No. Peminjaman:</th><td><a href="<?= base_url('index.php?page=peminjaman_detail&id=' . $ret['peminjaman_id']) ?>" class="fw-bold text-decoration-none font-monospace"><?= htmlspecialchars($ret['nomor_transaksi'], ENT_QUOTES, 'UTF-8') ?></a></td></tr>
                            <tr><th class="text-muted">Tanggal Pinjam:</th><td><?= format_date($ret['tanggal_pinjam']) ?></td></tr>
                            <tr><th class="text-muted">Tanggal Jatuh Tempo:</th><td><?= format_date($ret['tanggal_jatuh_tempo']) ?></td></tr>
                            <tr><th class="text-muted">Tanggal Kembali Aktual:</th><td class="fw-bold"><?= format_date($ret['tanggal_kembali']) ?></td></tr>
                            <tr>
                                <th class="text-muted">Keterlambatan:</th>
                                <td>
                                    <?php if ($ret['terlambat_hari'] > 0): ?>
                                        <span class="badge bg-danger text-white"><?= $ret['terlambat_hari'] ?> Hari</span>
                                    <?php else: ?>
                                        <span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Tepat Waktu (0 Hari)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr><th class="text-muted">Petugas Penerima:</th><td><?= htmlspecialchars($ret['nama_petugas'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Catatan:</th><td><?= htmlspecialchars($ret['catatan'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Rincian Keuangan & Peminjam -->
            <div class="col-lg-6">
                <div class="card-custom h-100 mb-0">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title text-primary">
                            <i class="bi bi-cash-coin"></i>
                            <span>Rincian Pembayaran & Anggota</span>
                        </h2>
                    </div>
                    <div class="card-custom-body">
                        <div class="p-3 bg-surface border rounded-3 mb-3">
                            <div class="small text-muted">Total Denda Ditagihkan:</div>
                            <div class="fs-4 fw-bold text-danger"><?= format_rupiah($ret['total_denda']) ?></div>
                            <div class="d-flex justify-content-between small mt-2 border-top pt-2">
                                <span>Jumlah Diterima:</span>
                                <span class="fw-bold text-success"><?= format_rupiah($ret['denda_dibayar']) ?></span>
                            </div>
                        </div>

                        <h6 class="fw-bold text-muted small mb-2">IDENTITAS PEMINJAM:</h6>
                        <table class="table table-sm table-borderless small mb-0">
                            <tr><th class="text-muted" style="width: 120px;">Nama Anggota:</th><td class="fw-bold"><?= htmlspecialchars($ret['nama_anggota'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">No. Anggota / NIS:</th><td><?= htmlspecialchars($ret['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($ret['identitas_no'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Kelas / Jurusan:</th><td><?= htmlspecialchars(($ret['nama_kelas'] ?? '-') . ' (' . ($ret['kode_jurusan'] ?? '-') . ')', ENT_QUOTES, 'UTF-8') ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Detail Buku Dikembalikan -->
        <div class="card-custom">
            <div class="card-custom-header">
                <h2 class="card-custom-title">
                    <i class="bi bi-book-half text-success"></i>
                    <span>Daftar Buku Yang Dikembalikan & Kondisi Fisik</span>
                </h2>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode Buku / Eksemplar</th>
                            <th>Judul Buku</th>
                            <th>Kondisi Fisik</th>
                            <th>Denda Terlambat</th>
                            <th>Denda Kondisi</th>
                            <th>Subtotal Denda</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $idx => $d): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <div class="fw-bold font-monospace"><?= htmlspecialchars($d['kode_buku'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($d['kode_eksemplar']): ?>
                                    <small class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($d['kode_eksemplar'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </td>
                            <td><div class="fw-semibold"><?= htmlspecialchars($d['judul'], ENT_QUOTES, 'UTF-8') ?></div></td>
                            <td>
                                <?php if ($d['kondisi_buku'] === 'Baik'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Baik (Mulus)</span>
                                <?php elseif ($d['kondisi_buku'] === 'Rusak Ringan'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Rusak Ringan</span>
                                <?php elseif ($d['kondisi_buku'] === 'Rusak Berat'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Rusak Berat</span>
                                <?php else: ?>
                                    <span class="badge bg-dark text-white">Hilang</span>
                                <?php endif; ?>
                            </td>
                            <td><?= format_rupiah($d['denda_terlambat']) ?></td>
                            <td><?= format_rupiah($d['denda_kondisi']) ?></td>
                            <td class="fw-bold text-danger"><?= format_rupiah($d['subtotal_denda']) ?></td>
                            <td><small class="text-muted"><?= htmlspecialchars($d['catatan'] ?: '-', ENT_QUOTES, 'UTF-8') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
