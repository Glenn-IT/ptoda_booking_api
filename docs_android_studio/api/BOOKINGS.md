# API — Bookings

> **Related files:** `models/BOOKING.md` · `flows/BOOKING_FLOW.md`
> **Backend files:** `controllers/BookingController.php` · `models/Booking.php`

---

## Overview

The bookings API is **role-aware**: the same endpoints return different data depending on the caller's JWT role.

| Role        | `GET /bookings` returns        |
| ----------- | ------------------------------ |
| `passenger` | Only their own bookings        |
| `driver`    | Only bookings assigned to them |
| `admin`     | All bookings in the system     |

Booking status lifecycle:

```
requested → accepted → in_progress → completed
                    ↘ rejected (by driver)
         ↘ cancelled (future: by passenger)
```

---

## POST `/bookings`

Create a new ride request. Passenger role only.

- **Auth required:** ✅ Yes
- **Role required:** `passenger`
- **PHP handler:** `BookingController::create()`

### Request Body

```json
{
  "pickup_address": "string — required",
  "pickup_lat": "decimal — required (e.g. 14.5995)",
  "pickup_lng": "decimal — required (e.g. 120.9750)",
  "dropoff_address": "string — required",
  "dropoff_lat": "decimal — required",
  "dropoff_lng": "decimal — required"
}
```

### Example

```json
{
  "pickup_address": "Quiapo Market, Manila",
  "pickup_lat": 14.5995,
  "pickup_lng": 120.975,
  "dropoff_address": "Rizal Park, Manila",
  "dropoff_lat": 14.5833,
  "dropoff_lng": 120.9797
}
```

### Success Response — `201 Created`

```json
{
  "success": true,
  "data": {
    "booking_id": 10
  },
  "message": "Booking created successfully."
}
```

### Error Responses

| HTTP | Condition              | Message                                 |
| ---- | ---------------------- | --------------------------------------- |
| 401  | No/invalid token       | `"Authorization token is required."`    |
| 403  | Role is not passenger  | `"You do not have permission."`         |
| 422  | Missing required field | `"Field 'pickup_address' is required."` |
| 500  | DB error               | `"Failed to create booking."`           |

---

## GET `/bookings`

List bookings. Response is filtered by the caller's role.

- **Auth required:** ✅ Yes
- **Role required:** Any authenticated role
- **PHP handler:** `BookingController::index()`

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
  "message": "Bookings retrieved successfully."
}
```

---

## GET `/bookings/{id}`

Get a single booking by its ID. Any authenticated user can call this, but only
if the booking belongs to them (passenger or driver). Admins can see any.

- **Auth required:** ✅ Yes
- **Role required:** Any
- **PHP handler:** `BookingController::getById(int $id)`

### URL Parameter

| Param | Type | Description |
| ----- | ---- | ----------- |
| `id`  | int  | Booking ID  |

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": {
    "id": 10,
    "passenger_id": 5,
    "driver_id": 3,
    "pickup_address": "Quiapo Market, Manila",
    "pickup_lat": "14.5995000",
    "pickup_lng": "120.9750000",
    "dropoff_address": "Rizal Park, Manila",
    "dropoff_lat": "14.5833000",
    "dropoff_lng": "120.9797000",
    "status": "accepted",
    "created_at": "2026-03-18 09:00:00",
    "updated_at": "2026-03-18 09:05:00"
  },
  "message": "Booking retrieved successfully."
}
```

### Error Responses

| HTTP | Condition         | Message                         |
| ---- | ----------------- | ------------------------------- |
| 404  | Booking not found | `"Booking not found."`          |
| 403  | Not their booking | `"You do not have permission."` |

---

## GET `/passenger/history`

Return all past bookings for the logged-in passenger. Functionally identical to
`GET /bookings` when called with a passenger token (role-filtered).

- **Auth required:** ✅ Yes
- **Role required:** `passenger`
- **PHP handler:** `BookingController::index()` (role-filtered)

### Success Response — `200 OK`

Same structure as `GET /bookings`.

---

## Kotlin — Data Classes & Retrofit

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
    val driver_id: Int?,
    val pickup_address: String,
    val pickup_lat: String,
    val pickup_lng: String,
    val dropoff_address: String,
    val dropoff_lat: String,
    val dropoff_lng: String,
    val status: String,        // "requested"|"accepted"|"in_progress"|"completed"|"cancelled"|"rejected"
    val created_at: String,
    val updated_at: String?
)

data class CreateBookingResponse(
    val booking_id: Int
)
```

```kotlin
// Booking status constants — use in when() expressions
object BookingStatus {
    const val REQUESTED   = "requested"
    const val ACCEPTED    = "accepted"
    const val IN_PROGRESS = "in_progress"
    const val COMPLETED   = "completed"
    const val CANCELLED   = "cancelled"
    const val REJECTED    = "rejected"
}
```

```kotlin
// data/api/ApiService.kt (bookings section)

@POST("bookings")
suspend fun createBooking(@Body body: BookingRequest): ApiResponse<CreateBookingResponse>

@GET("bookings")
suspend fun getBookings(): ApiResponse<List<Booking>>

@GET("bookings/{id}")
suspend fun getBookingById(@Path("id") id: Int): ApiResponse<Booking>

@GET("passenger/history")
suspend fun getPassengerHistory(): ApiResponse<List<Booking>>
```

---

## Polling Pattern (Ride Status Screen)

Since there is no WebSocket in MVP, use `Handler.postDelayed` to poll for status:

```kotlin
// ui/passenger/RideStatusActivity.kt (simplified)

private val handler = Handler(Looper.getMainLooper())
private val pollInterval = 5_000L // 5 seconds

private val pollRunnable = object : Runnable {
    override fun run() {
        viewModel.fetchBooking(bookingId)
        // Stop polling when ride is terminal
        val status = viewModel.booking.value?.status
        if (status != BookingStatus.COMPLETED && status != BookingStatus.CANCELLED) {
            handler.postDelayed(this, pollInterval)
        }
    }
}

override fun onResume() {
    super.onResume()
    handler.post(pollRunnable)
}

override fun onPause() {
    super.onPause()
    handler.removeCallbacks(pollRunnable)
}
```

---

## Sync Rules

| Backend Change                           | Update Here                                 |
| ---------------------------------------- | ------------------------------------------- |
| New field added to bookings table        | `Booking` data class + response examples    |
| New booking status value added           | `BookingStatus` object + lifecycle diagram  |
| Role filtering logic changed             | Overview table + error responses            |
| New endpoint for bookings (e.g., cancel) | Add new section + `INDEX.md` endpoint table |

---

_Last updated: 2026-03-18_
