<?php
/**
 * Helper Sistem Dua QR Code Terpadu
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 *
 * Mendukung:
 * 1. QR Code Anggota: "LIBRARY-MEMBER:{nomor_anggota}"
 * 2. QR Code Buku/Eksemplar: "LIBRARY-BOOK:{kode_buku}"
 */

require_once __DIR__ . '/config.php';

const QR_PREFIX_MEMBER = 'LIBRARY-MEMBER:';
const QR_PREFIX_BOOK   = 'LIBRARY-BOOK:';

/**
 * Buat format token QR Anggota
 */
function make_member_qr_token(string $nomor_anggota): string {
    return QR_PREFIX_MEMBER . trim($nomor_anggota);
}

/**
 * Buat format token QR Buku / Eksemplar
 */
function make_book_qr_token(string $kode_buku): string {
    return QR_PREFIX_BOOK . trim($kode_buku);
}

/**
 * Analisis dan validasi token QR yang discan
 * Returns: ['valid' => bool, 'type' => 'MEMBER'|'BOOK'|'UNKNOWN', 'identifier' => string, 'message' => string]
 */
function parse_and_validate_qr(string $rawQr): array {
    $rawQr = trim($rawQr);

    if (str_starts_with($rawQr, QR_PREFIX_MEMBER)) {
        $identifier = substr($rawQr, strlen(QR_PREFIX_MEMBER));
        return [
            'valid'      => !empty($identifier),
            'type'       => 'MEMBER',
            'token'      => $rawQr,
            'identifier' => $identifier,
            'message'    => 'QR Code Anggota Perpustakaan terverifikasi.'
        ];
    }

    if (str_starts_with($rawQr, QR_PREFIX_BOOK)) {
        $identifier = substr($rawQr, strlen(QR_PREFIX_BOOK));
        return [
            'valid'      => !empty($identifier),
            'type'       => 'BOOK',
            'token'      => $rawQr,
            'identifier' => $identifier,
            'message'    => 'QR Code Buku / Eksemplar terverifikasi.'
        ];
    }

    return [
        'valid'      => false,
        'type'       => 'UNKNOWN',
        'token'      => $rawQr,
        'identifier' => '',
        'message'    => 'QR Code tidak dikenali sebagai QR Perpustakaan SMKN 2 Sumbawa Besar.'
    ];
}

/**
 * Render container elemen QR Code interaktif untuk JavaScript lokal (qrcode.min.js)
 * Bekerja 100% offline tanpa akses internet
 */
function render_qr_code_html(string $token, int $size = 120, string $cssClass = ''): string {
    $uid = 'qr_' . md5($token . microtime(true) . rand(1000, 9999));
    $safeToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<div class="qr-code-wrapper d-inline-block text-center {$cssClass}">
    <div id="{$uid}" class="qr-container bg-white p-2 rounded shadow-sm d-inline-block" data-qr-text="{$safeToken}" data-qr-size="{$size}"></div>
    <div class="small text-muted font-monospace mt-1" style="font-size:10px; word-break:break-all;">{$safeToken}</div>
</div>
HTML;
}

/**
 * Catat log pemindaian QR ke database
 */
function log_scan(string $qrType, string $qrToken, ?int $scannedBy, string $action, string $resultInfo = ''): void {
    try {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO scan_log (qr_type, qr_token, scanned_by, action, result_info) VALUES (:type, :token, :by, :action, :info)");
        $stmt->execute([
            ':type'   => in_array($qrType, ['MEMBER', 'BOOK']) ? $qrType : 'UNKNOWN',
            ':token'  => substr($qrToken, 0, 100),
            ':by'     => $scannedBy,
            ':action' => substr($action, 0, 100),
            ':info'   => substr($resultInfo, 0, 255)
        ]);
    } catch (Exception $e) {
        error_log("Failed to log scan: " . $e->getMessage());
    }
}
