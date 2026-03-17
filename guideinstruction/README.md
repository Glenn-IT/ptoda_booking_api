# PTODA Booking API

PHP REST API for the Mobile-Based Tricycle Booking System (PTODA).

## Requirements

- PHP 7.4+
- MySQL 5.7+
- Apache with `mod_rewrite` enabled
- XAMPP (for local development)

## Local Setup

1. Clone/copy this folder to `C:\xampp\htdocs\ptoda_booking_api\`
2. Start Apache and MySQL in XAMPP Control Panel
3. Open `http://localhost/phpmyadmin` and create a database named `ptoda_db`
4. Import `database/schema.sql` to create all tables
5. Copy `config/config.example.php` to `config/config.php` and fill in your values
6. Test the API at `http://localhost/ptoda_booking_api/`

## API Base URL

```
http://localhost/ptoda_booking_api/
```

## Authentication

All protected endpoints require a `Bearer` token in the `Authorization` header:

```
Authorization: Bearer <jwt_token>
```

## Endpoints Summary

| Method | Endpoint                      | Auth | Role      | Description                          |
| ------ | ----------------------------- | ---- | --------- | ------------------------------------ |
| POST   | /auth/register                | No   | —         | Register new user (passenger/driver) |
| POST   | /auth/login                   | No   | —         | Login, returns JWT token             |
| GET    | /bookings                     | Yes  | Any       | List bookings (role-filtered)        |
| GET    | /bookings/{id}                | Yes  | Any       | Get booking by ID                    |
| POST   | /bookings                     | Yes  | Passenger | Create new booking                   |
| GET    | /passenger/history            | Yes  | Passenger | Passenger ride history               |
| GET    | /driver/requests              | Yes  | Driver    | Pending ride requests                |
| POST   | /driver/accept/{booking_id}   | Yes  | Driver    | Accept a ride                        |
| POST   | /driver/reject/{booking_id}   | Yes  | Driver    | Reject a ride                        |
| POST   | /driver/complete/{booking_id} | Yes  | Driver    | Complete a ride                      |
| PUT    | /driver/location              | Yes  | Driver    | Update driver GPS location           |
| PUT    | /user/fcm-token               | Yes  | Any       | Update FCM push token                |
| GET    | /admin/users                  | Yes  | Admin     | List all users                       |
| GET    | /admin/drivers/pending        | Yes  | Admin     | List drivers pending approval        |
| GET    | /admin/bookings               | Yes  | Admin     | List all bookings                    |
| PUT    | /admin/driver/approve/{id}    | Yes  | Admin     | Approve driver account               |
| PUT    | /admin/driver/reject/{id}     | Yes  | Admin     | Reject driver account                |
| PUT    | /admin/user/activate/{id}     | Yes  | Admin     | Activate (re-enable) a user          |
| PUT    | /admin/user/deactivate/{id}   | Yes  | Admin     | Deactivate a user                    |
| DELETE | /admin/user/{id}              | Yes  | Admin     | Permanently delete a user            |

## Project Docs

- [`PROJECT_STRUCTURE.md`](../PROJECT_STRUCTURE.md) — Full project structure
- [`DEVELOPMENT_CHECKLIST.md`](../DEVELOPMENT_CHECKLIST.md) — Step-by-step task checklist
- [`BUGS_AND_FIXES.md`](../BUGS_AND_FIXES.md) — Issues encountered and how they were fixed
- [`database/schema.sql`](database/schema.sql) — Full MySQL schema
