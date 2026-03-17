<?php
// models/Admin.php

require_once __DIR__ . '/../config/database.php';

class Admin {

    private PDO $db;

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Get all drivers with their approval status.
     */
    public function getAllDrivers(): array {
        return $this->db->query(
            "SELECT u.id, u.name, u.email, u.status,
                    di.license_no, di.vehicle_no, di.approval_status
             FROM users u
             LEFT JOIN driver_info di ON di.user_id = u.id
             WHERE u.role = 'driver'
             ORDER BY u.created_at DESC"
        )->fetchAll();
    }

    /**
     * Approve a driver account.
     */
    public function approveDriver(int $driverId): bool {
        $stmt = $this->db->prepare(
            "UPDATE driver_info SET approval_status = 'approved' WHERE user_id = :id"
        );
        $stmt->execute([':id' => $driverId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Deactivate any user account (passenger or driver).
     */
    public function deactivateUser(int $userId): bool {
        $stmt = $this->db->prepare(
            "UPDATE users SET status = 'inactive' WHERE id = :id"
        );
        $stmt->execute([':id' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
