<?php
// controllers/BookingController.php

require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/FCM.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/config.php';

class BookingController {

    private Booking $bookingModel;
    private User    $userModel;

    public function __construct() {
        $this->bookingModel = new Booking();
        $this->userModel    = new User();
    }

    /**
     * POST /bookings
     * Passenger creates a new ride request.
     */
    public function create(): void {
        $auth = AuthMiddleware::handle([ROLE_PASSENGER]);
        $data = json_decode(file_get_contents('php://input'), true);

        $required = ['pickup_address', 'pickup_lat', 'pickup_lng', 'dropoff_address', 'dropoff_lat', 'dropoff_lng'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                Response::error("Field '$field' is required.", 422);
            }
        }

        $data['passenger_id'] = $auth['user_id'];
        $bookingId = $this->bookingModel->create($data);
        $booking   = $this->bookingModel->findById($bookingId);

        // TODO: Notify nearby available drivers via FCM

        Response::success($booking, 'Ride request created.', 201);
    }

    /**
     * GET /bookings/{id}
     * Get a specific booking (passenger or driver).
     */
    public function getById(int $id): void {
        $auth    = AuthMiddleware::handle();
        $booking = $this->bookingModel->findById($id);

        if (!$booking) {
            Response::notFound('Booking not found.');
        }

        // Only the passenger, assigned driver, or admin can view
        $isOwner = $booking['passenger_id'] == $auth['user_id']
                || $booking['driver_id']    == $auth['user_id']
                || $auth['role'] === ROLE_ADMIN;

        if (!$isOwner) {
            Response::forbidden();
        }

        Response::success($booking);
    }

    /**
     * GET /bookings
     * List bookings — role-aware.
     */
    public function index(): void {
        $auth = AuthMiddleware::handle();

        if ($auth['role'] === ROLE_PASSENGER) {
            $bookings = $this->bookingModel->getByPassenger($auth['user_id']);
        } elseif ($auth['role'] === ROLE_DRIVER) {
            $bookings = $this->bookingModel->getByDriver($auth['user_id']);
        } else {
            $bookings = $this->bookingModel->getAll();
        }

        Response::success($bookings);
    }
}
