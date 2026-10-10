<?php
/**
 * Layout Footer
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */
$schoolName = get_setting('nama_sekolah', 'SMK Negeri 2 Sumbawa Besar');
?>
    <footer class="app-footer">
        <span>&copy; <?= date('Y') ?> <strong><?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></strong> - Perpustakaan Digital Berbasis Lokal. Semua Hak Dilindungi.</span>
    </footer>
</div><!-- /main-content -->
</div><!-- /app-wrapper -->

<!-- Bootstrap 5 JS Offline -->
<script src="<?= asset_url('vendor/bootstrap.bundle.min.js') ?>"></script>

<!-- Chart.js Offline -->
<script src="<?= asset_url('vendor/chart.umd.min.js') ?>"></script>

<!-- HTML5 QR Code Scanner Offline -->
<script src="<?= asset_url('vendor/html5-qrcode.min.js') ?>"></script>

<!-- QRCode Generator Offline -->
<script src="<?= asset_url('vendor/qrcode.min.js') ?>"></script>

<!-- Custom App Logic -->
<script src="<?= asset_url('js/app.js') ?>"></script>
<script src="<?= asset_url('js/chart-custom.js') ?>"></script>

</body>
</html>
