-- =============================================================
-- PTODA Booking System — MySQL Database Schema
-- Database: ptoda_db
-- Run this in phpMyAdmin or via MySQL CLI:
--   mysql -u root -p ptoda_db < schema.sql
-- =============================================================

CREATE DATABASE IF NOT EXISTS ptoda_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ptoda_db;

-- ─────────────────────────────────────────────────────────────
-- Table: users
-- Stores all system users: passengers, drivers, and admins.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id         INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('passenger', 'driver', 'admin') NOT NULL DEFAULT 'passenger',
    status     ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Table: driver_info
-- Extended driver profile — linked 1:1 to a user with role=driver.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS driver_info (
    id              INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT          UNSIGNED NOT NULL UNIQUE,
    license_no      VARCHAR(50)  DEFAULT NULL,
    vehicle_no      VARCHAR(50)  DEFAULT NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    current_lat     DECIMAL(10,7) DEFAULT NULL,
    current_lng     DECIMAL(10,7) DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_driver_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Table: bookings
-- Each row is one ride booking/request.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
    id              INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    passenger_id    INT          UNSIGNED NOT NULL,
    driver_id       INT          UNSIGNED DEFAULT NULL,
    pickup_address  VARCHAR(255) NOT NULL,
    pickup_lat      DECIMAL(10,7) NOT NULL,
    pickup_lng      DECIMAL(10,7) NOT NULL,
    dropoff_address VARCHAR(255) NOT NULL,
    dropoff_lat     DECIMAL(10,7) NOT NULL,
    dropoff_lng     DECIMAL(10,7) NOT NULL,
    status          ENUM('requested', 'accepted', 'in_progress', 'completed', 'cancelled', 'rejected')
                    NOT NULL DEFAULT 'requested',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_booking_passenger
        FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_driver
        FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_status       (status),
    INDEX idx_passenger_id (passenger_id),
    INDEX idx_driver_id    (driver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Table: booking_logs
-- Audit trail of every status change per booking.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS booking_logs (
    id          INT      UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT      UNSIGNED NOT NULL,
    old_status  VARCHAR(20) DEFAULT NULL,
    new_status  VARCHAR(20) NOT NULL,
    changed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_log_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,

    INDEX idx_booking_id (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Table: fcm_tokens
-- Stores one FCM push token per user (upserted on login/refresh).
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS fcm_tokens (
    id         INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          UNSIGNED NOT NULL UNIQUE,
    token      VARCHAR(255) NOT NULL,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_fcm_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Default Admin Account
-- Password: admin123  (change immediately in production!)
-- Hash generated with: password_hash('admin123', PASSWORD_BCRYPT)
-- =============================================================
INSERT INTO users (name, email, password, role, status)
VALUES (
    'PTODA Admin',
    'admin@ptoda.local',
    '$2y$10$FUblXxX0ntMZSK8Uvyad6OiBpOmxEld4/g0A84pibfv19IA0NxUf.', -- password: admin123
    'admin',
    'active'
) ON DUPLICATE KEY UPDATE id = id;
