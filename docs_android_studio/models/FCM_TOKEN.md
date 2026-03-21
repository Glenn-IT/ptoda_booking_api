# Model — FCM Token

> **Related files:** `models/USER.md` · `api/FCM.md` · `flows/AUTH_FLOW.md`
> **Backend files:** `models/User.php` · `database/schema.sql`
> **DB table:** `fcm_tokens`

---

## MySQL Table: `fcm_tokens`

One row per user. Upserted (INSERT or UPDATE) every time the device token changes.

```sql
CREATE TABLE fcm_tokens (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL UNIQUE,
    token      VARCHAR(255) NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_fcm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Column Reference

| Column       | Type                     | Notes                                     |
| ------------ | ------------------------ | ----------------------------------------- |
| `id`         | INT UNSIGNED, PK         | Auto-assigned                             |
| `user_id`    | INT UNSIGNED, FK, UNIQUE | 1:1 with `users.id` — cascade delete      |
| `token`      | VARCHAR(255)             | FCM device registration token             |
| `updated_at` | DATETIME                 | Auto-updated every time the token changes |

### Key Behaviours

- **Upsert** — calling `PUT /user/fcm-token` with a new token replaces the existing one (no duplicates).
- **Cascade delete** — when a user is deleted, their FCM token row is automatically removed.
- **One token per user** — multiple devices are not supported in MVP; the latest token wins.

---

## How the Backend Uses FCM Tokens

The backend reads tokens from this table when it needs to push a notification to a user:

| Event                   | Who Gets Notified | Trigger                           |
| ----------------------- | ----------------- | --------------------------------- |
| Driver accepts a ride   | Passenger         | `POST /driver/accept/{id}`        |
| Driver completes a ride | Passenger         | `POST /driver/complete/{id}`      |
| New booking created     | Driver (future)   | `POST /bookings` (when broadcast) |

Notification is sent via `helpers/FCM.php` using FCM HTTP v1 API.

---

## PHP Model Method (`models/User.php`)

| Method                                             | Description                               |
| -------------------------------------------------- | ----------------------------------------- |
| `updateFCMToken(int $userId, string $token): void` | INSERT ... ON DUPLICATE KEY UPDATE upsert |

---

## Kotlin Data Class

```kotlin
// data/models/FcmModels.kt

data class FcmTokenRequest(
    val token: String
)
```

---

## Full Token Lifecycle (Android)

```
App install / reinstall
    └─ FirebaseMessagingService.onNewToken(token)
           ├─ PrefsManager.saveFcmToken(token)         [local]
           └─ if logged in → PUT /user/fcm-token       [backend]

User logs in
    └─ Read PrefsManager.getFcmToken()
           └─ PUT /user/fcm-token                      [backend sync]

App running + token refreshed by Firebase
    └─ FirebaseMessagingService.onNewToken(token)
           ├─ PrefsManager.saveFcmToken(token)
           └─ PUT /user/fcm-token
```

---

## Sync Rules

| Backend Change                                      | Update Here                                |
| --------------------------------------------------- | ------------------------------------------ |
| Table extended to support multiple devices per user | MySQL table + Key Behaviours + UNIQUE note |
| `token` column size increased                       | Column Reference VARCHAR size              |
| New notification event added                        | "How the backend uses FCM tokens" table    |

---

_Last updated: 2026-03-18_
