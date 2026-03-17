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
     * Get drivers with approval_status = 'pending'.
     */
    public function getPendingDrivers(): array {
        return $this->db->query(
            "SELECT u.id, u.name, u.email, u.created_at,
                    di.license_no, di.vehicle_no, di.approval_status
             FROM users u
             INNER JOIN driver_info di ON di.user_id = u.id
             WHERE u.role = 'driver' AND di.approval_status = 'pending'
             ORDER BY u.created_at ASC"
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
     * Reject a driver account.
     */
    public function rejectDriver(int $driverId): bool {
        $stmt = $this->db->prepare(
            "UPDATE driver_info SET approval_status = 'rejected' WHERE user_id = :id"
        );
        $stmt->execute([':id' => $driverId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Deactivate any user account (passenger or driver).
     */
    public function deactivateUser(int $userId): bool {
        $stmt = $this->db->prepare(
            "UPDATE users SET status = 'inactive' WHERE id = :id AND status = 'active'"
        );
        $stmt->execute([':id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Activate (re-enable) any user account (passenger or driver).
     */
    public function activateUser(int $userId): bool {
        $stmt = $this->db->prepare(
            "UPDATE users SET status = 'active' WHERE id = :id AND status = 'inactive'"
        );
        $stmt->execute([':id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Permanently delete a user and their related records.
     */
    public function deleteUser(int $userId): bool {
        // driver_info, fcm_tokens, bookings rows are cleaned up via ON DELETE CASCADE in schema
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
