# API — Authentication

> **Related files:** `models/USER.md` · `flows/AUTH_FLOW.md`
> **Backend files:** `controllers/AuthController.php` · `models/User.php`

---

## Overview

Authentication is **stateless JWT-based**. No session is stored server-side.

| Step | Action                            | Endpoint                        |
| ---- | --------------------------------- | ------------------------------- |
| 1    | Register account                  | `POST /auth/register`           |
| 2    | Login, get token                  | `POST /auth/login`              |
| 3    | Use token on every protected call | `Authorization: Bearer <token>` |

---

## POST `/auth/register`

Register a new user. Role must be `passenger` or `driver`.
Drivers additionally require `license_no` and `vehicle_no`.

- **Auth required:** ❌ No
- **PHP handler:** `AuthController::register()`

### Request Body

```json
{
  "name": "string — required",
  "email": "string — required, unique",
  "password": "string — required, min 6 chars",
  "role": "passenger | driver — required",

  "license_no": "string — required if role=driver",
  "vehicle_no": "string — required if role=driver"
}
```

### Passenger Example

```json
{
  "name": "Maria Santos",
  "email": "maria@example.com",
  "password": "secret123",
  "role": "passenger"
}
```

### Driver Example

```json
{
  "name": "Juan dela Cruz",
  "email": "juan@example.com",
  "password": "secret123",
  "role": "driver",
  "license_no": "DL-123456",
  "vehicle_no": "ABC-1234"
}
```

### Success Response — `201 Created`

```json
{
  "success": true,
  "data": { "user_id": 5 },
  "message": "Registration successful."
}
```

### Error Responses

| HTTP | Condition                         | Message                                                 |
| ---- | --------------------------------- | ------------------------------------------------------- |
| 422  | Missing required field            | `"Field 'email' is required."`                          |
| 422  | Invalid role                      | `"Role must be 'passenger' or 'driver'."`               |
| 422  | Driver missing license or vehicle | `"license_no and vehicle_no are required for drivers."` |
| 409  | Email already registered          | `"Email is already registered."`                        |
| 500  | DB error                          | `"Registration failed."`                                |

---

## POST `/auth/login`

Authenticate a user and receive a JWT token. Checks for active account status and,
for drivers, approved driver status.

- **Auth required:** ❌ No
- **PHP handler:** `AuthController::login()`

### Request Body

```json
{
  "email": "string — required",
  "password": "string — required"
}
```

### Example

```json
{
  "email": "maria@example.com",
  "password": "secret123"
}
```

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 5,
      "name": "Maria Santos",
      "email": "maria@example.com",
      "role": "passenger",
      "status": "active"
    }
  },
  "message": "Login successful."
}
```

> 💡 **Save `token` and `user.role` to SharedPreferences immediately after login.**

### JWT Payload (decoded)

```json
{
  "user_id": 5,
  "role": "passenger",
  "iat": 1710720000,
  "exp": 1711324800
}
```

Token expiry: **7 days** (configurable in `config/config.php`).

### Error Responses

| HTTP | Condition                 | Message                                                   |
| ---- | ------------------------- | --------------------------------------------------------- |
| 422  | Missing email or password | `"Email and password are required."`                      |
| 401  | Wrong credentials         | `"Invalid email or password."`                            |
| 403  | Account deactivated       | `"Your account has been deactivated. Contact admin."`     |
| 403  | Driver not yet approved   | `"Your driver account is pending admin approval."`        |
| 403  | Driver rejected           | `"Your driver account has been rejected. Contact admin."` |

---

## Kotlin — Data Classes & Retrofit

```kotlin
// data/models/AuthModels.kt

data class RegisterRequest(
    val name: String,
    val email: String,
    val password: String,
    val role: String,                 // "passenger" or "driver"
    val license_no: String? = null,   // required if role == "driver"
    val vehicle_no: String? = null    // required if role == "driver"
)

data class LoginRequest(
    val email: String,
    val password: String
)

data class LoginResponse(
    val token: String,
    val user: UserResponse
)

data class UserResponse(
    val id: Int,
    val name: String,
    val email: String,
    val role: String,   // "passenger" | "driver" | "admin"
    val status: String  // "active" | "inactive"
)
```

```kotlin
// data/api/ApiService.kt (auth section)

@POST("auth/register")
suspend fun register(@Body body: RegisterRequest): ApiResponse<Map<String, Int>>

@POST("auth/login")
suspend fun login(@Body body: LoginRequest): ApiResponse<LoginResponse>
```

---

## Sync Rules

| Backend Change                                     | Update Here                             |
| -------------------------------------------------- | --------------------------------------- |
| New field added to register (e.g., `phone_number`) | Request body + Kotlin `RegisterRequest` |
| Login response adds new user fields                | `LoginResponse` / `UserResponse`        |
| Token expiry changed                               | JWT Payload section                     |
| New error condition added                          | Error Responses table                   |

---

_Last updated: 2026-03-18_
