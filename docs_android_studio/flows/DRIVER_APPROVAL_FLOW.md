# Flow — Driver Approval

> **Related files:** `api/ADMIN.md` · `api/AUTH.md` · `models/DRIVER_INFO.md`

---

## Overview

A driver cannot log in or accept rides until an admin explicitly approves their account.

```
Register → pending → (admin approves) → approved → can login & work
                   → (admin rejects)  → rejected → blocked at login
```

---

## Step-by-Step Flow

### 1. Driver Registers

```
Driver App               PHP Backend              MySQL
     │                        │                     │
     │─ POST /auth/register ─>│                     │
     │  { role: "driver",     │── INSERT users ────>│  status='active'
     │    license_no,         │── INSERT driver_info>│  approval_status='pending'
     │    vehicle_no }        │                     │
     │<── 201 { user_id:6 } ──│                     │
     │                        │                     │
     │  (show message:        │                     │
     │  "Awaiting approval")  │                     │
```

### 2. Driver Tries to Login (Blocked)

```
Driver App               PHP Backend
     │                        │
     │─ POST /auth/login ────>│
     │                        │  approval_status = 'pending'
     │<── 403 ────────────────│
     │  "Your driver account  │
     │   is pending approval" │
```

### 3. Admin Views Pending Drivers

```
Admin App                PHP Backend              MySQL
     │                        │                     │
     │─ GET /admin/drivers ──>│                     │
     │   /pending             │── SELECT users      │
     │                        │   JOIN driver_info ─>│
     │                        │   WHERE approval_    │
     │                        │   status='pending'   │
     │<── 200 [ driver #6 ] ──│                     │
```

### 4a. Admin Approves

```
Admin App                PHP Backend              MySQL
     │                        │                     │
     │─ PUT /admin/driver/ ──>│                     │
     │   approve/6            │── UPDATE driver_info>│
     │                        │   approval_status=   │
     │                        │   'approved'         │
     │<── 200 "Approved" ─────│                     │
```

### 4b. Admin Rejects

```
Admin App                PHP Backend              MySQL
     │                        │                     │
     │─ PUT /admin/driver/ ──>│                     │
     │   reject/6             │── UPDATE driver_info>│
     │                        │   approval_status=   │
     │                        │   'rejected'         │
     │<── 200 "Rejected" ─────│                     │
```

### 5. Driver Logs In (After Approval)

```
Driver App               PHP Backend
     │                        │
     │─ POST /auth/login ────>│
     │                        │  approval_status = 'approved' ✅
     │<── 200 { token, user } │
     │                        │
     │  (navigate to          │
     │   DriverHomeActivity)  │
```

---

## `approval_status` vs `users.status`

These are two **separate** checks on login for drivers:

| Check            | Field                         | Blocked Message                                       |
| ---------------- | ----------------------------- | ----------------------------------------------------- |
| Account active?  | `users.status`                | `"Your account has been deactivated. Contact admin."` |
| Driver approved? | `driver_info.approval_status` | `"Your driver account is pending admin approval."`    |

Both must pass for a driver to log in.

---

## Sync Rules

| Backend Change                             | Update Here                           |
| ------------------------------------------ | ------------------------------------- |
| FCM notification added on approve/reject   | Steps 4a/4b — add FCM push to diagram |
| Email notification added on approve/reject | Add email step to flow                |
| New approval status value added            | Overview diagram + step descriptions  |

---

_Last updated: 2026-03-18_
