<?php
/**
 * Master Data Lokasi Rak Buku
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas']);

$masterModel = new Master();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=master_rak'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'store') {
        $kode = trim($_POST['kode_rak'] ?? '');
        $nama = trim($_POST['nama_rak'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');
        $kapasitas = (int)($_POST['kapasitas'] ?? 100);

        if (!empty($kode) && !empty($nama)) {
            $masterModel->createRak($kode, $nama, $lokasi, $kapasitas);
            log_activity($user['id'], 'Master Rak', 'Tambah', "Menambah lokasi rak: $nama ($kode)");
            set_flash('success', "Rak '$nama' berhasil ditambahkan.");
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $kode = trim($_POST['kode_rak'] ?? '');
        $nama = trim($_POST['nama_rak'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');
        $kapasitas = (int)($_POST['kapasitas'] ?? 100);

        if ($id > 0 && !empty($kode) && !empty($nama)) {
            $masterModel->updateRak($id, $kode, $nama, $lokasi, $kapasitas);
            log_activity($user['id'], 'Master Rak', 'Edit', "Memperbarui rak: $nama (ID: $id)");
            set_flash('success', "Rak '$nama' berhasil diperbarui.");
        }
    }
    header('Location: ' . base_url('index.php?page=master_rak'));
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    if ($masterModel->deleteRak($id)) {
        log_activity($user['id'], 'Master Rak', 'Hapus', "Menghapus rak ID: $id");
        set_flash('success', 'Lokasi rak berhasil dihapus.');
    } else {
        set_flash('danger', 'Rak tidak dapat dihapus karena masih ada buku yang ditempatkan di rak ini.');
    }
    header('Location: ' . base_url('index.php?page=master_rak'));
    exit;
}

$pageTitle = 'Master Lokasi Rak Buku';
$shelves = $masterModel->getRak();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-inboxes-fill text-primary me-2"></i>Master Lokasi Rak Buku</h1>
                <div class="page-subtitle">Kelola kode rak, tata letak gedung, dan kapasitas penyimpanan buku</div>
            </div>
            <div>
                <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahRak">
                    <i class="bi bi-plus-circle"></i> Tambah Rak
                </button>
            </div>
        </div>

        <div class="card-custom">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No.</th>
                            <th>Kode Rak</th>
                            <th>Nama Rak</th>
                            <th>Lokasi Penempatan</th>
                            <th>Kapasitas</th>
                            <th>Jumlah Buku</th>
                            <th class="text-end" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shelves)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada lokasi rak terdaftar.</td></tr>
                        <?php else: ?>
                            <?php foreach ($shelves as $idx => $r): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="badge bg-light text-primary border font-monospace"><?= htmlspecialchars($r['kode_rak'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="fw-semibold text-main"><?= htmlspecialchars($r['nama_rak'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><i class="bi bi-geo-alt text-muted me-1"></i><?= htmlspecialchars($r['lokasi'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format($r['kapasitas']) ?> Eksemplar</td>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= $r['total_buku'] ?> Judul</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editRak(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= base_url('index.php?page=master_rak&delete_id=' . $r['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data rak <?= htmlspecialchars($r['nama_rak'], ENT_QUOTES, 'UTF-8') ?>?');">
                                        <i class="bi bi-trash"></i>
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

<!-- Modal Tambah Rak -->
<div class="modal fade" id="modalTambahRak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_rak') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="store">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-inbox text-primary me-2"></i>Tambah Lokasi Rak</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Rak:</label>
                        <input type="text" name="kode_rak" class="form-control font-monospace" placeholder="Contoh: RAK-A1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Rak:</label>
                        <input type="text" name="nama_rak" class="form-control" placeholder="Contoh: Rak Otomotif & Sepeda Motor" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Lokasi Gedung / Ruang:</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Lantai 1 Sayap Kiri" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kapasitas Maksimal (Buku):</label>
                        <input type="number" name="kapasitas" class="form-control" value="150" min="10">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Rak -->
<div class="modal fade" id="modalEditRak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_rak') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editRakId">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil text-primary me-2"></i>Edit Lokasi Rak</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Rak:</label>
                        <input type="text" name="kode_rak" id="editRakKode" class="form-control font-monospace" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Rak:</label>
                        <input type="text" name="nama_rak" id="editRakNama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Lokasi Penempatan:</label>
                        <input type="text" name="lokasi" id="editRakLokasi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kapasitas:</label>
                        <input type="number" name="kapasitas" id="editRakKapasitas" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    </div>
<script>
function editRak(r) {
    document.getElementById('editRakId').value = r.id;
    document.getElementById('editRakKode').value = r.kode_rak;
    document.getElementById('editRakNama').value = r.nama_rak;
    document.getElementById('editRakLokasi').value = r.lokasi;
    document.getElementById('editRakKapasitas').value = r.kapasitas;
    new bootstrap.Modal(document.getElementById('modalEditRak')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
