# PTODA Booking System — Development Checklist

> **Status Legend:**
>
> - `[ ]` — Not started
> - `[~]` — In progress
> - `[x]` — Done

---

## Phase 1 — Environment Setup

- [x] **1.1** Install and start XAMPP (Apache + MySQL running)
- [x] **1.2** Create project folder `C:\xampp\htdocs\ptoda_booking_api\`
- [x] **1.3** Create MySQL database `ptoda_db` via phpMyAdmin
- [x] **1.4** Set up Android Studio with a new Kotlin project (`PTODAApp`)
- [x] **1.5** Install required Android Studio plugins (Kotlin ✓, Firebase project/google-services.json ✓, Studio plugins pending)
- [x] **1.6** Configure `local.properties` (SDK path) — do **not** commit this file
- [x] **1.7** Add `google-services.json` to the Android `app/` folder

---

## Phase 2 — Database Schema

- [x] **2.1** Create `users` table (id, name, email, password, role, status, timestamps)
- [x] **2.2** Create `driver_info` table (user_id FK, license_no, vehicle_no, approval_status)
- [x] **2.3** Create `bookings` table (id, passenger_id, driver_id, pickup, dropoff, status, timestamps)
- [x] **2.4** Create `booking_logs` table (booking_id FK, old_status, new_status, changed_at)
- [x] **2.5** Create `fcm_tokens` table (user_id FK, token, device, updated_at)
- [x] **2.6** Run `database/schema.sql` in phpMyAdmin to create all tables
- [x] **2.7** Insert sample/seed data via `database/seed.sql` for testing

---

## Phase 3 — PHP REST API (Backend)

### 3.1 Core Setup

- [x] **3.1.1** Create `config/database.php` — PDO connection to `ptoda_db`
- [x] **3.1.2** Create `config/config.php` — JWT secret, FCM server key, app constants
- [x] **3.1.3** Create `index.php` — entry point, route dispatcher
- [x] **3.1.4** Create `.htaccess` — enable URL rewriting via `mod_rewrite`

### 3.2 Helpers

- [x] **3.2.1** Create `helpers/Response.php` — `sendSuccess()` / `sendError()` JSON helpers
- [x] **3.2.2** Create `helpers/JWT.php` — JWT encode and decode functions
- [x] **3.2.3** Create `helpers/FCM.php` — send FCM push notification via cURL

### 3.3 Middleware

- [x] **3.3.1** Create `middleware/AuthMiddleware.php` — validate Bearer JWT token on protected routes

### 3.4 Models

- [x] **3.4.1** Create `models/User.php` — register, findByEmail, updateFCMToken
- [x] **3.4.2** Create `models/Booking.php` — create, getById, updateStatus, getByDriver, getByPassenger
- [x] **3.4.3** Create `models/Admin.php` — getAllDrivers, getPendingDrivers, approveDriver, rejectDriver, activateUser, deactivateUser, deleteUser

### 3.5 Controllers

- [x] **3.5.1** Create `controllers/AuthController.php`
  - [x] `POST /auth/register` — register passenger or driver
  - [x] `POST /auth/login` — authenticate and return JWT token
- [x] **3.5.2** Create `controllers/PassengerController.php`
  - [x] `POST /bookings` — create a new ride request (handled by `BookingController`)
  - [x] `GET /bookings/{id}` — view ride status (handled by `BookingController`)
  - [x] `GET /passenger/history` — view ride history (handled by `BookingController`)
- [x] **3.5.3** Create `controllers/DriverController.php`
  - [x] `GET /driver/requests` — get pending ride requests near driver
  - [x] `POST /driver/accept/{booking_id}` — accept a ride request
  - [x] `POST /driver/reject/{booking_id}` — reject a ride request
  - [x] `POST /driver/complete/{booking_id}` — mark ride as completed
  - [x] `PUT /driver/location` — update driver's current GPS location
- [x] **3.5.4** Create `controllers/AdminController.php`
  - [x] `GET /admin/users` — list all users
  - [x] `GET /admin/drivers/pending` — list all drivers with `approval_status = 'pending'`
  - [x] `PUT /admin/driver/approve/{id}` — approve driver account
  - [x] `PUT /admin/driver/reject/{id}` — reject driver account
  - [x] `PUT /admin/user/activate/{id}` — re-activate a user account
  - [x] `PUT /admin/user/deactivate/{id}` — deactivate a user account
  - [x] `DELETE /admin/user/{id}` — permanently delete a user
  - [x] `GET /admin/bookings` — view all bookings
- [x] **3.5.5** Create `controllers/BookingController.php`
  - [x] `GET /bookings` — list bookings (role-aware)
  - [x] `PUT /bookings/{id}/status` — update booking status

### 3.6 Testing API (Postman / Thunder Client) - DETAILED POSTMAN GUIDE

**Working URL**: `http://localhost:8001` (PHP dev server - RECOMMENDED)

