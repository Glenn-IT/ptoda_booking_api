<?php
// models/User.php

require_once __DIR__ . '/../config/database.php';

class User {

    private PDO $db;

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Register a new user. Returns the new user's ID.
     */
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, password, role, status, created_at)
             VALUES (:name, :email, :password, :role, 'active', NOW())"
        );
        $stmt->execute([
            ':name'     => $data['name'],
            ':email'    => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':role'     => $data['role'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Find a user by ID.
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Check if an email is already registered.
     */
    public function emailExists(string $email): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Update the FCM device push token for a user.
     */
    public function updateFCMToken(int $userId, string $token): void {
        $stmt = $this->db->prepare(
            "INSERT INTO fcm_tokens (user_id, token, updated_at)
             VALUES (:user_id, :token, NOW())
             ON DUPLICATE KEY UPDATE token = :token, updated_at = NOW()"
        );
        $stmt->execute([':user_id' => $userId, ':token' => $token]);
    }

    /**
     * Get FCM token for a user.
     */
    public function getFCMToken(int $userId): ?string {
        $stmt = $this->db->prepare("SELECT token FROM fcm_tokens WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ? $row['token'] : null;
    }

    /**
     * Update user status (active / inactive).
     */
    public function updateStatus(int $userId, string $status): void {
        $stmt = $this->db->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $userId]);
    }

    /**
     * Get all users (admin use).
     */
    public function getAll(): array {
        return $this->db->query("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC")->fetchAll();
    }
}
