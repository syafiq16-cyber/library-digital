<?php
/**
 * Form Proses Pengembalian Buku & Perhitungan Denda Otomatis
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Peminjaman.php';
require_once __DIR__ . '/../../models/Pengembalian.php';

require_role(['Administrator', 'Petugas']);

$pageTitle = 'Proses Pengembalian Buku';
$peminjamanModel = new Peminjaman();

$pinjamId = (int)($_GET['pinjam_id'] ?? 0);
$selectedLoan = null;
$loanDetails = [];

if ($pinjamId > 0) {
    $selectedLoan = $peminjamanModel->getById($pinjamId);
    if ($selectedLoan) {
        $loanDetails = $peminjamanModel->getDetails($pinjamId);
        // Filter hanya detail yang masih dipinjam
        $loanDetails = array_filter($loanDetails, fn($d) => $d['status'] === 'Dipinjam');
    }
}

$today = date('Y-m-d');
$dendaPerHari = (float)get_setting('denda_per_hari', '1000');
$dendaRusakRingan = (float)get_setting('denda_rusak_ringan', '15000');
$dendaRusakBerat = (float)get_setting('denda_rusak_berat', '50000');
$dendaHilang = (float)get_setting('denda_hilang', '100000');

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-arrow-down-left-square-fill text-success me-2"></i>Proses Pengembalian Buku</h1>
                <div class="page-subtitle">Scan QR Anggota / Buku atau Cari Transaksi untuk memproses pengembalian buku dan denda</div>
            </div>
            <div>
                <a href="<?= base_url('index.php?page=pengembalian') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Riwayat Pengembalian
                </a>
            </div>
        </div>

        <!-- Panel Pencarian Transaksi / Scan QR -->
        <div class="card-custom mb-4">
            <div class="card-custom-header bg-surface">
                <h2 class="card-custom-title">
                    <i class="bi bi-search text-primary"></i>
                    <span>Cari Transaksi Peminjaman Aktif</span>
                </h2>
                <a href="<?= base_url('index.php?page=scanner') ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                    <i class="bi bi-qr-code-scan me-1"></i>Buka Scanner Kamera
                </a>
            </div>
            <div class="card-custom-body py-3">
                <div class="row g-2">
                    <div class="col-md-9">
                        <input type="text" id="searchTransInput" class="form-control" placeholder="Scan QR Anggota / QR Buku, atau ketik Nomor Transaksi (PJM-...) / Nama Anggota...">
                    </div>
                    <div class="col-md-3">
                        <button type="button" id="btnCariTransaksi" class="btn btn-primary-custom w-100 justify-content-center">
                            <i class="bi bi-search me-1"></i>Cari Transaksi
                        </button>
                    </div>
                </div>
                <div id="transSearchResults" class="list-group list-group-flush border rounded-2 mt-2 shadow-sm" style="display: none; max-height: 220px; overflow-y: auto;"></div>
            </div>
        </div>

        <?php if (!$selectedLoan): ?>
            <div class="text-center py-5 card-custom">
                <i class="bi bi-arrow-down-left-circle display-4 text-success opacity-25 d-block mb-3"></i>
                <h4 class="fw-bold">Pilih Transaksi Peminjaman</h4>
                <p class="text-muted small mb-0">Cari berdasarkan QR Anggota, QR Buku, atau Nomor Transaksi di atas untuk menampilkan daftar buku yang sedang dipinjam.</p>
            </div>
        <?php else: ?>
            <!-- Form Pemrosesan Pengembalian -->
            <form method="POST" action="<?= base_url('controllers/TransaksiController.php?action=kembali_store') ?>" id="formPengembalian">
                <?= csrf_field() ?>
                <input type="hidden" name="peminjaman_id" value="<?= $selectedLoan['id'] ?>">

                <div class="row g-4 mb-4">
                    <!-- Data Peminjaman & Peminjam -->
                    <div class="col-lg-5">
                        <div class="card-custom h-100 mb-0">
                            <div class="card-custom-header bg-primary-subtle border-primary-subtle">
                                <h2 class="card-custom-title text-primary">
                                    <i class="bi bi-person-badge"></i>
                                    <span>Peminjam & Transaksi</span>
                                </h2>
                                <span class="badge bg-primary text-white font-monospace"><?= htmlspecialchars($selectedLoan['nomor_transaksi'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="card-custom-body">
                                <div class="d-flex gap-3 align-items-center mb-3">
                                    <img src="<?= base_url('uploads/anggota/' . ($selectedLoan['foto'] ?: 'default_avatar_m.png')) ?>" alt="Foto" class="rounded border" style="width: 65px; height: 85px; object-fit: cover;">
                                    <div>
                                        <h5 class="fw-bold mb-1 text-primary"><?= htmlspecialchars($selectedLoan['nama_anggota'], ENT_QUOTES, 'UTF-8') ?></h5>
                                        <div class="badge bg-secondary mb-1"><?= htmlspecialchars($selectedLoan['nomor_anggota'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars(($selectedLoan['nama_kelas'] ?? '') . ' (' . ($selectedLoan['kode_jurusan'] ?? '') . ')', ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                </div>
                                <hr>
                                <table class="table table-sm table-borderless small mb-0">
                                    <tr><th class="text-muted">Tanggal Pinjam:</th><td><?= date('d/m/Y', strtotime($selectedLoan['tanggal_pinjam'])) ?></td></tr>
                                    <tr>
                                        <th class="text-muted">Tanggal Jatuh Tempo:</th>
                                        <td class="fw-bold text-danger" id="textJatuhTempo" data-tempo="<?= $selectedLoan['tanggal_jatuh_tempo'] ?>">
                                            <?= date('d/m/Y', strtotime($selectedLoan['tanggal_jatuh_tempo'])) ?>
                                        </td>
                                    </tr>
                                    <tr><th class="text-muted">Petugas Pinjam:</th><td><?= htmlspecialchars($selectedLoan['nama_petugas'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Input Tanggal Kembali & Ringkasan Denda -->
                    <div class="col-lg-7">
                        <div class="card-custom h-100 mb-0">
                            <div class="card-custom-header bg-success-subtle border-success-subtle">
                                <h2 class="card-custom-title text-success">
                                    <i class="bi bi-calculator"></i>
                                    <span>Kalkulasi Keterlambatan & Denda</span>
                                </h2>
                            </div>
                            <div class="card-custom-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Tanggal Kembali:</label>
                                        <input type="date" class="form-control" id="inputTanggalKembali" name="tanggal_kembali" value="<?= $today ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Jumlah Keterlambatan:</label>
                                        <div class="input-group">
                                            <input type="text" id="dispTelatHari" class="form-control bg-light text-danger fw-bold text-center" value="0" readonly>
                                            <span class="input-group-text">Hari</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Total Denda Terhitung:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" id="dispTotalDenda" class="form-control bg-light text-danger fw-bold fs-5" value="0" readonly>
                                        </div>
                                        <small class="text-muted" style="font-size: 11px;">Tarif telat: <?= format_rupiah($dendaPerHari) ?>/hari/buku</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Nominal Denda Dibayar:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" step="500" class="form-control fw-bold text-success" id="inputDendaDibayar" name="denda_dibayar" value="0">
                                        </div>
                                        <small class="text-muted" style="font-size: 11px;">Masukkan jumlah uang tunai yang diterima</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Catatan Pengembalian (Opsional):</label>
                                        <input type="text" name="catatan_umum" class="form-control form-control-sm" placeholder="Catatan kondisi buku atau alasan keterlambatan...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Buku Yang Dikembalikan -->
                <div class="card-custom mb-4">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title">
                            <i class="bi bi-journal-check text-success"></i>
                            <span>Pilih Buku Yang Dikembalikan (Multi-Buku)</span>
                        </h2>
                        <div class="d-flex gap-2">
                            <button type="button" id="btnSelectAll" class="btn btn-sm btn-outline-secondary">Pilih Semua</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">Kembalikan?</th>
                                    <th>Kode Buku / Eksemplar</th>
                                    <th>Judul Buku</th>
                                    <th>Kondisi Buku Saat Dikembalikan</th>
                                    <th>Denda Fisik</th>
                                    <th>Catatan Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($loanDetails)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-success">
                                        <i class="bi bi-check-circle fs-3 d-block mb-1"></i>
                                        Seluruh buku dalam transaksi ini telah dikembalikan.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($loanDetails as $d): ?>
                                    <tr class="item-return-row" data-detail-id="<?= $d['id'] ?>">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input item-checkbox" name="selected_detail_id[]" value="<?= $d['id'] ?>" checked style="cursor: pointer; width: 18px; height: 18px;">
                                        </td>
                                        <td>
                                            <div class="fw-bold font-monospace"><?= htmlspecialchars($d['kode_buku'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if ($d['kode_eksemplar']): ?>
                                                <small class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($d['kode_eksemplar'], ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($d['judul'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <small class="text-muted">Rak: <?= htmlspecialchars($d['kode_rak'] ?? '-', ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td style="width: 200px;">
                                            <select name="kondisi_buku[<?= $d['id'] ?>]" class="form-select form-select-sm select-kondisi">
                                                <option value="Baik" data-denda="0">Baik (Mulus)</option>
                                                <option value="Rusak Ringan" data-denda="<?= $dendaRusakRingan ?>">Rusak Ringan (+<?= format_rupiah($dendaRusakRingan) ?>)</option>
                                                <option value="Rusak Berat" data-denda="<?= $dendaRusakBerat ?>">Rusak Berat (+<?= format_rupiah($dendaRusakBerat) ?>)</option>
                                                <option value="Hilang" data-denda="<?= $dendaHilang ?>">Hilang (+<?= format_rupiah($dendaHilang) ?>)</option>
                                            </select>
                                        </td>
                                        <td>
                                            <span class="disp-denda-kondisi small fw-bold text-muted">Rp0</span>
                                        </td>
                                        <td>
                                            <input type="text" name="catatan_detail[<?= $d['id'] ?>]" class="form-control form-control-sm" placeholder="Kondisi halaman, robek, dll...">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-custom-body border-top text-end">
                        <button type="submit" class="btn btn-success btn-lg px-4" id="btnSubmitPengembalian">
                            <i class="bi bi-check2-circle me-1"></i>Simpan Pengembalian Buku
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>

    </div>
<script>
const DENDA_PER_HARI = <?= $dendaPerHari ?>;

document.addEventListener('DOMContentLoaded', () => {
    // Pencarian Transaksi
    const searchInput = document.getElementById('searchTransInput');
    const searchBtn = document.getElementById('btnCariTransaksi');
    const resultsBox = document.getElementById('transSearchResults');

    function executeSearch() {
        const q = searchInput.value.trim();
        if (!q) return;

        // Cek jika yang diinput adalah QR Code
        if (q.startsWith('LIBRARY-MEMBER:') || q.startsWith('LIBRARY-BOOK:')) {
            fetch(`${window.BASE_URL}/api/scan_handler.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ qr_data: q, action: 'Pencarian Pengembalian' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.type === 'MEMBER') {
                    searchInput.value = data.data.nomor_anggota;
                    cariPinjamanByKeyword(data.data.nomor_anggota);
                } else if (data.success && data.type === 'BOOK') {
                    searchInput.value = data.data.kode_buku;
                    cariPinjamanByKeyword(data.data.kode_buku);
                } else {
                    showToast('warning', data.message || 'Data tidak ditemukan.');
                }
            });
            return;
        }

        cariPinjamanByKeyword(q);
    }

    function cariPinjamanByKeyword(kw) {
        fetch(`${window.BASE_URL}/api/search_global.php?q=${encodeURIComponent(kw)}`)
        .then(res => res.json())
        .then(data => {
            resultsBox.innerHTML = '';
            const pinjamResults = data.results.filter(r => r.category === 'Peminjaman');
            if (pinjamResults.length === 0) {
                // Fallback cari via peminjaman page
                window.location.href = `${window.BASE_URL}/index.php?page=peminjaman&search=${encodeURIComponent(kw)}`;
                return;
            }

            pinjamResults.forEach(r => {
                const a = document.createElement('a');
                a.href = '#';
                a.className = 'list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2';
                a.innerHTML = `
                    <div>
                        <strong class="text-primary">${r.title}</strong>
                        <div class="small text-muted">${r.subtitle}</div>
                    </div>
                    <span class="btn btn-sm btn-outline-success">Pilih Transaksi</span>
                `;
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    // Ambil ID peminjaman dari URL
                    const m = r.url.match(/id=(\d+)/);
                    if (m) {
                        window.location.href = `${window.BASE_URL}/index.php?page=pengembalian_proses&pinjam_id=${m[1]}`;
                    }
                });
                resultsBox.appendChild(a);
            });
            resultsBox.style.display = 'block';
        });
    }

    if (searchBtn) searchBtn.addEventListener('click', executeSearch);
    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); executeSearch(); }
        });
    }

    // Kalkulasi Denda
    const inputTglKembali = document.getElementById('inputTanggalKembali');
    const textJatuhTempo = document.getElementById('textJatuhTempo');
    const dispTelatHari = document.getElementById('dispTelatHari');
    const dispTotalDenda = document.getElementById('dispTotalDenda');
    const inputDendaDibayar = document.getElementById('inputDendaDibayar');

    function calculateGrandTotal() {
        if (!inputTglKembali || !textJatuhTempo) return;

        const tglKembali = new Date(inputTglKembali.value);
        const tglTempo = new Date(textJatuhTempo.getAttribute('data-tempo'));

        let diffHari = 0;
        if (tglKembali > tglTempo) {
            const diffTime = Math.abs(tglKembali - tglTempo);
            diffHari = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        }

        dispTelatHari.value = diffHari;

        // Hitung buku yang dicentang
        let totalDendaTerlambat = 0;
        let totalDendaKondisi = 0;
        let countChecked = 0;

        document.querySelectorAll('.item-return-row').forEach(row => {
            const chk = row.querySelector('.item-checkbox');
            if (chk && chk.checked) {
                countChecked++;
                totalDendaTerlambat += (diffHari * DENDA_PER_HARI);

                const selKondisi = row.querySelector('.select-kondisi');
                const opt = selKondisi.options[selKondisi.selectedIndex];
                const dendaKondisi = parseFloat(opt.getAttribute('data-denda') || 0);
                totalDendaKondisi += dendaKondisi;

                row.querySelector('.disp-denda-kondisi').textContent = 'Rp' + dendaKondisi.toLocaleString('id-ID');
            } else {
                row.querySelector('.disp-denda-kondisi').textContent = 'Rp0';
            }
        });

        const grandTotal = totalDendaTerlambat + totalDendaKondisi;
        dispTotalDenda.value = grandTotal.toLocaleString('id-ID');
        inputDendaDibayar.value = grandTotal;

        const submitBtn = document.getElementById('btnSubmitPengembalian');
        if (submitBtn) {
            submitBtn.disabled = (countChecked === 0);
        }
    }

    if (inputTglKembali) {
        inputTglKembali.addEventListener('change', calculateGrandTotal);
    }

    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.addEventListener('change', calculateGrandTotal);
    });

    document.querySelectorAll('.select-kondisi').forEach(sel => {
        sel.addEventListener('change', calculateGrandTotal);
    });

    const btnSelectAll = document.getElementById('btnSelectAll');
    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', () => {
            const allCbs = document.querySelectorAll('.item-checkbox');
            const allChecked = Array.from(allCbs).every(c => c.checked);
            allCbs.forEach(c => c.checked = !allChecked);
            calculateGrandTotal();
        });
    }

    calculateGrandTotal();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