**Apache Alt**: `http://localhost/ptoda_booking_api` (after mod_rewrite full fix)

**Run server**: `cd c:/xampp/htdocs/ptoda_booking_api && php -S localhost:8001`

**Headers** (all requests): `Content-Type: application/json`

#### Pre-reqs:

**🚀 Start PHP Dev Server (recommended)**:

```
cd c:/xampp/htdocs/ptoda_booking_api
php -S localhost:8001
```

1. XAMPP MySQL running (Apache optional)
2. Database seeded (`database/seed.sql`)
3. Postman/Thunder Client

**All URLs now**: Replace `http://localhost/ptoda_booking_api` → `http://localhost:8001`

**Android**:

- Emulator: `http://10.0.2.2:8001`
- Phone: `http://YOUR_PC_IP:8001` (`ipconfig`)

#### 3.6.1 Test `POST /auth/register` (passenger) ✓

**URL**: `POST http://localhost/ptoda_booking_api/auth/register`

**Body** (raw JSON):

```json
{
  "name": "Test Passenger",
  "email": "passenger@test.com",
  "password": "password123",
  "role": "passenger"
}
```

**Expected Response** (201):

```json
{
  "success": true,
  "data": { "user_id": 5 },
  "message": "Registration successful."
}
```

**Edge Cases**:

- Missing `email` → 422 `Field 'email' is required`
- Duplicate email → 409 `Email is already registered`
- Invalid role → 422 `Role must be 'passenger' or 'driver'`

#### 3.6.2 Test `POST /auth/register` (driver) ✓

**URL**: `POST http://localhost/ptoda_booking_api/auth/register`

**Body**:

```json
{
  "name": "Test Driver",
  "email": "driver@test.com",
  "password": "password123",
  "role": "driver",
  "license_no": "DL123456",
  "vehicle_no": "ABC123"
}
```

**Expected** (201): `{ "user_id": 6 }`

#### 3.6.3 Test `POST /auth/login` — verify JWT returned ✓

**URL**: `POST http://localhost/ptoda_booking_api/auth/login`

**Body** (use seeded or registered creds):

```json
{
  "email": "passenger@test.com",
  "password": "password123"
}
```

**Expected** (200):

```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": { "id": 5, "name": "Test Passenger", ... }
  }
}
```

**💡 Copy the `token` value for next tests!**

**Admin Login** (if seeded): Check `users` table for admin.

#### 3.6.4 Test protected routes with Bearer token ✓

**1. Without token** (should fail):

- `GET http://localhost/ptoda_booking_api/bookings`
- **Expected**: 401 `Authorization token is required`

**2. Invalid token**:

- `Authorization: Bearer invalidtoken`
- **Expected**: 401 `Invalid token`

**3. Valid token** (passenger):

