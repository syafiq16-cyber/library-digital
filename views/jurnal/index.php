<?php
/**
 * Jurnal Kegiatan Harian Petugas Perpustakaan
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Jurnal.php';

require_role(['Administrator', 'Petugas', 'Kepala Perpustakaan']);

$pageTitle = 'Jurnal Kegiatan Perpustakaan';
$jurnalModel = new Jurnal();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=jurnal'));
        exit;
    }

    $kegiatan = trim($_POST['kegiatan'] ?? '');
    if (!empty($kegiatan)) {
        $data = [
            'tanggal'             => $_POST['tanggal'] ?? date('Y-m-d'),
            'petugas_id'          => $user['id'],
            'kegiatan'            => $kegiatan,
            'jumlah_pengunjung'   => (int)($_POST['jumlah_pengunjung'] ?? 0),
            'jumlah_peminjaman'   => (int)($_POST['jumlah_peminjaman'] ?? 0),
            'jumlah_pengembalian' => (int)($_POST['jumlah_pengembalian'] ?? 0),
            'catatan'             => trim($_POST['catatan'] ?? '')
        ];

        $jurnalModel->create($data);
        log_activity($user['id'], 'Jurnal', 'Tambah', "Mencatat jurnal kegiatan: $kegiatan");
        set_flash('success', 'Catatan jurnal harian perpustakaan berhasil disimpan.');
    }
    header('Location: ' . base_url('index.php?page=jurnal'));
    exit;
}

$journals = $jurnalModel->getAll(50, 0);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-journal-text text-primary me-2"></i>Jurnal Kegiatan Perpustakaan</h1>
                <div class="page-subtitle">Pencatatan rekam jejak aktivitas operasional, inventarisasi, dan layanan perpustakaan</div>
            </div>
            <div>
                <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahJurnal">
                    <i class="bi bi-plus-circle"></i> Tulis Jurnal Harian
                </button>
            </div>
        </div>

        <div class="card-custom">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No.</th>
                            <th>Tanggal</th>
                            <th>Petugas Pencatat</th>
                            <th>Uraian Kegiatan / Layanan</th>
                            <th class="text-center">Pengunjung</th>
                            <th class="text-center">Pinjam</th>
                            <th class="text-center">Kembali</th>
                            <th>Catatan Khusus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($journals)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada catatan jurnal kegiatan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($journals as $idx => $j): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="badge bg-light text-primary border font-monospace"><?= date('d/m/Y', strtotime($j['tanggal'])) ?></span></td>
                                <td class="fw-semibold text-main"><?= htmlspecialchars($j['nama_petugas'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="fw-semibold text-primary"><?= htmlspecialchars($j['kegiatan'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= $j['jumlah_pengunjung'] ?> Org</span></td>
                                <td class="text-center"><span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= $j['jumlah_peminjaman'] ?> Bku</span></td>
                                <td class="text-center"><span class="badge bg-success-subtle text-success border border-success-subtle"><?= $j['jumlah_pengembalian'] ?> Bku</span></td>
                                <td><small class="text-muted"><?= htmlspecialchars($j['catatan'] ?: '-', ENT_QUOTES, 'UTF-8') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

<!-- Modal Tulis Jurnal -->
<div class="modal fade" id="modalTambahJurnal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=jurnal') ?>">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-journal-plus text-primary me-2"></i>Catat Jurnal Harian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tanggal Kegiatan:</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Uraian Kegiatan / Tugas: <span class="text-danger">*</span></label>
                        <textarea name="kegiatan" class="form-control" rows="2" placeholder="Contoh: Pelayanan sirkulasi rutin, bimbingan literasi kelas X, penataan rak buku..." required></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Pengunjung:</label>
                            <input type="number" name="jumlah_pengunjung" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Peminjaman:</label>
                            <input type="number" name="jumlah_peminjaman" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Pengembalian:</label>
                            <input type="number" name="jumlah_pengembalian" class="form-control" value="0" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan / Hambatan:</label>
                        <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan evaluasi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Jurnal</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
