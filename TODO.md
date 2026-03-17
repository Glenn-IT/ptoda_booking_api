# PTODA Booking API - Testing TODO ✅

## 3.6 Testing API (Postman / Thunder Client) - WORKING ON PORT 8001

**Start server**: `cd c:/xampp/htdocs/ptoda_booking_api && php -S localhost:8001`

### Step 1: [x] Server running ✓

- `http://localhost:8001/` → JSON "PTODA is running"

### Step 2: [ ] **3.6.1** POST /auth/register (passenger)

```
POST http://localhost:8001/auth/register
{ "name": "Test", "email": "test@test.com", "password": "123", "role": "passenger" }
```

### Step 3: [ ] **3.6.2** POST /auth/register (driver)

### Step 4: [ ] **3.6.3** POST /auth/login (save token)

### Step 5: [ ] **3.6.4** Protected: GET /bookings (Bearer token)

### Step 6: [ ] **3.6.5** Full flow ✓ (template ready)

**Android**: Emulator `10.0.2.2:8001` | Phone `192.168.x.x:8001` (ipconfig)

Mark [x] as you test!

### Step 2: [ ] 3.6.1 Test POST /auth/register (passenger)

- See DEVELOPMENT_CHECKLIST.md for exact payload/expected response

### Step 3: [ ] 3.6.2 Test POST /auth/register (driver)

### Step 4: [ ] 3.6.3 Test POST /auth/login — verify JWT returned

- Copy token for protected tests

### Step 5: [ ] 3.6.4 Test protected routes with Bearer token

- GET `/bookings`
- POST `/bookings` (fail without token)

### Step 6: [ ] 3.6.5 Test full booking flow

- POST `/bookings` (passenger)
- GET `/driver/requests` (driver)
- POST `/driver/accept/{id}`
- POST `/driver/complete/{id}`
- Verify GET `/bookings/{id}`

**Next:** Update checklist with detailed instructions, then test step-by-step.