```
GET http://localhost/ptoda_booking_api/bookings
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Expected**: 200 array of passenger bookings

**Wrong role**:

- Driver token → `POST /bookings` → 403 `You do not have permission`

#### 3.6.5 Test full booking flow: create → accept → complete ✓

**Setup**: Login as passenger (token P) and driver (token D). Note driver must be 'approved'.

**1. Passenger creates**:

```
POST http://localhost/ptoda_booking_api/bookings
Authorization: Bearer [P]
```

**Body**:

```json
{
  "pickup_address": "Quiapo Market",
  "pickup_lat": 14.5995,
  "pickup_lng": 120.975,
  "dropoff_address": "Rizal Park",
  "dropoff_lat": 14.5833,
  "dropoff_lng": 120.9797
}
```

**Response** → Copy `booking.id` (e.g. 10)

**2. Driver sees pending**:

```
GET http://localhost/ptoda_booking_api/driver/requests
Authorization: Bearer [D]
```

→ Find booking 10 in `status: "requested"`

**3. Driver accepts**:

```
POST http://localhost/ptoda_booking_api/driver/accept/10
Authorization: Bearer [D]
```

**Expected**: `Ride accepted successfully` (status → 'accepted')

**4. Driver completes**:

```
POST http://localhost/ptoda_booking_api/driver/complete/10
Authorization: Bearer [D]
```

**Expected**: `Ride marked as completed` (status → 'completed')

**5. Verify**:

```
GET http://localhost/ptoda_booking_api/bookings/10
Authorization: Bearer [P] or [D]
```

→ `status: "completed", driver_id: X, passenger_id: Y`

**Pro Tips**:

- Use Postman Collection Variables: `{{base_url}}`, `{{passenger_token}}`, `{{driver_token}}`
- Test FCM notifications if tokens set
- Check `booking_logs` table: `SELECT * FROM booking_logs ORDER BY changed_at DESC`
- Health check: `GET {{base_url}}`

**Collection JSON** (import to Postman):

```json
{
  "info": {...},
  "variable": [
    { "key": "base_url", "value": "http://localhost/ptoda_booking_api" }
  ],
  "item": [...]
}
```

- [x] **3.6.1** Test `POST /auth/register` (passenger)
- [x] **3.6.2** Test `POST /auth/register` (driver)
- [x] **3.6.3** Test `POST /auth/login` — verify JWT returned
- [x] **3.6.4** Test protected routes with Bearer token
- [x] **3.6.5** Test full booking flow: create → accept → complete
- [x] **3.6.6** Test Admin Driver Approval flow
- [x] **3.6.7** Test Admin User Activate / Deactivate
- [x] **3.6.8** Test Admin Delete User

#### 3.6.6 Test Admin Driver Approval flow ✓

**Pre-req**: Admin JWT token (login with seeded admin account). Newly registered driver with `approval_status = 'pending'`.

**1. List pending drivers**:

```
GET http://localhost/ptoda_booking_api/admin/drivers/pending
Authorization: Bearer [ADMIN_TOKEN]
```

**Expected** (200):

```json
{
  "success": true,
  "data": [
    {
      "id": 6,
      "name": "Test Driver",
      "email": "driver@test.com",
      "created_at": "2026-03-17 10:00:00",
      "license_no": "DL123456",
      "vehicle_no": "ABC123",
      "approval_status": "pending"
    }
  ]
}
```

**2a. Approve the driver** (use the driver's `id` from above):

```
PUT http://localhost/ptoda_booking_api/admin/driver/approve/6
Authorization: Bearer [ADMIN_TOKEN]
```

**Expected** (200): `"Driver approved successfully."`

**2b. OR — Reject the driver**:

```
PUT http://localhost/ptoda_booking_api/admin/driver/reject/6
Authorization: Bearer [ADMIN_TOKEN]
```

**Expected** (200): `"Driver rejected successfully."`

**3. Verify login is blocked until approved**:

- Try `POST /auth/login` with driver credentials **before** approval.
- **Expected** (403): `"Your driver account is pending admin approval."`
- After approval, login succeeds and returns a JWT.

**Edge Cases**:

- Non-existent driver ID → 404 `Driver not found or already approved/rejected`
- Non-admin token → 403 `You do not have permission`
- Passenger token → 403 `You do not have permission`

---

#### 3.6.7 Test Admin User Activate / Deactivate ✓

**Pre-req**: Admin JWT token. A target user ID (passenger or driver).

**1. Deactivate a user**:

```
PUT http://localhost/ptoda_booking_api/admin/user/deactivate/5
Authorization: Bearer [ADMIN_TOKEN]
```

**Expected** (200): `"User deactivated successfully."`

**Verify — login is blocked after deactivate**:

```
POST http://localhost/ptoda_booking_api/auth/login
Body: { "email": "passenger@test.com", "password": "password123" }
```

**Expected** (403): `"Your account has been deactivated. Contact admin."`

**2. Activate the same user**:

```
PUT http://localhost/ptoda_booking_api/admin/user/activate/5
Authorization: Bearer [ADMIN_TOKEN]
```

**Expected** (200): `"User activated successfully."`

**Verify — login works again** after activation.

**Edge Cases**:

- Deactivate already-inactive user → 404 `User not found or already inactive`
- Activate already-active user → 404 `User not found or already active`
- Non-admin token → 403 `You do not have permission`

---

#### 3.6.8 Test Admin Delete User ✓

> ⚠️ **Destructive action** — permanently removes the user and all related records (driver_info, fcm_tokens). Use with caution; intended for test cleanup or GDPR-style removal.

**Pre-req**: Admin JWT token. A target user ID to delete.

**1. Delete a user**:

```
DELETE http://localhost/ptoda_booking_api/admin/user/6
Authorization: Bearer [ADMIN_TOKEN]
```

**Expected** (200): `"User deleted successfully."`

**2. Verify user is gone**:

```
GET http://localhost/ptoda_booking_api/admin/users
Authorization: Bearer [ADMIN_TOKEN]
```

→ User with `id: 6` no longer appears in the list.

**3. Verify login is gone**:

```
POST http://localhost/ptoda_booking_api/auth/login
Body: { "email": "driver@test.com", "password": "password123" }
```

**Expected** (401): `"Invalid email or password."`

**Edge Cases**:

- Non-existent user ID → 404 `User not found`
- Non-admin token → 403 `You do not have permission`

---

## Phase 4 — Android App (Kotlin)

### 4.1 Project Setup

- [x] **4.1.1** Create Kotlin Android project in Android Studio
- [x] **4.1.2** Add dependencies to `build.gradle.kts`:
  - Retrofit2 + OkHttp (networking)
  - Gson converter (JSON parsing)
  - Google Maps SDK
  - Firebase Messaging
  - ViewModel + LiveData (Jetpack)
  - DataStore or SharedPreferences (token storage)
- [x] **4.1.3** Create `utils/Constants.kt` — base API URL, keys

### 4.2 Networking Layer

- [x] **4.2.1** Create `data/api/ApiClient.kt` — Retrofit instance with base URL + auth interceptor
- [x] **4.2.2** Create `data/api/ApiService.kt` — all Retrofit interface methods
- [x] **4.2.3** Create `data/api/ApiResponse.kt` — generic wrapper `data class ApiResponse<T>`
- [x] **4.2.4** Create `data/local/PrefsManager.kt` — save/get JWT token and user role

### 4.3 Data Models (Kotlin data classes)

- [x] **4.3.1** `data/models/User.kt`
- [x] **4.3.2** `data/models/Booking.kt`
- [x] **4.3.3** `data/models/Location.kt` _(implemented as `DriverModels.kt`, `AdminModels.kt`, `AuthModels.kt`, `FcmModels.kt`)_

### 4.4 Repositories

- [x] **4.4.1** `data/repository/AuthRepository.kt`
- [x] **4.4.2** `data/repository/BookingRepository.kt`
- [x] **4.4.3** `data/repository/UserRepository.kt` _(+ `AdminRepository.kt`, `BaseRepository.kt`)_

### 4.5 Auth Screens

- [x] **4.5.1** Build `LoginActivity.kt` + layout XML
- [x] **4.5.2** Build `RegisterActivity.kt` + layout XML (select role: passenger/driver)
- [x] **4.5.3** Create `AuthViewModel.kt` — call AuthRepository, expose LiveData
- [x] **4.5.4** On login success: save JWT token, redirect to role-appropriate Home screen

### 4.6 Passenger Screens

- [x] **4.6.1** Build `PassengerHomeActivity.kt` — Google Map fragment showing current location
- [x] **4.6.2** Build `BookRideActivity.kt` — pick pickup & drop-off on map, request ride
- [x] **4.6.3** Build `RideStatusActivity.kt` — polling or push status display
- [x] **4.6.4** Create `PassengerViewModel.kt`

### 4.7 Driver Screens

- [x] **4.7.1** Build `DriverHomeActivity.kt` — toggle online/offline, show map
- [x] **4.7.2** Build `RideRequestActivity.kt` — show incoming request, accept/reject buttons
- [x] **4.7.3** Build `ActiveRideActivity.kt` — show passenger pickup on map, mark complete
- [x] **4.7.4** Create `DriverViewModel.kt`

### 4.8 Admin Screens

- [x] **4.8.1** Build `AdminDashboardActivity.kt` — stats overview
- [x] **4.8.2** Build `ManageUsersActivity.kt` — list, approve, deactivate users
- [x] **4.8.3** Create `AdminViewModel.kt`

### 4.9 Firebase Cloud Messaging (FCM)

- [x] **4.9.1** Create Firebase project at [console.firebase.google.com](https://console.firebase.google.com)
- [x] **4.9.2** Add Android app to Firebase project, download `google-services.json`
- [x] **4.9.3** Implement `PTODAFirebaseMessagingService.kt`
  - Override `onNewToken` — save token locally + sync to API (`PUT /user/fcm-token`) if already logged in
  - Override `onMessageReceived` — handle both `notification` and `data` payloads; show notification with `PendingIntent` tap-to-open (role-aware routing: driver → `DriverHomeActivity`, passenger → `RideStatusActivity` / `PassengerHomeActivity`)
- [x] **4.9.4** Register service in `AndroidManifest.xml` + `POST_NOTIFICATIONS` runtime permission requested in `PassengerHomeActivity` and `DriverHomeActivity` (Android 13+)
- [x] **4.9.5** FCM token sync after login wired in `AuthRepository.login()` — calls `syncFcmTokenIfAvailable()` which posts locally-stored FCM token to `PUT /user/fcm-token` immediately after JWT is saved
- [~] **4.9.6** End-to-end test: driver receives push notification when passenger creates a booking — requires XAMPP PHP server running + real FCM server key in `config/config.php` + physical device or emulator with Play Services

### 4.10 Google Maps API

- [~] **4.10.1** Enable Maps SDK for Android in Google Cloud Console _(manual step — go to console.cloud.google.com → APIs & Services → Enable "Maps SDK for Android")_
- [~] **4.10.2** Get API key, add to `AndroidManifest.xml` as `<meta-data>` _(placeholder added — replace `YOUR_MAPS_API_KEY` with real key from Google Cloud Console)_
- [x] **4.10.3** Add `SupportMapFragment` in passenger and driver screens
- [x] **4.10.4** Implement location permission request (`ACCESS_FINE_LOCATION`)
- [x] **4.10.5** Show user's current location on map with `FusedLocationProviderClient`
- [x] **4.10.6** Implement tap-to-set-marker for pickup & drop-off in `BookRideActivity`
  - `MapMode` enum (`NONE` / `PICKUP` / `DROPOFF`) drives map click behaviour
  - Active mode button fills blue; tapping a second time deactivates it
  - Map tap → places coloured marker (red=pickup, green=dropoff), animates camera, fills lat/lng fields
  - `reverseGeocode()` auto-fills address field via Android `Geocoder` (async on API 33+, coroutine on lower)
  - "Use Current Location" button now delegates to `setPickupFromMap()` so it also places the marker
  - All 5 hint strings added to `strings.xml`

---

## Phase 5 — Integration Testing

- [ ] **5.1** Run PHP API on XAMPP, connect Android emulator using `10.0.2.2`
- [ ] **5.2** Test register + login flow end-to-end
- [ ] **5.3** Test full ride booking flow (passenger → driver → complete)
- [ ] **5.4** Test FCM notification delivery on ride request acceptance
- [ ] **5.5** Test Admin user management endpoints
- [ ] **5.6** Test on a physical Android device (use PC local IP)

---

## Phase 6 — Polish & Pre-production Prep

- [ ] **6.1** Add input validation (API + Android side)
- [ ] **6.2** Add error handling and user-friendly error messages
- [ ] **6.3** Secure API: rate limiting, HTTPS prep, sanitize inputs
- [ ] **6.4** Review and clean up all TODO comments in code
- [ ] **6.5** Write API documentation in `README.md`
- [ ] **6.6** Create `.env`-style config approach for production secrets
- [ ] **6.7** Prepare VPS/production deployment plan (next phase)

---

_Last updated: 2026-03-19 — Phase 4.1–4.9 Android app built. Pending: Gradle sync, Maps API key, FCM test, integration testing._
