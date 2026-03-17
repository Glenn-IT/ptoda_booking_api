# PTODA Booking System — Development Checklist

> **Status Legend:**
>
> - `[ ]` — Not started
> - `[~]` — In progress
> - `[x]` — Done

---

## Phase 1 — Environment Setup

- [ ] **1.1** Install and start XAMPP (Apache + MySQL running)
- [ ] **1.2** Create project folder `C:\xampp\htdocs\ptoda_booking_api\`
- [ ] **1.3** Create MySQL database `ptoda_db` via phpMyAdmin
- [ ] **1.4** Set up Android Studio with a new Kotlin project (`PTODAApp`)
- [ ] **1.5** Install required Android Studio plugins (Kotlin, Google Maps, Firebase)
- [ ] **1.6** Configure `local.properties` (SDK path) — do **not** commit this file
- [ ] **1.7** Add `google-services.json` to the Android `app/` folder

---

## Phase 2 — Database Schema

- [ ] **2.1** Create `users` table (id, name, email, password, role, status, timestamps)
- [ ] **2.2** Create `driver_info` table (user_id FK, license_no, vehicle_no, approval_status)
- [ ] **2.3** Create `bookings` table (id, passenger_id, driver_id, pickup, dropoff, status, timestamps)
- [ ] **2.4** Create `booking_logs` table (booking_id FK, old_status, new_status, changed_at)
- [ ] **2.5** Create `fcm_tokens` table (user_id FK, token, device, updated_at)
- [ ] **2.6** Run `database/schema.sql` in phpMyAdmin to create all tables
- [ ] **2.7** Insert sample/seed data via `database/seed.sql` for testing

---

## Phase 3 — PHP REST API (Backend)

### 3.1 Core Setup

- [ ] **3.1.1** Create `config/database.php` — PDO connection to `ptoda_db`
- [ ] **3.1.2** Create `config/config.php` — JWT secret, FCM server key, app constants
- [ ] **3.1.3** Create `index.php` — entry point, route dispatcher
- [ ] **3.1.4** Create `.htaccess` — enable URL rewriting via `mod_rewrite`

### 3.2 Helpers

- [ ] **3.2.1** Create `helpers/Response.php` — `sendSuccess()` / `sendError()` JSON helpers
- [ ] **3.2.2** Create `helpers/JWT.php` — JWT encode and decode functions
- [ ] **3.2.3** Create `helpers/FCM.php` — send FCM push notification via cURL

### 3.3 Middleware

- [ ] **3.3.1** Create `middleware/AuthMiddleware.php` — validate Bearer JWT token on protected routes

### 3.4 Models

- [ ] **3.4.1** Create `models/User.php` — register, findByEmail, updateFCMToken
- [ ] **3.4.2** Create `models/Booking.php` — create, getById, updateStatus, getByDriver, getByPassenger
- [ ] **3.4.3** Create `models/Admin.php` — getAllUsers, approveDriver, deactivateUser

### 3.5 Controllers

- [ ] **3.5.1** Create `controllers/AuthController.php`
  - [ ] `POST /auth/register` — register passenger or driver
  - [ ] `POST /auth/login` — authenticate and return JWT token
- [ ] **3.5.2** Create `controllers/PassengerController.php`
  - [ ] `POST /bookings` — create a new ride request
  - [ ] `GET /bookings/{id}` — view ride status
  - [ ] `GET /passenger/history` — view ride history
- [ ] **3.5.3** Create `controllers/DriverController.php`
  - [ ] `GET /driver/requests` — get pending ride requests near driver
  - [ ] `POST /driver/accept/{booking_id}` — accept a ride request
  - [ ] `POST /driver/reject/{booking_id}` — reject a ride request
  - [ ] `POST /driver/complete/{booking_id}` — mark ride as completed
  - [ ] `PUT /driver/location` — update driver's current GPS location
- [ ] **3.5.4** Create `controllers/AdminController.php`
  - [ ] `GET /admin/users` — list all users
  - [ ] `PUT /admin/driver/approve/{id}` — approve driver account
  - [ ] `PUT /admin/user/deactivate/{id}` — deactivate any user
  - [ ] `GET /admin/bookings` — view all bookings
- [ ] **3.5.5** Create `controllers/BookingController.php`
  - [ ] `GET /bookings` — list bookings (role-aware)
  - [ ] `PUT /bookings/{id}/status` — update booking status

### 3.6 Testing API (Postman / Thunder Client)

- [ ] **3.6.1** Test `POST /auth/register` (passenger)
- [ ] **3.6.2** Test `POST /auth/register` (driver)
- [ ] **3.6.3** Test `POST /auth/login` — verify JWT returned
- [ ] **3.6.4** Test protected routes with Bearer token
- [ ] **3.6.5** Test full booking flow: create → accept → complete

---

## Phase 4 — Android App (Kotlin)

### 4.1 Project Setup

- [ ] **4.1.1** Create Kotlin Android project in Android Studio
- [ ] **4.1.2** Add dependencies to `build.gradle.kts`:
  - Retrofit2 + OkHttp (networking)
  - Gson converter (JSON parsing)
  - Google Maps SDK
  - Firebase Messaging
  - ViewModel + LiveData (Jetpack)
  - DataStore or SharedPreferences (token storage)
- [ ] **4.1.3** Create `utils/Constants.kt` — base API URL, keys

### 4.2 Networking Layer

- [ ] **4.2.1** Create `data/api/ApiClient.kt` — Retrofit instance with base URL + auth interceptor
- [ ] **4.2.2** Create `data/api/ApiService.kt` — all Retrofit interface methods
- [ ] **4.2.3** Create `data/api/ApiResponse.kt` — generic wrapper `data class ApiResponse<T>`
- [ ] **4.2.4** Create `data/local/PrefsManager.kt` — save/get JWT token and user role

### 4.3 Data Models (Kotlin data classes)

- [ ] **4.3.1** `data/models/User.kt`
- [ ] **4.3.2** `data/models/Booking.kt`
- [ ] **4.3.3** `data/models/Location.kt`

### 4.4 Repositories

- [ ] **4.4.1** `data/repository/AuthRepository.kt`
- [ ] **4.4.2** `data/repository/BookingRepository.kt`
- [ ] **4.4.3** `data/repository/UserRepository.kt`

### 4.5 Auth Screens

- [ ] **4.5.1** Build `LoginActivity.kt` + layout XML
- [ ] **4.5.2** Build `RegisterActivity.kt` + layout XML (select role: passenger/driver)
- [ ] **4.5.3** Create `AuthViewModel.kt` — call AuthRepository, expose LiveData
- [ ] **4.5.4** On login success: save JWT token, redirect to role-appropriate Home screen

### 4.6 Passenger Screens

- [ ] **4.6.1** Build `PassengerHomeActivity.kt` — Google Map fragment showing current location
- [ ] **4.6.2** Build `BookRideActivity.kt` — pick pickup & drop-off on map, request ride
- [ ] **4.6.3** Build `RideStatusActivity.kt` — polling or push status display
- [ ] **4.6.4** Create `PassengerViewModel.kt`

### 4.7 Driver Screens

- [ ] **4.7.1** Build `DriverHomeActivity.kt` — toggle online/offline, show map
- [ ] **4.7.2** Build `RideRequestActivity.kt` — show incoming request, accept/reject buttons
- [ ] **4.7.3** Build `ActiveRideActivity.kt` — show passenger pickup on map, mark complete
- [ ] **4.7.4** Create `DriverViewModel.kt`

### 4.8 Admin Screens

- [ ] **4.8.1** Build `AdminDashboardActivity.kt` — stats overview
- [ ] **4.8.2** Build `ManageUsersActivity.kt` — list, approve, deactivate users
- [ ] **4.8.3** Create `AdminViewModel.kt`

### 4.9 Firebase Cloud Messaging (FCM)

- [ ] **4.9.1** Create Firebase project at [console.firebase.google.com](https://console.firebase.google.com)
- [ ] **4.9.2** Add Android app to Firebase project, download `google-services.json`
- [ ] **4.9.3** Implement `PTODAFirebaseMessagingService.kt`
  - Override `onNewToken` — send token to API (`PUT /user/fcm-token`)
  - Override `onMessageReceived` — show notification to user
- [ ] **4.9.4** Register service in `AndroidManifest.xml`
- [ ] **4.9.5** Test push notification: driver receives notification on new booking

### 4.10 Google Maps API

- [ ] **4.10.1** Enable Maps SDK for Android in Google Cloud Console
- [ ] **4.10.2** Get API key, add to `AndroidManifest.xml` as `<meta-data>`
- [ ] **4.10.3** Add `SupportMapFragment` in passenger and driver screens
- [ ] **4.10.4** Implement location permission request (`ACCESS_FINE_LOCATION`)
- [ ] **4.10.5** Show user's current location on map with `FusedLocationProviderClient`
- [ ] **4.10.6** Implement place picker / tap-to-set-marker for pickup & drop-off

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

_Last updated: 2026-03-17_
