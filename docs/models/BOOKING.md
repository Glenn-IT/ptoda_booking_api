# Model — Booking & Booking Logs

> **Related files:** `api/BOOKINGS.md` · `api/DRIVER.md` · `flows/BOOKING_FLOW.md`
> **Backend files:** `models/Booking.php` · `database/schema.sql`
> **DB tables:** `bookings` · `booking_logs`

---

## MySQL Table: `bookings`

```sql
CREATE TABLE bookings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    passenger_id    INT UNSIGNED NOT NULL,
    driver_id       INT UNSIGNED DEFAULT NULL,
    pickup_address  VARCHAR(255) NOT NULL,
    pickup_lat      DECIMAL(10,7) NOT NULL,
    pickup_lng      DECIMAL(10,7) NOT NULL,
    dropoff_address VARCHAR(255) NOT NULL,
    dropoff_lat     DECIMAL(10,7) NOT NULL,
    dropoff_lng     DECIMAL(10,7) NOT NULL,
    status          ENUM('requested','accepted','in_progress','completed','cancelled','rejected')
                    NOT NULL DEFAULT 'requested',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_booking_passenger FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_driver    FOREIGN KEY (driver_id)    REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_status       (status),
    INDEX idx_passenger_id (passenger_id),
    INDEX idx_driver_id    (driver_id)
);
```

### Column Reference

| Column            | Type             | Notes                                                |
| ----------------- | ---------------- | ---------------------------------------------------- |
| `id`              | INT UNSIGNED, PK | Auto-assigned booking identifier                     |
| `passenger_id`    | INT UNSIGNED, FK | References `users.id` — cascade delete               |
| `driver_id`       | INT UNSIGNED, FK | NULL until a driver accepts; SET NULL on user delete |
| `pickup_address`  | VARCHAR(255)     | Human-readable pickup location                       |
| `pickup_lat`      | DECIMAL(10,7)    | Latitude to 7 decimal places                         |
| `pickup_lng`      | DECIMAL(10,7)    | Longitude to 7 decimal places                        |
| `dropoff_address` | VARCHAR(255)     | Human-readable drop-off location                     |
| `dropoff_lat`     | DECIMAL(10,7)    | Latitude to 7 decimal places                         |
| `dropoff_lng`     | DECIMAL(10,7)    | Longitude to 7 decimal places                        |
| `status`          | ENUM             | See status lifecycle below                           |
| `created_at`      | DATETIME         | Auto-set on INSERT                                   |
| `updated_at`      | DATETIME         | Auto-updated on any UPDATE                           |

### Booking Status Lifecycle

```
[requested] → [accepted]    → [in_progress] → [completed]
                           ↘ [rejected]       (driver rejects the booking)
[requested] ↘ [cancelled]                     (future: passenger cancels)
```

| Status        | Set By    | Meaning                                        |
| ------------- | --------- | ---------------------------------------------- |
| `requested`   | Passenger | Booking created, waiting for a driver          |
| `accepted`    | Driver    | Driver has accepted the booking                |
| `in_progress` | Driver    | Ride is actively ongoing (future phase)        |
| `completed`   | Driver    | Ride successfully finished                     |
| `rejected`    | Driver    | Driver declined this specific booking          |
| `cancelled`   | Passenger | Passenger cancelled before acceptance (future) |

---

## MySQL Table: `booking_logs`

Audit trail — every status change creates a row here.

```sql
CREATE TABLE booking_logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(20) DEFAULT NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_log_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id)
);
```

### Column Reference

| Column       | Type         | Notes                                     |
| ------------ | ------------ | ----------------------------------------- |
| `id`         | INT UNSIGNED | Auto-assigned log entry identifier        |
| `booking_id` | INT, FK      | References `bookings.id` — cascade delete |
| `old_status` | VARCHAR(20)  | NULL for the initial `requested` entry    |
| `new_status` | VARCHAR(20)  | The status after this change              |
| `changed_at` | DATETIME     | Timestamp of the change                   |

---

## PHP Model Methods (`models/Booking.php`)

| Method                                                              | Description                         |
| ------------------------------------------------------------------- | ----------------------------------- |
| `create(array $data): int`                                          | Insert booking, log initial status  |
| `getById(int $id): array\|null`                                     | Fetch single booking by ID          |
| `updateStatus(int $id, string $status, int $driverId = null): bool` | Update status + log the change      |
| `getByPassenger(int $passengerId): array`                           | All bookings for a passenger        |
| `getByDriver(int $driverId): array`                                 | All bookings assigned to a driver   |
| `getPendingRequests(): array`                                       | All `status = 'requested'` bookings |

---

## Kotlin Data Classes

```kotlin
// data/models/Booking.kt

data class BookingRequest(
    val pickup_address: String,
    val pickup_lat: Double,
    val pickup_lng: Double,
    val dropoff_address: String,
    val dropoff_lat: Double,
    val dropoff_lng: Double
)

data class Booking(
    val id: Int,
    val passenger_id: Int,
    val driver_id: Int?,          // null until accepted
    val pickup_address: String,
    val pickup_lat: String,       // returned as String from MySQL DECIMAL
    val pickup_lng: String,
    val dropoff_address: String,
    val dropoff_lat: String,
    val dropoff_lng: String,
    val status: String,
    val created_at: String,
    val updated_at: String?
) {
    /** Convenience: parse lat/lng strings to Double for map use */
    fun pickupLatLng()  = Pair(pickup_lat.toDouble(),  pickup_lng.toDouble())
    fun dropoffLatLng() = Pair(dropoff_lat.toDouble(), dropoff_lng.toDouble())
}

data class CreateBookingResponse(
    val booking_id: Int
)
```

```kotlin
// Booking status constants
object BookingStatus {
    const val REQUESTED   = "requested"
    const val ACCEPTED    = "accepted"
    const val IN_PROGRESS = "in_progress"
    const val COMPLETED   = "completed"
    const val CANCELLED   = "cancelled"
    const val REJECTED    = "rejected"

    /** Returns true if the booking has reached a terminal state */
    fun isTerminal(status: String) =
        status == COMPLETED || status == CANCELLED || status == REJECTED
}
```

---

## Map Usage (LatLng Conversion)

```kotlin
// In PassengerHomeActivity or DriverHomeActivity

val booking: Booking = ...

// Pickup marker
val pickupLatLng = LatLng(booking.pickup_lat.toDouble(), booking.pickup_lng.toDouble())
map.addMarker(MarkerOptions().position(pickupLatLng).title("Pickup: ${booking.pickup_address}"))

// Drop-off marker
val dropoffLatLng = LatLng(booking.dropoff_lat.toDouble(), booking.dropoff_lng.toDouble())
map.addMarker(MarkerOptions().position(dropoffLatLng).title("Drop-off: ${booking.dropoff_address}"))

map.animateCamera(CameraUpdateFactory.newLatLngZoom(pickupLatLng, 15f))
```

---

## Sync Rules

| Backend Change                 | Update Here                                           |
| ------------------------------ | ----------------------------------------------------- |
| New column added to `bookings` | MySQL table + Column Reference + `Booking` data class |
| New status value added         | Status Lifecycle + `BookingStatus` object             |
| New `Booking.php` method added | PHP Model Methods table                               |
| `booking_logs` schema changed  | `booking_logs` section                                |

---

_Last updated: 2026-03-18_
