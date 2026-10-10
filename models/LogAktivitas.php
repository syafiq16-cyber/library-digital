<?php
/**
 * Model Log Aktivitas (Audit Trail)
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/database.php';

class LogAktivitas {
    private PDO $db;

    public function __construct() {
        $this->db = get_db();
    }

    public function getAll(int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT l.*, u.username, u.nama_lengkap, r.nama_role 
            FROM log_aktivitas l 
            LEFT JOIN users u ON l.user_id = u.id 
            LEFT JOIN roles r ON u.role_id = r.id 
            ORDER BY l.id DESC 
            LIMIT :lim OFFSET :off
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRecentScans(int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT s.*, u.nama_lengkap AS nama_petugas 
            FROM scan_log s 
            LEFT JOIN users u ON s.scanned_by = u.id 
            ORDER BY s.id DESC 
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
