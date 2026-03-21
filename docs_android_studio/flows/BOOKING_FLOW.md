# Flow — Booking (Full Ride Lifecycle)

> **Related files:** `api/BOOKINGS.md` · `api/DRIVER.md` · `models/BOOKING.md` · `api/FCM.md`

---

## Status Lifecycle

```
[requested] → [accepted] → [in_progress] → [completed]
                        ↘ [rejected]
[requested] ↘ [cancelled]   (future)
```

---

## Step-by-Step Flow

### 1. Passenger Creates a Booking

```
Passenger App               PHP Backend              MySQL
     │                           │                     │
     │── POST /bookings ────────>│                     │
     │   { pickup, dropoff }     │── INSERT bookings ─>│
     │                           │   status='requested'│
     │                           │── INSERT booking_log│
     │                           │   new_status='requested'
     │<── 201 { booking_id:10 } ─│                     │
     │                           │                     │
     │  (navigate to             │                     │
     │   RideStatusActivity)     │                     │
```

### 2. Driver Sees Pending Requests

```
Driver App                  PHP Backend              MySQL
     │                           │                     │
     │── GET /driver/requests ──>│                     │
     │                           │── SELECT bookings ─>│
     │                           │   WHERE status=      │
     │                           │   'requested'        │
     │<── 200 [ booking #10 ] ───│                     │
     │                           │                     │
     │  (show in                 │                     │
     │   RideRequestActivity)    │                     │
```

### 3a. Driver Accepts

```
Driver App                  PHP Backend              MySQL          FCM
     │                           │                     │              │
     │── POST /driver/accept/10 >│                     │              │
     │                           │── UPDATE bookings ─>│              │
     │                           │   status='accepted' │              │
     │                           │   driver_id=3       │              │
     │                           │── INSERT log ──────>│              │
     │                           │                     │              │
     │                           │── GET passenger FCM token ────────>│
     │                           │<── token ──────────────────────────│
     │                           │── PUSH notify passenger ──────────>│
     │                           │   "Driver is on the way!"          │
     │<── 200 "Ride accepted" ───│                     │              │
```

### 3b. Driver Rejects

```
Driver App                  PHP Backend              MySQL
     │                           │                     │
     │── POST /driver/reject/10 >│                     │
     │                           │── UPDATE bookings ─>│
     │                           │   status='rejected' │
     │                           │── INSERT log ──────>│
     │<── 200 "Ride rejected" ───│                     │
     │                           │                     │
     │  (booking removed         │                     │
     │   from driver list)       │                     │
```

### 4. Driver Completes the Ride

```
Driver App                  PHP Backend              MySQL          FCM
     │                           │                     │              │
     │─ POST /driver/complete/10>│                     │              │
     │                           │── UPDATE bookings ─>│              │
     │                           │   status='completed'│              │
     │                           │── INSERT log ──────>│              │
     │                           │── PUSH notify passenger ──────────>│
     │                           │   "Your ride is complete!"         │
     │<── 200 "Ride completed" ──│                     │              │
```

### 5. Passenger Polls for Status (MVP)

```kotlin
// RideStatusActivity.kt — poll every 5 seconds until terminal status

private val pollRunnable = object : Runnable {
    override fun run() {
        viewModel.fetchBooking(bookingId)
        val status = viewModel.booking.value?.status
        if (!BookingStatus.isTerminal(status ?: "")) {
            handler.postDelayed(this, 5_000L)
        }
    }
}
```

---

## Booking Object at Each Stage

| Stage       | `status`      | `driver_id` |
| ----------- | ------------- | ----------- |
| Created     | `requested`   | `null`      |
| Accepted    | `accepted`    | `3` (set)   |
| In Progress | `in_progress` | `3`         |
| Completed   | `completed`   | `3`         |
| Rejected    | `rejected`    | `null`      |

---

## Sync Rules

| Backend Change                       | Update Here                         |
| ------------------------------------ | ----------------------------------- |
| New status value added               | Lifecycle diagram + stage table     |
| FCM payload content changed          | Steps 3a and 4 notification content |
| Cancel endpoint added for passengers | Add Step 3c with cancel flow        |

---

_Last updated: 2026-03-18_
