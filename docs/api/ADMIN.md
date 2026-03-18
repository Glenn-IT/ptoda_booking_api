# API — Admin Endpoints

> **Related files:** `models/USER.md` · `models/DRIVER_INFO.md` · `models/BOOKING.md` · `flows/DRIVER_APPROVAL_FLOW.md`
> **Backend files:** `controllers/AdminController.php` · `models/Admin.php`

---

## Overview

All admin endpoints require:

1. A valid JWT token (`Authorization: Bearer <token>`)
2. The token's role must be `admin`

Any non-admin token (passenger or driver) will receive `403 Forbidden`.

---

## GET `/admin/users`

List all registered users in the system (all roles).

- **Auth required:** ✅ Yes
- **Role required:** `admin`
- **PHP handler:** `AdminController::getAllUsers()`

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "PTODA Admin",
      "email": "admin@ptoda.local",
      "role": "admin",
      "status": "active",
      "created_at": "2026-03-18 08:00:00"
    },
    {
      "id": 5,
      "name": "Maria Santos",
      "email": "maria@example.com",
      "role": "passenger",
      "status": "active",
      "created_at": "2026-03-18 09:00:00"
    }
  ],
  "message": "Users retrieved successfully."
}
```

---

## GET `/admin/drivers/pending`

List all drivers whose `approval_status = 'pending'` in `driver_info`.
Used by admin to see the queue of drivers waiting for approval.

- **Auth required:** ✅ Yes
- **Role required:** `admin`
- **PHP handler:** `AdminController::getPendingDrivers()`

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 6,
      "name": "Juan dela Cruz",
      "email": "juan@example.com",
      "created_at": "2026-03-18 09:15:00",
      "license_no": "DL-123456",
      "vehicle_no": "ABC-1234",
      "approval_status": "pending"
    }
  ],
  "message": "Pending drivers retrieved."
}
```

---

## GET `/admin/bookings`

List all bookings in the system regardless of status.

- **Auth required:** ✅ Yes
- **Role required:** `admin`
- **PHP handler:** `AdminController::getAllBookings()`

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "passenger_id": 5,
      "driver_id": 6,
      "pickup_address": "Quiapo Market, Manila",
      "dropoff_address": "Rizal Park, Manila",
      "status": "completed",
      "created_at": "2026-03-18 09:00:00",
      "updated_at": "2026-03-18 09:20:00"
    }
  ],
  "message": "Bookings retrieved successfully."
}
```

---

## PUT `/admin/driver/approve/{id}`

Approve a pending driver account. Sets `driver_info.approval_status = 'approved'`.
The driver can now log in and accept rides.

- **Auth required:** ✅ Yes
- **Role required:** `admin`
- **PHP handler:** `AdminController::approveDriver(int $driverId)`

### URL Parameter

| Param | Type | Description       |
| ----- | ---- | ----------------- |
| `id`  | int  | User ID of driver |

### Request Body

_None required._

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "Driver approved successfully."
}
```

### Error Responses

| HTTP | Condition                            | Message                                            |
| ---- | ------------------------------------ | -------------------------------------------------- |
| 404  | Driver not found or already approved | `"Driver not found or already approved/rejected."` |
| 403  | Role is not admin                    | `"You do not have permission."`                    |

---

## PUT `/admin/driver/reject/{id}`

Reject a pending driver account. Sets `driver_info.approval_status = 'rejected'`.
The driver will be blocked at login with a clear rejection message.

- **Auth required:** ✅ Yes
- **Role required:** `admin`
- **PHP handler:** `AdminController::rejectDriver(int $driverId)`

### URL Parameter

| Param | Type | Description       |
| ----- | ---- | ----------------- |
| `id`  | int  | User ID of driver |

### Request Body

_None required._

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "Driver rejected successfully."
}
```

### Error Responses

| HTTP | Condition                            | Message                                            |
| ---- | ------------------------------------ | -------------------------------------------------- |
| 404  | Driver not found or already rejected | `"Driver not found or already approved/rejected."` |
| 403  | Role is not admin                    | `"You do not have permission."`                    |

---

## PUT `/admin/user/activate/{id}`

Re-enable a deactivated user account. Sets `users.status = 'active'`.
The user can log in again after this.

- **Auth required:** ✅ Yes
- **Role required:** `admin`
- **PHP handler:** `AdminController::activateUser(int $userId)`

### URL Parameter

| Param | Type | Description |
| ----- | ---- | ----------- |
| `id`  | int  | User ID     |

### Request Body

_None required._

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "User activated successfully."
}
```

