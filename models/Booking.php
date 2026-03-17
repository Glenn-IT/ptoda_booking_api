<?php
// models/Booking.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class Booking {

    private PDO $db;

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Create a new booking. Returns the new booking ID.
     */
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO bookings
                (passenger_id, pickup_address, pickup_lat, pickup_lng,
                 dropoff_address, dropoff_lat, dropoff_lng, status, created_at)
             VALUES
                (:passenger_id, :pickup_address, :pickup_lat, :pickup_lng,
                 :dropoff_address, :dropoff_lat, :dropoff_lng, :status, NOW())"
        );
        $stmt->execute([
            ':passenger_id'    => $data['passenger_id'],
            ':pickup_address'  => $data['pickup_address'],
            ':pickup_lat'      => $data['pickup_lat'],
            ':pickup_lng'      => $data['pickup_lng'],
            ':dropoff_address' => $data['dropoff_address'],
            ':dropoff_lat'     => $data['dropoff_lat'],
            ':dropoff_lng'     => $data['dropoff_lng'],
            ':status'          => STATUS_REQUESTED,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Find a booking by ID.
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT b.*, 
                    p.name AS passenger_name, p.email AS passenger_email,
                    d.name AS driver_name,    d.email AS driver_email
             FROM bookings b
             LEFT JOIN users p ON b.passenger_id = p.id
             LEFT JOIN users d ON b.driver_id    = d.id
             WHERE b.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Update booking status and (optionally) the driver_id.
     */
    public function updateStatus(int $bookingId, string $newStatus, ?int $driverId = null): void {
        // Log status change first
        $this->logStatusChange($bookingId, $newStatus);

        $sql    = "UPDATE bookings SET status = :status, updated_at = NOW()";
        $params = [':status' => $newStatus, ':id' => $bookingId];

        if ($driverId !== null) {
            $sql           .= ", driver_id = :driver_id";
            $params[':driver_id'] = $driverId;
        }

        $sql .= " WHERE id = :id";
        $this->db->prepare($sql)->execute($params);
    }

    /**
     * Get all pending bookings not yet assigned to a driver.
     */
    public function getPendingRequests(): array {
        $stmt = $this->db->prepare(
            "SELECT b.*, p.name AS passenger_name
             FROM bookings b
             JOIN users p ON b.passenger_id = p.id
             WHERE b.status = :status
             ORDER BY b.created_at ASC"
        );
        $stmt->execute([':status' => STATUS_REQUESTED]);
        return $stmt->fetchAll();
    }

    /**
     * Get all bookings for a specific passenger.
     */
    public function getByPassenger(int $passengerId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM bookings WHERE passenger_id = :id ORDER BY created_at DESC"
        );
        $stmt->execute([':id' => $passengerId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all bookings for a specific driver.
     */
    public function getByDriver(int $driverId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM bookings WHERE driver_id = :id ORDER BY created_at DESC"
        );
        $stmt->execute([':id' => $driverId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all bookings (admin).
     */
    public function getAll(): array {
        return $this->db->query(
            "SELECT b.*, p.name AS passenger_name, d.name AS driver_name
             FROM bookings b
             LEFT JOIN users p ON b.passenger_id = p.id
             LEFT JOIN users d ON b.driver_id    = d.id
             ORDER BY b.created_at DESC"
        )->fetchAll();
    }

    // ─── Internal ─────────────────────────────────────────

    private function logStatusChange(int $bookingId, string $newStatus): void {
        $booking = $this->findById($bookingId);
        if (!$booking) return;

        $stmt = $this->db->prepare(
            "INSERT INTO booking_logs (booking_id, old_status, new_status, changed_at)
             VALUES (:booking_id, :old_status, :new_status, NOW())"
        );
        $stmt->execute([
            ':booking_id' => $bookingId,
            ':old_status' => $booking['status'],
            ':new_status' => $newStatus,
        ]);
    }
}
