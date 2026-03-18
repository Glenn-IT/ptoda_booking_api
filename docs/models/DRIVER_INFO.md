# Model — Driver Info

> **Related files:** `models/USER.md` · `api/DRIVER.md` · `api/ADMIN.md` · `flows/DRIVER_APPROVAL_FLOW.md`
> **Backend files:** `models/Admin.php` · `models/User.php` · `database/schema.sql`
> **DB table:** `driver_info`

---

## MySQL Table: `driver_info`

One row per driver. Created automatically when a user registers with `role = 'driver'`.

```sql
CREATE TABLE driver_info (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL UNIQUE,
    license_no      VARCHAR(50)  DEFAULT NULL,
    vehicle_no      VARCHAR(50)  DEFAULT NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    current_lat     DECIMAL(10,7) DEFAULT NULL,
    current_lng     DECIMAL(10,7) DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_driver_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Column Reference

| Column            | Type                     | Notes                                              |
| ----------------- | ------------------------ | -------------------------------------------------- |
| `id`              | INT UNSIGNED, PK         | Auto-assigned                                      |
| `user_id`         | INT UNSIGNED, FK, UNIQUE | 1:1 with `users.id` — cascade delete               |
| `license_no`      | VARCHAR(50), nullable    | Driver's license number — provided at registration |
| `vehicle_no`      | VARCHAR(50), nullable    | Tricycle plate/vehicle number                      |
| `approval_status` | ENUM                     | `pending` \| `approved` \| `rejected`              |
| `current_lat`     | DECIMAL(10,7), nullable  | Updated by `PUT /driver/location`                  |
| `current_lng`     | DECIMAL(10,7), nullable  | Updated by `PUT /driver/location`                  |
| `created_at`      | DATETIME                 | Auto-set on INSERT                                 |
| `updated_at`      | DATETIME                 | Auto-updated on any UPDATE                         |

### Approval Status Lifecycle

```
[pending]  ──(admin approve)──→  [approved]
           ──(admin reject) ──→  [rejected]
```

| Status     | Login Allowed | Can Accept Rides | Set By                                     |
| ---------- | ------------- | ---------------- | ------------------------------------------ |
| `pending`  | ❌ (403)      | ❌               | System (default on register)               |
| `approved` | ✅            | ✅               | Admin via `PUT /admin/driver/approve/{id}` |
| `rejected` | ❌ (403)      | ❌               | Admin via `PUT /admin/driver/reject/{id}`  |

---

## Relationship to `users`

```
users.id (1) ──────────── (1) driver_info.user_id
```

- `driver_info` is **always** queried via `user_id`, not its own `id`.
- Admin endpoints use `user_id` in the URL path (e.g., `PUT /admin/driver/approve/{user_id}`).

---

## PHP Model Methods

From `models/User.php`:

| Method                       | Description                             |
| ---------------------------- | --------------------------------------- |
| `register()` — driver branch | Creates `users` row + `driver_info` row |

From `models/Admin.php`:

| Method                             | Description                                            |
| ---------------------------------- | ------------------------------------------------------ |
| `getPendingDrivers(): array`       | JOIN users+driver_info WHERE approval_status='pending' |
| `approveDriver(int $userId): bool` | Sets approval_status='approved'                        |
| `rejectDriver(int $userId): bool`  | Sets approval_status='rejected'                        |

From `controllers/DriverController.php`:

| Method             | Description                      |
| ------------------ | -------------------------------- |
| `updateLocation()` | Updates current_lat, current_lng |

---

## Kotlin Data Classes

```kotlin
// data/models/DriverInfo.kt

/**
 * Used in admin pending driver list — see api/ADMIN.md
 */
data class PendingDriver(
    val id: Int,                    // users.id (not driver_info.id)
    val name: String,
    val email: String,
    val created_at: String,
    val license_no: String?,
    val vehicle_no: String?,
    val approval_status: String     // will always be "pending" in this list
)

/**
 * Used when displaying a driver's profile or location on map.
 */
data class DriverLocation(
    val user_id: Int,
    val current_lat: Double?,
    val current_lng: Double?
) {
    fun toLatLng(): LatLng? {
        return if (current_lat != null && current_lng != null)
            LatLng(current_lat, current_lng)
        else null
    }
}

/**
 * Request body for updating driver GPS position.
 * See: api/DRIVER.md → PUT /driver/location
 */
data class UpdateLocationRequest(
    val lat: Double,
    val lng: Double
)
```

---

## Approval Status Constants

```kotlin
// utils/Constants.kt

object DriverApprovalStatus {
    const val PENDING  = "pending"
    const val APPROVED = "approved"
    const val REJECTED = "rejected"
}
```

---

## Sync Rules

| Backend Change                                        | Update Here                                   |
| ----------------------------------------------------- | --------------------------------------------- |
| New column added to `driver_info` (e.g., `photo_url`) | MySQL table + Column Reference + data classes |
| New approval status value added                       | ENUM + Approval Status Lifecycle table        |
| Location update extended (e.g., `heading`)            | `UpdateLocationRequest` + column reference    |
| Driver profile endpoint added (GET driver details)    | New section in `api/DRIVER.md` + here         |

---

_Last updated: 2026-03-18_
