<?php
/**
 * Form Tambah Anggota Baru
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Anggota.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas']);

$pageTitle = 'Tambah Anggota Baru';
$anggotaModel = new Anggota();
$nomorOtomatis = $anggotaModel->generateNomorAnggota();

$masterModel = new Master();
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
                <h1 class="page-title"><i class="bi bi-person-plus-fill text-primary me-2"></i>Tambah Anggota Baru</h1>
                <div class="page-subtitle">Sistem otomatis membuat Nomor Anggota dan QR Token <code>LIBRARY-MEMBER:{no}</code></div>
            </div>
            <div>
                <a href="<?= base_url('index.php?page=anggota') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>
        </div>

        <form method="POST" action="<?= base_url('controllers/AnggotaController.php?action=store') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card-custom mb-4">
                        <div class="card-custom-header">
                            <h2 class="card-custom-title"><i class="bi bi-person text-primary"></i><span>Biodata Anggota</span></h2>
                        </div>
                        <div class="card-custom-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nomor Anggota (Otomatis):</label>
                                    <input type="text" name="nomor_anggota" class="form-control font-monospace bg-light fw-bold text-primary" value="<?= htmlspecialchars($nomorOtomatis, ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">NISN / NIP / No. Identitas: <span class="text-danger">*</span></label>
                                    <input type="text" name="identitas_no" class="form-control" placeholder="Nomor Induk Siswa Nasional / NIP..." required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold">Nama Lengkap: <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" class="form-control" placeholder="Nama lengkap siswa atau guru..." required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Jenis Kelamin: <span class="text-danger">*</span></label>
                                    <select name="jenis_kelamin" class="form-select" required>
                                        <option value="L">Laki-laki (L)</option>
                                        <option value="P">Perempuan (P)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Kategori Anggota: <span class="text-danger">*</span></label>
                                    <select name="jenis_anggota" class="form-select" id="selectJenisAnggota" required>
                                        <option value="Siswa">Siswa</option>
                                        <option value="Guru">Guru</option>
                                        <option value="Tenaga Kependidikan">Tenaga Kependidikan</option>
                                        <option value="Umum">Umum</option>
                                    </select>
                                </div>
                                <div class="col-md-4" id="boxKelas">
                                    <label class="form-label small fw-semibold">Tingkat / Kelas:</label>
                                    <select name="kelas_id" class="form-select">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php foreach ($kelasList as $k): ?>
                                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4" id="boxJurusan">
                                    <label class="form-label small fw-semibold">Kompetensi Keahlian (Jurusan):</label>
                                    <select name="jurusan_id" class="form-select">
                                        <option value="">-- Pilih Jurusan --</option>
                                        <?php foreach ($jurusanList as $j): ?>
                                        <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['kode_jurusan'] . ' - ' . $j['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nomor WhatsApp / HP:</label>
                                    <input type="text" name="nomor_hp" class="form-control" placeholder="Contoh: 081234567890">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Tanggal Registrasi:</label>
                                    <input type="date" name="tanggal_registrasi" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Alamat Lengkap Domisili:</label>
                                    <textarea name="alamat" class="form-control" rows="2" placeholder="Nama jalan, kelurahan, kecamatan..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-custom mb-4">
                        <div class="card-custom-header">
                            <h2 class="card-custom-title"><i class="bi bi-image text-success"></i><span>Foto & Status Akun</span></h2>
                        </div>
                        <div class="card-custom-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Upload Pasfoto Anggota (3x4):</label>
                                <input type="file" name="foto" class="form-control" accept="image/*">
                                <small class="text-muted" style="font-size: 11px;">Format JPG, PNG, atau WebP. Maksimal 2MB.</small>
                            </div>

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="status_aktif" id="statusAktif" value="1" checked style="cursor: pointer;">
                                <label class="form-check-label small fw-semibold" for="statusAktif" style="cursor: pointer;">
                                    Status Keanggotaan Aktif
                                </label>
                            </div>

                            <div class="p-3 bg-light rounded border mb-3 text-center">
                                <div class="small fw-bold text-muted mb-1">Format Token QR Otomatis:</div>
                                <code class="small text-primary">LIBRARY-MEMBER:<?= $nomorOtomatis ?></code>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100 justify-content-center py-2">
                                <i class="bi bi-person-check-fill me-1"></i>Simpan Data Anggota
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
