<?php
/**
 * Master Data Kompetensi Keahlian (Jurusan)
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
        header('Location: ' . base_url('index.php?page=master_jurusan'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'store') {
        $kode = trim($_POST['kode_jurusan'] ?? '');
        $nama = trim($_POST['nama_jurusan'] ?? '');
        if (!empty($kode) && !empty($nama)) {
            $masterModel->createJurusan($kode, $nama);
            log_activity($user['id'], 'Master Jurusan', 'Tambah', "Menambah jurusan: $nama ($kode)");
            set_flash('success', "Jurusan '$nama' berhasil ditambahkan.");
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $kode = trim($_POST['kode_jurusan'] ?? '');
        $nama = trim($_POST['nama_jurusan'] ?? '');
        if ($id > 0 && !empty($kode) && !empty($nama)) {
            $masterModel->updateJurusan($id, $kode, $nama);
            log_activity($user['id'], 'Master Jurusan', 'Edit', "Memperbarui jurusan: $nama (ID: $id)");
            set_flash('success', "Jurusan '$nama' berhasil diperbarui.");
        }
    }
    header('Location: ' . base_url('index.php?page=master_jurusan'));
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    if ($masterModel->deleteJurusan($id)) {
        log_activity($user['id'], 'Master Jurusan', 'Hapus', "Menghapus jurusan ID: $id");
        set_flash('success', 'Jurusan berhasil dihapus.');
    } else {
        set_flash('danger', 'Jurusan tidak dapat dihapus karena masih digunakan oleh data siswa / kelas.');
    }
    header('Location: ' . base_url('index.php?page=master_jurusan'));
    exit;
}

$pageTitle = 'Master Kompetensi Keahlian (Jurusan)';
$jurusanList = $masterModel->getJurusan();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-gear-wide-connected text-primary me-2"></i>Kompetensi Keahlian (Jurusan)</h1>
                <div class="page-subtitle">Program keahlian kejuruan SMKN 2 Sumbawa Besar untuk filter anggota dan laporan</div>
            </div>
            <div>
                <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahJurusan">
                    <i class="bi bi-plus-circle"></i> Tambah Jurusan
                </button>
            </div>
        </div>

        <div class="card-custom">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No.</th>
                            <th>Kode Jurusan</th>
                            <th>Nama Kompetensi Keahlian</th>
                            <th>Waktu Pendaftaran</th>
                            <th class="text-end" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jurusanList)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada kompetensi keahlian.</td></tr>
                        <?php else: ?>
                            <?php foreach ($jurusanList as $idx => $j): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="badge bg-light text-primary border font-monospace fs-6"><?= htmlspecialchars($j['kode_jurusan'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="fw-semibold text-main"><?= htmlspecialchars($j['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><small class="text-muted"><?= date('d/m/Y', strtotime($j['created_at'])) ?></small></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editJurusan(<?= htmlspecialchars(json_encode($j), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= base_url('index.php?page=master_jurusan&delete_id=' . $j['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus jurusan <?= htmlspecialchars($j['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?>?');">
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

<!-- Modal Tambah Jurusan -->
<div class="modal fade" id="modalTambahJurusan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_jurusan') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="store">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-gear text-primary me-2"></i>Tambah Kompetensi Keahlian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Singkatan Jurusan:</label>
                        <input type="text" name="kode_jurusan" class="form-control font-monospace text-uppercase" placeholder="Contoh: TSM, TOI, RPL..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap Keahlian:</label>
                        <input type="text" name="nama_jurusan" class="form-control" placeholder="Contoh: Teknik Sepeda Motor" required>
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

<!-- Modal Edit Jurusan -->
<div class="modal fade" id="modalEditJurusan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_jurusan') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editJurusanId">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil text-primary me-2"></i>Edit Jurusan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Jurusan:</label>
                        <input type="text" name="kode_jurusan" id="editJurusanKode" class="form-control font-monospace text-uppercase" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Jurusan:</label>
                        <input type="text" name="nama_jurusan" id="editJurusanNama" class="form-control" required>
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
function editJurusan(j) {
    document.getElementById('editJurusanId').value = j.id;
    document.getElementById('editJurusanKode').value = j.kode_jurusan;
    document.getElementById('editJurusanNama').value = j.nama_jurusan;
    new bootstrap.Modal(document.getElementById('modalEditJurusan')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
