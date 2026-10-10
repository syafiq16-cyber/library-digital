<?php
/**
 * Form Edit Data Anggota
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/qrcode_helper.php';
require_once __DIR__ . '/../../models/Anggota.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas']);

$id = (int)($_GET['id'] ?? 0);
$anggotaModel = new Anggota();
$member = $anggotaModel->getById($id);

if (!$member) {
    set_flash('danger', 'Data anggota tidak ditemukan.');
    header('Location: ' . base_url('index.php?page=anggota'));
    exit;
}

$pageTitle = 'Edit Anggota ' . $member['nomor_anggota'];
$masterModel = new Master();
$kelasList = $masterModel->getKelas();
$jurusanList = $masterModel->getJurusan();

$photoUrl = !empty($member['foto']) && file_exists(UPLOAD_ANGGOTA_PATH . '/' . $member['foto'])
    ? base_url('uploads/anggota/' . $member['foto'])
    : asset_url('images/' . ($member['jenis_kelamin'] === 'P' ? 'default_avatar_f.png' : 'default_avatar_m.png'));

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-person-gear text-primary me-2"></i>Edit Data Anggota</h1>
                <div class="page-subtitle">Perbarui biodata anggota <?= htmlspecialchars($member['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('index.php?page=cetak_kartu_massal&anggota_id=' . $member['id']) ?>" target="_blank" class="btn btn-outline-info">
                    <i class="bi bi-credit-card me-1"></i>Cetak Kartu
                </a>
                <a href="<?= base_url('index.php?page=anggota') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <form method="POST" action="<?= base_url('controllers/AnggotaController.php?action=update') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $member['id'] ?>">

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card-custom mb-4">
                        <div class="card-custom-header">
                            <h2 class="card-custom-title"><i class="bi bi-person text-primary"></i><span>Biodata Anggota</span></h2>
                        </div>
                        <div class="card-custom-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nomor Anggota:</label>
                                    <input type="text" class="form-control font-monospace bg-light" value="<?= htmlspecialchars($member['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">NISN / NIP / Identitas: <span class="text-danger">*</span></label>
                                    <input type="text" name="identitas_no" class="form-control" value="<?= htmlspecialchars($member['identitas_no'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold">Nama Lengkap: <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($member['nama'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Jenis Kelamin: <span class="text-danger">*</span></label>
                                    <select name="jenis_kelamin" class="form-select" required>
                                        <option value="L" <?= ($member['jenis_kelamin'] === 'L') ? 'selected' : '' ?>>Laki-laki (L)</option>
                                        <option value="P" <?= ($member['jenis_kelamin'] === 'P') ? 'selected' : '' ?>>Perempuan (P)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Kategori Anggota: <span class="text-danger">*</span></label>
                                    <select name="jenis_anggota" class="form-select" id="selectJenisAnggota" required>
                                        <option value="Siswa" <?= ($member['jenis_anggota'] === 'Siswa') ? 'selected' : '' ?>>Siswa</option>
                                        <option value="Guru" <?= ($member['jenis_anggota'] === 'Guru') ? 'selected' : '' ?>>Guru</option>
                                        <option value="Tenaga Kependidikan" <?= ($member['jenis_anggota'] === 'Tenaga Kependidikan') ? 'selected' : '' ?>>Tenaga Kependidikan</option>
                                        <option value="Umum" <?= ($member['jenis_anggota'] === 'Umum') ? 'selected' : '' ?>>Umum</option>
                                    </select>
                                </div>
                                <div class="col-md-4" id="boxKelas">
                                    <label class="form-label small fw-semibold">Tingkat / Kelas:</label>
                                    <select name="kelas_id" class="form-select">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php foreach ($kelasList as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= ($member['kelas_id'] == $k['id']) ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4" id="boxJurusan">
                                    <label class="form-label small fw-semibold">Kompetensi Keahlian (Jurusan):</label>
                                    <select name="jurusan_id" class="form-select">
                                        <option value="">-- Pilih Jurusan --</option>
                                        <?php foreach ($jurusanList as $j): ?>
                                        <option value="<?= $j['id'] ?>" <?= ($member['jurusan_id'] == $j['id']) ? 'selected' : '' ?>><?= htmlspecialchars($j['kode_jurusan'] . ' - ' . $j['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nomor WhatsApp / HP:</label>
                                    <input type="text" name="nomor_hp" class="form-control" value="<?= htmlspecialchars($member['nomor_hp'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Tanggal Registrasi:</label>
                                    <input type="text" class="form-control bg-light" value="<?= format_date($member['tanggal_registrasi']) ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Alamat Domisili:</label>
                                    <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($member['alamat'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-custom mb-4">
                        <div class="card-custom-header">
                            <h2 class="card-custom-title"><i class="bi bi-image text-success"></i><span>Foto & QR Code</span></h2>
                        </div>
                        <div class="card-custom-body text-center">
                            <img src="<?= $photoUrl ?>" alt="Foto Anggota" class="rounded-3 border shadow-sm mb-3" style="width: 100px; height: 130px; object-fit: cover;">

                            <div class="mb-3 text-start">
                                <label class="form-label small fw-semibold">Ganti Pasfoto:</label>
                                <input type="file" name="foto" class="form-control form-control-sm" accept="image/*">
                            </div>

                            <div class="form-check form-switch mb-3 text-start">
                                <input class="form-check-input" type="checkbox" name="status_aktif" id="statusAktif" value="1" <?= $member['status_aktif'] ? 'checked' : '' ?> style="cursor: pointer;">
                                <label class="form-check-label small fw-semibold" for="statusAktif" style="cursor: pointer;">
                                    Status Keanggotaan Aktif
                                </label>
                            </div>

                            <hr>

                            <!-- Render QR Code Anggota -->
                            <div class="small fw-bold text-muted mb-1"><i class="bi bi-qr-code me-1"></i>QR Code Anggota:</div>
                            <?= render_qr_code_html($member['qr_token'], 120) ?>

                            <button type="submit" class="btn btn-primary-custom w-100 justify-content-center py-2 mt-3">
                                <i class="bi bi-save me-1"></i>Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
<script>
    const jenisSelect = document.getElementById('selectJenisAnggota');
    const boxKelas = document.getElementById('boxKelas');
    const boxJurusan = document.getElementById('boxJurusan');

    function toggleStudentFields() {
        if (jenisSelect.value === 'Siswa') {
            boxKelas.style.display = 'block';
            boxJurusan.style.display = 'block';
        } else if (jenisSelect.value === 'Guru') {
            boxKelas.style.display = 'none';
            boxJurusan.style.display = 'block';
        } else {
            boxKelas.style.display = 'none';
            boxJurusan.style.display = 'none';
        }
    }

    jenisSelect.addEventListener('change', toggleStudentFields);
    toggleStudentFields();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
