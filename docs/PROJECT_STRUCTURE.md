# PTODA Booking System — Project Structure

## Overview

This document defines the full project structure for both the **PHP REST API** (backend) and the **Android (Kotlin)** app (frontend).

---

## 📁 Backend — PHP REST API (`C:\xampp\htdocs\ptoda_booking_api\`)

```
ptoda_booking_api/
├── config/
│   ├── database.php              # PDO database connection
│   └── config.php                # App constants (JWT secret, FCM key, etc.)
│
├── helpers/
│   ├── Response.php              # Standardized JSON response helper
│   ├── JWT.php                   # JWT encode/decode helper
│   └── FCM.php                   # Firebase Cloud Messaging helper
│
├── middleware/
│   └── AuthMiddleware.php        # JWT token verification middleware
│
├── models/
│   ├── User.php                  # User model (passenger & driver)
│   ├── Booking.php               # Booking model
│   └── Admin.php                 # Admin model (getAllDrivers, getPendingDrivers, approveDriver, rejectDriver, activateUser, deactivateUser, deleteUser)
│
├── controllers/
│   ├── AuthController.php        # Register, Login, Logout
│   ├── PassengerController.php   # Passenger-specific actions
│   ├── DriverController.php      # Driver-specific actions
│   ├── BookingController.php     # Booking CRUD & status management
│   └── AdminController.php       # Admin panel actions (includes driver pending list, approve, reject)
│
├── routes/
│   └── api.php                   # Route definitions (maps URI → controller)
│
├── database/
│   ├── schema.sql                # Full MySQL schema (CREATE TABLE scripts)
│   └── seed.sql                  # Sample data for testing
│
├── uploads/
│   └── drivers/                  # Driver license / profile photo uploads
│
├── logs/
│   └── error.log                 # API error log
│
├── .htaccess                     # URL rewriting (Apache mod_rewrite)
├── index.php                     # Entry point — routes all requests
└── README.md                     # API documentation
```

---

## 📱 Frontend — Android App (Kotlin, Android Studio)

```
PTODAApp/
├── app/
│   ├── src/
│   │   ├── main/
│   │   │   ├── java/com/ptoda/app/
│   │   │   │   ├── data/
│   │   │   │   │   ├── api/
│   │   │   │   │   │   ├── ApiService.kt          # Retrofit API interface
│   │   │   │   │   │   ├── ApiClient.kt           # Retrofit client setup
│   │   │   │   │   │   └── ApiResponse.kt         # Generic API response wrapper
│   │   │   │   │   ├── models/
│   │   │   │   │   │   ├── User.kt                # User data class
│   │   │   │   │   │   ├── Booking.kt             # Booking data class
│   │   │   │   │   │   └── Location.kt            # LatLng wrapper
│   │   │   │   │   ├── repository/
│   │   │   │   │   │   ├── AuthRepository.kt      # Auth API calls
│   │   │   │   │   │   ├── BookingRepository.kt   # Booking API calls
│   │   │   │   │   │   └── UserRepository.kt      # User API calls
│   │   │   │   │   └── local/
│   │   │   │   │       └── PrefsManager.kt        # SharedPreferences (token, user info)
│   │   │   │   │
│   │   │   │   ├── ui/
│   │   │   │   │   ├── auth/
│   │   │   │   │   │   ├── LoginActivity.kt
│   │   │   │   │   │   ├── RegisterActivity.kt
│   │   │   │   │   │   └── AuthViewModel.kt
│   │   │   │   │   ├── passenger/
│   │   │   │   │   │   ├── PassengerHomeActivity.kt
│   │   │   │   │   │   ├── BookRideActivity.kt
│   │   │   │   │   │   ├── RideStatusActivity.kt
│   │   │   │   │   │   └── PassengerViewModel.kt
│   │   │   │   │   ├── driver/
│   │   │   │   │   │   ├── DriverHomeActivity.kt
│   │   │   │   │   │   ├── RideRequestActivity.kt
│   │   │   │   │   │   ├── ActiveRideActivity.kt
│   │   │   │   │   │   └── DriverViewModel.kt
│   │   │   │   │   └── admin/
│   │   │   │   │       ├── AdminDashboardActivity.kt
│   │   │   │   │       ├── ManageUsersActivity.kt
│   │   │   │   │       └── AdminViewModel.kt
│   │   │   │   │
│   │   │   │   ├── services/
│   │   │   │   │   └── PTODAFirebaseMessagingService.kt  # FCM push notification handler
│   │   │   │   │
│   │   │   │   └── utils/
│   │   │   │       ├── Constants.kt               # Base URL, keys
│   │   │   │       ├── Extensions.kt              # Kotlin extension functions
│   │   │   │       └── NetworkUtils.kt            # Internet connectivity check
│   │   │   │
│   │   │   ├── res/
│   │   │   │   ├── layout/                        # XML layouts per Activity/Fragment
│   │   │   │   ├── drawable/                      # Icons, images
│   │   │   │   ├── values/
│   │   │   │   │   ├── strings.xml
│   │   │   │   │   ├── colors.xml
│   │   │   │   │   └── themes.xml
│   │   │   │   └── raw/
│   │   │   │       └── google-services.json       # Firebase config (NOT in VCS)
│   │   │   │
│   │   │   └── AndroidManifest.xml
│   │   │
│   │   └── test/ & androidTest/                   # Unit and instrumented tests
│   │
│   ├── build.gradle.kts                           # App-level Gradle config
│   └── google-services.json                       # Firebase config (root app/)
│
├── build.gradle.kts                               # Project-level Gradle config
├── settings.gradle.kts
└── local.properties                               # SDK path (NOT in VCS)
```

---

## 🗄️ MySQL Database — Table Overview

| Table          | Purpose                                       |
| -------------- | --------------------------------------------- |
| `users`        | Stores all users (passengers, drivers, admin) |
| `driver_info`  | Extended driver details (license, status)     |
| `bookings`     | All ride booking records                      |
| `booking_logs` | Status change history per booking             |
| `fcm_tokens`   | FCM device tokens per user                    |

---

## 🔌 API Base URL (Local Development)

```
http://localhost/ptoda_booking_api/          (Browser / Postman — Apache ✅ Confirmed)
http://10.0.2.2/ptoda_booking_api/           (Android Emulator → localhost)
http://192.168.0.100/ptoda_booking_api/      (Physical device → PC LAN IP ✅ Active)
```

---

_Last updated: 2026-03-21_
