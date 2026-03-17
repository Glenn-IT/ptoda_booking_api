<?php
// index.php — Entry point & router for the PTODA Booking API

// ─── Headers ──────────────────────────────────────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ─── Global Error Handling ─────────────────────────────────────────────────────
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error.',
        'error'   => $e->getMessage(), // Remove in production
    ]);
    exit;
});

set_error_handler(function (int $errno, string $errstr) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error.',
        'error'   => $errstr, // Remove in production
    ]);
    exit;
});

// ─── Bootstrap ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/helpers/Response.php';

// ─── Controllers ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/BookingController.php';
require_once __DIR__ . '/controllers/DriverController.php';
require_once __DIR__ . '/controllers/AdminController.php';

// ─── Router ───────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip base path (e.g. /ptoda_booking_api)
$basePath = '/ptoda_booking_api';
if (str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

$uri    = '/' . trim($uri, '/');
$parts  = explode('/', trim($uri, '/'));

// ─── Route Definitions ────────────────────────────────────────────────────────

// Auth
if ($uri === '/auth/register' && $method === 'POST') {
    (new AuthController())->register();

} elseif ($uri === '/auth/login' && $method === 'POST') {
    (new AuthController())->login();

// Bookings
} elseif ($uri === '/bookings' && $method === 'POST') {
    (new BookingController())->create();

} elseif ($uri === '/bookings' && $method === 'GET') {
    (new BookingController())->index();

} elseif (preg_match('#^/bookings/(\d+)$#', $uri, $m) && $method === 'GET') {
    (new BookingController())->getById((int) $m[1]);

// Passenger
} elseif ($uri === '/passenger/history' && $method === 'GET') {
    (new BookingController())->index(); // reuses index() — passenger role filtered

// Driver
} elseif ($uri === '/driver/requests' && $method === 'GET') {
    (new DriverController())->getPendingRequests();

} elseif (preg_match('#^/driver/accept/(\d+)$#', $uri, $m) && $method === 'POST') {
    (new DriverController())->acceptRide((int) $m[1]);

} elseif (preg_match('#^/driver/reject/(\d+)$#', $uri, $m) && $method === 'POST') {
    (new DriverController())->rejectRide((int) $m[1]);

} elseif (preg_match('#^/driver/complete/(\d+)$#', $uri, $m) && $method === 'POST') {
    (new DriverController())->completeRide((int) $m[1]);

} elseif ($uri === '/driver/location' && $method === 'PUT') {
    (new DriverController())->updateLocation();

// FCM Token
} elseif ($uri === '/user/fcm-token' && $method === 'PUT') {
    require_once __DIR__ . '/middleware/AuthMiddleware.php';
    require_once __DIR__ . '/models/User.php';
    $auth = \AuthMiddleware::handle();
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['token'])) {
        Response::error('FCM token is required.', 422);
    }
    (new User())->updateFCMToken($auth['user_id'], $data['token']);
    Response::success(null, 'FCM token updated.');

// Admin
} elseif ($uri === '/admin/users' && $method === 'GET') {
    (new AdminController())->getAllUsers();

} elseif ($uri === '/admin/drivers/pending' && $method === 'GET') {
    (new AdminController())->getPendingDrivers();

} elseif ($uri === '/admin/bookings' && $method === 'GET') {
    (new AdminController())->getAllBookings();

} elseif (preg_match('#^/admin/driver/approve/(\d+)$#', $uri, $m) && $method === 'PUT') {
    (new AdminController())->approveDriver((int) $m[1]);

} elseif (preg_match('#^/admin/driver/reject/(\d+)$#', $uri, $m) && $method === 'PUT') {
    (new AdminController())->rejectDriver((int) $m[1]);

} elseif (preg_match('#^/admin/user/deactivate/(\d+)$#', $uri, $m) && $method === 'PUT') {
    (new AdminController())->deactivateUser((int) $m[1]);

// Health check
} elseif ($uri === '/' || $uri === '') {
    Response::success(['version' => APP_VERSION], APP_NAME . ' is running.');

} else {
    Response::notFound('Endpoint not found.');
}
