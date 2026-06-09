# Hospitalia

A lightweight hospital management web app built with plain PHP and PostgreSQL.

Hospitalia provides a clean admin/user workflow for managing doctors, patients, schedules, appointments, and billing with automatic database bootstrapping.

## Highlights

- Role-based authentication (admin and user)
- PostgreSQL-only backend with PDO
- Automatic schema creation on first run
- Seeded admin account for fresh databases
- CRUD flows for:
  - Doctors (admin only)
  - Patients
  - Doctor schedules
  - Appointments (admin only)
  - Bills
- No framework dependency; easy to run locally

## Tech Stack

- PHP 8+
- PostgreSQL 13+
- PDO (pgsql driver)
- Vanilla HTML/CSS

## Project Structure

```text
.
|-- appointments.php      # Admin appointment management
|-- bills.php             # Billing management
|-- config.php            # App bootstrap + env + DB constants
|-- doctors.php           # Admin doctor management
|-- index.php             # Dashboard
|-- login.php             # Login form and auth flow
|-- logout.php            # Session destroy + redirect
|-- patients.php          # Patient management
|-- register.php          # User self-registration (role=user)
|-- schedules.php         # Doctor schedule management
|-- assets/
|   `-- style.css         # UI styles
|-- includes/
|   |-- auth.php          # Auth helpers and route guards
|   |-- db.php            # PDO connection + schema initialization
|   |-- env.php           # .env parser/loader
|   |-- header.php        # Shared layout header + navbar
|   `-- footer.php        # Shared layout footer
|-- .env.example          # Environment template
`-- README.md
```

## Features by Role

### Admin

- Full access to all pages
- Manage doctors
- Manage appointments
- Access all user features

### User

- Dashboard access
- Manage patients
- Manage schedules (if doctors already exist)
- Manage bills
- Cannot access doctor or appointment pages

## Database Model

On first successful DB connection, the app auto-creates these tables:

- `hms_users`
- `hms_doctors`
- `hms_patients`
- `hms_schedules`
- `hms_appointments`
- `hms_bills`

All tables are prefixed with `hms_` to reduce naming collisions in shared databases.

## Quick Start

### 1. Clone and enter the repo

```bash
git clone <your-repo-url>
cd Hospitalia
```

### 2. Create environment file

Copy `.env.example` to `.env` and update values:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hospitalia
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 3. Create PostgreSQL database

```sql
CREATE DATABASE hospitalia;
```

### 4. Ensure PHP has pgsql enabled

Verify the extension is available:

```bash
php -m | grep pgsql
```

On Windows PowerShell, you can use:

```powershell
php -m | Select-String pgsql
```

### 5. Run the app

From the project root:

```bash
php -S localhost:8000
```

Open:

```text
http://localhost:8000/login.php
```

## Default Credentials (Fresh DB)

The app seeds one admin account when no admin exists:

- Username: `admin`
- Password: `admin123`

Change this immediately in non-local environments.

## Route Map

- `/login.php` - Sign in
- `/register.php` - Create user account
- `/logout.php` - End session
- `/index.php` - Dashboard
- `/patients.php` - Patients CRUD (login required)
- `/schedules.php` - Schedules CRUD (login required)
- `/bills.php` - Bills CRUD (login required)
- `/doctors.php` - Doctors CRUD (admin only)
- `/appointments.php` - Appointments CRUD (admin only)

## Security Notes

- Passwords are hashed via `password_hash()` and verified with `password_verify()`.
- Session-based authentication is used for access control.
- Basic output escaping is handled by helper `h()`.
- This is a simple educational-style codebase; harden further before production.




