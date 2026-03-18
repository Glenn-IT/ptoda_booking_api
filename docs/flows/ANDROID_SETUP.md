# Flow — Android App Setup

> **Related files:** `api/AUTH.md` · `api/FCM.md` · `models/USER.md`
> **Checklist reference:** `DEVELOPMENT_CHECKLIST.md` Phase 4

---

## 1. `build.gradle.kts` Dependencies

```kotlin
// app/build.gradle.kts

dependencies {
    // Retrofit + OkHttp (networking)
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")

    // Firebase
    implementation(platform("com.google.firebase:firebase-bom:33.0.0"))
    implementation("com.google.firebase:firebase-messaging-ktx")

    // Google Maps
    implementation("com.google.android.gms:play-services-maps:18.2.0")
    implementation("com.google.android.gms:play-services-location:21.2.0")

    // Jetpack
    implementation("androidx.lifecycle:lifecycle-viewmodel-ktx:2.7.0")
    implementation("androidx.lifecycle:lifecycle-livedata-ktx:2.7.0")

    // Coroutines
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")
}
```

---

## 2. `ApiResponse` Wrapper

```kotlin
// data/api/ApiResponse.kt

data class ApiResponse<T>(
    val success: Boolean,
    val data: T?,
    val message: String
)
```

---

## 3. `ApiClient` — Retrofit + Auth Interceptor

```kotlin
// data/api/ApiClient.kt

object ApiClient {

    // Change based on environment — see INDEX.md for all URL options
    private const val BASE_URL = "http://10.0.2.2/ptoda_booking_api/"

    private lateinit var appContext: Context

    fun init(context: Context) {
        appContext = context.applicationContext
    }

    private val authInterceptor = Interceptor { chain ->
        val token = PrefsManager.getJwtToken(appContext)
        val request = chain.request().newBuilder()
            .apply { if (token != null) addHeader("Authorization", "Bearer $token") }
            .build()

        val response = chain.proceed(request)

        // Auto-logout on 401
        if (response.code == 401) {
            PrefsManager.clearAll(appContext)
            // Broadcast logout event to redirect to LoginActivity
        }
        response
    }

    private val client = OkHttpClient.Builder()
        .addInterceptor(authInterceptor)
        .addInterceptor(HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY  // remove in production
        })
        .build()

    val instance: ApiService by lazy {
        Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(client)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(ApiService::class.java)
    }
}
```

---

## 4. `ApiService` — Full Retrofit Interface

```kotlin
// data/api/ApiService.kt

interface ApiService {

    // ── Auth ──────────────────────────────────────────────────────
    @POST("auth/register")
    suspend fun register(@Body body: RegisterRequest): ApiResponse<Map<String, Int>>

    @POST("auth/login")
    suspend fun login(@Body body: LoginRequest): ApiResponse<LoginResponse>

    // ── Bookings ──────────────────────────────────────────────────
    @POST("bookings")
    suspend fun createBooking(@Body body: BookingRequest): ApiResponse<CreateBookingResponse>

    @GET("bookings")
    suspend fun getBookings(): ApiResponse<List<Booking>>

    @GET("bookings/{id}")
    suspend fun getBookingById(@Path("id") id: Int): ApiResponse<Booking>

    @GET("passenger/history")
    suspend fun getPassengerHistory(): ApiResponse<List<Booking>>

    // ── Driver ────────────────────────────────────────────────────
    @GET("driver/requests")
    suspend fun getDriverRequests(): ApiResponse<List<Booking>>

    @POST("driver/accept/{booking_id}")
    suspend fun acceptRide(@Path("booking_id") id: Int): ApiResponse<Unit>

    @POST("driver/reject/{booking_id}")
    suspend fun rejectRide(@Path("booking_id") id: Int): ApiResponse<Unit>

    @POST("driver/complete/{booking_id}")
    suspend fun completeRide(@Path("booking_id") id: Int): ApiResponse<Unit>

    @PUT("driver/location")
    suspend fun updateLocation(@Body body: UpdateLocationRequest): ApiResponse<Unit>

    // ── FCM ───────────────────────────────────────────────────────
    @PUT("user/fcm-token")
    suspend fun updateFcmToken(@Body body: FcmTokenRequest): ApiResponse<Unit>

    // ── Admin ─────────────────────────────────────────────────────
    @GET("admin/users")
    suspend fun getAllUsers(): ApiResponse<List<AdminUser>>

    @GET("admin/drivers/pending")
    suspend fun getPendingDrivers(): ApiResponse<List<PendingDriver>>

    @GET("admin/bookings")
    suspend fun getAllBookings(): ApiResponse<List<Booking>>

    @PUT("admin/driver/approve/{id}")
    suspend fun approveDriver(@Path("id") id: Int): ApiResponse<Unit>

    @PUT("admin/driver/reject/{id}")
    suspend fun rejectDriver(@Path("id") id: Int): ApiResponse<Unit>

    @PUT("admin/user/activate/{id}")
    suspend fun activateUser(@Path("id") id: Int): ApiResponse<Unit>

    @PUT("admin/user/deactivate/{id}")
    suspend fun deactivateUser(@Path("id") id: Int): ApiResponse<Unit>

    @DELETE("admin/user/{id}")
    suspend fun deleteUser(@Path("id") id: Int): ApiResponse<Unit>
}
```

---

## 5. `Constants.kt`

```kotlin
// utils/Constants.kt

object Constants {
    // Switch this per build environment
    const val BASE_URL_EMULATOR = "http://10.0.2.2/ptoda_booking_api/"
    const val BASE_URL_DEVICE   = "http://192.168.1.x/ptoda_booking_api/" // replace x
    const val BASE_URL_DEV_SRV  = "http://10.0.2.2:8001/"  // if using php -S localhost:8001
}
```

---

## 6. `AndroidManifest.xml` Permissions

```xml
<!-- Internet -->
<uses-permission android:name="android.permission.INTERNET" />

<!-- Location (for maps) -->
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />

<!-- Notifications (Android 13+) -->
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />

<!-- Inside <application> -->

<!-- Google Maps API Key -->
<meta-data
    android:name="com.google.android.geo.API_KEY"
    android:value="YOUR_MAPS_API_KEY" />

<!-- FCM Service -->
<service
    android:name=".services.PTODAFirebaseMessagingService"
    android:exported="false">
    <intent-filter>
        <action android:name="com.google.firebase.MESSAGING_EVENT" />
    </intent-filter>
</service>
```

---

## 7. `Application` Class — Init ApiClient

```kotlin
// PTODAApplication.kt

class PTODAApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        ApiClient.init(this)
    }
}
```

```xml
<!-- AndroidManifest.xml -->
<application
    android:name=".PTODAApplication"
    ...>
```

---

## 8. Base URL Reference

| Where you're running      | Use this BASE_URL                       |
| ------------------------- | --------------------------------------- |
| Android Emulator + Apache | `http://10.0.2.2/ptoda_booking_api/`    |
| Android Emulator + php -S | `http://10.0.2.2:8001/`                 |
| Physical device + Apache  | `http://192.168.x.x/ptoda_booking_api/` |
| Physical device + php -S  | `http://192.168.x.x:8001/`              |

> Run `ipconfig` on Windows to find your PC's local IP for physical device testing.

---

## Sync Rules

| Backend Change                   | Update Here                               |
| -------------------------------- | ----------------------------------------- |
| New endpoint added               | `ApiService` interface + matching section |
| Base URL path changes            | `ApiClient` BASE_URL + Constants table    |
| New request/response model added | Import correct data class in ApiService   |

---

_Last updated: 2026-03-18_
