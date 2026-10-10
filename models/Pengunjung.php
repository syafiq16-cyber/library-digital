<?php
/**
 * Model Pengunjung (Buku Tamu Harian)
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/database.php';

class Pengunjung {
    private PDO $db;

    public function __construct() {
        $this->db = get_db();
    }

    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "
            SELECT p.*, k.nama_kelas, j.kode_jurusan 
            FROM pengunjung p 
            LEFT JOIN kelas k ON p.kelas_id = k.id 
            LEFT JOIN jurusan j ON p.jurusan_id = j.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.nama LIKE :search OR p.identitas_no LIKE :search OR p.keperluan LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['jenis_pengunjung'])) {
            $sql .= " AND p.jenis_pengunjung = :jenis";
            $params[':jenis'] = $filters['jenis_pengunjung'];
        }

        if (!empty($filters['tanggal'])) {
            $sql .= " AND p.tanggal = :tgl";
            $params[':tgl'] = $filters['tanggal'];
        }

        if (!empty($filters['tgl_awal']) && !empty($filters['tgl_akhir'])) {
            $sql .= " AND p.tanggal BETWEEN :tgl_awal AND :tgl_akhir";
            $params[':tgl_awal'] = $filters['tgl_awal'];
            $params[':tgl_akhir'] = $filters['tgl_akhir'];
        }

        $sql .= " ORDER BY p.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM pengunjung p WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.nama LIKE :search OR p.identitas_no LIKE :search OR p.keperluan LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['jenis_pengunjung'])) {
            $sql .= " AND p.jenis_pengunjung = :jenis";
            $params[':jenis'] = $filters['jenis_pengunjung'];
        }

        if (!empty($filters['tanggal'])) {
            $sql .= " AND p.tanggal = :tgl";
            $params[':tgl'] = $filters['tanggal'];
        }

        if (!empty($filters['tgl_awal']) && !empty($filters['tgl_akhir'])) {
            $sql .= " AND p.tanggal BETWEEN :tgl_awal AND :tgl_akhir";
            $params[':tgl_awal'] = $filters['tgl_awal'];
            $params[':tgl_akhir'] = $filters['tgl_akhir'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO pengunjung (identitas_no, nama, jenis_pengunjung, kelas_id, jurusan_id, keperluan, waktu_masuk, waktu_keluar, tanggal, keterangan) 
            VALUES (:idno, :nama, :jenis, :kelas_id, :jurusan_id, :keperluan, :w_masuk, :w_keluar, :tgl, :ket)
        ");
        $stmt->execute([
            ':idno'       => $data['identitas_no'] ?? null,
            ':nama'       => trim($data['nama']),
            ':jenis'      => $data['jenis_pengunjung'] ?? 'Siswa',
            ':kelas_id'   => !empty($data['kelas_id']) ? (int)$data['kelas_id'] : null,
            ':jurusan_id' => !empty($data['jurusan_id']) ? (int)$data['jurusan_id'] : null,
            ':keperluan'  => trim($data['keperluan']),
            ':w_masuk'    => $data['waktu_masuk'] ?? date('H:i:s'),
            ':w_keluar'   => $data['waktu_keluar'] ?? null,
            ':tgl'        => $data['tanggal'] ?? date('Y-m-d'),
            ':ket'        => $data['keterangan'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM pengunjung WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function countToday(): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM pengunjung WHERE tanggal = :tgl");
        $stmt->execute([':tgl' => date('Y-m-d')]);
        return (int)$stmt->fetchColumn();
    }
}
