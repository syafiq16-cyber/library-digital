<?php
/**
 * Master Data Penerbit
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
        header('Location: ' . base_url('index.php?page=master_penerbit'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'store') {
        $nama = trim($_POST['nama_penerbit'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $kontak = trim($_POST['kontak'] ?? '');
        if (!empty($nama)) {
            $masterModel->createPenerbit($nama, $alamat, $kontak);
            log_activity($user['id'], 'Master Penerbit', 'Tambah', "Menambah penerbit: $nama");
            set_flash('success', "Penerbit '$nama' berhasil ditambahkan.");
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $nama = trim($_POST['nama_penerbit'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $kontak = trim($_POST['kontak'] ?? '');
        if ($id > 0 && !empty($nama)) {
            $masterModel->updatePenerbit($id, $nama, $alamat, $kontak);
            log_activity($user['id'], 'Master Penerbit', 'Edit', "Memperbarui penerbit: $nama (ID: $id)");
            set_flash('success', "Penerbit '$nama' berhasil diperbarui.");
        }
    }
    header('Location: ' . base_url('index.php?page=master_penerbit'));
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    if ($masterModel->deletePenerbit($id)) {
        log_activity($user['id'], 'Master Penerbit', 'Hapus', "Menghapus penerbit ID: $id");
        set_flash('success', 'Penerbit berhasil dihapus.');
    } else {
        set_flash('danger', 'Penerbit tidak dapat dihapus karena masih digunakan oleh data buku.');
    }
    header('Location: ' . base_url('index.php?page=master_penerbit'));
    exit;
}

$pageTitle = 'Master Data Penerbit';
$publishers = $masterModel->getPenerbit();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-building text-primary me-2"></i>Master Data Penerbit</h1>
                <div class="page-subtitle">Kelola informasi instansi penerbit buku dan kontak distribusi</div>
            </div>
            <div>
                <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahPenerbit">
                    <i class="bi bi-plus-circle"></i> Tambah Penerbit
                </button>
            </div>
        </div>

        <div class="card-custom">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No.</th>
                            <th>Nama Penerbit</th>
                            <th>Alamat Kantor</th>
                            <th>Kontak / Telp</th>
                            <th>Jumlah Buku</th>
                            <th class="text-end" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($publishers)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data penerbit.</td></tr>
                        <?php else: ?>
                            <?php foreach ($publishers as $idx => $pub): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td class="fw-semibold text-main"><?= htmlspecialchars($pub['nama_penerbit'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($pub['alamat'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-light text-muted border"><?= htmlspecialchars($pub['kontak'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= $pub['total_buku'] ?> Judul</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editPenerbit(<?= htmlspecialchars(json_encode($pub), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= base_url('index.php?page=master_penerbit&delete_id=' . $pub['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data penerbit <?= htmlspecialchars($pub['nama_penerbit'], ENT_QUOTES, 'UTF-8') ?>?');">
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

<!-- Modal Tambah Penerbit -->
<div class="modal fade" id="modalTambahPenerbit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_penerbit') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="store">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-building text-primary me-2"></i>Tambah Penerbit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Penerbit:</label>
                        <input type="text" name="nama_penerbit" class="form-control" placeholder="Contoh: Penerbit Andi Yogyakarta" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat Kantor / Kota:</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap penerbit..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor Kontak / Email:</label>
                        <input type="text" name="kontak" class="form-control" placeholder="Nomor telepon atau email...">
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

<!-- Modal Edit Penerbit -->
<div class="modal fade" id="modalEditPenerbit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_penerbit') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editPenerbitId">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil text-primary me-2"></i>Edit Penerbit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Penerbit:</label>
                        <input type="text" name="nama_penerbit" id="editPenerbitNama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat:</label>
                        <textarea name="alamat" id="editPenerbitAlamat" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kontak:</label>
                        <input type="text" name="kontak" id="editPenerbitKontak" class="form-control">
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
function editPenerbit(p) {
    document.getElementById('editPenerbitId').value = p.id;
    document.getElementById('editPenerbitNama').value = p.nama_penerbit;
    document.getElementById('editPenerbitAlamat').value = p.alamat || '';
    document.getElementById('editPenerbitKontak').value = p.kontak || '';
    new bootstrap.Modal(document.getElementById('modalEditPenerbit')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
