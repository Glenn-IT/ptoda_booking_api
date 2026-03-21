# Flow — Authentication

> **Related files:** `api/AUTH.md` · `models/USER.md` · `models/FCM_TOKEN.md`

---

## Overview

Authentication is **JWT-based** and **stateless**. The server issues a signed token on login;
the client stores it and includes it in every subsequent request.

---

## Registration Flow

### Passenger

```
Android App                          PHP Backend                     MySQL
    │                                     │                            │
    │── POST /auth/register ─────────────>│                            │
    │   { name, email, password,          │                            │
    │     role: "passenger" }             │                            │
    │                                     │── INSERT users ───────────>│
    │                                     │   role='passenger'         │
    │                                     │   status='active'          │
    │<── 201 { user_id: 5 } ─────────────│                            │
    │                                     │                            │
    │  (redirect to LoginActivity)        │                            │
```

### Driver

```
Android App                          PHP Backend                     MySQL
    │                                     │                            │
    │── POST /auth/register ─────────────>│                            │
    │   { name, email, password,          │                            │
    │     role: "driver",                 │                            │
    │     license_no, vehicle_no }        │                            │
    │                                     │── INSERT users ───────────>│
    │                                     │   role='driver'            │
    │                                     │── INSERT driver_info ─────>│
    │                                     │   approval_status='pending'│
    │<── 201 { user_id: 6 } ─────────────│                            │
    │                                     │                            │
    │  (show "Awaiting admin              │                            │
    │   approval" message)                │                            │
```

---

## Login Flow

```
Android App                          PHP Backend                     MySQL
    │                                     │                            │
    │── POST /auth/login ────────────────>│                            │
    │   { email, password }               │                            │
    │                                     │── SELECT user by email ──>│
    │                                     │<── user row ──────────────│
    │                                     │                            │
    │                                     │  [verify password_hash]    │
    │                                     │  [check status = 'active'] │
    │                                     │  [if driver: check         │
    │                                     │   approval_status]         │
    │                                     │                            │
    │                                     │  [generate JWT]            │
    │<── 200 { token, user } ────────────│                            │
    │                                     │                            │
    │  PrefsManager.saveLoginData()       │                            │
    │  updateFcmToken() if token saved    │                            │
    │  redirect to role-based Home:       │                            │
    │    passenger → PassengerHome        │                            │
    │    driver    → DriverHome           │                            │
    │    admin     → AdminDashboard       │                            │
```

---

## Authenticated Request Flow

Every call to a protected endpoint follows this pattern:

```
Android App                          PHP Backend
    │                                     │
    │── GET /bookings ───────────────────>│
    │   Authorization: Bearer <token>     │
    │                                     │  AuthMiddleware::handle()
    │                                     │  ├─ extract token from header
    │                                     │  ├─ decode & verify JWT signature
    │                                     │  ├─ check token not expired
    │                                     │  └─ return { user_id, role }
    │                                     │
    │                                     │  [controller logic with user_id/role]
    │<── 200 { data: [...] } ────────────│
```

---

## Token Expiry & Refresh

> MVP does not implement token refresh. When a token expires:

```
Android App                          PHP Backend
    │                                     │
    │── Any protected request ───────────>│
    │   Authorization: Bearer <expired>   │
    │                                     │  AuthMiddleware: token expired
    │<── 401 { "Token has expired." } ───│
    │                                     │
    │  Clear PrefsManager                 │
    │  Redirect to LoginActivity          │
```

### Kotlin — Handle 401 Globally

```kotlin
// data/api/ApiClient.kt

val client = OkHttpClient.Builder()
    .addInterceptor { chain ->
        val request = chain.request().newBuilder()
            .addHeader("Authorization", "Bearer ${PrefsManager.getJwtToken(context) ?: ""}")
            .build()
        val response = chain.proceed(request)

        if (response.code == 401) {
            PrefsManager.clearAll(context)
            // Post event to redirect to LoginActivity
            authEventBus.post(AuthEvent.LOGOUT)
        }
        response
    }
    .build()
```

---

## JWT Token Details

| Property  | Value                                 |
| --------- | ------------------------------------- |
| Algorithm | HS256                                 |
| Expiry    | 7 days (configurable in `config.php`) |
| Payload   | `{ user_id, role, iat, exp }`         |
| Storage   | Android SharedPreferences             |

### Decoded Payload Example

```json
{
  "user_id": 5,
  "role": "passenger",
  "iat": 1742256000,
  "exp": 1742860800
}
```

---

## Android — Role-Based Navigation After Login

```kotlin
// In AuthViewModel or LoginActivity

fun navigateAfterLogin(role: String, context: Context) {
    val intent = when (role) {
        UserRole.PASSENGER -> Intent(context, PassengerHomeActivity::class.java)
        UserRole.DRIVER    -> Intent(context, DriverHomeActivity::class.java)
        UserRole.ADMIN     -> Intent(context, AdminDashboardActivity::class.java)
        else               -> Intent(context, LoginActivity::class.java)
    }
    intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
    context.startActivity(intent)
}
```

---

## Error Handling Reference

| Scenario                 | HTTP | Message                                                   |
| ------------------------ | ---- | --------------------------------------------------------- |
| Wrong password           | 401  | `"Invalid email or password."`                            |
| Account deactivated      | 403  | `"Your account has been deactivated. Contact admin."`     |
| Driver pending approval  | 403  | `"Your driver account is pending admin approval."`        |
| Driver rejected          | 403  | `"Your driver account has been rejected. Contact admin."` |
| Token expired or invalid | 401  | `"Token has expired."` / `"Invalid token."`               |
| No token sent            | 401  | `"Authorization token is required."`                      |

---

## Sync Rules

| Backend Change                     | Update Here                           |
| ---------------------------------- | ------------------------------------- |
| Token expiry duration changed      | JWT Token Details table               |
| New role added                     | Role-Based Navigation + `api/AUTH.md` |
| New login error condition added    | Error Handling Reference table        |
| Token refresh endpoint implemented | Add new "Token Refresh Flow" section  |

---

_Last updated: 2026-03-18_
