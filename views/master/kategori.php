<?php
/**
 * Master Data Kategori Buku
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas']);

$masterModel = new Master();
$user = current_user();

// Penanganan Aksi Simpan / Edit / Hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token CSRF tidak valid.');
        header('Location: ' . base_url('index.php?page=master_kategori'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'store') {
        $kode = trim($_POST['kode_kategori'] ?? '');
        $nama = trim($_POST['nama_kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if (!empty($kode) && !empty($nama)) {
            $masterModel->createKategori($kode, $nama, $deskripsi);
            log_activity($user['id'], 'Master Kategori', 'Tambah', "Menambah kategori: $nama ($kode)");
            set_flash('success', "Kategori '$nama' berhasil ditambahkan.");
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $kode = trim($_POST['kode_kategori'] ?? '');
        $nama = trim($_POST['nama_kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if ($id > 0 && !empty($kode) && !empty($nama)) {
            $masterModel->updateKategori($id, $kode, $nama, $deskripsi);
            log_activity($user['id'], 'Master Kategori', 'Edit', "Memperbarui kategori: $nama (ID: $id)");
            set_flash('success', "Kategori '$nama' berhasil diperbarui.");
        }
    }
    header('Location: ' . base_url('index.php?page=master_kategori'));
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    if ($masterModel->deleteKategori($id)) {
        log_activity($user['id'], 'Master Kategori', 'Hapus', "Menghapus kategori ID: $id");
        set_flash('success', 'Kategori buku berhasil dihapus.');
    } else {
        set_flash('danger', 'Kategori tidak dapat dihapus karena masih digunakan oleh data buku.');
    }
    header('Location: ' . base_url('index.php?page=master_kategori'));
    exit;
}

$pageTitle = 'Master Kategori Buku';
$categories = $masterModel->getKategori();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-tags-fill text-primary me-2"></i>Master Kategori Buku</h1>
                <div class="page-subtitle">Kelola klasifikasi koleksi buku pelajaran, kejuruan, dan fiksi</div>
            </div>
            <div>
                <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                    <i class="bi bi-plus-circle"></i> Tambah Kategori
                </button>
            </div>
        </div>

        <div class="card-custom">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No.</th>
                            <th>Kode Kategori</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi / Keterangan</th>
                            <th>Jumlah Koleksi</th>
                            <th class="text-end" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada kategori buku.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $idx => $c): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="badge bg-light text-primary border font-monospace"><?= htmlspecialchars($c['kode_kategori'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="fw-semibold text-main"><?= htmlspecialchars($c['nama_kategori'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($c['deskripsi'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= $c['total_buku'] ?> Judul</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editKategori(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= base_url('index.php?page=master_kategori&delete_id=' . $c['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kategori <?= htmlspecialchars($c['nama_kategori'], ENT_QUOTES, 'UTF-8') ?>?');">
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

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_kategori') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="store">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-tag text-primary me-2"></i>Tambah Kategori Buku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Kategori:</label>
                        <input type="text" name="kode_kategori" class="form-control font-monospace" placeholder="Contoh: KAT-VOK" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Kategori:</label>
                        <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Kejuruan Otomotif" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Deskripsi / Cakupan Materi:</label>
                        <textarea name="deskripsi" class="form-control" rows="2" placeholder="Keterangan kategori..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_kategori') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editKategoriId">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil text-primary me-2"></i>Edit Kategori Buku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Kategori:</label>
                        <input type="text" name="kode_kategori" id="editKategoriKode" class="form-control font-monospace" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Kategori:</label>
                        <input type="text" name="nama_kategori" id="editKategoriNama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Deskripsi:</label>
                        <textarea name="deskripsi" id="editKategoriDeskripsi" class="form-control" rows="2"></textarea>
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
function editKategori(c) {
    document.getElementById('editKategoriId').value = c.id;
    document.getElementById('editKategoriKode').value = c.kode_kategori;
    document.getElementById('editKategoriNama').value = c.nama_kategori;
    document.getElementById('editKategoriDeskripsi').value = c.deskripsi || '';
    new bootstrap.Modal(document.getElementById('modalEditKategori')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
