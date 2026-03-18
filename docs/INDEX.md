# PTODA Booking System — Documentation Index

> **Single Source of Truth** for the PHP backend API and the Android (Kotlin) app.
> Keep this folder synced with every backend change.

---

## 📁 Folder Structure

```
docs/
├── INDEX.md                        ← You are here — master navigation
│
├── api/
│   ├── AUTH.md                     ← /auth/register, /auth/login
│   ├── BOOKINGS.md                 ← /bookings (passenger & driver)
│   ├── DRIVER.md                   ← /driver/* endpoints
│   ├── ADMIN.md                    ← /admin/* endpoints
│   └── FCM.md                      ← /user/fcm-token
│
├── models/
│   ├── USER.md                     ← users table + Kotlin data class
│   ├── BOOKING.md                  ← bookings table + Kotlin data class
│   ├── DRIVER_INFO.md              ← driver_info table + Kotlin data class
│   └── FCM_TOKEN.md                ← fcm_tokens table + Kotlin data class
│
└── flows/
    ├── AUTH_FLOW.md                ← Register → Login → Token usage
    ├── BOOKING_FLOW.md             ← Full ride lifecycle
    ├── DRIVER_APPROVAL_FLOW.md     ← Driver registration → Admin approval
    └── ANDROID_SETUP.md            ← Retrofit + FCM + Maps Android bootstrap
```

---

## 🔌 API Base URL

| Context                        | URL                                     |
| ------------------------------ | --------------------------------------- |
| Localhost (browser / Postman)  | `http://localhost/ptoda_booking_api/`   |
| PHP dev server (recommended)   | `http://localhost:8001/`                |
| Android Emulator → host PC     | `http://10.0.2.2/ptoda_booking_api/`    |
| Android Emulator → PHP dev srv | `http://10.0.2.2:8001/`                 |
| Physical device (same Wi-Fi)   | `http://192.168.x.x/ptoda_booking_api/` |

---

## 🔐 Authentication

All protected endpoints require a **JWT Bearer token** in the request header:

```
Authorization: Bearer <jwt_token>
```

Tokens are obtained from `POST /auth/login`. Roles encoded in the JWT payload:
`passenger` | `driver` | `admin`

---

## 📋 All Endpoints at a Glance

| Method   | Endpoint                        | Auth | Role      | Doc File          |
| -------- | ------------------------------- | ---- | --------- | ----------------- |
| `POST`   | `/auth/register`                | ❌   | —         | `api/AUTH.md`     |
| `POST`   | `/auth/login`                   | ❌   | —         | `api/AUTH.md`     |
| `POST`   | `/bookings`                     | ✅   | Passenger | `api/BOOKINGS.md` |
| `GET`    | `/bookings`                     | ✅   | Any       | `api/BOOKINGS.md` |
| `GET`    | `/bookings/{id}`                | ✅   | Any       | `api/BOOKINGS.md` |
| `GET`    | `/passenger/history`            | ✅   | Passenger | `api/BOOKINGS.md` |
| `GET`    | `/driver/requests`              | ✅   | Driver    | `api/DRIVER.md`   |
| `POST`   | `/driver/accept/{booking_id}`   | ✅   | Driver    | `api/DRIVER.md`   |
| `POST`   | `/driver/reject/{booking_id}`   | ✅   | Driver    | `api/DRIVER.md`   |
| `POST`   | `/driver/complete/{booking_id}` | ✅   | Driver    | `api/DRIVER.md`   |
| `PUT`    | `/driver/location`              | ✅   | Driver    | `api/DRIVER.md`   |
| `PUT`    | `/user/fcm-token`               | ✅   | Any       | `api/FCM.md`      |
| `GET`    | `/admin/users`                  | ✅   | Admin     | `api/ADMIN.md`    |
| `GET`    | `/admin/drivers/pending`        | ✅   | Admin     | `api/ADMIN.md`    |
| `GET`    | `/admin/bookings`               | ✅   | Admin     | `api/ADMIN.md`    |
| `PUT`    | `/admin/driver/approve/{id}`    | ✅   | Admin     | `api/ADMIN.md`    |
| `PUT`    | `/admin/driver/reject/{id}`     | ✅   | Admin     | `api/ADMIN.md`    |
| `PUT`    | `/admin/user/activate/{id}`     | ✅   | Admin     | `api/ADMIN.md`    |
| `PUT`    | `/admin/user/deactivate/{id}`   | ✅   | Admin     | `api/ADMIN.md`    |
| `DELETE` | `/admin/user/{id}`              | ✅   | Admin     | `api/ADMIN.md`    |

---

## 🗄️ Database Tables

| Table          | Purpose                                     | Model Doc               |
| -------------- | ------------------------------------------- | ----------------------- |
| `users`        | All users — passengers, drivers, admins     | `models/USER.md`        |
| `driver_info`  | Extended driver profile + approval status   | `models/DRIVER_INFO.md` |
| `bookings`     | All ride booking records                    | `models/BOOKING.md`     |
| `booking_logs` | Audit trail of every booking status change  | `models/BOOKING.md`     |
| `fcm_tokens`   | FCM push token per user (upserted on login) | `models/FCM_TOKEN.md`   |

---

## 🔄 Sync Rules — When to Update Docs

> **Rule:** Every backend change that affects an API contract MUST update the matching doc file before the PR is merged.

| Backend Change                           | Update These Files                            |
| ---------------------------------------- | --------------------------------------------- |
| New endpoint added                       | `INDEX.md` table + matching `api/*.md` file   |
| Endpoint request/response shape changed  | Matching `api/*.md` + `models/*.md` if needed |
| New DB column added                      | Matching `models/*.md` + Kotlin data class    |
| New DB table added                       | New `models/*.md` + `INDEX.md` table          |
| Auth rules changed (role, status checks) | `api/AUTH.md` + matching `flows/*.md`         |
| New business flow                        | New or updated `flows/*.md`                   |
| Bug found & fixed                        | `BUGS_AND_FIXES.md`                           |

---

## 📚 Legacy Guide Files (kept for reference)

| File                         | Purpose                                     |
| ---------------------------- | ------------------------------------------- |
| `README.md`                  | Setup instructions, quick endpoint overview |
| `PROJECT_STRUCTURE.md`       | Backend + Android folder structure          |
| `DEVELOPMENT_CHECKLIST.md`   | Phase-by-phase task checklist               |
| `BUGS_AND_FIXES.md`          | Issue log with root causes and fixes        |
| `Mobile-Based Tricycle...md` | Original MVP roadmap document               |

---

_Last updated: 2026-03-18_
