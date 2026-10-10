<?php
/**
 * Form Tambah Buku Baru
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Buku.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas']);

$pageTitle = 'Tambah Buku Baru';
$bukuModel = new Buku();
$masterModel = new Master();

$kodeOtomatis = $bukuModel->generateKodeBuku();
$kategoriList = $masterModel->getKategori();
$pengarangList = $masterModel->getPengarang();
$penerbitList = $masterModel->getPenerbit();
$rakList = $masterModel->getRak();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-journal-plus text-primary me-2"></i>Tambah Buku Baru</h1>
                <div class="page-subtitle">Sistem otomatis membuat kode buku dan token QR unik untuk setiap eksemplar</div>
            </div>
            <div>
                <a href="<?= base_url('index.php?page=buku') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>
        </div>

        <form method="POST" action="<?= base_url('controllers/BukuController.php?action=store') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="row g-4">
                <!-- Kolom Utama: Identitas Buku -->
                <div class="col-lg-8">
                    <div class="card-custom mb-4">
                        <div class="card-custom-header">
                            <h2 class="card-custom-title"><i class="bi bi-book text-primary"></i><span>Informasi Bibliografi Buku</span></h2>
                        </div>
                        <div class="card-custom-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Kode Buku (Otomatis):</label>
                                    <input type="text" name="kode_buku" class="form-control font-monospace bg-light fw-bold" value="<?= htmlspecialchars($kodeOtomatis, ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">ISBN (Nomor Standar Internasional):</label>
                                    <input type="text" name="isbn" class="form-control" placeholder="Contoh: 978-602-00-1234-5">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Judul Lengkap Buku: <span class="text-danger">*</span></label>
                                    <input type="text" name="judul" class="form-control" placeholder="Masukkan judul buku..." required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Pengarang / Penulis: <span class="text-danger">*</span></label>
                                    <select name="pengarang_id" class="form-select" required>
                                        <option value="">-- Pilih Pengarang --</option>
                                        <?php foreach ($pengarangList as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_pengarang'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Penerbit: <span class="text-danger">*</span></label>
                                    <select name="penerbit_id" class="form-select" required>
                                        <option value="">-- Pilih Penerbit --</option>
                                        <?php foreach ($penerbitList as $pub): ?>
                                        <option value="<?= $pub['id'] ?>"><?= htmlspecialchars($pub['nama_penerbit'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Kategori Buku: <span class="text-danger">*</span></label>
                                    <select name="kategori_id" class="form-select" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php foreach ($kategoriList as $k): ?>
                                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Subkategori / Topik:</label>
                                    <input type="text" name="subkategori" class="form-control" placeholder="Contoh: Mesin Otomotif, Algoritma, dll...">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Deskripsi / Sinopsis Buku:</label>
                                    <textarea name="deskripsi" class="form-control" rows="3" placeholder="Ringkasan isi buku atau catatan spesifik..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Samping: Lokasi, Fisik & Stok -->
                <div class="col-lg-4">
                    <div class="card-custom mb-4">
                        <div class="card-custom-header">
                            <h2 class="card-custom-title"><i class="bi bi-inbox text-success"></i><span>Stok & Penempatan Rak</span></h2>
                        </div>
                        <div class="card-custom-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Lokasi Rak Buku: <span class="text-danger">*</span></label>
                                <select name="rak_id" class="form-select" required>
                                    <option value="">-- Pilih Rak --</option>
                                    <?php foreach ($rakList as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['kode_rak'] . ' - ' . $r['nama_rak'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Jumlah Eksemplar Fisik: <span class="text-danger">*</span></label>
                                <input type="number" name="stok_total" class="form-control fw-bold" value="5" min="1" max="100" required>
                                <small class="text-muted" style="font-size: 11px;">Sistem akan otomatis membuat kode eksemplar <code>-01</code> s/d <code>-NN</code> dan QR unik masing-masing.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Tahun Terbit:</label>
                                <input type="number" name="tahun_terbit" class="form-control" value="<?= date('Y') ?>" min="1950" max="<?= date('Y') + 1 ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Edisi / Cetakan:</label>
                                <input type="text" name="edisi" class="form-control" value="Cetakan 1">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Bahasa:</label>
                                <input type="text" name="bahasa" class="form-control" value="Indonesia">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Jumlah Halaman:</label>
                                <input type="number" name="jumlah_halaman" class="form-control" value="200" min="1">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Sumber Buku:</label>
                                <select name="sumber_buku" class="form-select">
                                    <option value="BOS">BOS (Bantuan Operasional Sekolah)</option>
                                    <option value="BOSDA">BOSDA (Daerah)</option>
                                    <option value="Hibah/Sumbangan">Hibah / Sumbangan Siswa</option>
                                    <option value="Pembelian Mandiri">Pembelian Mandiri Sekolah</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Harga Buku (Rp):</label>
                                <input type="number" name="harga" class="form-control" value="75000" step="1000">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Upload Cover Buku (JPG/PNG/WebP):</label>
                                <input type="file" name="cover" class="form-control" accept="image/*">
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100 justify-content-center py-2 mt-2">
                                <i class="bi bi-check2-circle me-1"></i>Simpan Buku & Eksemplar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
