<?php
// helpers/Response.php
// Standardized JSON response helper

class Response {

    /**
     * Send a 200 OK success response.
     */
    public static function success(mixed $data = null, string $message = 'Success', int $code = 200): void {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
        exit;
    }

    /**
     * Send an error response.
     */
    public static function error(string $message = 'An error occurred', int $code = 400, mixed $errors = null): void {
        http_response_code($code);
        $body = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        echo json_encode($body);
        exit;
    }

    /**
     * 401 Unauthorized
     */
    public static function unauthorized(string $message = 'Unauthorized'): void {
        self::error($message, 401);
    }

    /**
     * 403 Forbidden
     */
    public static function forbidden(string $message = 'Forbidden'): void {
        self::error($message, 403);
    }

    /**
     * 404 Not Found
     */
    public static function notFound(string $message = 'Resource not found'): void {
        self::error($message, 404);
    }
}
