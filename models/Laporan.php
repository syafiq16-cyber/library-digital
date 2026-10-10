<?php
/**
 * Model Laporan & Analitik Statistik
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/database.php';

class Laporan {
    private PDO $db;

    public function __construct() {
        $this->db = get_db();
    }

    /**
     * Dapatkan KPI Dashboard Utama
     */
    public function getDashboardKPI(): array {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');

        // Total Judul Buku & Total Eksemplar
        $qBuku = $this->db->query("SELECT COUNT(*) AS total_judul, SUM(stok_total) AS total_buku, SUM(stok_tersedia) AS total_tersedia FROM buku")->fetch();

        // Total Anggota Aktif
        $totalAnggota = (int)$this->db->query("SELECT COUNT(*) FROM anggota WHERE status_aktif = 1")->fetchColumn();

        // Total Buku Sedang Dipinjam
        $totalDipinjam = (int)$this->db->query("SELECT COUNT(*) FROM peminjaman_detail WHERE status = 'Dipinjam'")->fetchColumn();

        // Total Peminjaman Terlambat
        $totalTerlambat = (int)$this->db->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'Terlambat' OR (status = 'Dipinjam' AND tanggal_jatuh_tempo < '$today')")->fetchColumn();

        // Peminjaman Hari Ini
        $stmtPjmToday = $this->db->prepare("SELECT COUNT(*) FROM peminjaman WHERE tanggal_pinjam = :tgl");
        $stmtPjmToday->execute([':tgl' => $today]);
        $pjmHariIni = (int)$stmtPjmToday->fetchColumn();

        // Peminjaman Bulan Ini
        $stmtPjmMonth = $this->db->prepare("SELECT COUNT(*) FROM peminjaman WHERE DATE_FORMAT(tanggal_pinjam, '%Y-%m') = :bln");
        $stmtPjmMonth->execute([':bln' => $thisMonth]);
        $pjmBulanIni = (int)$stmtPjmMonth->fetchColumn();

        // Pengembalian Bulan Ini
        $stmtKmbMonth = $this->db->prepare("SELECT COUNT(*) FROM pengembalian WHERE DATE_FORMAT(tanggal_kembali, '%Y-%m') = :bln");
        $stmtKmbMonth->execute([':bln' => $thisMonth]);
        $kmbBulanIni = (int)$stmtKmbMonth->fetchColumn();

        // Total Denda Terkumpul
        $totalDenda = (float)$this->db->query("SELECT SUM(denda_dibayar) FROM pengembalian")->fetchColumn();

        // Statistik QR Code
        $totalQrAnggota = (int)$this->db->query("SELECT COUNT(*) FROM anggota WHERE qr_token IS NOT NULL AND qr_token != ''")->fetchColumn();
        $totalQrBuku = (int)$this->db->query("SELECT COUNT(*) FROM buku WHERE qr_token IS NOT NULL AND qr_token != ''")->fetchColumn();
        $totalQrEksemplar = (int)$this->db->query("SELECT COUNT(*) FROM buku_eksemplar WHERE qr_token IS NOT NULL AND qr_token != ''")->fetchColumn();
        $stmtScanToday = $this->db->prepare("SELECT COUNT(*) FROM scan_log WHERE DATE(created_at) = :tgl");
        $stmtScanToday->execute([':tgl' => $today]);
        $scanHariIni = (int)$stmtScanToday->fetchColumn();

        return [
            'total_judul'        => (int)($qBuku['total_judul'] ?? 0),
            'total_buku'         => (int)($qBuku['total_buku'] ?? 0),
            'total_tersedia'     => (int)($qBuku['total_tersedia'] ?? 0),
            'total_anggota'      => $totalAnggota,
            'total_dipinjam'     => $totalDipinjam,
            'total_terlambat'    => $totalTerlambat,
            'pinjam_hari_ini'    => $pjmHariIni,
            'pinjam_bulan_ini'   => $pjmBulanIni,
            'kembali_bulan_ini'  => $kmbBulanIni,
            'total_denda'        => $totalDenda,
            'total_qr_anggota'   => $totalQrAnggota,
            'total_qr_buku'      => $totalQrBuku + $totalQrEksemplar,
            'scan_hari_ini'      => $scanHariIni
        ];
    }

    /**
     * Dapatkan Data Statistik 12 Bulan Terakhir untuk Chart.js
     */
    public function getMonthlyChartData(): array {
        $labels = [];
        $peminjamanData = [];
        $pengembalianData = [];

        // Buat daftar 12 bulan terakhir
        for ($i = 11; $i >= 0; $i--) {
            $monthTime = strtotime("-$i month");
            $ym = date('Y-m', $monthTime);
            $label = date('M Y', $monthTime);
            $labels[] = $label;

            // Query Peminjaman
            $stmt1 = $this->db->prepare("SELECT COUNT(*) FROM peminjaman WHERE DATE_FORMAT(tanggal_pinjam, '%Y-%m') = :ym");
            $stmt1->execute([':ym' => $ym]);
            $peminjamanData[] = (int)$stmt1->fetchColumn();

            // Query Pengembalian
            $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM pengembalian WHERE DATE_FORMAT(tanggal_kembali, '%Y-%m') = :ym");
            $stmt2->execute([':ym' => $ym]);
            $pengembalianData[] = (int)$stmt2->fetchColumn();
        }

        return [
            'labels'       => $labels,
            'peminjaman'   => $peminjamanData,
            'pengembalian' => $pengembalianData
        ];
    }

    /**
     * Data Distribusi Kategori Buku untuk Grafik Donat
     */
    public function getCategoryChartData(): array {
        $stmt = $this->db->query("
            SELECT k.nama_kategori, COUNT(b.id) AS total 
            FROM kategori_buku k 
            JOIN buku b ON k.id = b.kategori_id 
            GROUP BY k.id 
            ORDER BY total DESC 
            LIMIT 7
        ");
        $labels = [];
        $data = [];
        while ($row = $stmt->fetch()) {
            $labels[] = $row['nama_kategori'];
            $data[] = (int)$row['total'];
        }
        return ['labels' => $labels, 'data' => $data];
    }

    // ==========================================
    // 12 METODE LAPORAN KOMPREHENSIF
    // ==========================================

    // 1. Laporan Data Buku
    public function laporanBuku(array $filters = []): array {
        $sql = "
            SELECT b.*, k.nama_kategori, p.nama_pengarang, pub.nama_penerbit, r.nama_rak, r.kode_rak 
            FROM buku b 
            LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
            LEFT JOIN pengarang p ON b.pengarang_id = p.id 
            LEFT JOIN penerbit pub ON b.penerbit_id = pub.id 
            LEFT JOIN rak r ON b.rak_id = r.id 
            WHERE 1=1
        ";
        $params = [];
        if (!empty($filters['kategori_id'])) {
            $sql .= " AND b.kategori_id = :kid";
            $params[':kid'] = (int)$filters['kategori_id'];
        }
        if (!empty($filters['rak_id'])) {
            $sql .= " AND b.rak_id = :rid";
            $params[':rid'] = (int)$filters['rak_id'];
        }
        if (!empty($filters['tahun'])) {
            $sql .= " AND b.tahun_terbit = :tahun";
            $params[':tahun'] = (int)$filters['tahun'];
        }
        $sql .= " ORDER BY b.kode_buku ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // 2. Laporan Data Anggota
    public function laporanAnggota(array $filters = []): array {
        $sql = "
            SELECT a.*, k.nama_kelas, j.nama_jurusan, j.kode_jurusan 
            FROM anggota a 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            WHERE 1=1
        ";
        $params = [];
        if (!empty($filters['jenis_anggota'])) {
            $sql .= " AND a.jenis_anggota = :jenis";
            $params[':jenis'] = $filters['jenis_anggota'];
        }
        if (!empty($filters['kelas_id'])) {
            $sql .= " AND a.kelas_id = :kid";
            $params[':kid'] = (int)$filters['kelas_id'];
        }
        if (!empty($filters['jurusan_id'])) {
            $sql .= " AND a.jurusan_id = :jid";
            $params[':jid'] = (int)$filters['jurusan_id'];
        }
        $sql .= " ORDER BY a.nomor_anggota ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // 3. Laporan Peminjaman
    public function laporanPeminjaman(array $filters = []): array {
        $sql = "
            SELECT p.*, a.nama AS nama_anggota, a.nomor_anggota, a.identitas_no, 
                   k.nama_kelas, j.kode_jurusan, u.nama_lengkap AS nama_petugas 
            FROM peminjaman p 
            JOIN anggota a ON p.anggota_id = a.id 
            JOIN users u ON p.petugas_id = u.id 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            WHERE 1=1
        ";
        $params = [];
        if (!empty($filters['tgl_awal']) && !empty($filters['tgl_akhir'])) {
            $sql .= " AND p.tanggal_pinjam BETWEEN :t1 AND :t2";
            $params[':t1'] = $filters['tgl_awal'];
            $params[':t2'] = $filters['tgl_akhir'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        $sql .= " ORDER BY p.tanggal_pinjam DESC, p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // 4. Laporan Pengembalian
    public function laporanPengembalian(array $filters = []): array {
        $sql = "
            SELECT k.*, p.nomor_transaksi, a.nama AS nama_anggota, a.nomor_anggota, 
                   u.nama_lengkap AS nama_petugas 
            FROM pengembalian k 
            JOIN peminjaman p ON k.peminjaman_id = p.id 
            JOIN anggota a ON p.anggota_id = a.id 
            JOIN users u ON k.petugas_id = u.id 
            WHERE 1=1
        ";
        $params = [];
        if (!empty($filters['tgl_awal']) && !empty($filters['tgl_akhir'])) {
            $sql .= " AND k.tanggal_kembali BETWEEN :t1 AND :t2";
            $params[':t1'] = $filters['tgl_awal'];
            $params[':t2'] = $filters['tgl_akhir'];
        }
        if (!empty($filters['status_denda'])) {
            $sql .= " AND k.status_denda = :sd";
            $params[':sd'] = $filters['status_denda'];
        }
        $sql .= " ORDER BY k.tanggal_kembali DESC, k.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // 5. Laporan Keterlambatan
    public function laporanKeterlambatan(): array {
        $today = date('Y-m-d');
        $sql = "
            SELECT p.*, a.nama AS nama_anggota, a.nomor_anggota, a.nomor_hp, 
                   k.nama_kelas, j.kode_jurusan,
                   DATEDIFF('$today', p.tanggal_jatuh_tempo) AS telat_hari 
            FROM peminjaman p 
            JOIN anggota a ON p.anggota_id = a.id 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            WHERE (p.status = 'Terlambat' OR (p.status = 'Dipinjam' AND p.tanggal_jatuh_tempo < '$today'))
            ORDER BY telat_hari DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    // 6. Laporan Denda
    public function laporanDenda(array $filters = []): array {
        $sql = "
            SELECT d.*, a.nama AS nama_anggota, a.nomor_anggota, u.nama_lengkap AS nama_petugas,
                   k.nomor_pengembalian 
            FROM denda d 
            JOIN anggota a ON d.anggota_id = a.id 
            LEFT JOIN pengembalian k ON d.pengembalian_id = k.id 
            LEFT JOIN users u ON d.petugas_id = u.id 
            WHERE 1=1
        ";
        $params = [];
        if (!empty($filters['tgl_awal']) && !empty($filters['tgl_akhir'])) {
            $sql .= " AND DATE(d.created_at) BETWEEN :t1 AND :t2";
            $params[':t1'] = $filters['tgl_awal'];
            $params[':t2'] = $filters['tgl_akhir'];
        }
        if (!empty($filters['status_bayar'])) {
            $sql .= " AND d.status_bayar = :sb";
            $params[':sb'] = $filters['status_bayar'];
        }
        $sql .= " ORDER BY d.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // 7. Laporan Buku Rusak
    public function laporanBukuRusak(): array {
        return $this->db->query("
            SELECT e.kode_eksemplar, e.kondisi, e.status, b.kode_buku, b.judul, 
                   k.nama_kategori, r.nama_rak 
            FROM buku_eksemplar e 
            JOIN buku b ON e.buku_id = b.id 
            LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
            LEFT JOIN rak r ON b.rak_id = r.id 
            WHERE e.kondisi IN ('Rusak Ringan', 'Rusak Berat')
            ORDER BY e.id DESC
        ")->fetchAll();
    }

    // 8. Laporan Buku Hilang
    public function laporanBukuHilang(): array {
        return $this->db->query("
            SELECT e.kode_eksemplar, e.kondisi, e.status, b.kode_buku, b.judul, b.harga,
                   k.nama_kategori, r.nama_rak 
            FROM buku_eksemplar e 
            JOIN buku b ON e.buku_id = b.id 
            LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
            LEFT JOIN rak r ON b.rak_id = r.id 
            WHERE e.kondisi = 'Hilang' OR e.status = 'Hilang'
            ORDER BY e.id DESC
        ")->fetchAll();
    }

    // 9. Laporan Pengunjung
    public function laporanPengunjung(array $filters = []): array {
        $sql = "
            SELECT p.*, k.nama_kelas, j.kode_jurusan 
            FROM pengunjung p 
            LEFT JOIN kelas k ON p.kelas_id = k.id 
            LEFT JOIN jurusan j ON p.jurusan_id = j.id 
            WHERE 1=1
        ";
        $params = [];
        if (!empty($filters['tgl_awal']) && !empty($filters['tgl_akhir'])) {
            $sql .= " AND p.tanggal BETWEEN :t1 AND :t2";
            $params[':t1'] = $filters['tgl_awal'];
            $params[':t2'] = $filters['tgl_akhir'];
        }
        if (!empty($filters['jenis_pengunjung'])) {
            $sql .= " AND p.jenis_pengunjung = :jp";
            $params[':jp'] = $filters['jenis_pengunjung'];
        }
        $sql .= " ORDER BY p.tanggal DESC, p.waktu_masuk DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // 10. Laporan Buku Terpopuler
    public function laporanBukuTerpopuler(int $limit = 20): array {
        $stmt = $this->db->prepare("
            SELECT b.id, b.kode_buku, b.judul, b.isbn, k.nama_kategori, p.nama_pengarang, 
                   pub.nama_penerbit, COUNT(pd.id) AS total_dipinjam 
            FROM buku b 
            LEFT JOIN peminjaman_detail pd ON b.id = pd.buku_id 
            LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
            LEFT JOIN pengarang p ON b.pengarang_id = p.id 
            LEFT JOIN penerbit pub ON b.penerbit_id = pub.id 
            GROUP BY b.id 
            ORDER BY total_dipinjam DESC, b.judul ASC 
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 11. Laporan Anggota Paling Aktif
    public function laporanAnggotaAktif(int $limit = 20): array {
        $stmt = $this->db->prepare("
            SELECT a.id, a.nomor_anggota, a.identitas_no, a.nama, a.jenis_anggota,
                   k.nama_kelas, j.kode_jurusan, COUNT(p.id) AS total_transaksi,
                   SUM(p.total_buku) AS total_buku_dipinjam 
            FROM anggota a 
            JOIN peminjaman p ON a.id = p.anggota_id 
            LEFT JOIN kelas k ON a.kelas_id = k.id 
            LEFT JOIN jurusan j ON a.jurusan_id = j.id 
            GROUP BY a.id 
            ORDER BY total_buku_dipinjam DESC, total_transaksi DESC 
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 12. Laporan Ringkasan Statistik Perpustakaan
    public function laporanStatistikUmum(): array {
        return [
            'kpi'          => $this->getDashboardKPI(),
            'kategori'     => $this->getCategoryChartData(),
            'populer_buku' => $this->laporanBukuTerpopuler(5),
            'anggota_top'  => $this->laporanAnggotaAktif(5)
        ];
    }
}
