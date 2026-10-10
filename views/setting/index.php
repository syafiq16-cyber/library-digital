<?php
/**
 * Pengaturan Sistem Perpustakaan
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

$pageTitle = 'Pengaturan Sistem';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

require_role('Administrator');

// Proses form melalui controller
require_once __DIR__ . '/../../controllers/SettingController.php';

// Baca semua setting saat ini
$settings = [
    'nama_sekolah'              => get_setting('nama_sekolah', 'SMK Negeri 2 Sumbawa Besar'),
    'nama_perpustakaan'         => get_setting('nama_perpustakaan', 'PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR'),
    'npsn'                      => get_setting('npsn', ''),
    'alamat'                    => get_setting('alamat', 'Jl. Lingkar Luar No. 02, Sumbawa Besar, NTB'),
    'email'                     => get_setting('email', ''),
    'nomor_telepon'             => get_setting('nomor_telepon', ''),
    'kepala_sekolah'            => get_setting('kepala_sekolah', ''),
    'nip_kepala_sekolah'        => get_setting('nip_kepala_sekolah', ''),
    'kepala_perpustakaan'       => get_setting('kepala_perpustakaan', ''),
    'nip_kepala_perpustakaan'   => get_setting('nip_kepala_perpustakaan', ''),
    'lama_pinjam_hari'          => get_setting('lama_pinjam_hari', '7'),
    'maksimal_buku_pinjam'      => get_setting('maksimal_buku_pinjam', '3'),
    'denda_per_hari'            => get_setting('denda_per_hari', '1000'),
    'denda_rusak_ringan'        => get_setting('denda_rusak_ringan', '15000'),
    'denda_rusak_berat'         => get_setting('denda_rusak_berat', '50000'),
    'denda_hilang'              => get_setting('denda_hilang', '100000'),
    'hari_operasional'          => get_setting('hari_operasional', 'Senin - Jumat, 07:30 - 15:30 WITA'),
    'logo'                      => get_setting('logo', 'logo_smkn2.png'),
];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">

    <!-- Flash Messages -->
    <?php if ($flash = get_flash()): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?> me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="page-title"><i class="bi bi-gear-fill text-primary me-2"></i>Pengaturan Sistem</h1>
            <p class="text-muted mb-0 small">Konfigurasi identitas sekolah, aturan peminjaman, dan tarif denda perpustakaan</p>
        </div>
    </div>

    <form method="POST" action="<?= base_url('index.php?page=setting') ?>" enctype="multipart/form-data" id="settingForm">
        <?= csrf_field() ?>

        <div class="row g-4">

            <!-- IDENTITAS SEKOLAH -->
            <div class="col-lg-8">
                <div class="card card-custom mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <strong><i class="bi bi-building-fill me-2"></i>Identitas Sekolah & Perpustakaan</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-medium">Nama Sekolah <span class="text-danger">*</span></label>
                                <input type="text" name="nama_sekolah" class="form-control" value="<?= htmlspecialchars($settings['nama_sekolah']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">NPSN</label>
                                <input type="text" name="npsn" class="form-control" value="<?= htmlspecialchars($settings['npsn']) ?>" placeholder="e.g. 50215789">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Nama Perpustakaan <span class="text-danger">*</span></label>
                                <input type="text" name="nama_perpustakaan" class="form-control" value="<?= htmlspecialchars($settings['nama_perpustakaan']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Alamat Sekolah</label>
                                <textarea name="alamat" class="form-control" rows="2" placeholder="Jl. Lingkar Luar No. 02, Sumbawa Besar, NTB"><?= htmlspecialchars($settings['alamat']) ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Email Sekolah</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($settings['email']) ?>" placeholder="info@smkn2sumbawa.sch.id">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Nomor Telepon</label>
                                <input type="text" name="nomor_telepon" class="form-control" value="<?= htmlspecialchars($settings['nomor_telepon']) ?>" placeholder="(0371) 21XXX">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Kepala Sekolah</label>
                                <input type="text" name="kepala_sekolah" class="form-control" value="<?= htmlspecialchars($settings['kepala_sekolah']) ?>" placeholder="Nama kepala sekolah">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">NIP Kepala Sekolah</label>
                                <input type="text" name="nip_kepala_sekolah" class="form-control" value="<?= htmlspecialchars($settings['nip_kepala_sekolah']) ?>" placeholder="19XX0101 XXXXXX 1 001">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Kepala Perpustakaan</label>
                                <input type="text" name="kepala_perpustakaan" class="form-control" value="<?= htmlspecialchars($settings['kepala_perpustakaan']) ?>" placeholder="Nama kepala perpustakaan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">NIP Kepala Perpustakaan</label>
                                <input type="text" name="nip_kepala_perpustakaan" class="form-control" value="<?= htmlspecialchars($settings['nip_kepala_perpustakaan']) ?>" placeholder="19XX0101 XXXXXX 2 001">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Hari &amp; Jam Operasional</label>
                                <input type="text" name="hari_operasional" class="form-control" value="<?= htmlspecialchars($settings['hari_operasional']) ?>" placeholder="Senin - Jumat, 07:30 - 15:30 WITA">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ATURAN PEMINJAMAN -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-success text-white py-3">
                        <strong><i class="bi bi-sliders me-2"></i>Aturan Peminjaman Buku</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Lama Pinjam (hari) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="lama_pinjam_hari" class="form-control" 
                                           value="<?= (int)$settings['lama_pinjam_hari'] ?>" min="1" max="60" required>
                                    <span class="input-group-text">hari</span>
                                </div>
                                <div class="form-text">Jangka waktu standar peminjaman buku.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Maks. Buku per Peminjaman <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="maksimal_buku_pinjam" class="form-control"
                                           value="<?= (int)$settings['maksimal_buku_pinjam'] ?>" min="1" max="20" required>
                                    <span class="input-group-text">judul</span>
                                </div>
                                <div class="form-text">Jumlah maksimal buku dalam satu transaksi pinjam.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TARIF DENDA -->
                <div class="card card-custom">
                    <div class="card-header bg-danger text-white py-3">
                        <strong><i class="bi bi-cash-stack me-2"></i>Konfigurasi Tarif Denda</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Denda Keterlambatan (per hari)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="denda_per_hari" class="form-control"
                                           value="<?= (int)$settings['denda_per_hari'] ?>" min="0" step="100">
                                </div>
                                <div class="form-text">Denda yang dikenakan per hari keterlambatan.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Denda Kerusakan Ringan</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="denda_rusak_ringan" class="form-control"
                                           value="<?= (int)$settings['denda_rusak_ringan'] ?>" min="0" step="1000">
                                </div>
                                <div class="form-text">Halaman robek, cover rusak ringan.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Denda Kerusakan Berat</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="denda_rusak_berat" class="form-control"
                                           value="<?= (int)$settings['denda_rusak_berat'] ?>" min="0" step="1000">
                                </div>
                                <div class="form-text">Buku sobek parah, basah, tidak bisa dibaca.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Denda Buku Hilang</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="denda_hilang" class="form-control"
                                           value="<?= (int)$settings['denda_hilang'] ?>" min="0" step="1000">
                                </div>
                                <div class="form-text">Biaya penggantian buku yang hilang (minimum).</div>
                            </div>
                        </div>

                        <div class="alert alert-warning small mt-3 mb-0 py-2">
                            <i class="bi bi-lightbulb-fill me-1"></i>
                            <strong>Catatan:</strong> Denda buku hilang yang dibayar diambil dari nilai tertinggi antara tarif ini dan harga buku di database.
                        </div>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: Logo & Pratinjau -->
            <div class="col-lg-4">
                <div class="card card-custom mb-4">
                    <div class="card-header py-3">
                        <strong><i class="bi bi-image-fill me-2"></i>Logo Sekolah</strong>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img id="logoPreview" src="<?= asset_url('images/' . $settings['logo']) ?>"
                                 alt="Logo SMKN 2" class="rounded border p-2"
                                 style="width:120px;height:120px;object-fit:contain;background:#f8fafc;">
                        </div>
                        <div class="mb-1 text-muted small">Logo saat ini: <code><?= htmlspecialchars($settings['logo']) ?></code></div>
                        <label class="form-label fw-medium d-block">Ganti Logo (PNG/JPG/WebP)</label>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp"
                               class="form-control form-control-sm" id="logoInput">
                        <div class="form-text">Ukuran disarankan: 200×200 px. Maks. 2 MB.</div>
                    </div>
                </div>

                <!-- Pratinjau Kop Surat -->
                <div class="card card-custom mb-4">
                    <div class="card-header py-3">
                        <strong><i class="bi bi-eye-fill me-2"></i>Pratinjau Kop Surat</strong>
                    </div>
                    <div class="card-body p-3">
                        <div style="border:2px solid #1a237e;border-radius:6px;padding:10px;background:#fff;">
                            <div class="d-flex align-items-center gap-2">
                                <img id="kopLogo" src="<?= asset_url('images/' . $settings['logo']) ?>" 
                                     style="width:45px;height:45px;object-fit:contain;">
                                <div>
                                    <div class="fw-bold" style="font-size:8pt;color:#1a237e;text-transform:uppercase;" id="kopNamaSekolah">
                                        <?= htmlspecialchars($settings['nama_sekolah']) ?>
                                    </div>
                                    <div class="fw-semibold" style="font-size:7pt;color:#1a237e;text-transform:uppercase;" id="kopNamaPerpus">
                                        <?= htmlspecialchars($settings['nama_perpustakaan']) ?>
                                    </div>
                                    <div style="font-size:6pt;color:#555;" id="kopAlamat">
                                        <?= htmlspecialchars($settings['alamat']) ?>
                                    </div>
                                </div>
                            </div>
                            <hr style="border-color:#1a237e;border-width:2px;margin:5px 0 0;">
                        </div>
                        <p class="text-muted small mt-2 mb-0">Kop ini akan tampil di semua laporan cetak resmi.</p>
                    </div>
                </div>

                <!-- Ringkasan Setting -->
                <div class="card card-custom border-info">
                    <div class="card-header py-3 bg-info text-white">
                        <strong><i class="bi bi-info-circle-fill me-2"></i>Ringkasan Konfigurasi Aktif</strong>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span class="text-muted">Lama Pinjam</span>
                                <strong><?= (int)$settings['lama_pinjam_hari'] ?> hari</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span class="text-muted">Maks. Buku/Pinjam</span>
                                <strong><?= (int)$settings['maksimal_buku_pinjam'] ?> judul</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span class="text-muted">Denda/Hari</span>
                                <strong class="text-danger"><?= format_rupiah((float)$settings['denda_per_hari']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span class="text-muted">Rusak Ringan</span>
                                <strong class="text-warning"><?= format_rupiah((float)$settings['denda_rusak_ringan']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span class="text-muted">Rusak Berat</span>
                                <strong class="text-danger"><?= format_rupiah((float)$settings['denda_rusak_berat']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span class="text-muted">Buku Hilang</span>
                                <strong class="text-danger"><?= format_rupiah((float)$settings['denda_hilang']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span class="text-muted">Jam Buka</span>
                                <strong style="font-size:0.7rem"><?= htmlspecialchars($settings['hari_operasional']) ?></strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
            <a href="<?= base_url('index.php?page=dashboard') ?>" class="btn btn-outline-secondary px-4">
                <i class="bi bi-x-circle me-1"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary px-5">
                <i class="bi bi-save-fill me-1"></i> Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
<script>
// Preview logo saat file dipilih
document.getElementById('logoInput').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(ev) {
        document.getElementById('logoPreview').src = ev.target.result;
        document.getElementById('kopLogo').src = ev.target.result;
    };
    reader.readAsDataURL(file);
});

// Live update kop surat preview
document.querySelector('[name="nama_sekolah"]').addEventListener('input', function() {
    document.getElementById('kopNamaSekolah').textContent = this.value;
});
document.querySelector('[name="nama_perpustakaan"]').addEventListener('input', function() {
    document.getElementById('kopNamaPerpus').textContent = this.value;
});
document.querySelector('[name="alamat"]').addEventListener('input', function() {
    document.getElementById('kopAlamat').textContent = this.value;
});
</script>


<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
