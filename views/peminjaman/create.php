<?php
/**
 * Form Peminjaman Buku dengan Alur Dua QR Code Terpadu
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Peminjaman.php';
require_once __DIR__ . '/../../models/Anggota.php';

require_role(['Administrator', 'Petugas']);

$pageTitle = 'Peminjaman Buku Baru (Dua QR)';
$peminjamanModel = new Peminjaman();
$nomorOtomatis = $peminjamanModel->generateNomorTransaksi();

$lamaPinjamHari = (int)get_setting('lama_pinjam_hari', '7');
$tglPinjam = date('Y-m-d');
$tglJatuhTempo = date('Y-m-d', strtotime("+$lamaPinjamHari days"));
$maxPinjam = (int)get_setting('maksimal_buku_pinjam', '3');

// Jika dibuka dengan parameter anggota_id dari scanner
$preAnggota = null;
if (!empty($_GET['anggota_id'])) {
    $anggotaModel = new Anggota();
    $preAnggota = $anggotaModel->getById((int)$_GET['anggota_id']);
}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-qr-code-scan text-primary me-2"></i>Peminjaman Buku Baru</h1>
                <div class="page-subtitle">Alur Peminjaman Cerdas: Scan QR Anggota &rarr; Scan QR Buku &rarr; Simpan Transaksi</div>
            </div>
            <div>
                <a href="<?= base_url('index.php?page=peminjaman') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>
        </div>

        <form id="formPeminjaman" method="POST" action="<?= base_url('controllers/TransaksiController.php?action=pinjam_store') ?>">
            <?= csrf_field() ?>
            <input type="hidden" id="inputAnggotaId" name="anggota_id" value="<?= $preAnggota ? $preAnggota['id'] : '' ?>" required>

            <div class="row g-4">
                <!-- STEP 1: IDENTIFIKASI ANGGOTA -->
                <div class="col-lg-5">
                    <div class="card-custom h-100 mb-0">
                        <div class="card-custom-header bg-primary-subtle border-primary-subtle">
                            <h2 class="card-custom-title text-primary">
                                <span class="badge bg-primary text-white rounded-circle px-2 py-1 me-1">1</span>
                                <span>Pilih / Scan Anggota</span>
                            </h2>
                            <span class="badge bg-primary text-white">LIBRARY-MEMBER</span>
                        </div>
                        <div class="card-custom-body">
                            <!-- Toggle Mode Scan vs Manual -->
                            <div class="btn-group w-100 mb-3" role="group">
                                <button type="button" id="tabScanMember" class="btn btn-sm btn-outline-primary active">
                                    <i class="bi bi-camera me-1"></i>Scan QR Anggota
                                </button>
                                <button type="button" id="tabManualMember" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-search me-1"></i>Cari Manual
                                </button>
                            </div>

                            <!-- Area Kamera Anggota -->
                            <div id="memberCameraBox" class="text-center mb-3">
                                <div class="scanner-viewport-box position-relative" style="height: 180px; background: #0b1120;">
                                    <div id="readerMember" style="width: 100%;"></div>
                                    <div id="camMemberPlaceholder" class="p-3 text-white">
                                        <i class="bi bi-person-badge fs-2 opacity-50 d-block mb-1"></i>
                                        <button type="button" id="btnStartMemberCam" class="btn btn-sm btn-primary">
                                            <i class="bi bi-play-circle me-1"></i>Buka Kamera Anggota
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Arahkan QR Card Anggota ke kamera</small>
                            </div>

                            <!-- Area Pencarian Manual Anggota -->
                            <div id="memberManualBox" class="mb-3" style="display: none;">
                                <label class="form-label small fw-bold">Ketik Nama / NISN / No. Anggota:</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="searchMemberInput" class="form-control" placeholder="Contoh: Budi atau AGT-00001...">
                                    <button type="button" id="btnSearchMember" class="btn btn-primary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                                <div id="memberSearchResults" class="list-group list-group-flush border rounded-2 mt-2 shadow-sm" style="display: none; max-height: 180px; overflow-y: auto;"></div>
                            </div>

                            <hr>

                            <!-- Kartu Preview Anggota Terpilih -->
                            <div id="memberSelectedCard" class="p-3 bg-surface border rounded-3 shadow-sm" style="<?= $preAnggota ? '' : 'display: none;' ?>">
                                <div class="d-flex gap-3 align-items-center">
                                    <img id="viewMemberFoto" src="<?= $preAnggota ? base_url('uploads/anggota/' . $preAnggota['foto']) : '' ?>" alt="Foto" class="rounded border" style="width: 65px; height: 85px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h5 id="viewMemberNama" class="fw-bold mb-1 text-primary"><?= $preAnggota ? htmlspecialchars($preAnggota['nama'], ENT_QUOTES, 'UTF-8') : '' ?></h5>
                                        <div class="badge bg-secondary mb-1" id="viewMemberNo"><?= $preAnggota ? htmlspecialchars($preAnggota['nomor_anggota'], ENT_QUOTES, 'UTF-8') : '' ?></div>
                                        <div class="small text-muted" id="viewMemberKelas"><?= $preAnggota ? htmlspecialchars(($preAnggota['nama_kelas'] ?? '') . ' (' . ($preAnggota['kode_jurusan'] ?? '') . ')', ENT_QUOTES, 'UTF-8') : '' ?></div>
                                        <div class="small fw-semibold mt-1" id="viewMemberQuota">
                                            Status: <span class="text-success"><i class="bi bi-check-circle-fill"></i> Siap Meminjam</span>
                                        </div>
                                    </div>
                                    <button type="button" id="btnResetMember" class="btn btn-sm btn-outline-danger" title="Ganti Anggota">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <div id="memberEmptyNotice" class="text-center py-4 text-muted" style="<?= $preAnggota ? 'display: none;' : '' ?>">
                                <i class="bi bi-person-x fs-2 opacity-50 d-block mb-1"></i>
                                <span>Belum ada anggota dipilih. Silakan scan QR anggota terlebih dahulu.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: SCAN BUKU & KERANJANG PEMINJAMAN -->
                <div class="col-lg-7">
                    <div class="card-custom h-100 mb-0">
                        <div class="card-custom-header bg-success-subtle border-success-subtle">
                            <h2 class="card-custom-title text-success">
                                <span class="badge bg-success text-white rounded-circle px-2 py-1 me-1">2</span>
                                <span>Scan Buku & Daftar Peminjaman</span>
                            </h2>
                            <span class="badge bg-success text-white">LIBRARY-BOOK</span>
                        </div>
                        <div class="card-custom-body">
                            <!-- Toggle Mode Scan Buku vs Manual -->
                            <div class="row g-2 align-items-center mb-3">
                                <div class="col-md-6">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" id="tabScanBook" class="btn btn-sm btn-outline-success active">
                                            <i class="bi bi-camera me-1"></i>Scan QR Buku
                                        </button>
                                        <button type="button" id="tabManualBook" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-search me-1"></i>Cari Manual
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <span class="small text-muted">Batas Pinjam: <strong><?= $maxPinjam ?> Buku</strong></span>
                                </div>
                            </div>

                            <!-- Area Kamera Buku -->
                            <div id="bookCameraBox" class="text-center mb-3">
                                <div class="scanner-viewport-box position-relative" style="height: 180px; background: #0b1120;">
                                    <div id="readerBook" style="width: 100%;"></div>
                                    <div id="camBookPlaceholder" class="p-3 text-white">
                                        <i class="bi bi-book fs-2 opacity-50 d-block mb-1"></i>
                                        <button type="button" id="btnStartBookCam" class="btn btn-sm btn-success">
                                            <i class="bi bi-play-circle me-1"></i>Buka Kamera Buku
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Arahkan QR Stiker Buku / Eksemplar ke kamera</small>
                            </div>

                            <!-- Area Pencarian Manual Buku -->
                            <div id="bookManualBox" class="mb-3" style="display: none;">
                                <label class="form-label small fw-bold">Ketik Judul / Kode Buku / ISBN:</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="searchBookInput" class="form-control" placeholder="Contoh: Otomotif atau BK-00001...">
                                    <button type="button" id="btnSearchBook" class="btn btn-success">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                                <div id="bookSearchResults" class="list-group list-group-flush border rounded-2 mt-2 shadow-sm" style="display: none; max-height: 180px; overflow-y: auto;"></div>
                            </div>

                            <!-- Keranjang Buku Terpilih (Multi-Buku) -->
                            <h6 class="fw-bold mb-2 text-main d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-basket2 me-1 text-success"></i>Buku yang Dipinjam:</span>
                                <span id="cartCountBadge" class="badge bg-success">0 Buku</span>
                            </h6>

                            <div class="table-responsive border rounded-3 mb-3">
                                <table class="table table-sm table-hover align-middle mb-0" id="tableCart">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kode</th>
                                            <th>Judul Buku</th>
                                            <th>Rak</th>
                                            <th>Kategori</th>
                                            <th class="text-center" style="width: 50px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cartTableBody">
                                        <tr id="cartEmptyRow">
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="bi bi-book fs-3 opacity-25 d-block mb-1"></i>
                                                Belum ada buku dimasukkan ke daftar pinjam.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Detail Transaksi & Submit -->
                            <div class="row g-3 p-3 bg-surface border rounded-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">No. Transaksi (Otomatis):</label>
                                    <input type="text" class="form-control form-control-sm bg-light fw-bold font-monospace" name="nomor_transaksi" value="<?= htmlspecialchars($nomorOtomatis, ENT_QUOTES, 'UTF-8') ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Petugas:</label>
                                    <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars(current_user()['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Tanggal Pinjam:</label>
                                    <input type="date" class="form-control form-control-sm" id="inputTglPinjam" name="tanggal_pinjam" value="<?= $tglPinjam ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Jatuh Tempo (<?= $lamaPinjamHari ?> Hari):</label>
                                    <input type="date" class="form-control form-control-sm text-primary fw-bold" id="inputTglTempo" name="tanggal_jatuh_tempo" value="<?= $tglJatuhTempo ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Catatan Peminjaman (Opsional):</label>
                                    <input type="text" class="form-control form-control-sm" name="catatan" placeholder="Keperluan tugas sekolah, praktikum lab, dll...">
                                </div>
                            </div>

                            <button type="submit" id="btnSubmitPinjam" class="btn btn-primary-custom w-100 justify-content-center py-2 fs-6" disabled>
                                <i class="bi bi-check-circle-fill"></i> Simpan Transaksi Peminjaman
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
<script>
let memberScanner = null;
let bookScanner = null;
let cartItems = [];
const maxAllowedPinjam = <?= $maxPinjam ?>;

document.addEventListener('DOMContentLoaded', () => {
    // Mode Switcher Anggota
    document.getElementById('tabScanMember').addEventListener('click', () => {
        document.getElementById('tabScanMember').classList.add('active');
        document.getElementById('tabManualMember').classList.remove('active');
        document.getElementById('memberCameraBox').style.display = 'block';
        document.getElementById('memberManualBox').style.display = 'none';
    });
    document.getElementById('tabManualMember').addEventListener('click', () => {
        document.getElementById('tabManualMember').classList.add('active');
        document.getElementById('tabScanMember').classList.remove('active');
        document.getElementById('memberManualBox').style.display = 'block';
        document.getElementById('memberCameraBox').style.display = 'none';
        stopMemberCamera();
    });

    // Mode Switcher Buku
    document.getElementById('tabScanBook').addEventListener('click', () => {
        document.getElementById('tabScanBook').classList.add('active');
        document.getElementById('tabManualBook').classList.remove('active');
        document.getElementById('bookCameraBox').style.display = 'block';
        document.getElementById('bookManualBook').style.display = 'none';
    });
    document.getElementById('tabManualBook').addEventListener('click', () => {
        document.getElementById('tabManualBook').classList.add('active');
        document.getElementById('tabScanBook').classList.remove('active');
        document.getElementById('bookManualBox').style.display = 'block';
        document.getElementById('bookCameraBox').style.display = 'none';
        stopBookCamera();
    });

    // Kamera Anggota
    document.getElementById('btnStartMemberCam').addEventListener('click', startMemberCamera);
    // Kamera Buku
    document.getElementById('btnStartBookCam').addEventListener('click', startBookCamera);

    // Pencarian Manual Anggota
    document.getElementById('btnSearchMember').addEventListener('click', searchMemberManual);
    document.getElementById('searchMemberInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); searchMemberManual(); }
    });

    // Pencarian Manual Buku
    document.getElementById('btnSearchBook').addEventListener('click', searchBookManual);
    document.getElementById('searchBookInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); searchBookManual(); }
    });

    // Reset Anggota
    document.getElementById('btnResetMember').addEventListener('click', () => {
        document.getElementById('inputAnggotaId').value = '';
        document.getElementById('memberSelectedCard').style.display = 'none';
        document.getElementById('memberEmptyNotice').style.display = 'block';
        checkSubmitEligibility();
    });

    // Tanggal Pinjam Change -> Auto Hitung Jatuh Tempo
    document.getElementById('inputTglPinjam').addEventListener('change', (e) => {
        const val = e.target.value;
        if (val) {
            const d = new Date(val);
            d.setDate(d.getDate() + <?= $lamaPinjamHari ?>);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            document.getElementById('inputTglTempo').value = `${y}-${m}-${day}`;
        }
    });

    <?php if ($preAnggota): ?>
    checkSubmitEligibility();
    <?php endif; ?>
});

// ==========================================
// KAMERA ANGGOTA & VALIDASI DUA QR
// ==========================================
function startMemberCamera() {
    memberScanner = new Html5Qrcode("readerMember");
    document.getElementById('camMemberPlaceholder').style.display = 'none';
    memberScanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 160, height: 160 } },
        (decodedText) => {
            handleScanMember(decodedText);
        },
        (err) => {}
    ).catch(err => {
        console.error("Camera error:", err);
        showToast('danger', 'Gagal membuka kamera anggota.');
        document.getElementById('camMemberPlaceholder').style.display = 'block';
    });
}

function stopMemberCamera() {
    if (memberScanner) {
        memberScanner.stop().then(() => {
            memberScanner = null;
            document.getElementById('camMemberPlaceholder').style.display = 'block';
        }).catch(() => {});
    }
}

function handleScanMember(qrText) {
    // Validasi Silang: cegah scan QR Buku saat memilih anggota
    fetch(`${window.BASE_URL}/api/scan_handler.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            qr_data: qrText,
            expected_type: 'MEMBER',
            action: 'Peminjaman: Scan Anggota'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            showToast('danger', data.message);
            return;
        }

        const m = data.data;
        if (!m.can_borrow) {
            showToast('warning', `Anggota ${m.nama} sudah mencapai batas pinjam (${m.total_pinjam} / ${m.max_pinjam} buku).`);
            return;
        }

        selectMember(m);
        stopMemberCamera();
        showToast('success', `Anggota ${m.nama} berhasil dipilih!`);
    })
    .catch(err => {
        console.error(err);
        showToast('danger', 'Gagal memproses QR Anggota.');
    });
}

function selectMember(m) {
    document.getElementById('inputAnggotaId').value = m.id;
    document.getElementById('viewMemberNama').textContent = m.nama;
    document.getElementById('viewMemberNo').textContent = m.nomor_anggota;
    document.getElementById('viewMemberKelas').textContent = `${m.nama_kelas} (${m.kode_jurusan})`;
    document.getElementById('viewMemberFoto').src = m.foto_url;
    document.getElementById('memberSelectedCard').style.display = 'block';
    document.getElementById('memberEmptyNotice').style.display = 'none';
    document.getElementById('memberSearchResults').style.display = 'none';

    checkSubmitEligibility();
}

function searchMemberManual() {
    const q = document.getElementById('searchMemberInput').value.trim();
    if (!q) return;

    fetch(`${window.BASE_URL}/api/search_anggota.php?q=${encodeURIComponent(q)}`)
    .then(res => res.json())
    .then(data => {
        const box = document.getElementById('memberSearchResults');
        box.innerHTML = '';
        if (!data.results || data.results.length === 0) {
            box.innerHTML = '<div class="p-2 text-muted small text-center">Anggota tidak ditemukan.</div>';
            box.style.display = 'block';
            return;
        }

        data.results.forEach(m => {
            const a = document.createElement('a');
            a.href = '#';
            a.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2 py-2';
            a.innerHTML = `
                <img src="${m.foto_url}" style="width:34px;height:34px;object-fit:cover;" class="rounded-circle border">
                <div class="flex-grow-1">
                    <div class="fw-semibold small">${m.nama} (${m.nomor_anggota})</div>
                    <small class="text-muted" style="font-size:11px;">${m.nama_kelas} &bull; Pinjam aktif: ${m.total_pinjam}/${m.max_pinjam}</small>
                </div>
            `;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (!m.can_borrow) {
                    showToast('warning', `Anggota sudah mencapai batas pinjam.`);
                    return;
                }
                selectMember(m);
            });
            box.appendChild(a);
        });
        box.style.display = 'block';
    });
}

// ==========================================
// KAMERA BUKU & VALIDASI DUA QR
// ==========================================
function startBookCamera() {
    bookScanner = new Html5Qrcode("readerBook");
    document.getElementById('camBookPlaceholder').style.display = 'none';
    bookScanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 160, height: 160 } },
        (decodedText) => {
            handleScanBook(decodedText);
        },
        (err) => {}
    ).catch(err => {
        console.error("Camera error:", err);
        showToast('danger', 'Gagal membuka kamera buku.');
        document.getElementById('camBookPlaceholder').style.display = 'block';
    });
}

function stopBookCamera() {
    if (bookScanner) {
        bookScanner.stop().then(() => {
            bookScanner = null;
            document.getElementById('camBookPlaceholder').style.display = 'block';
        }).catch(() => {});
    }
}

function handleScanBook(qrText) {
    // Validasi Silang: cegah scan QR Anggota saat memilih buku
    fetch(`${window.BASE_URL}/api/scan_handler.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            qr_data: qrText,
            expected_type: 'BOOK',
            action: 'Peminjaman: Scan Buku'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            showToast('danger', data.message);
            return;
        }

        const b = data.data;
        if (!b.is_available) {
            showToast('warning', `Stok buku "${b.judul}" sedang habis.`);
            return;
        }

        addBookToCart(b);
    })
    .catch(err => {
        console.error(err);
        showToast('danger', 'Gagal memproses QR Buku.');
    });
}

function addBookToCart(b) {
    // Cek batas pinjam
    if (cartItems.length >= maxAllowedPinjam) {
        showToast('warning', `Maksimal peminjaman adalah ${maxAllowedPinjam} buku.`);
        return;
    }

    // Cek apakah buku sudah ada di keranjang
    const exists = cartItems.find(item => item.id === b.id);
    if (exists) {
        showToast('info', `Buku "${b.judul}" sudah ada dalam daftar peminjaman.`);
        return;
    }

    cartItems.push(b);
    renderCart();
    showToast('success', `Buku "${b.judul}" ditambahkan ke keranjang pinjam.`);
}

function removeBookFromCart(bId) {
    cartItems = cartItems.filter(item => item.id !== bId);
    renderCart();
}

function renderCart() {
    const tbody = document.getElementById('cartTableBody');
    const countBadge = document.getElementById('cartCountBadge');
    countBadge.textContent = `${cartItems.length} Buku`;

    if (cartItems.length === 0) {
        tbody.innerHTML = `
            <tr id="cartEmptyRow">
                <td colspan="5" class="text-center py-4 text-muted">
                    <i class="bi bi-book fs-3 opacity-25 d-block mb-1"></i>
                    Belum ada buku dimasukkan ke daftar pinjam.
                </td>
            </tr>
        `;
    } else {
        let html = '';
        cartItems.forEach((b, idx) => {
            const exInfo = b.eksemplar_info ? `<span class="badge bg-light text-dark border ms-1">${b.eksemplar_info.kode_eksemplar}</span>` : '';
            const exId = b.eksemplar_info ? b.eksemplar_info.id : '';

            html += `
                <tr>
                    <td>
                        <span class="fw-bold font-monospace small">${b.kode_buku}</span>${exInfo}
                        <input type="hidden" name="buku_id[]" value="${b.id}">
                        <input type="hidden" name="eksemplar_id[]" value="${exId}">
                    </td>
                    <td>
                        <div class="fw-semibold small">${b.judul}</div>
                        <small class="text-muted" style="font-size:11px;">${b.nama_pengarang}</small>
                    </td>
                    <td><span class="badge bg-light text-muted border">${b.kode_rak}</span></td>
                    <td><small class="text-muted">${b.nama_kategori}</small></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeBookFromCart(${b.id})" title="Hapus dari daftar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    checkSubmitEligibility();
}

function searchBookManual() {
    const q = document.getElementById('searchBookInput').value.trim();
    if (!q) return;

    fetch(`${window.BASE_URL}/api/search_buku.php?q=${encodeURIComponent(q)}&available_only=1`)
    .then(res => res.json())
    .then(data => {
        const box = document.getElementById('bookSearchResults');
        box.innerHTML = '';
        if (!data.results || data.results.length === 0) {
            box.innerHTML = '<div class="p-2 text-muted small text-center">Buku tidak ditemukan atau stok habis.</div>';
            box.style.display = 'block';
            return;
        }

        data.results.forEach(b => {
            const a = document.createElement('a');
            a.href = '#';
            a.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2 py-2';
            a.innerHTML = `
                <img src="${b.cover_url}" style="width:28px;height:40px;object-fit:cover;" class="rounded border">
                <div class="flex-grow-1">
                    <div class="fw-semibold small">${b.judul}</div>
                    <small class="text-muted" style="font-size:11px;">${b.kode_buku} &bull; Tersedia: ${b.stok_tersedia} eksemplar</small>
                </div>
            `;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                addBookToCart({
                    id: b.id,
                    kode_buku: b.kode_buku,
                    judul: b.judul,
                    nama_pengarang: b.nama_pengarang,
                    nama_kategori: b.nama_kategori,
                    kode_rak: b.nama_rak,
                    is_available: true
                });
                box.style.display = 'none';
            });
            box.appendChild(a);
        });
        box.style.display = 'block';
    });
}

function checkSubmitEligibility() {
    const hasMember = Boolean(document.getElementById('inputAnggotaId').value);
    const hasBooks = cartItems.length > 0;
    const btn = document.getElementById('btnSubmitPinjam');

    if (hasMember && hasBooks) {
        btn.removeAttribute('disabled');
    } else {
        btn.setAttribute('disabled', 'disabled');
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
