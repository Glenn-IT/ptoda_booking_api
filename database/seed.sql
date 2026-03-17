-- =============================================================
-- PTODA Booking System — Seed Data
-- Sample data for local development and testing.
-- Run AFTER schema.sql
-- =============================================================

USE ptoda_db;

-- ─── Sample Passengers ──────────────────────────────────────
INSERT INTO users (name, email, password, role, status) VALUES
('Juan dela Cruz',   'juan@test.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'passenger', 'active'),
('Maria Santos',     'maria@test.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'passenger', 'active');
-- Password for all seed users: password

-- ─── Sample Drivers ─────────────────────────────────────────
INSERT INTO users (name, email, password, role, status) VALUES
('Pedro Reyes',      'pedro@test.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'driver', 'active'),
('Jose Garcia',      'jose@test.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'driver', 'active');

-- ─── Driver Info (link to drivers above) ────────────────────
-- Adjust user_id values if IDs differ after insert
INSERT INTO driver_info (user_id, license_no, vehicle_no, approval_status)
SELECT id, 'LIC-001', 'TRK-001', 'approved' FROM users WHERE email = 'pedro@test.com';

INSERT INTO driver_info (user_id, license_no, vehicle_no, approval_status)
SELECT id, 'LIC-002', 'TRK-002', 'pending' FROM users WHERE email = 'jose@test.com';

-- ─── Sample Bookings ────────────────────────────────────────
INSERT INTO bookings
    (passenger_id, driver_id, pickup_address, pickup_lat, pickup_lng,
     dropoff_address, dropoff_lat, dropoff_lng, status)
SELECT
    p.id, d.id,
    'Barangay Poblacion, Sample City', 14.5995, 120.9842,
    'SM City Sample',                  14.6100, 120.9900,
    'completed'
FROM users p, users d
WHERE p.email = 'juan@test.com' AND d.email = 'pedro@test.com'
LIMIT 1;

INSERT INTO bookings
    (passenger_id, pickup_address, pickup_lat, pickup_lng,
     dropoff_address, dropoff_lat, dropoff_lng, status)
SELECT
    id,
    'Rizal Street, Sample City', 14.6050, 120.9870,
    'City Hall, Sample City',    14.6080, 120.9910,
    'requested'
FROM users WHERE email = 'maria@test.com'
LIMIT 1;
