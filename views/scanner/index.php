<?php
/**
 * Halaman Scanner Terpadu Dua QR Code (Kamera Perangkat)
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

require_role(['Administrator', 'Petugas']);

$pageTitle = 'Scanner Terpadu Dua QR';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="bi bi-qr-code-scan text-primary me-2"></i>Scanner Terpadu Dua QR</h1>
                <div class="page-subtitle">Pindai QR Code Anggota (<code>LIBRARY-MEMBER:</code>) atau QR Code Buku (<code>LIBRARY-BOOK:</code>)</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('index.php?page=peminjaman_create') ?>" class="btn btn-outline-primary rounded-pill">
                    <i class="bi bi-arrow-up-right-square me-1"></i>Ke Peminjaman
                </a>
                <a href="<?= base_url('index.php?page=pengembalian') ?>" class="btn btn-outline-success rounded-pill">
                    <i class="bi bi-arrow-down-left-square me-1"></i>Ke Pengembalian
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Kolom Kiri: Kamera Scanner -->
            <div class="col-lg-6">
                <div class="card-custom">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title">
                            <i class="bi bi-camera-video text-primary"></i>
                            <span>Area Kamera Pemindai</span>
                        </h2>
                        <div class="d-flex gap-2">
                            <button id="btnStartScan" class="btn btn-sm btn-success">
                                <i class="bi bi-play-fill me-1"></i>Mulai Kamera
                            </button>
                            <button id="btnStopScan" class="btn btn-sm btn-danger" style="display: none;">
                                <i class="bi bi-stop-fill me-1"></i>Hentikan
                            </button>
                        </div>
                    </div>
                    <div class="card-custom-body text-center p-3">
                        <div class="scanner-viewport-box position-relative" style="min-height: 280px; background: #0b1120; display: flex; align-items: center; justify-content: center;">
                            <div id="qrReader" style="width: 100%;"></div>
                            <div id="scannerOverlayLine" class="scanner-laser-line" style="display: none;"></div>
                            <div id="cameraPlaceholder" class="p-4 text-white">
                                <i class="bi bi-camera fs-1 opacity-50 d-block mb-2"></i>
                                <span>Klik <strong>"Mulai Kamera"</strong> untuk mengaktifkan scanner perangkat</span>
                            </div>
                        </div>

                        <!-- Kontrol Kamera -->
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <label class="small text-muted mb-0">Pilih Kamera:</label>
                                <select id="cameraSelection" class="form-select form-select-sm" style="width: auto; max-width: 220px;">
                                    <option value="">Memuat kamera...</option>
                                </select>
                            </div>
                            <span id="scanStatusBadge" class="badge bg-secondary">Kamera Tidak Aktif</span>
                        </div>

                        <hr class="my-3">

                        <!-- Mode Fallback Input Manual -->
                        <div class="text-start">
                            <label class="form-label small fw-bold"><i class="bi bi-keyboard me-1"></i>Atau Masukkan / Scan Token Manual:</label>
                            <div class="input-group">
                                <input type="text" id="manualInput" class="form-control" placeholder="Contoh: LIBRARY-MEMBER:AGT-00001 atau LIBRARY-BOOK:BK-00001">
                                <button id="btnProcessManual" class="btn btn-primary-custom" type="button">
                                    <i class="bi bi-search me-1"></i>Proses
                                </button>
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Mendukung input barcode scanner USB atau ketik manual jika webcam tidak tersedia.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Hasil Identifikasi Cerdas -->
            <div class="col-lg-6">
                <div class="card-custom h-100">
                    <div class="card-custom-header">
                        <h2 class="card-custom-title">
                            <i class="bi bi-person-bounding-box text-info"></i>
                            <span>Hasil Identifikasi QR Code</span>
                        </h2>
                        <span id="resultBadge" class="badge bg-light text-muted border">Menunggu Pemindaian</span>
                    </div>
                    <div class="card-custom-body" id="scanResultContainer">
                        <!-- Empty State Awal -->
                        <div class="text-center py-5 text-muted" id="scanEmptyState">
                            <i class="bi bi-qr-code-scan display-4 opacity-25 d-block mb-3"></i>
                            <h5 class="fw-semibold">Belum Ada Data Terdeteksi</h5>
                            <p class="small mb-0">Arahkan QR Code Anggota atau QR Code Buku ke kamera scanner untuk melihat informasi data secara instan.</p>
                        </div>

                        <!-- Container Hasil Anggota (Hidden saat awal) -->
                        <div id="memberResultBox" style="display: none;">
                            <div class="d-flex align-items-center gap-3 p-3 bg-primary-subtle border border-primary-subtle rounded-3 mb-3">
                                <i class="bi bi-person-badge fs-2 text-primary"></i>
                                <div>
                                    <h5 class="mb-0 fw-bold text-primary">QR Code Anggota Terverifikasi</h5>
                                    <small class="text-muted">LIBRARY-MEMBER terdeteksi valid di sistem perpustakaan</small>
                                </div>
                            </div>
                            <div class="d-flex gap-4 align-items-start mb-3">
                                <img id="resMemberFoto" src="" alt="Foto Anggota" class="rounded-3 shadow-sm border" style="width: 100px; height: 130px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h4 id="resMemberNama" class="fw-bold mb-1">-</h4>
                                    <div class="badge bg-primary text-white mb-2" id="resMemberNo">-</div>
                                    <table class="table table-sm table-borderless small mb-0">
                                        <tr><th class="text-muted" style="width: 100px;">NIS/NIP:</th><td id="resMemberIdentitas">-</td></tr>
                                        <tr><th class="text-muted">Kategori:</th><td id="resMemberJenis">-</td></tr>
                                        <tr><th class="text-muted">Kelas / Jurusan:</th><td id="resMemberKelas">-</td></tr>
                                        <tr><th class="text-muted">Pinjaman Aktif:</th><td id="resMemberPinjam">-</td></tr>
                                    </table>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a id="btnLinkPinjam" href="#" class="btn btn-primary-custom flex-grow-1 justify-content-center">
                                    <i class="bi bi-plus-circle"></i> Mulai Peminjaman untuk Siswa Ini
                                </a>
                                <a id="btnLinkKembali" href="#" class="btn btn-outline-success flex-grow-1 justify-content-center">
                                    <i class="bi bi-arrow-down-left-square"></i> Proses Pengembalian
                                </a>
                            </div>
                        </div>

                        <!-- Container Hasil Buku (Hidden saat awal) -->
                        <div id="bookResultBox" style="display: none;">
                            <div class="d-flex align-items-center gap-3 p-3 bg-success-subtle border border-success-subtle rounded-3 mb-3">
                                <i class="bi bi-book fs-2 text-success"></i>
                                <div>
                                    <h5 class="mb-0 fw-bold text-success">QR Code Buku Terverifikasi</h5>
                                    <small class="text-muted">LIBRARY-BOOK terdeteksi valid di katalog buku</small>
                                </div>
                            </div>
                            <div class="d-flex gap-4 align-items-start mb-3">
                                <img id="resBookCover" src="" alt="Cover Buku" class="rounded-3 shadow-sm border" style="width: 95px; height: 135px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h4 id="resBookJudul" class="fw-bold mb-1">-</h4>
                                    <div class="badge bg-success text-white mb-2" id="resBookKode">-</div>
                                    <table class="table table-sm table-borderless small mb-0">
                                        <tr><th class="text-muted" style="width: 100px;">Pengarang:</th><td id="resBookPengarang">-</td></tr>
                                        <tr><th class="text-muted">Kategori:</th><td id="resBookKategori">-</td></tr>
                                        <tr><th class="text-muted">Lokasi Rak:</th><td id="resBookRak">-</td></tr>
                                        <tr><th class="text-muted">Ketersediaan:</th><td id="resBookStok">-</td></tr>
                                    </table>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a id="btnLinkBukuDetail" href="#" class="btn btn-outline-primary flex-grow-1 justify-content-center">
                                    <i class="bi bi-info-circle"></i> Lihat Detail Katalog
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div> <!-- /page-container -->

<!-- Logika Scanner Terpadu -->
<script>
let html5QrCode = null;
let isScanning = false;

document.addEventListener('DOMContentLoaded', () => {
    initCameraDevices();

    document.getElementById('btnStartScan').addEventListener('click', startScanner);
    document.getElementById('btnStopScan').addEventListener('click', stopScanner);
    document.getElementById('btnProcessManual').addEventListener('click', () => {
        const val = document.getElementById('manualInput').value.trim();
        if (val) processQrToken(val);
    });

    document.getElementById('manualInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = document.getElementById('manualInput').value.trim();
            if (val) processQrToken(val);
        }
    });
});

function initCameraDevices() {
    if (typeof Html5Qrcode === 'undefined') {
        console.warn("Library Html5Qrcode belum termuat.");
        return;
    }

    Html5Qrcode.getCameras().then(devices => {
        const select = document.getElementById('cameraSelection');
        select.innerHTML = '';
        if (devices && devices.length) {
            devices.forEach(dev => {
                const opt = document.createElement('option');
                opt.value = dev.id;
                opt.text = dev.label || `Kamera ${select.options.length + 1}`;
                select.appendChild(opt);
            });
        } else {
            select.innerHTML = '<option value="">Tidak ada kamera terdeteksi</option>';
        }
    }).catch(err => {
        console.error("Gagal mendapatkan kamera:", err);
        document.getElementById('cameraSelection').innerHTML = '<option value="">Izin kamera diperlukan</option>';
    });
}

function startScanner() {
    const select = document.getElementById('cameraSelection');
    const cameraId = select.value;
    if (!cameraId) {
        showToast('warning', 'Pilih kamera atau pastikan perangkat memiliki kamera.');
        return;
    }

    html5QrCode = new Html5Qrcode("qrReader");
    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    document.getElementById('cameraPlaceholder').style.display = 'none';
    document.getElementById('scannerOverlayLine').style.display = 'block';

    html5QrCode.start(
        cameraId,
        config,
        (decodedText, decodedResult) => {
            // Suara bip atau notifikasi
            processQrToken(decodedText);
            // Jeda sebentar agar tidak memicu scan beruntun
            pauseScanningTemporarily();
        },
        (errorMessage) => {
            // Error biasa saat kamera mencari QR, abaikan
        }
    ).then(() => {
        isScanning = true;
        document.getElementById('btnStartScan').style.display = 'none';
        document.getElementById('btnStopScan').style.display = 'inline-flex';
        const badge = document.getElementById('scanStatusBadge');
        badge.className = 'badge bg-success';
        badge.textContent = 'Kamera Aktif & Memindai';
    }).catch(err => {
        console.error("Gagal memulai scanner:", err);
        showToast('danger', 'Gagal membuka kamera: ' . err);
        document.getElementById('cameraPlaceholder').style.display = 'block';
        document.getElementById('scannerOverlayLine').style.display = 'none';
    });
}

function stopScanner() {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(() => {
            isScanning = false;
            document.getElementById('btnStartScan').style.display = 'inline-flex';
            document.getElementById('btnStopScan').style.display = 'none';
            document.getElementById('cameraPlaceholder').style.display = 'block';
            document.getElementById('scannerOverlayLine').style.display = 'none';
            const badge = document.getElementById('scanStatusBadge');
            badge.className = 'badge bg-secondary';
            badge.textContent = 'Kamera Tidak Aktif';
        }).catch(err => {
            console.error("Gagal menghentikan scanner:", err);
        });
    }
}

let isPaused = false;
function pauseScanningTemporarily() {
    isPaused = true;
    setTimeout(() => { isPaused = false; }, 2000);
}

function processQrToken(qrString) {
    if (isPaused) return;

    fetch(`${window.BASE_URL}/api/scan_handler.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            qr_data: qrString,
            action: 'Halaman Scanner Terpadu'
        })
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('scanEmptyState').style.display = 'none';
        document.getElementById('memberResultBox').style.display = 'none';
        document.getElementById('bookResultBox').style.display = 'none';

        if (!data.success) {
            showToast('danger', data.message || 'QR Code tidak dikenali.');
            document.getElementById('resultBadge').className = 'badge bg-danger text-white';
            document.getElementById('resultBadge').textContent = 'Tidak Dikenal';
            return;
        }

        showToast('success', data.message);

        // 1. JIKA HASIL ADALAH ANGGOTA
        if (data.type === 'MEMBER') {
            const m = data.data;
            document.getElementById('resultBadge').className = 'badge bg-primary text-white';
            document.getElementById('resultBadge').textContent = 'Anggota Perpustakaan';

            document.getElementById('resMemberNama').textContent = m.nama;
            document.getElementById('resMemberNo').textContent = m.nomor_anggota;
            document.getElementById('resMemberIdentitas').textContent = m.identitas_no;
            document.getElementById('resMemberJenis').textContent = m.jenis_anggota;
            document.getElementById('resMemberKelas').textContent = `${m.nama_kelas} (${m.kode_jurusan})`;
            document.getElementById('resMemberPinjam').textContent = `${m.total_pinjam} / Maks ${m.max_pinjam} Buku`;
            document.getElementById('resMemberFoto').src = m.foto_url;

            document.getElementById('btnLinkPinjam').href = `${window.BASE_URL}/index.php?page=peminjaman_create&anggota_id=${m.id}`;
            document.getElementById('btnLinkKembali').href = `${window.BASE_URL}/index.php?page=pengembalian&search=${encodeURIComponent(m.nomor_anggota)}`;

            document.getElementById('memberResultBox').style.display = 'block';
        }

        // 2. JIKA HASIL ADALAH BUKU
        if (data.type === 'BOOK') {
            const b = data.data;
            document.getElementById('resultBadge').className = 'badge bg-success text-white';
            document.getElementById('resultBadge').textContent = 'Buku Perpustakaan';

            document.getElementById('resBookJudul').textContent = b.judul;
            document.getElementById('resBookKode').textContent = b.kode_buku;
            document.getElementById('resBookPengarang').textContent = b.nama_pengarang;
            document.getElementById('resBookKategori').textContent = b.nama_kategori;
            document.getElementById('resBookRak').textContent = `${b.nama_rak} (${b.kode_rak})`;
            document.getElementById('resBookStok').textContent = `${b.stok_tersedia} dari total ${b.stok_total} eksemplar`;
            document.getElementById('resBookCover').src = b.cover_url;

            document.getElementById('btnLinkBukuDetail').href = `${window.BASE_URL}/index.php?page=buku_detail&id=${b.id}`;

            document.getElementById('bookResultBox').style.display = 'block';
        }
    })
    .catch(err => {
        console.error("Scan processing error:", err);
        showToast('danger', 'Gagal memproses data pemindaian.');
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
