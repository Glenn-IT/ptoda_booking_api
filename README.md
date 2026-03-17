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

| Method | Endpoint                      | Auth | Description                |
| ------ | ----------------------------- | ---- | -------------------------- |
| POST   | /auth/register                | No   | Register new user          |
| POST   | /auth/login                   | No   | Login, returns JWT token   |
| GET    | /bookings/{id}                | Yes  | Get booking by ID          |
| POST   | /bookings                     | Yes  | Create new booking         |
| GET    | /passenger/history            | Yes  | Passenger ride history     |
| GET    | /driver/requests              | Yes  | Driver — pending requests  |
| POST   | /driver/accept/{booking_id}   | Yes  | Driver — accept ride       |
| POST   | /driver/reject/{booking_id}   | Yes  | Driver — reject ride       |
| POST   | /driver/complete/{booking_id} | Yes  | Driver — complete ride     |
| PUT    | /driver/location              | Yes  | Update driver GPS location |
| PUT    | /user/fcm-token               | Yes  | Update FCM push token      |
| GET    | /admin/users                  | Yes  | Admin — list all users     |
| PUT    | /admin/driver/approve/{id}    | Yes  | Admin — approve driver     |
| PUT    | /admin/user/deactivate/{id}   | Yes  | Admin — deactivate user    |
| GET    | /admin/bookings               | Yes  | Admin — all bookings       |

## Project Docs

- [`PROJECT_STRUCTURE.md`](../PROJECT_STRUCTURE.md) — Full project structure
- [`DEVELOPMENT_CHECKLIST.md`](../DEVELOPMENT_CHECKLIST.md) — Step-by-step task checklist
- [`BUGS_AND_FIXES.md`](../BUGS_AND_FIXES.md) — Issues encountered and how they were fixed
- [`database/schema.sql`](database/schema.sql) — Full MySQL schema
