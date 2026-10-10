<?php
/**
 * Detail Transaksi Peminjaman
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Peminjaman.php';
require_once __DIR__ . '/../../models/Pengembalian.php';

require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);

$id = (int)($_GET['id'] ?? 0);
$peminjamanModel = new Peminjaman();
$loan = $peminjamanModel->getById($id);

if (!$loan) {
    set_flash('danger', 'Transaksi peminjaman tidak ditemukan.');
    header('Location: ' . base_url('index.php?page=peminjaman'));
    exit;
}

$pageTitle = 'Detail Peminjaman ' . $loan['nomor_transaksi'];
$details = $peminjamanModel->getDetails($id);

$pengembalianModel = new Pengembalian();
$extensions = $pengembalianModel->getExtensionHistory($id);

$isOverdue = ($loan['status'] === 'Terlambat' || ($loan['status'] === 'Dipinjam' && strtotime($loan['tanggal_jatuh_tempo']) < time()));

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-file-earmark-text text-primary me-2"></i><?= htmlspecialchars($loan['nomor_transaksi'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="page-subtitle">Detail transaksi peminjaman buku dan status pengembalian eksemplar</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('index.php?page=peminjaman_cetak&id=' . $loan['id']) ?>" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-printer me-1"></i>Cetak Struk Bukti
                </a>
                <?php if ($loan['status'] !== 'Selesai' && has_role(['Administrator', 'Petugas'])): ?>
                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalPerpanjang">
                    <i class="bi bi-arrow-clockwise me-1"></i>Perpanjang Masa Pinjam
                </button>
                <a href="<?= base_url('index.php?page=pengembalian_proses&pinjam_id=' . $loan['id']) ?>" class="btn btn-success">
                    <i class="bi bi-arrow-down-left-square me-1"></i>Proses Pengembalian
                </a>
                <?php endif; ?>
                <a href="<?= base_url('index.php?page=peminjaman') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Informasi Peminjaman -->
            <div class="col-lg-6">
                <div class="card-custom h-100 mb-0">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title">
                            <i class="bi bi-info-circle text-primary"></i>
                            <span>Informasi Sirkulasi</span>
                        </h2>
                        <?php if ($loan['status'] === 'Dipinjam'): ?>
                            <span class="badge-status badge-status-warning"><i class="bi bi-hourglass-split"></i> Dipinjam</span>
                        <?php elseif ($loan['status'] === 'Selesai'): ?>
                            <span class="badge-status badge-status-success"><i class="bi bi-check2-circle"></i> Selesai (Kembali)</span>
                        <?php else: ?>
                            <span class="badge-status badge-status-danger"><i class="bi bi-exclamation-octagon"></i> Terlambat</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-custom-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="text-muted" style="width: 160px;">Nomor Transaksi:</th><td class="fw-bold font-monospace"><?= htmlspecialchars($loan['nomor_transaksi'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Tanggal Pinjam:</th><td><?= format_date($loan['tanggal_pinjam']) ?></td></tr>
                            <tr>
                                <th class="text-muted">Tanggal Jatuh Tempo:</th>
                                <td class="<?= $isOverdue ? 'text-danger fw-bold' : 'fw-bold text-primary' ?>">
                                    <?= format_date($loan['tanggal_jatuh_tempo']) ?>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger ms-2">Terlambat</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr><th class="text-muted">Total Buku:</th><td><?= $loan['total_buku'] ?> Buku</td></tr>
                            <tr><th class="text-muted">Petugas Layanan:</th><td><?= htmlspecialchars($loan['nama_petugas'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Catatan:</th><td><?= htmlspecialchars($loan['catatan'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <tr><th class="text-muted">Waktu Transaksi:</th><td class="text-muted small"><?= date('d/m/Y H:i:s', strtotime($loan['created_at'])) ?> WITA</td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Informasi Peminjam (Anggota) -->
            <div class="col-lg-6">
                <div class="card-custom h-100 mb-0">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title">
                            <i class="bi bi-person-badge text-success"></i>
                            <span>Data Anggota Peminjam</span>
                        </h2>
                        <span class="badge bg-light text-muted border"><?= htmlspecialchars($loan['jenis_anggota'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="card-custom-body">
                        <div class="d-flex gap-3 align-items-start">
                            <img src="<?= base_url('uploads/anggota/' . ($loan['foto'] ?: 'default_avatar_m.png')) ?>" alt="Foto" class="rounded border shadow-sm" style="width: 75px; height: 100px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1 text-primary"><?= htmlspecialchars($loan['nama_anggota'], ENT_QUOTES, 'UTF-8') ?></h5>
                                <div class="badge bg-secondary mb-2"><?= htmlspecialchars($loan['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?></div>
                                <table class="table table-sm table-borderless small mb-0">
                                    <tr><th class="text-muted" style="width: 110px;">NISN / NIP:</th><td><?= htmlspecialchars($loan['identitas_no'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                                    <tr><th class="text-muted">Kelas / Jurusan:</th><td><?= htmlspecialchars(($loan['nama_kelas'] ?? '-') . ' (' . ($loan['kode_jurusan'] ?? '-') . ')', ENT_QUOTES, 'UTF-8') ?></td></tr>
                                    <tr><th class="text-muted">No. HP:</th><td><?= htmlspecialchars($loan['nomor_hp'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                                    <tr><th class="text-muted">Alamat:</th><td><?= htmlspecialchars($loan['alamat'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Buku Yang Dipinjam -->
        <div class="card-custom mb-4">
            <div class="card-custom-header">
                <h2 class="card-custom-title">
                    <i class="bi bi-journal-bookmark-fill text-primary"></i>
                    <span>Daftar Buku & Eksemplar Fisik</span>
                </h2>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode Buku</th>
                            <th>Kode Eksemplar</th>
                            <th>Judul Buku</th>
                            <th>Kategori</th>
                            <th>Lokasi Rak</th>
                            <th>Status Buku</th>
                            <th>Tgl Kembali Aktual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $idx => $item): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><span class="fw-bold font-monospace"><?= htmlspecialchars($item['kode_buku'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td>
                                <?php if ($item['kode_eksemplar']): ?>
                                    <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($item['kode_eksemplar'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?></div>
                                <small class="text-muted">ISBN: <?= htmlspecialchars($item['isbn'] ?: '-', ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($item['nama_kategori'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><small class="text-muted"><?= htmlspecialchars(($item['nama_rak'] ?? '') . ' (' . ($item['kode_rak'] ?? '') . ')', ENT_QUOTES, 'UTF-8') ?></small></td>
                            <td>
                                <?php if ($item['status'] === 'Dipinjam'): ?>
                                    <span class="badge-status badge-status-warning"><i class="bi bi-clock"></i> Dipinjam</span>
                                <?php else: ?>
                                    <span class="badge-status badge-status-success"><i class="bi bi-check-all"></i> Dikembalikan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $item['tanggal_kembali_aktual'] ? date('d/m/Y', strtotime($item['tanggal_kembali_aktual'])) : '<span class="text-muted">-</span>' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Riwayat Perpanjangan Jika Ada -->
        <?php if (!empty($extensions)): ?>
        <div class="card-custom">
            <div class="card-custom-header">
                <h2 class="card-custom-title">
                    <i class="bi bi-clock-history text-info"></i>
                    <span>Riwayat Perpanjangan Peminjaman</span>
                </h2>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Ke-</th>
                            <th>Tanggal Perpanjangan</th>
                            <th>Jatuh Tempo Lama</th>
                            <th>Jatuh Tempo Baru</th>
                            <th>Petugas</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($extensions as $ext): ?>
                        <tr>
                            <td><strong>Ke-<?= $ext['perpanjangan_ke'] ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($ext['tanggal_perpanjangan'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($ext['jatuh_tempo_lama'])) ?></td>
                            <td class="text-primary fw-bold"><?= date('d/m/Y', strtotime($ext['jatuh_tempo_baru'])) ?></td>
                            <td><?= htmlspecialchars($ext['nama_petugas'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($ext['catatan'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

<!-- Modal Perpanjang Masa Pinjam -->
<div class="modal fade" id="modalPerpanjang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('controllers/TransaksiController.php?action=extend_loan') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="peminjaman_id" value="<?= $loan['id'] ?>">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-arrow-clockwise text-warning me-2"></i>Perpanjang Masa Pinjam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small border-0 mb-3">
                        Perpanjangan akan menambah tanggal jatuh tempo dari tanggal sebelumnya (<strong><?= date('d/m/Y', strtotime($loan['tanggal_jatuh_tempo'])) ?></strong>).
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tambah Durasi (Hari):</label>
                        <select name="tambah_hari" class="form-select">
                            <option value="3">3 Hari</option>
                            <option value="7" selected>7 Hari (Standar)</option>
                            <option value="14">14 Hari (2 Minggu)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan Perpanjangan:</label>
                        <textarea name="catatan" class="form-control" rows="2" placeholder="Alasan perpanjangan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check2"></i> Simpan Perpanjangan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
