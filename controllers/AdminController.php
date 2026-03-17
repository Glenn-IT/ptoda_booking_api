<?php
// controllers/AdminController.php

require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/config.php';

class AdminController {

    private Admin   $adminModel;
    private Booking $bookingModel;
    private User    $userModel;

    public function __construct() {
        $this->adminModel   = new Admin();
        $this->bookingModel = new Booking();
        $this->userModel    = new User();
    }

    /**
     * GET /admin/users
     */
    public function getAllUsers(): void {
        AuthMiddleware::handle([ROLE_ADMIN]);
        $users = $this->userModel->getAll();
        Response::success($users);
    }

    /**
     * GET /admin/bookings
     */
    public function getAllBookings(): void {
        AuthMiddleware::handle([ROLE_ADMIN]);
        $bookings = $this->bookingModel->getAll();
        Response::success($bookings);
    }

    /**
     * PUT /admin/driver/approve/{id}
     */
    public function approveDriver(int $driverId): void {
        AuthMiddleware::handle([ROLE_ADMIN]);
        $success = $this->adminModel->approveDriver($driverId);

        if (!$success) {
            Response::notFound('Driver not found.');
        }

        Response::success(null, 'Driver approved successfully.');
    }

    /**
     * PUT /admin/user/deactivate/{id}
     */
    public function deactivateUser(int $userId): void {
        AuthMiddleware::handle([ROLE_ADMIN]);
        $success = $this->adminModel->deactivateUser($userId);

        if (!$success) {
            Response::notFound('User not found.');
        }

        Response::success(null, 'User deactivated successfully.');
    }
}
