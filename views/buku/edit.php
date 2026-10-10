<?php
/**
 * Form Edit Data Buku
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Buku.php';
require_once __DIR__ . '/../../models/Master.php';

require_role(['Administrator', 'Petugas']);

$id = (int)($_GET['id'] ?? 0);
$bukuModel = new Buku();
$book = $bukuModel->getById($id);

if (!$book) {
    set_flash('danger', 'Buku tidak ditemukan.');
    header('Location: ' . base_url('index.php?page=buku'));
    exit;
}

$pageTitle = 'Edit Buku ' . $book['kode_buku'];
$masterModel = new Master();

$kategoriList = $masterModel->getKategori();
$pengarangList = $masterModel->getPengarang();
$penerbitList = $masterModel->getPenerbit();
$rakList = $masterModel->getRak();

$coverUrl = !empty($book['cover']) && file_exists(UPLOAD_COVER_PATH . '/' . $book['cover'])
    ? base_url('uploads/covers/' . $book['cover'])
    : asset_url('images/default_book.png');

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Buku</h1>
                <div class="page-subtitle">Perbarui informasi katalog buku <?= htmlspecialchars($book['kode_buku'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <a href="<?= base_url('index.php?page=buku_detail&id=' . $book['id']) ?>" class="btn btn-outline-info me-1">
                    <i class="bi bi-eye"></i> Detail
                </a>
                <a href="<?= base_url('index.php?page=buku') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <form method="POST" action="<?= base_url('controllers/BukuController.php?action=update') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $book['id'] ?>">

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card-custom mb-4">
                        <div class="card-custom-header">
                            <h2 class="card-custom-title"><i class="bi bi-book text-primary"></i><span>Informasi Buku</span></h2>
                        </div>
                        <div class="card-custom-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Kode Buku:</label>
                                    <input type="text" class="form-control font-monospace bg-light" value="<?= htmlspecialchars($book['kode_buku'], ENT_QUOTES, 'UTF-8') ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">ISBN:</label>
                                    <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($book['isbn'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Judul Buku: <span class="text-danger">*</span></label>
                                    <input type="text" name="judul" class="form-control" value="<?= htmlspecialchars($book['judul'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Pengarang: <span class="text-danger">*</span></label>
                                    <select name="pengarang_id" class="form-select" required>
                                        <?php foreach ($pengarangList as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= ($book['pengarang_id'] == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_pengarang'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Penerbit: <span class="text-danger">*</span></label>
                                    <select name="penerbit_id" class="form-select" required>
                                        <?php foreach ($penerbitList as $pub): ?>
                                        <option value="<?= $pub['id'] ?>" <?= ($book['penerbit_id'] == $pub['id']) ? 'selected' : '' ?>><?= htmlspecialchars($pub['nama_penerbit'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Kategori: <span class="text-danger">*</span></label>
                                    <select name="kategori_id" class="form-select" required>
                                        <?php foreach ($kategoriList as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= ($book['kategori_id'] == $k['id']) ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kategori'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Subkategori:</label>
                                    <input type="text" name="subkategori" class="form-control" value="<?= htmlspecialchars($book['subkategori'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Deskripsi / Sinopsis:</label>
                                    <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($book['deskripsi'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-custom mb-4">
                        <div class="card-custom-header">
                            <h2 class="card-custom-title"><i class="bi bi-gear text-success"></i><span>Pengaturan & Cover</span></h2>
                        </div>
                        <div class="card-custom-body">
                            <div class="text-center mb-3">
                                <img src="<?= $coverUrl ?>" alt="Cover Saat Ini" class="rounded border shadow-sm" style="width: 100px; height: 140px; object-fit: cover;">
                                <div class="small text-muted mt-1">Cover Buku Saat Ini</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Ganti Cover Buku:</label>
                                <input type="file" name="cover" class="form-control" accept="image/*">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Lokasi Rak:</label>
                                <select name="rak_id" class="form-select" required>
                                    <?php foreach ($rakList as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= ($book['rak_id'] == $r['id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['kode_rak'] . ' - ' . $r['nama_rak'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Tahun Terbit:</label>
                                <input type="number" name="tahun_terbit" class="form-control" value="<?= $book['tahun_terbit'] ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Edisi:</label>
                                <input type="text" name="edisi" class="form-control" value="<?= htmlspecialchars($book['edisi'] ?? 'Cetakan 1', ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Bahasa:</label>
                                <input type="text" name="bahasa" class="form-control" value="<?= htmlspecialchars($book['bahasa'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Halaman:</label>
                                <input type="number" name="jumlah_halaman" class="form-control" value="<?= $book['jumlah_halaman'] ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Harga Buku (Rp):</label>
                                <input type="number" name="harga" class="form-control" value="<?= $book['harga'] ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Status Buku:</label>
                                <select name="status" class="form-select">
                                    <option value="Tersedia" <?= ($book['status'] === 'Tersedia') ? 'selected' : '' ?>>Tersedia</option>
                                    <option value="Habis Dipinjam" <?= ($book['status'] === 'Habis Dipinjam') ? 'selected' : '' ?>>Habis Dipinjam</option>
                                    <option value="Nonaktif" <?= ($book['status'] === 'Nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100 justify-content-center py-2">
                                <i class="bi bi-save me-1"></i>Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