### Error Responses

| HTTP | Condition                        | Message                               |
| ---- | -------------------------------- | ------------------------------------- |
| 404  | User not found or already active | `"User not found or already active."` |
| 403  | Role is not admin                | `"You do not have permission."`       |

---

## PUT `/admin/user/deactivate/{id}`

Deactivate a user account. Sets `users.status = 'inactive'`.
The user will be blocked at login until re-activated.

- **Auth required:** ✅ Yes
- **Role required:** `admin`
- **PHP handler:** `AdminController::deactivateUser(int $userId)`

### URL Parameter

| Param | Type | Description |
| ----- | ---- | ----------- |
| `id`  | int  | User ID     |

### Request Body

_None required._

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "User deactivated successfully."
}
```

### Error Responses

| HTTP | Condition                          | Message                                 |
| ---- | ---------------------------------- | --------------------------------------- |
| 404  | User not found or already inactive | `"User not found or already inactive."` |
| 403  | Role is not admin                  | `"You do not have permission."`         |

---

## DELETE `/admin/user/{id}`

Permanently delete a user and all associated records (`driver_info`, `fcm_tokens`).
Bookings with this user as passenger are also cascade-deleted.

> ⚠️ **Destructive — irreversible. Use only for test cleanup or GDPR removal requests.**

- **Auth required:** ✅ Yes
- **Role required:** `admin`
- **PHP handler:** `AdminController::deleteUser(int $userId)`

### URL Parameter

| Param | Type | Description |
| ----- | ---- | ----------- |
| `id`  | int  | User ID     |

### Request Body

_None required._

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "User deleted successfully."
}
```

### Error Responses

| HTTP | Condition         | Message                         |
| ---- | ----------------- | ------------------------------- |
| 404  | User not found    | `"User not found."`             |
| 403  | Role is not admin | `"You do not have permission."` |

---

## Kotlin — Data Classes & Retrofit

```kotlin
// data/models/AdminModels.kt

data class AdminUser(
    val id: Int,
    val name: String,
    val email: String,
    val role: String,
    val status: String,
    val created_at: String
)

data class PendingDriver(
    val id: Int,
    val name: String,
    val email: String,
    val created_at: String,
    val license_no: String?,
    val vehicle_no: String?,
    val approval_status: String   // always "pending" in this list
)
```

```kotlin
// data/api/ApiService.kt (admin section)

@GET("admin/users")
suspend fun getAllUsers(): ApiResponse<List<AdminUser>>

@GET("admin/drivers/pending")
suspend fun getPendingDrivers(): ApiResponse<List<PendingDriver>>

@GET("admin/bookings")
suspend fun getAllBookings(): ApiResponse<List<Booking>>

@PUT("admin/driver/approve/{id}")
suspend fun approveDriver(@Path("id") driverId: Int): ApiResponse<Unit>

@PUT("admin/driver/reject/{id}")
suspend fun rejectDriver(@Path("id") driverId: Int): ApiResponse<Unit>

@PUT("admin/user/activate/{id}")
suspend fun activateUser(@Path("id") userId: Int): ApiResponse<Unit>

@PUT("admin/user/deactivate/{id}")
suspend fun deactivateUser(@Path("id") userId: Int): ApiResponse<Unit>

@DELETE("admin/user/{id}")
suspend fun deleteUser(@Path("id") userId: Int): ApiResponse<Unit>
```

---

## Sync Rules

| Backend Change                                | Update Here                               |
| --------------------------------------------- | ----------------------------------------- |
| New admin endpoint added                      | New section + `INDEX.md` endpoint table   |
| User response adds new fields (e.g., `phone`) | `AdminUser` data class + response example |
| `PendingDriver` response includes photo URL   | `PendingDriver` data class + example      |
| Delete endpoint changed to soft-delete        | `DELETE` section description + note       |

---

_Last updated: 2026-03-18_
