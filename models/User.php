<?php
/**
 * Model User
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = get_db();
    }

    public function authenticate(string $username, string $password): ?array {
        $stmt = $this->db->prepare("
            SELECT u.*, r.nama_role 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.username = :username AND u.status_aktif = 1 
            LIMIT 1
        ");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT u.id, u.username, u.nama_lengkap, u.email, u.role_id, u.status_aktif, u.created_at, r.nama_role 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            ORDER BY u.id ASC
        ");
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.nama_lengkap, u.email, u.role_id, u.status_aktif, u.created_at, r.nama_role 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function getByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function getRoles(): array {
        return $this->db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password, nama_lengkap, email, role_id, status_aktif) 
            VALUES (:username, :password, :nama_lengkap, :email, :role_id, :status_aktif)
        ");
        $stmt->execute([
            ':username'     => $data['username'],
            ':password'     => password_hash($data['password'], PASSWORD_BCRYPT),
            ':nama_lengkap' => $data['nama_lengkap'],
            ':email'        => $data['email'] ?? null,
            ':role_id'      => $data['role_id'],
            ':status_aktif' => $data['status_aktif'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [
            'nama_lengkap = :nama_lengkap',
            'email = :email',
            'role_id = :role_id',
            'status_aktif = :status_aktif'
        ];
        $params = [
            ':id'           => $id,
            ':nama_lengkap' => $data['nama_lengkap'],
            ':email'        => $data['email'] ?? null,
            ':role_id'      => $data['role_id'],
            ':status_aktif' => $data['status_aktif'] ?? 1
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password = :password';
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        // Jangan hapus jika user_id = 1 (Administrator Utama)
        if ($id === 1) return false;
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
