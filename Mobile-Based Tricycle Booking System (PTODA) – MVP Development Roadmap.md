# Mobile-Based Tricycle Booking System (PTODA) — MVP Development Roadmap

File: `C:\xampp\htdocs\ptoda_booking_api\Mobile-Based Tricycle Booking System (PTODA) – MVP Development Roadmap.md`

## Overview

Develop a Mobile-Based Booking System for Tricycles (PTODA) — a simple ride-booking platform where passengers request tricycles and drivers accept rides. Build a Minimum Viable Product (MVP) first, focusing on local development using XAMPP.

**Note:** Deployment to VPS/production will be handled later; this roadmap targets local development with XAMPP.

## Technology Stack

- **Frontend**
  - Kotlin (Android Studio)
- **Backend**
  - PHP REST API
  - MySQL database
- **Development environment**
  - XAMPP (Apache + MySQL for local development)
- **Services**
  - Firebase Cloud Messaging (push notifications)
  - Google Maps API (location and maps)

## Application Roles

1. Passenger
2. Driver
3. Admin

## MVP Features

### Passenger

- Register an account
- Log in
- Choose pickup and drop-off locations using Google Maps
- Request a tricycle ride
- View ride status (requested, accepted, in-progress, completed)
- Receive notification when a driver accepts the request

### Driver

- Register and log in
- Receive booking requests (push notification)
- Accept or reject ride requests
- View passenger pickup location on map
- Mark ride as completed

### Admin

- Manage users (drivers and passengers)
- View bookings
- Approve or deactivate drivers

## What I Need (Deliverables)

1. A clear MVP system architecture explaining how the Android app, PHP REST API, and MySQL database interact.
2. A step-by-step development roadmap for building the system using Android Studio and XAMPP.
3. Recommended project structure for:
   - Android (Kotlin)
   - PHP REST API
4. MySQL database schema design for the MVP.
5. A list of required API endpoints for the booking system.
6. Instructions for integrating:
   - Google Maps API
   - Firebase Cloud Messaging

The goal: build a working MVP locally using XAMPP before moving to production deployment.
