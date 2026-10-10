<?php
/**
 * Model Pengembalian, Perpanjangan & Denda
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class Pengembalian {
    private PDO $db;

    public function __construct() {
        $this->db = get_db();
    }

    /**
     * Generate Nomor Pengembalian Otomatis: KMB-YYYYMMDD-XXXX
     */
    public function generateNomorPengembalian(): string {
        $prefix = 'KMB-' . date('Ymd') . '-';
        $stmt = $this->db->prepare("
            SELECT nomor_pengembalian 
            FROM pengembalian 
            WHERE nomor_pengembalian LIKE :pfx 
            ORDER BY nomor_pengembalian DESC 
            LIMIT 1
        ");
        $stmt->execute([':pfx' => $prefix . '%']);
        $lastNo = $stmt->fetchColumn();

        if ($lastNo) {
            $lastSeq = (int)substr($lastNo, -4);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
    }

    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "
            SELECT k.*, p.nomor_transaksi, p.tanggal_pinjam, p.tanggal_jatuh_tempo,
                   a.nama AS nama_anggota, a.nomor_anggota, a.identitas_no,
                   u.nama_lengkap AS nama_petugas 
            FROM pengembalian k 
            JOIN peminjaman p ON k.peminjaman_id = p.id 
            JOIN anggota a ON p.anggota_id = a.id 
            JOIN users u ON k.petugas_id = u.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (k.nomor_pengembalian LIKE :search OR p.nomor_transaksi LIKE :search OR a.nama LIKE :search OR a.nomor_anggota LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['status_denda'])) {
            $sql .= " AND k.status_denda = :status_denda";
            $params[':status_denda'] = $filters['status_denda'];
        }

        if (!empty($filters['tgl_awal']) && !empty($filters['tgl_akhir'])) {
            $sql .= " AND k.tanggal_kembali BETWEEN :tgl_awal AND :tgl_akhir";
            $params[':tgl_awal'] = $filters['tgl_awal'];
            $params[':tgl_akhir'] = $filters['tgl_akhir'];
        }

        $sql .= " ORDER BY k.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(array $filters = []): int {
        $sql = "
            SELECT COUNT(*) AS total 
            FROM pengembalian k 
            JOIN peminjaman p ON k.peminjaman_id = p.id 
            JOIN anggota a ON p.anggota_id = a.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (k.nomor_pengembalian LIKE :search OR p.nomor_transaksi LIKE :search OR a.nama LIKE :search OR a.nomor_anggota LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['status_denda'])) {
            $sql .= " AND k.status_denda = :status_denda";
            $params[':status_denda'] = $filters['status_denda'];
        }

        if (!empty($filters['tgl_awal']) && !empty($filters['tgl_akhir'])) {
            $sql .= " AND k.tanggal_kembali BETWEEN :tgl_awal AND :tgl_akhir";
            $params[':tgl_awal'] = $filters['tgl_awal'];
            $params[':tgl_akhir'] = $filters['tgl_akhir'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT k.*, p.nomor_transaksi, p.tanggal_pinjam, p.tanggal_jatuh_tempo,
                   a.nama AS nama_anggota, a.nomor_anggota, a.identitas_no, a.jenis_anggota,
                   k2.nama_kelas, j.kode_jurusan,
                   u.nama_lengkap AS nama_petugas 
            FROM pengembalian k 
            JOIN peminjaman p ON k.peminjaman_id = p.id 
            JOIN anggota a ON p.anggota_id = a.id 
            LEFT JOIN kelas k2 ON a.kelas_id = k2.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            JOIN users u ON k.petugas_id = u.id 
            WHERE k.id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getDetails(int $pengembalianId): array {
        $stmt = $this->db->prepare("
            SELECT kd.*, b.judul, b.kode_buku, b.isbn, e.kode_eksemplar 
            FROM pengembalian_detail kd 
            JOIN buku b ON kd.buku_id = b.id 
            LEFT JOIN buku_eksemplar e ON kd.eksemplar_id = e.id 
            WHERE kd.pengembalian_id = :kid 
            ORDER BY kd.id ASC
        ");
        $stmt->execute([':kid' => $pengembalianId]);
        return $stmt->fetchAll();
    }

    /**
     * Hitung keterlambatan dan denda dinamis
     */
    public function calculateDenda(string $jatuhTempo, string $tanggalKembali, string $kondisiBuku = 'Baik'): array {
        $dendaPerHari = (float)get_setting('denda_per_hari', '1000');
        $dendaRusakRingan = (float)get_setting('denda_rusak_ringan', '15000');
        $dendaRusakBerat = (float)get_setting('denda_rusak_berat', '50000');
        $dendaHilang = (float)get_setting('denda_hilang', '100000');

        $tglTempo = new DateTime($jatuhTempo);
        $tglKembali = new DateTime($tanggalKembali);

        $terlambatHari = 0;
        if ($tglKembali > $tglTempo) {
            $diff = $tglKembali->diff($tglTempo);
            $terlambatHari = (int)$diff->days;
        }

        $dendaTerlambat = $terlambatHari * $dendaPerHari;
        $dendaKondisi = 0.0;

        switch ($kondisiBuku) {
            case 'Rusak Ringan':
                $dendaKondisi = $dendaRusakRingan;
                break;
            case 'Rusak Berat':
                $dendaKondisi = $dendaRusakBerat;
                break;
            case 'Hilang':
                $dendaKondisi = $dendaHilang;
                break;
            default:
                $dendaKondisi = 0.0;
                break;
        }

        return [
            'terlambat_hari'  => $terlambatHari,
            'denda_terlambat' => $dendaTerlambat,
            'denda_kondisi'   => $dendaKondisi,
            'total_denda'     => $dendaTerlambat + $dendaKondisi
        ];
    }

    /**
     * Proses Pengembalian Buku
     * $returnedItems: array of [
     *    'peminjaman_detail_id' => int,
     *    'buku_id' => int,
     *    'eksemplar_id' => ?int,
     *    'kondisi_buku' => 'Baik'|'Rusak Ringan'|'Rusak Berat'|'Hilang',
     *    'catatan' => string
     * ]
     */
    public function processReturn(int $peminjamanId, int $petugasId, string $tanggalKembali, array $returnedItems, float $dendaDibayar = 0.0, ?string $catatanUmum = null): int {
        if (empty($returnedItems)) {
            throw new Exception("Pilih minimal satu buku untuk dikembalikan.");
        }

        $this->db->beginTransaction();
        try {
            // Ambil data peminjaman
            $stmtP = $this->db->prepare("SELECT * FROM peminjaman WHERE id = :id FOR UPDATE");
            $stmtP->execute([':id' => $peminjamanId]);
            $pinjam = $stmtP->fetch();
            if (!$pinjam) {
                throw new Exception("Data peminjaman tidak ditemukan.");
            }

            $nomorPengembalian = $this->generateNomorPengembalian();
            $maxTerlambatHari = 0;
            $grandTotalDenda = 0.0;

            // Hitung denda untuk setiap item
            $processedItems = [];
            foreach ($returnedItems as $item) {
                $calc = $this->calculateDenda($pinjam['tanggal_jatuh_tempo'], $tanggalKembali, $item['kondisi_buku']);
                $grandTotalDenda += $calc['total_denda'];
                if ($calc['terlambat_hari'] > $maxTerlambatHari) {
                    $maxTerlambatHari = $calc['terlambat_hari'];
                }
                $processedItems[] = array_merge($item, $calc);
            }

            $statusDenda = 'Tidak Ada';
            if ($grandTotalDenda > 0) {
                $statusDenda = ($dendaDibayar >= $grandTotalDenda) ? 'Lunas' : 'Belum Lunas';
            }

            // Simpan header pengembalian
            $stmtIns = $this->db->prepare("
                INSERT INTO pengembalian (
                    nomor_pengembalian, peminjaman_id, petugas_id, tanggal_kembali,
                    terlambat_hari, total_denda, denda_dibayar, status_denda, catatan
                ) VALUES (
                    :no_kmb, :pid, :petugas_id, :tgl_kmb,
                    :terlambat, :total_denda, :dibayar, :status_denda, :catatan
                )
            ");
            $stmtIns->execute([
                ':no_kmb'       => $nomorPengembalian,
                ':pid'          => $peminjamanId,
                ':petugas_id'   => $petugasId,
                ':tgl_kmb'      => $tanggalKembali,
                ':terlambat'    => $maxTerlambatHari,
                ':total_denda'  => $grandTotalDenda,
                ':dibayar'      => $dendaDibayar,
                ':status_denda' => $statusDenda,
                ':catatan'      => $catatanUmum
            ]);
            $pengembalianId = (int)$this->db->lastInsertId();

            // Simpan detail & update status buku
            $stmtInsDet = $this->db->prepare("
                INSERT INTO pengembalian_detail (
                    pengembalian_id, peminjaman_detail_id, buku_id, eksemplar_id,
                    kondisi_buku, denda_terlambat, denda_kondisi, subtotal_denda, catatan
                ) VALUES (
                    :kid, :pdid, :bid, :eid,
                    :kondisi, :d_terlambat, :d_kondisi, :subtotal, :catatan
                )
            ");

            $stmtUpdPD = $this->db->prepare("
                UPDATE peminjaman_detail 
                SET tanggal_kembali_aktual = :tgl, status = 'Dikembalikan' 
                WHERE id = :pdid
            ");

            $stmtUpdBuku = $this->db->prepare("
                UPDATE buku 
                SET stok_tersedia = LEAST(stok_total, stok_tersedia + 1),
                    status = 'Tersedia' 
                WHERE id = :bid
            ");

            $stmtUpdEx = $this->db->prepare("
                UPDATE buku_eksemplar 
                SET status = :status, kondisi = :kondisi 
                WHERE id = :eid
            ");

            foreach ($processedItems as $pItem) {
                $stmtInsDet->execute([
                    ':kid'         => $pengembalianId,
                    ':pdid'        => (int)$pItem['peminjaman_detail_id'],
                    ':bid'         => (int)$pItem['buku_id'],
                    ':eid'         => !empty($pItem['eksemplar_id']) ? (int)$pItem['eksemplar_id'] : null,
                    ':kondisi'     => $pItem['kondisi_buku'],
                    ':d_terlambat' => $pItem['denda_terlambat'],
                    ':d_kondisi'   => $pItem['denda_kondisi'],
                    ':subtotal'    => $pItem['total_denda'],
                    ':catatan'     => $pItem['catatan'] ?? null
                ]);

                // Update detail peminjaman
                $stmtUpdPD->execute([
                    ':tgl'  => $tanggalKembali,
                    ':pdid' => (int)$pItem['peminjaman_detail_id']
                ]);

                // Jika buku tidak hilang, kembalikan stok
                if ($pItem['kondisi_buku'] !== 'Hilang') {
                    $stmtUpdBuku->execute([':bid' => (int)$pItem['buku_id']]);
                }

                // Update eksemplar jika ada
                if (!empty($pItem['eksemplar_id'])) {
                    $exStatus = ($pItem['kondisi_buku'] === 'Hilang') ? 'Hilang' : 
                               (($pItem['kondisi_buku'] === 'Rusak Berat') ? 'Perbaikan' : 'Tersedia');
                    $stmtUpdEx->execute([
                        ':status'  => $exStatus,
                        ':kondisi' => $pItem['kondisi_buku'],
                        ':eid'     => (int)$pItem['eksemplar_id']
                    ]);
                }
            }

            // Cek apakah seluruh detail peminjaman telah dikembalikan
            $stmtRem = $this->db->prepare("SELECT COUNT(*) FROM peminjaman_detail WHERE peminjaman_id = :pid AND status = 'Dipinjam'");
            $stmtRem->execute([':pid' => $peminjamanId]);
            $remaining = (int)$stmtRem->fetchColumn();

            if ($remaining === 0) {
                $stmtUpdPinjam = $this->db->prepare("UPDATE peminjaman SET status = 'Selesai' WHERE id = :pid");
                $stmtUpdPinjam->execute([':pid' => $peminjamanId]);
            }

            // Jika ada denda, catat ke tabel denda
            if ($grandTotalDenda > 0) {
                $stmtDenda = $this->db->prepare("
                    INSERT INTO denda (pengembalian_id, anggota_id, jenis_denda, jumlah_denda, status_bayar, tanggal_bayar, petugas_id) 
                    VALUES (:kid, :aid, :jenis, :jml, :sbayar, :tglbyr, :petugas_id)
                ");
                $jenisDenda = ($maxTerlambatHari > 0) ? 'Keterlambatan' : 'Kerusakan';
                $tglBayar = ($statusDenda === 'Lunas') ? $tanggalKembali : null;
                $stmtDenda->execute([
                    ':kid'        => $pengembalianId,
                    ':aid'        => $pinjam['anggota_id'],
                    ':jenis'      => $jenisDenda,
                    ':jml'        => $grandTotalDenda,
                    ':sbayar'     => $statusDenda,
                    ':tglbyr'     => $tglBayar,
                    ':petugas_id' => $petugasId
                ]);
            }

            $this->db->commit();
            return $pengembalianId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Failed processing return: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perpanjangan Masa Peminjaman
     */
    public function extendLoan(int $peminjamanId, int $petugasId, int $tambahHari = 7, ?string $catatan = null): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM peminjaman WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $peminjamanId]);
            $pinjam = $stmt->fetch();
            if (!$pinjam) {
                throw new Exception("Peminjaman tidak ditemukan.");
            }

            // Hitung kali perpanjangan ke berapa
            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM perpanjangan WHERE peminjaman_id = :pid");
            $stmtCount->execute([':pid' => $peminjamanId]);
            $perpanjanganKe = ((int)$stmtCount->fetchColumn()) + 1;

            $jatuhTempoLama = $pinjam['tanggal_jatuh_tempo'];
            $jatuhTempoBaru = date('Y-m-d', strtotime("+$tambahHari days", strtotime($jatuhTempoLama)));

            // Simpan riwayat perpanjangan
            $stmtIns = $this->db->prepare("
                INSERT INTO perpanjangan (peminjaman_id, petugas_id, tanggal_perpanjangan, jatuh_tempo_lama, jatuh_tempo_baru, perpanjangan_ke, catatan) 
                VALUES (:pid, :petugas_id, :tgl, :lama, :baru, :ke, :catatan)
            ");
            $stmtIns->execute([
                ':pid'        => $peminjamanId,
                ':petugas_id' => $petugasId,
                ':tgl'        => date('Y-m-d'),
                ':lama'       => $jatuhTempoLama,
                ':baru'       => $jatuhTempoBaru,
                ':ke'         => $perpanjanganKe,
                ':catatan'    => $catatan
            ]);

            // Update tanggal jatuh tempo di peminjaman dan reset status ke 'Dipinjam'
            $stmtUpd = $this->db->prepare("
                UPDATE peminjaman 
                SET tanggal_jatuh_tempo = :baru, status = 'Dipinjam' 
                WHERE id = :pid
            ");
            $stmtUpd->execute([':baru' => $jatuhTempoBaru, ':pid' => $peminjamanId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Failed extending loan: " . $e->getMessage());
            throw $e;
        }
    }

    public function getExtensionHistory(int $peminjamanId): array {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap AS nama_petugas 
            FROM perpanjangan p 
            JOIN users u ON p.petugas_id = u.id 
            WHERE p.peminjaman_id = :pid 
            ORDER BY p.id ASC
        ");
        $stmt->execute([':pid' => $peminjamanId]);
        return $stmt->fetchAll();
    }
}
