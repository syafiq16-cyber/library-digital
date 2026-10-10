<?php
/**
 * Master Data Pengarang
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
        header('Location: ' . base_url('index.php?page=master_pengarang'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'store') {
        $nama = trim($_POST['nama_pengarang'] ?? '');
        $bio = trim($_POST['biografi'] ?? '');
        if (!empty($nama)) {
            $masterModel->createPengarang($nama, $bio);
            log_activity($user['id'], 'Master Pengarang', 'Tambah', "Menambah pengarang: $nama");
            set_flash('success', "Pengarang '$nama' berhasil ditambahkan.");
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $nama = trim($_POST['nama_pengarang'] ?? '');
        $bio = trim($_POST['biografi'] ?? '');
        if ($id > 0 && !empty($nama)) {
            $masterModel->updatePengarang($id, $nama, $bio);
            log_activity($user['id'], 'Master Pengarang', 'Edit', "Memperbarui pengarang: $nama (ID: $id)");
            set_flash('success', "Pengarang '$nama' berhasil diperbarui.");
        }
    }
    header('Location: ' . base_url('index.php?page=master_pengarang'));
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    if ($masterModel->deletePengarang($id)) {
        log_activity($user['id'], 'Master Pengarang', 'Hapus', "Menghapus pengarang ID: $id");
        set_flash('success', 'Pengarang berhasil dihapus.');
    } else {
        set_flash('danger', 'Pengarang tidak dapat dihapus karena masih digunakan oleh data buku.');
    }
    header('Location: ' . base_url('index.php?page=master_pengarang'));
    exit;
}

$pageTitle = 'Master Data Pengarang';
$authors = $masterModel->getPengarang();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-person-lines-fill text-primary me-2"></i>Master Data Pengarang</h1>
                <div class="page-subtitle">Kelola daftar penulis, akademisi, dan kontributor buku</div>
            </div>
            <div>
                <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahPengarang">
                    <i class="bi bi-plus-circle"></i> Tambah Pengarang
                </button>
            </div>
        </div>

        <div class="card-custom">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No.</th>
                            <th>Nama Pengarang</th>
                            <th>Biografi / Catatan</th>
                            <th>Jumlah Buku</th>
                            <th class="text-end" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($authors)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data pengarang.</td></tr>
                        <?php else: ?>
                            <?php foreach ($authors as $idx => $p): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td class="fw-semibold text-main"><?= htmlspecialchars($p['nama_pengarang'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($p['biografi'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= $p['total_buku'] ?> Judul</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editPengarang(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= base_url('index.php?page=master_pengarang&delete_id=' . $p['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data pengarang <?= htmlspecialchars($p['nama_pengarang'], ENT_QUOTES, 'UTF-8') ?>?');">
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

<!-- Modal Tambah Pengarang -->
<div class="modal fade" id="modalTambahPengarang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_pengarang') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="store">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>Tambah Pengarang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap & Gelar Pengarang:</label>
                        <input type="text" name="nama_pengarang" class="form-control" placeholder="Contoh: Ir. Budi Raharjo, M.T." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Profil Singkat / Biografi:</label>
                        <textarea name="biografi" class="form-control" rows="3" placeholder="Latar belakang kepenulisan..."></textarea>
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

<!-- Modal Edit Pengarang -->
<div class="modal fade" id="modalEditPengarang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_pengarang') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editPengarangId">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil text-primary me-2"></i>Edit Pengarang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Pengarang:</label>
                        <input type="text" name="nama_pengarang" id="editPengarangNama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Profil / Biografi:</label>
                        <textarea name="biografi" id="editPengarangBio" class="form-control" rows="3"></textarea>
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
function editPengarang(p) {
    document.getElementById('editPengarangId').value = p.id;
    document.getElementById('editPengarangNama').value = p.nama_pengarang;
    document.getElementById('editPengarangBio').value = p.biografi || '';
    new bootstrap.Modal(document.getElementById('modalEditPengarang')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
