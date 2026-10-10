<?php
/**
 * Model Jurnal Kegiatan Harian Perpustakaan
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/database.php';

class Jurnal {
    private PDO $db;

    public function __construct() {
        $this->db = get_db();
    }

    public function getAll(int $limit = 30, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT j.*, u.nama_lengkap AS nama_petugas 
            FROM jurnal j 
            JOIN users u ON j.petugas_id = u.id 
            ORDER BY j.tanggal DESC, j.id DESC 
            LIMIT :lim OFFSET :off
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO jurnal (tanggal, petugas_id, kegiatan, jumlah_pengunjung, jumlah_peminjaman, jumlah_pengembalian, catatan) 
            VALUES (:tgl, :petugas_id, :kegiatan, :pengunjung, :pinjam, :kembali, :catatan)
        ");
        $stmt->execute([
            ':tgl'        => $data['tanggal'] ?? date('Y-m-d'),
            ':petugas_id' => (int)$data['petugas_id'],
            ':kegiatan'   => trim($data['kegiatan']),
            ':pengunjung' => (int)($data['jumlah_pengunjung'] ?? 0),
            ':pinjam'     => (int)($data['jumlah_peminjaman'] ?? 0),
            ':kembali'    => (int)($data['jumlah_pengembalian'] ?? 0),
            ':catatan'    => $data['catatan'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM jurnal WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
