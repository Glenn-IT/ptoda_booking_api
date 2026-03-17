<?php
// controllers/DriverController.php

require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/FCM.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/config.php';

class DriverController {

    private Booking $bookingModel;
    private User    $userModel;

    public function __construct() {
        $this->bookingModel = new Booking();
        $this->userModel    = new User();
    }

    /**
     * GET /driver/requests
     * Get all pending ride requests.
     */
    public function getPendingRequests(): void {
        AuthMiddleware::handle([ROLE_DRIVER]);
        $requests = $this->bookingModel->getPendingRequests();
        Response::success($requests);
    }

    /**
     * POST /driver/accept/{booking_id}
     * Driver accepts a ride request.
     */
    public function acceptRide(int $bookingId): void {
        $auth    = AuthMiddleware::handle([ROLE_DRIVER]);
        $booking = $this->bookingModel->findById($bookingId);

        if (!$booking) {
            Response::notFound('Booking not found.');
        }

        if ($booking['status'] !== STATUS_REQUESTED) {
            Response::error('This ride is no longer available.', 409);
        }

        $this->bookingModel->updateStatus($bookingId, STATUS_ACCEPTED, $auth['user_id']);

        // Notify passenger
        $passengerToken = $this->userModel->getFCMToken($booking['passenger_id']);
        if ($passengerToken) {
            FCM::sendToDevice(
                $passengerToken,
                'Ride Accepted!',
                'A driver has accepted your ride request. They are on their way.',
                ['booking_id' => (string) $bookingId, 'status' => STATUS_ACCEPTED]
            );
        }

        Response::success(null, 'Ride accepted successfully.');
    }

    /**
     * POST /driver/reject/{booking_id}
     * Driver rejects a ride request.
     */
    public function rejectRide(int $bookingId): void {
        $auth    = AuthMiddleware::handle([ROLE_DRIVER]);
        $booking = $this->bookingModel->findById($bookingId);

        if (!$booking) {
            Response::notFound('Booking not found.');
        }

        if ($booking['status'] !== STATUS_REQUESTED) {
            Response::error('This ride cannot be rejected in its current state.', 409);
        }

        $this->bookingModel->updateStatus($bookingId, STATUS_REJECTED);
        Response::success(null, 'Ride rejected.');
    }

    /**
     * POST /driver/complete/{booking_id}
     * Driver marks a ride as completed.
     */
    public function completeRide(int $bookingId): void {
        $auth    = AuthMiddleware::handle([ROLE_DRIVER]);
        $booking = $this->bookingModel->findById($bookingId);

        if (!$booking) {
            Response::notFound('Booking not found.');
        }

        if ((int) $booking['driver_id'] !== $auth['user_id']) {
            Response::forbidden('You are not the driver for this booking.');
        }

        if ($booking['status'] !== STATUS_ACCEPTED && $booking['status'] !== STATUS_IN_PROGRESS) {
            Response::error('Cannot complete ride in current status.', 409);
        }

        $this->bookingModel->updateStatus($bookingId, STATUS_COMPLETED);

        // Notify passenger
        $passengerToken = $this->userModel->getFCMToken($booking['passenger_id']);
        if ($passengerToken) {
            FCM::sendToDevice(
                $passengerToken,
                'Ride Completed',
                'Your ride has been completed. Thank you for using PTODA!',
                ['booking_id' => (string) $bookingId, 'status' => STATUS_COMPLETED]
            );
        }

        Response::success(null, 'Ride marked as completed.');
    }

    /**
     * PUT /driver/location
     * Update driver's current GPS coordinates.
     * Body: { lat, lng }
     */
    public function updateLocation(): void {
        $auth = AuthMiddleware::handle([ROLE_DRIVER]);
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['lat'], $data['lng'])) {
            Response::error('lat and lng are required.', 422);
        }

        $db   = getDBConnection();
        $stmt = $db->prepare(
            "UPDATE driver_info SET current_lat = :lat, current_lng = :lng WHERE user_id = :id"
        );
        $stmt->execute([':lat' => $data['lat'], ':lng' => $data['lng'], ':id' => $auth['user_id']]);

        Response::success(null, 'Location updated.');
    }
}
