<?php
// controllers/AuthController.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../config/config.php';

class AuthController {

    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * POST /auth/register
     * Body: { name, email, password, role, license_no?, vehicle_no? }
     */
    public function register(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validation
        $required = ['name', 'email', 'password', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                Response::error("Field '$field' is required.", 422);
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email address.', 422);
        }

        if (strlen($data['password']) < 6) {
            Response::error('Password must be at least 6 characters.', 422);
        }

        $allowedRoles = [ROLE_PASSENGER, ROLE_DRIVER];
        if (!in_array($data['role'], $allowedRoles, true)) {
            Response::error("Role must be 'passenger' or 'driver'.", 422);
        }

        if ($this->userModel->emailExists($data['email'])) {
            Response::error('Email is already registered.', 409);
        }

        // Create user
        $userId = $this->userModel->create($data);

        // If driver, insert driver_info record
        if ($data['role'] === ROLE_DRIVER) {
            $db   = getDBConnection();
            $stmt = $db->prepare(
                "INSERT INTO driver_info (user_id, license_no, vehicle_no, approval_status)
                 VALUES (:user_id, :license_no, :vehicle_no, 'pending')"
            );
            $stmt->execute([
                ':user_id'    => $userId,
                ':license_no' => $data['license_no'] ?? '',
                ':vehicle_no' => $data['vehicle_no'] ?? '',
            ]);
        }

        Response::success(['user_id' => $userId], 'Registration successful.', 201);
    }

    /**
     * POST /auth/login
     * Body: { email, password }
     */
    public function login(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['email']) || empty($data['password'])) {
            Response::error('Email and password are required.', 422);
        }

        $user = $this->userModel->findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            Response::error('Invalid email or password.', 401);
        }

        if ($user['status'] === 'inactive') {
            Response::forbidden('Your account has been deactivated. Contact admin.');
        }

        // Driver approval check
        if ($user['role'] === ROLE_DRIVER) {
            $db   = getDBConnection();
            $stmt = $db->prepare("SELECT approval_status FROM driver_info WHERE user_id = :id");
            $stmt->execute([':id' => $user['id']]);
            $driverInfo = $stmt->fetch();

            if (!$driverInfo || $driverInfo['approval_status'] !== 'approved') {
                Response::forbidden('Your driver account is pending admin approval.');
            }
        }

        // Generate JWT
        $token = JWT::encode([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ]);

        Response::success([
            'token' => $token,
            'user'  => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ], 'Login successful.');
    }
}
