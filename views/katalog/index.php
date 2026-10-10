<?php
/**
 * Halaman Katalog Buku Digital (Card View & AJAX Live Filter)
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Buku.php';
require_once __DIR__ . '/../../models/Master.php';

$pageTitle = 'Katalog Buku Digital';
$bukuModel = new Buku();
$masterModel = new Master();

$kategoriList = $masterModel->getKategori();
$rakList = $masterModel->getRak();

$books = $bukuModel->getAll([], 30, 0);

$schoolName = get_setting('nama_sekolah', 'SMK Negeri 2 Sumbawa Besar');
$appName = get_setting('nama_perpustakaan', 'PERPUSTAKAAN DIGITAL SMKN 2 SUMBAWA BESAR');

require_once __DIR__ . '/../layouts/header.php';
if (is_logged_in()) {
    require_once __DIR__ . '/../layouts/sidebar.php';
}
?>

<div class="main-content <?= !is_logged_in() ? 'ms-0' : '' ?>">
    <?php if (is_logged_in()): ?>
        <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
    <?php else: ?>
        <!-- Topbar Khusus Pengunjung Publik Tanpa Login -->
        <div class="topbar px-3 px-md-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <img src="<?= asset_url('images/logo_smkn2.png') ?>" alt="Logo" style="width: 38px; height: 38px;">
                <span class="fw-bold fs-6 d-none d-md-inline"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="fw-bold fs-6 d-md-none" style="font-size: 0.9rem !important;">PERPUSTAKAAN</span>
            </div>
            <div>
                <a href="<?= base_url('login.php') ?>" class="btn btn-sm btn-primary-custom">
                    <i class="bi bi-box-arrow-in-right me-1"></i><span class="d-none d-md-inline">Login Petugas / Anggota</span><span class="d-md-none">Login</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="page-container">
        <!-- Hero Header Katalog -->
        <div class="p-4 rounded-4 mb-4 text-white position-relative overflow-hidden shadow-sm" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #0284c7 100%);">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-white text-primary fw-bold mb-2 px-3 py-1">E-KATALOG PUSTAKA</span>
                    <h2 class="fw-bold mb-2">Jelajahi Koleksi Buku <?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="mb-0 opacity-75 small">Temukan buku teks kurikulum merdeka, modul kejuruan, serta karya inspiratif lainnya.</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 p-2 rounded-3">
                        <i class="bi bi-book fs-1 text-warning"></i>
                        <div class="text-start">
                            <span class="d-block small text-white-50">Total Koleksi</span>
                            <span class="fw-bold fs-5 text-white"><?= count($books) ?> Judul Tersedia</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Real-Time Live Search -->
        <div class="card-custom mb-4">
            <div class="card-custom-body py-3">
                <div class="row g-2">
                    <div class="col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-surface"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="liveSearchInput" class="form-control" placeholder="Cari judul, pengarang, ISBN, atau topik buku...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="filterKategori" class="form-select">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kategoriList as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filterStatus" class="form-select">
                            <option value="">Status Buku</option>
                            <option value="Tersedia">Hanya Tersedia</option>
                            <option value="Habis Dipinjam">Sedang Dipinjam</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btnResetFilter" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container Card View Buku -->
        <div class="row g-4" id="bookCardGrid">
            <?php foreach ($books as $b): ?>
            <?php
                $coverUrl = !empty($b['cover']) && file_exists(UPLOAD_COVER_PATH . '/' . $b['cover'])
                    ? base_url('uploads/covers/' . $b['cover'])
                    : asset_url('images/default_book.png');
            ?>
            <div class="col-xl-3 col-lg-4 col-md-6 book-card-item" 
                 data-title="<?= strtolower($b['judul']) ?>" 
                 data-author="<?= strtolower($b['nama_pengarang'] ?? '') ?>" 
                 data-code="<?= strtolower($b['kode_buku']) ?>" 
                 data-isbn="<?= strtolower($b['isbn'] ?? '') ?>" 
                 data-category="<?= $b['kategori_id'] ?>" 
                 data-status="<?= $b['status'] ?>">
                <div class="card-custom h-100 d-flex flex-column mb-0" style="transition: all 0.2s;">
                    <div class="position-relative overflow-hidden text-center p-3" style="background: var(--bg-hover);">
                        <img src="<?= $coverUrl ?>" alt="Cover" class="rounded shadow-sm" style="height: 180px; width: 130px; object-fit: cover;">
                        <span class="position-absolute top-0 start-0 m-2 badge bg-primary text-white font-monospace" style="font-size: 10.5px;">
                            <?= htmlspecialchars($b['kode_buku'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php if ($b['stok_tersedia'] > 0): ?>
                            <span class="position-absolute top-0 end-0 m-2 badge bg-success text-white" style="font-size: 10px;">
                                <i class="bi bi-check-circle me-1"></i><?= $b['stok_tersedia'] ?> Ada
                            </span>
                        <?php else: ?>
                            <span class="position-absolute top-0 end-0 m-2 badge bg-danger text-white" style="font-size: 10px;">
                                Habis
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-custom-body d-flex flex-column flex-grow-1 p-3">
                        <small class="text-primary fw-semibold text-truncate mb-1" style="font-size: 11.5px;">
                            <?= htmlspecialchars($b['nama_kategori'] ?? 'Umum', ENT_QUOTES, 'UTF-8') ?>
                        </small>
                        <h6 class="fw-bold text-main mb-1 text-truncate-2" style="font-size: 14px; line-height: 1.3;" title="<?= htmlspecialchars($b['judul'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($b['judul'], ENT_QUOTES, 'UTF-8') ?>
                        </h6>
                        <small class="text-muted mb-2 text-truncate"><?= htmlspecialchars($b['nama_pengarang'] ?? '-', ENT_QUOTES, 'UTF-8') ?></small>

                        <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                            <span class="small text-muted" style="font-size: 11px;">
                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($b['kode_rak'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" onclick="showBookDetail(<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>, '<?= $coverUrl ?>')">
                                Detail
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="noResultsAlert" class="text-center py-5 card-custom" style="display: none;">
            <i class="bi bi-search display-4 text-muted opacity-25 d-block mb-3"></i>
            <h5 class="fw-semibold">Tidak Ada Buku Ditemukan</h5>
            <p class="text-muted small mb-0">Coba ubah kata kunci pencarian atau gunakan filter kategori lainnya.</p>
        </div>

    </div>

<!-- Modal Detail Buku Interaktif -->
<div class="modal fade" id="modalBookDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalDetailTitle">Detail Koleksi Buku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        <img id="modalDetailCover" src="" alt="Cover" class="img-fluid rounded-3 shadow border mb-2" style="max-height: 240px; object-fit: cover;">
                        <div class="badge bg-light text-primary border font-monospace mt-2" id="modalDetailKode">-</div>
                    </div>
                    <div class="col-md-8">
                        <h4 class="fw-bold mb-1" id="modalDetailJudul">-</h4>
                        <div class="text-muted small mb-3" id="modalDetailPengarang">-</div>

                        <table class="table table-sm table-borderless small mb-3">
                            <tr><th class="text-muted" style="width: 140px;">Kategori:</th><td id="modalDetailKategori">-</td></tr>
                            <tr><th class="text-muted">Penerbit:</th><td id="modalDetailPenerbit">-</td></tr>
                            <tr><th class="text-muted">Tahun / Edisi:</th><td id="modalDetailTahun">-</td></tr>
                            <tr><th class="text-muted">ISBN:</th><td id="modalDetailIsbn">-</td></tr>
                            <tr><th class="text-muted">Lokasi Rak:</th><td class="fw-bold text-success" id="modalDetailRak">-</td></tr>
                            <tr><th class="text-muted">Ketersediaan Stok:</th><td id="modalDetailStok">-</td></tr>
                        </table>

                        <h6 class="fw-semibold small text-main mb-1">Sinopsis / Keterangan:</h6>
                        <p class="small text-muted p-2 bg-light rounded border mb-0" id="modalDetailDeskripsi">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <?php if (has_role(['Administrator', 'Petugas'])): ?>
                <a id="modalDetailBtnPinjam" href="#" class="btn btn-primary-custom">
                    <i class="bi bi-plus-circle me-1"></i>Proses Peminjaman Buku Ini
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('liveSearchInput');
    const filterKat = document.getElementById('filterKategori');
    const filterStat = document.getElementById('filterStatus');
    const resetBtn = document.getElementById('btnResetFilter');
    const cards = document.querySelectorAll('.book-card-item');
    const noResults = document.getElementById('noResultsAlert');

    function applyFilter() {
        const q = searchInput.value.toLowerCase().trim();
        const kat = filterKat.value;
        const stat = filterStat.value;
        let visibleCount = 0;

        cards.forEach(card => {
            const title = card.getAttribute('data-title') || '';
            const author = card.getAttribute('data-author') || '';
            const code = card.getAttribute('data-code') || '';
            const isbn = card.getAttribute('data-isbn') || '';
            const category = card.getAttribute('data-category') || '';
            const status = card.getAttribute('data-status') || '';

            const matchSearch = (!q || title.includes(q) || author.includes(q) || code.includes(q) || isbn.includes(q));
            const matchKat = (!kat || category === kat);
            const matchStat = (!stat || status === stat);

            if (matchSearch && matchKat && matchStat) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        noResults.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    searchInput.addEventListener('input', applyFilter);
    filterKat.addEventListener('change', applyFilter);
    filterStat.addEventListener('change', applyFilter);

    resetBtn.addEventListener('click', () => {
        searchInput.value = '';
        filterKat.value = '';
        filterStat.value = '';
        applyFilter();
    });
});

function showBookDetail(b, coverUrl) {
    document.getElementById('modalDetailJudul').textContent = b.judul;
    document.getElementById('modalDetailKode').textContent = b.kode_buku;
    document.getElementById('modalDetailCover').src = coverUrl;
    document.getElementById('modalDetailPengarang').textContent = b.nama_pengarang || '-';
    document.getElementById('modalDetailKategori').textContent = b.nama_kategori || '-';
    document.getElementById('modalDetailPenerbit').textContent = b.nama_penerbit || '-';
    document.getElementById('modalDetailTahun').textContent = `${b.tahun_terbit} (${b.edisi || 'Cetakan 1'})`;
    document.getElementById('modalDetailIsbn').textContent = b.isbn || '-';
    document.getElementById('modalDetailRak').textContent = `${b.nama_rak || ''} (${b.kode_rak || ''})`;
    document.getElementById('modalDetailStok').textContent = `${b.stok_tersedia} eksemplar tersedia dari total ${b.stok_total}`;
    document.getElementById('modalDetailDeskripsi').textContent = b.deskripsi || 'Tidak ada deskripsi.';

    const btnPinjam = document.getElementById('modalDetailBtnPinjam');
    if (btnPinjam) {
        btnPinjam.href = `${window.BASE_URL}/index.php?page=peminjaman_create`;
    }

    new bootstrap.Modal(document.getElementById('modalBookDetail')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
