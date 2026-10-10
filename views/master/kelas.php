<?php
/**
 * Master Data Tingkat & Kelas Rombel
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
        header('Location: ' . base_url('index.php?page=master_kelas'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'store') {
        $tingkat = $_POST['tingkat'] ?? 'X';
        $jurusanId = (int)$_POST['jurusan_id'];
        $rombel = trim($_POST['rombel'] ?? '1');
        $namaKelas = trim($_POST['nama_kelas'] ?? '');

        if ($jurusanId > 0 && !empty($namaKelas)) {
            $masterModel->createKelas($tingkat, $jurusanId, $rombel, $namaKelas);
            log_activity($user['id'], 'Master Kelas', 'Tambah', "Menambah rombel kelas: $namaKelas");
            set_flash('success', "Kelas '$namaKelas' berhasil ditambahkan.");
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $tingkat = $_POST['tingkat'] ?? 'X';
        $jurusanId = (int)$_POST['jurusan_id'];
        $rombel = trim($_POST['rombel'] ?? '1');
        $namaKelas = trim($_POST['nama_kelas'] ?? '');

        if ($id > 0 && $jurusanId > 0 && !empty($namaKelas)) {
            $masterModel->updateKelas($id, $tingkat, $jurusanId, $rombel, $namaKelas);
            log_activity($user['id'], 'Master Kelas', 'Edit', "Memperbarui kelas: $namaKelas (ID: $id)");
            set_flash('success', "Kelas '$namaKelas' berhasil diperbarui.");
        }
    }
    header('Location: ' . base_url('index.php?page=master_kelas'));
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    if ($masterModel->deleteKelas($id)) {
        log_activity($user['id'], 'Master Kelas', 'Hapus', "Menghapus kelas ID: $id");
        set_flash('success', 'Kelas berhasil dihapus.');
    } else {
        set_flash('danger', 'Kelas tidak dapat dihapus karena masih digunakan oleh data anggota.');
    }
    header('Location: ' . base_url('index.php?page=master_kelas'));
    exit;
}

$pageTitle = 'Master Tingkat & Rombel Kelas';
$kelasList = $masterModel->getKelas();
$jurusanList = $masterModel->getJurusan();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-mortarboard-fill text-primary me-2"></i>Tingkat & Rombel Kelas</h1>
                <div class="page-subtitle">Master data rombongan belajar (X TSM 1, XI TOI 1, XII RPL 1, dll)</div>
            </div>
            <div>
                <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambahKelas">
                    <i class="bi bi-plus-circle"></i> Tambah Kelas
                </button>
            </div>
        </div>

        <div class="card-custom">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No.</th>
                            <th>Tingkat</th>
                            <th>Nama Rombel / Kelas</th>
                            <th>Kompetensi Keahlian</th>
                            <th>Rombel</th>
                            <th class="text-end" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kelasList)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data rombel kelas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($kelasList as $idx => $k): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $k['tingkat'] ?></span></td>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($k['nama_jurusan'] . ' (' . $k['kode_jurusan'] . ')', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>Rombel <?= htmlspecialchars($k['rombel'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editKelas(<?= htmlspecialchars(json_encode($k), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= base_url('index.php?page=master_kelas&delete_id=' . $k['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kelas <?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>?');">
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

<!-- Modal Tambah Kelas -->
<div class="modal fade" id="modalTambahKelas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_kelas') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="store">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-mortarboard text-primary me-2"></i>Tambah Rombel Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tingkat Kelas:</label>
                        <select name="tingkat" class="form-select" id="addTingkat" required>
                            <option value="X">Kelas X (Sepuluh)</option>
                            <option value="XI">Kelas XI (Sebelas)</option>
                            <option value="XII">Kelas XII (Dua Belas)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kompetensi Keahlian (Jurusan):</label>
                        <select name="jurusan_id" class="form-select" id="addJurusan" required>
                            <?php foreach ($jurusanList as $j): ?>
                            <option value="<?= $j['id'] ?>" data-kode="<?= $j['kode_jurusan'] ?>"><?= htmlspecialchars($j['kode_jurusan'] . ' - ' . $j['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor Rombel:</label>
                        <input type="text" name="rombel" id="addRombel" class="form-control" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Tampilan Kelas:</label>
                        <input type="text" name="nama_kelas" id="addNamaKelas" class="form-control fw-bold text-primary" placeholder="Contoh: X TSM 1" required>
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

<!-- Modal Edit Kelas -->
<div class="modal fade" id="modalEditKelas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('index.php?page=master_kelas') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editKelasId">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil text-primary me-2"></i>Edit Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tingkat:</label>
                        <select name="tingkat" id="editKelasTingkat" class="form-select" required>
                            <option value="X">Kelas X</option>
                            <option value="XI">Kelas XI</option>
                            <option value="XII">Kelas XII</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jurusan:</label>
                        <select name="jurusan_id" id="editKelasJurusan" class="form-select" required>
                            <?php foreach ($jurusanList as $j): ?>
                            <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['kode_jurusan'] . ' - ' . $j['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Rombel:</label>
                        <input type="text" name="rombel" id="editKelasRombel" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Kelas:</label>
                        <input type="text" name="nama_kelas" id="editKelasNama" class="form-control fw-bold text-primary" required>
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
function autoFillNamaKelas() {
    const tingkat = document.getElementById('addTingkat').value;
    const jurusanSel = document.getElementById('addJurusan');
    const kode = jurusanSel.options[jurusanSel.selectedIndex].getAttribute('data-kode') || '';
    const rombel = document.getElementById('addRombel').value.trim();
    document.getElementById('addNamaKelas').value = `${tingkat} ${kode} ${rombel}`;
}

document.getElementById('addTingkat').addEventListener('change', autoFillNamaKelas);
document.getElementById('addJurusan').addEventListener('change', autoFillNamaKelas);
document.getElementById('addRombel').addEventListener('input', autoFillNamaKelas);

function editKelas(k) {
    document.getElementById('editKelasId').value = k.id;
    document.getElementById('editKelasTingkat').value = k.tingkat;
    document.getElementById('editKelasJurusan').value = k.jurusan_id;
    document.getElementById('editKelasRombel').value = k.rombel;
    document.getElementById('editKelasNama').value = k.nama_kelas;
    new bootstrap.Modal(document.getElementById('modalEditKelas')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
