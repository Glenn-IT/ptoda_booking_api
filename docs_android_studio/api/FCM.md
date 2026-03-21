# API — FCM Token

> **Related files:** `models/FCM_TOKEN.md` · `flows/AUTH_FLOW.md`
> **Backend files:** `index.php` (inline handler) · `models/User.php`

---

## Overview

The FCM (Firebase Cloud Messaging) token is a device-specific push notification identifier.
It must be registered or refreshed with the server so that the backend can send push
notifications (e.g., "A driver has accepted your ride!") to the correct device.

**When to call this endpoint:**

- After a successful login
- Whenever `onNewToken()` fires in `PTODAFirebaseMessagingService`

---

## PUT `/user/fcm-token`

Register or update the FCM device token for the authenticated user.
Uses an upsert: if a token record exists for the user, it is updated; otherwise it is inserted.

- **Auth required:** ✅ Yes
- **Role required:** Any (passenger, driver, admin)
- **PHP handler:** Inline in `index.php` → calls `User::updateFCMToken()`

### Request Body

```json
{
  "token": "string — required (FCM registration token)"
}
```

### Example

```json
{
  "token": "eXaMpLeToKeN_abcdef1234567890..."
}
```

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "FCM token updated."
}
```

### Error Responses

| HTTP | Condition             | Message                              |
| ---- | --------------------- | ------------------------------------ |
| 401  | No/invalid token      | `"Authorization token is required."` |
| 422  | Missing `token` field | `"FCM token is required."`           |
| 500  | DB error              | `"Failed to update FCM token."`      |

---

## Kotlin — Data Class & Retrofit

```kotlin
// data/models/FcmModels.kt

data class FcmTokenRequest(
    val token: String
)
```

```kotlin
// data/api/ApiService.kt (FCM section)

@PUT("user/fcm-token")
suspend fun updateFcmToken(@Body body: FcmTokenRequest): ApiResponse<Unit>
```

---

## Full Android Integration

### 1. FirebaseMessagingService

```kotlin
// services/PTODAFirebaseMessagingService.kt

class PTODAFirebaseMessagingService : FirebaseMessagingService() {

    override fun onNewToken(token: String) {
        super.onNewToken(token)
        // Save token locally first so it persists if user is not logged in yet
        PrefsManager.saveFcmToken(applicationContext, token)

        // If user is already logged in, sync the new token to backend immediately
        if (PrefsManager.getJwtToken(applicationContext) != null) {
            CoroutineScope(Dispatchers.IO).launch {
                try {
                    ApiClient.instance.updateFcmToken(FcmTokenRequest(token))
                } catch (e: Exception) {
                    // Will retry on next login — see LoginActivity flow below
                }
            }
        }
    }

    override fun onMessageReceived(message: RemoteMessage) {
        super.onMessageReceived(message)
        val title = message.notification?.title ?: "PTODA"
        val body  = message.notification?.body  ?: ""
        showNotification(title, body)
    }

    private fun showNotification(title: String, body: String) {
        val channelId = "ptoda_channel"
        val notification = NotificationCompat.Builder(this, channelId)
            .setSmallIcon(R.drawable.ic_notification)
            .setContentTitle(title)
            .setContentText(body)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setAutoCancel(true)
            .build()

        val manager = getSystemService(NOTIFICATION_SERVICE) as NotificationManager
        manager.notify(System.currentTimeMillis().toInt(), notification)
    }
}
```

### 2. Register in AndroidManifest.xml

```xml
<service
    android:name=".services.PTODAFirebaseMessagingService"
    android:exported="false">
    <intent-filter>
        <action android:name="com.google.firebase.MESSAGING_EVENT" />
    </intent-filter>
</service>
```

### 3. Sync Token After Login

```kotlin
// In LoginActivity or AuthRepository — after successful login:

val savedFcmToken = PrefsManager.getFcmToken(context)
if (savedFcmToken != null) {
    CoroutineScope(Dispatchers.IO).launch {
        try {
            apiService.updateFcmToken(FcmTokenRequest(savedFcmToken))
        } catch (e: Exception) {
            // Non-critical — notifications won't work but app is functional
        }
    }
}
```

---

## Sync Rules

| Backend Change                                  | Update Here                         |
| ----------------------------------------------- | ----------------------------------- |
| Endpoint moved out of `index.php` to controller | Update "PHP handler" reference      |
| Request body adds `device_type` field           | Request body + `FcmTokenRequest`    |
| Multi-device token support (multiple rows)      | Overview + upsert note + data class |

---

_Last updated: 2026-03-18_
