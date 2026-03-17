<?php
// middleware/AuthMiddleware.php
// Validates the JWT Bearer token on protected routes

require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../helpers/Response.php';

class AuthMiddleware {

    /**
     * Validates the Authorization header and returns the decoded JWT payload.
     * Calls Response::unauthorized() and exits if the token is missing or invalid.
     *
     * @param array $allowedRoles  Optional whitelist of roles, e.g. ['admin', 'driver']
     * @return array               Decoded JWT payload (user_id, role, email, ...)
     */
    public static function handle(array $allowedRoles = []): array {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            Response::unauthorized('Authorization token is required.');
        }

        $token = substr($authHeader, 7); // strip "Bearer "

        try {
            $payload = JWT::decode($token);
        } catch (Exception $e) {
            Response::unauthorized($e->getMessage());
        }

        // Role-based access control
        if (!empty($allowedRoles) && !in_array($payload['role'] ?? '', $allowedRoles, true)) {
            Response::forbidden('You do not have permission to access this resource.');
        }

        return $payload;
    }
}
