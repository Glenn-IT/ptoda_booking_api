# Model — User

> **Related files:** `api/AUTH.md` · `api/ADMIN.md` · `flows/AUTH_FLOW.md`
> **Backend files:** `models/User.php` · `database/schema.sql`
> **DB table:** `users`

---

## MySQL Table: `users`

```sql
CREATE TABLE users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,            -- bcrypt hash
    role       ENUM('passenger', 'driver', 'admin') NOT NULL DEFAULT 'passenger',
    status     ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
);
```

### Column Reference

| Column       | Type                             | Notes                                            |
| ------------ | -------------------------------- | ------------------------------------------------ |
| `id`         | INT UNSIGNED, PK, AUTO_INCREMENT | System-assigned user identifier                  |
| `name`       | VARCHAR(100)                     | Display name                                     |
| `email`      | VARCHAR(150), UNIQUE             | Login credential, case-insensitive               |
| `password`   | VARCHAR(255)                     | `password_hash()` bcrypt — never returned in API |
| `role`       | ENUM                             | `passenger` \| `driver` \| `admin`               |
| `status`     | ENUM                             | `active` \| `inactive` — controls login access   |
| `created_at` | DATETIME                         | Auto-set on INSERT                               |
| `updated_at` | DATETIME                         | Auto-updated on any UPDATE                       |

### Business Rules

- `password` is **never** returned by any API endpoint.
- A `status = 'inactive'` user is blocked at login with `403`.
- `role = 'driver'` users also need `driver_info.approval_status = 'approved'` to log in.
- Admin accounts are seeded via `database/schema.sql`; registration endpoint does not allow `role = 'admin'`.

---

## Relationships

```
users (1) ──────── (1) driver_info   [if role = 'driver']
users (1) ──────── (1) fcm_tokens
users (1) ──────── (*) bookings      [as passenger_id]
users (1) ──────── (*) bookings      [as driver_id]
```

---

## PHP Model Methods (`models/User.php`)

| Method                                             | Description                           |
| -------------------------------------------------- | ------------------------------------- |
| `register(array $data): int`                       | Insert user (+ driver_info if driver) |
| `findByEmail(string $email): array\|null`          | Lookup user for login                 |
| `updateFCMToken(int $userId, string $token): void` | Upsert `fcm_tokens` row               |

---

## Kotlin Data Classes

```kotlin
// data/models/User.kt

/**
 * Returned in login response and admin user lists.
 * NOTE: Password is never included in any API response.
 */
data class User(
    val id: Int,
    val name: String,
    val email: String,
    val role: String,       // "passenger" | "driver" | "admin"
    val status: String,     // "active" | "inactive"
    val created_at: String
)

/**
 * Compact user info embedded inside LoginResponse.
 * See: api/AUTH.md → POST /auth/login
 */
data class LoggedInUser(
    val id: Int,
    val name: String,
    val email: String,
    val role: String,
    val status: String
)
```

### Role & Status Constants

```kotlin
// utils/Constants.kt

object UserRole {
    const val PASSENGER = "passenger"
    const val DRIVER    = "driver"
    const val ADMIN     = "admin"
}

object UserStatus {
    const val ACTIVE   = "active"
    const val INACTIVE = "inactive"
}
```

---

## SharedPreferences Storage (PrefsManager)

After login, persist the user info locally:

```kotlin
// data/local/PrefsManager.kt

object PrefsManager {
    private const val PREFS_NAME = "ptoda_prefs"
    private const val KEY_TOKEN    = "jwt_token"
    private const val KEY_USER_ID  = "user_id"
    private const val KEY_ROLE     = "user_role"
    private const val KEY_NAME     = "user_name"
    private const val KEY_FCM      = "fcm_token"

    fun saveLoginData(context: Context, token: String, user: LoggedInUser) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit()
            .putString(KEY_TOKEN,   token)
            .putInt(KEY_USER_ID,    user.id)
            .putString(KEY_ROLE,    user.role)
            .putString(KEY_NAME,    user.name)
            .apply()
    }

    fun getJwtToken(context: Context): String? =
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .getString(KEY_TOKEN, null)

    fun getUserRole(context: Context): String? =
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .getString(KEY_ROLE, null)

    fun getUserId(context: Context): Int =
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .getInt(KEY_USER_ID, -1)

    fun saveFcmToken(context: Context, token: String) {
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .edit().putString(KEY_FCM, token).apply()
    }

    fun getFcmToken(context: Context): String? =
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .getString(KEY_FCM, null)

    fun clearAll(context: Context) {
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .edit().clear().apply()
    }
}
```

---

## Sync Rules

| Backend Change                      | Update Here                                 |
| ----------------------------------- | ------------------------------------------- |
| New column added to `users` table   | MySQL table + Column Reference + data class |
| New role added (e.g., `supervisor`) | ENUM values + `UserRole` constants          |
| New status value added              | ENUM values + `UserStatus` constants        |
| New `User.php` method added         | PHP Model Methods table                     |

---

_Last updated: 2026-03-18_
