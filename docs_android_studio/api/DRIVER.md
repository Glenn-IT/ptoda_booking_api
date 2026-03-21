# API — Driver Endpoints

> **Related files:** `models/BOOKING.md` · `models/DRIVER_INFO.md` · `flows/BOOKING_FLOW.md`
> **Backend files:** `controllers/DriverController.php` · `models/Booking.php`

---

## Overview

All driver endpoints require:

1. A valid JWT token (`Authorization: Bearer <token>`)
2. The token's role must be `driver`
3. The driver's `approval_status` in `driver_info` must be `approved`

If the driver is `pending` or `rejected`, they will receive `403` on login itself (see `api/AUTH.md`).

---

## GET `/driver/requests`

Fetch all bookings with `status = 'requested'` that are not yet assigned to any driver.

- **Auth required:** ✅ Yes
- **Role required:** `driver`
- **PHP handler:** `DriverController::getPendingRequests()`

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "passenger_id": 5,
      "driver_id": null,
      "pickup_address": "Quiapo Market, Manila",
      "pickup_lat": "14.5995000",
      "pickup_lng": "120.9750000",
      "dropoff_address": "Rizal Park, Manila",
      "dropoff_lat": "14.5833000",
      "dropoff_lng": "120.9797000",
      "status": "requested",
      "created_at": "2026-03-18 09:00:00",
      "updated_at": null
    }
  ],
  "message": "Pending requests retrieved."
}
```

### Error Responses

| HTTP | Condition          | Message                              |
| ---- | ------------------ | ------------------------------------ |
| 401  | No/invalid token   | `"Authorization token is required."` |
| 403  | Role is not driver | `"You do not have permission."`      |

---

## POST `/driver/accept/{booking_id}`

Accept a pending booking. Sets `status` to `accepted` and assigns the driver.
Triggers an FCM push notification to the passenger.

- **Auth required:** ✅ Yes
- **Role required:** `driver`
- **PHP handler:** `DriverController::acceptRide(int $bookingId)`

### URL Parameter

| Param        | Type | Description |
| ------------ | ---- | ----------- |
| `booking_id` | int  | Booking ID  |

### Request Body

_None required._

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "Ride accepted successfully."
}
```

### Error Responses

| HTTP | Condition                         | Message                             |
| ---- | --------------------------------- | ----------------------------------- |
| 404  | Booking not found                 | `"Booking not found."`              |
| 409  | Booking already accepted by other | `"Booking is no longer available."` |
| 403  | Role is not driver                | `"You do not have permission."`     |

---

## POST `/driver/reject/{booking_id}`

Reject a pending booking. Sets `status` to `rejected`.

- **Auth required:** ✅ Yes
- **Role required:** `driver`
- **PHP handler:** `DriverController::rejectRide(int $bookingId)`

### URL Parameter

| Param        | Type | Description |
| ------------ | ---- | ----------- |
| `booking_id` | int  | Booking ID  |

### Request Body

_None required._

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "Ride rejected."
}
```

### Error Responses

| HTTP | Condition          | Message                         |
| ---- | ------------------ | ------------------------------- |
| 404  | Booking not found  | `"Booking not found."`          |
| 403  | Role is not driver | `"You do not have permission."` |

---

## POST `/driver/complete/{booking_id}`

Mark an accepted/in-progress ride as completed. Sets `status` to `completed`.
Triggers an FCM push notification to the passenger.

- **Auth required:** ✅ Yes
- **Role required:** `driver`
- **PHP handler:** `DriverController::completeRide(int $bookingId)`

### URL Parameter

| Param        | Type | Description |
| ------------ | ---- | ----------- |
| `booking_id` | int  | Booking ID  |

### Request Body

_None required._

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "Ride marked as completed."
}
```

### Error Responses

| HTTP | Condition                      | Message                         |
| ---- | ------------------------------ | ------------------------------- |
| 404  | Booking not found              | `"Booking not found."`          |
| 403  | Booking not assigned to driver | `"You do not have permission."` |
| 403  | Role is not driver             | `"You do not have permission."` |

---

## PUT `/driver/location`

Update the driver's current GPS coordinates. Stored in `driver_info.current_lat`
and `driver_info.current_lng`.

- **Auth required:** ✅ Yes
- **Role required:** `driver`
- **PHP handler:** `DriverController::updateLocation()`

### Request Body

```json
{
  "lat": "decimal — required (e.g. 14.5995)",
  "lng": "decimal — required (e.g. 120.9750)"
}
```

### Example

```json
{
  "lat": 14.5995,
  "lng": 120.975
}
```

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "Location updated."
}
```

### Error Responses

| HTTP | Condition          | Message                         |
| ---- | ------------------ | ------------------------------- |
| 422  | Missing lat or lng | `"lat and lng are required."`   |
| 403  | Role is not driver | `"You do not have permission."` |

---

## Kotlin — Data Classes & Retrofit

```kotlin
// data/models/DriverModels.kt

data class UpdateLocationRequest(
    val lat: Double,
    val lng: Double
)
```

```kotlin
// data/api/ApiService.kt (driver section)

@GET("driver/requests")
suspend fun getDriverRequests(): ApiResponse<List<Booking>>

@POST("driver/accept/{booking_id}")
suspend fun acceptRide(@Path("booking_id") bookingId: Int): ApiResponse<Unit>

@POST("driver/reject/{booking_id}")
suspend fun rejectRide(@Path("booking_id") bookingId: Int): ApiResponse<Unit>

@POST("driver/complete/{booking_id}")
suspend fun completeRide(@Path("booking_id") bookingId: Int): ApiResponse<Unit>

@PUT("driver/location")
suspend fun updateLocation(@Body body: UpdateLocationRequest): ApiResponse<Unit>
```

---

## Location Update — Usage in Android

```kotlin
// Recommended: call updateLocation() inside FusedLocationProviderClient callback

val fusedLocationClient = LocationServices.getFusedLocationProviderClient(this)

fusedLocationClient.lastLocation.addOnSuccessListener { location ->
    location?.let {
        viewModel.updateLocation(it.latitude, it.longitude)
    }
}
```

---

## Sync Rules

| Backend Change                                    | Update Here                             |
| ------------------------------------------------- | --------------------------------------- |
| New driver action endpoint added                  | New section + `INDEX.md` endpoint table |
| `acceptRide` response adds booking snapshot       | Success Response + `Booking` data class |
| Location endpoint adds `heading` / `speed` fields | Request body + `UpdateLocationRequest`  |

---

_Last updated: 2026-03-18_
