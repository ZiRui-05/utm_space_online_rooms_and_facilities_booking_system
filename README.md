# UTM Space Online Rooms and Facilities Booking System

A web-based booking platform for managing rooms and facilities at Universiti
Teknologi Malaysia (UTM). The system allows users to browse available
resources, submit booking requests, upload payment receipts, track booking
status, receive notifications, and report facility issues. Dedicated
management interfaces are provided for facility managers and administrators.

## Main Features

- Room and facility browsing with availability and schedule checks
- User registration, login, profile management, and password reset
- Role-based access for students, staff, facility managers, and administrators
- Booking creation, approval, rejection, cancellation, and return tracking
- Payment receipt upload and verification
- Booking history and in-application notifications
- UTM card verification
- Room and facility management
- Resource schedule and maintenance management
- Issue reporting with attachment support
- Administrative reports and user management
- Optional email notifications and booking return reminders

## User Roles

| Role | Main access |
| --- | --- |
| Student | Browse resources, make bookings, upload receipts, and report issues |
| Staff | Use the standard booking and account features |
| Facility Manager | Review bookings and manage assigned operational resources |
| Administrator | Manage users, resources, schedules, reports, and system records |

## Technology Stack

- PHP 8 or later
- Apache HTTP Server
- MariaDB or MySQL
- HTML, CSS, and JavaScript
- PDO and MySQLi database interfaces
- Composer
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) `^6.9`
- Optional [Brevo](https://www.brevo.com/) SMTP and Transactional Email API

## Verified Development Environment

The project was developed and verified with the following environment:

| Component | Version |
| --- | --- |
| Operating system | Windows 64-bit |
| Local server package | XAMPP |
| Apache | 2.4.58 |
| PHP | 8.2.12 |
| MariaDB | 10.4.32 |
| Composer | 2.9.8 |
| PHPMailer | 6.12.0 (locked version) |

The minimum PHP version declared in `composer.json` is PHP 8.0. PHPMailer uses
the `^6.9` dependency constraint and is currently locked to version 6.12.0.

## Tested Browser Environment

Manual browser compatibility testing was performed with:

- Google Chrome Version 149.0.7827.54 (Official Build) (64-bit)
- Microsoft Edge Version 149.0.4022.62 (Official build) (64-bit)

Other modern browsers may work, but they have not been included in the
documented test environment.

## System Requirements

### Required

- Windows 10 or later is recommended for the documented XAMPP setup
- XAMPP with Apache, PHP 8.0 or later, and MariaDB/MySQL
- Composer 2
- A modern 64-bit web browser
- Write permission for the `logs/` directory

### Required PHP Extensions

The following PHP extensions should be enabled:

- `curl`
- `fileinfo`
- `json`
- `mbstring`
- `mysqli`
- `openssl`
- `pdo`
- `pdo_mysql`
- `session`

These extensions are included in a typical XAMPP PHP installation. Run the
following command to inspect the enabled modules:

```powershell
C:\xampp\php\php.exe -m
```

### Optional Services

- A Brevo account with a verified sender or domain is required only for email
  delivery.
- Windows Task Scheduler or another scheduler is required only for automatic
  booking return reminder emails.

The core local booking system can run without email credentials or a scheduled
reminder task.

## Installation

### 1. Place the Project in XAMPP

Clone or place the project at:

```text
C:\xampp\htdocs\utm_space_online_rooms_and_facilities_booking_system
```

Then open PowerShell in that directory:

```powershell
cd C:\xampp\htdocs\utm_space_online_rooms_and_facilities_booking_system
```

### 2. Start Apache and MySQL

Open the XAMPP Control Panel and start:

- Apache
- MySQL

Resolve any port conflicts before continuing. The usual local ports are `80`
for Apache and `3306` for MariaDB/MySQL.

### 3. Install PHP Dependencies

If Composer is available in `PATH`, run:

```powershell
composer install
```

Alternatively, use the included Composer PHAR:

```powershell
C:\xampp\php\php.exe composer.phar install
```

This installs PHPMailer and creates `vendor/autoload.php`.

### 4. Create and Import the Database

The expected database name is:

```text
utm_space_booking_system
```

Using phpMyAdmin:

1. Open `http://localhost/phpmyadmin/`.
2. Create a database named `utm_space_booking_system`.
3. Select the database and open the **Import** tab.
4. Import `database/utm_space_booking_system.sql`.

The SQL dump contains sample development records. Replace or sanitize these
records, accounts, and personal information before any production deployment.

### 5. Configure Database Access

The application reads database settings from operating-system or web-server
environment variables. When variables are not set, it uses these local
defaults:

| Variable | Default |
| --- | --- |
| `DB_HOST` | `localhost` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `utm_space_booking_system` |
| `DB_USER` | `root` |
| `DB_PASS` | Empty password |

If the XAMPP database uses these defaults, no additional database
configuration is needed. Otherwise, define the variables for Apache before
starting the application.

For example, they can be added to the XAMPP Apache configuration:

```apache
SetEnv DB_HOST "localhost"
SetEnv DB_PORT "3306"
SetEnv DB_NAME "utm_space_booking_system"
SetEnv DB_USER "root"
SetEnv DB_PASS ""
```

Restart Apache after changing its configuration.

### 6. Configure Application Variables

`.env.example` lists the available application and email settings, but it is a
reference file only. The project does not currently load `.env` files
automatically. Values must be supplied through Windows, Apache, the hosting
control panel, or another process environment.

For basic local operation, configure at least:

```apache
SetEnv APP_TIMEZONE "Asia/Kuala_Lumpur"
SetEnv APP_URL "http://localhost/utm_space_online_rooms_and_facilities_booking_system"
SetEnv APP_SECRET "replace-with-a-long-random-secret"
SetEnv CRON_WEB_SECRET "replace-with-another-long-random-secret"
```

Use long, unique, cryptographically random values for secrets. Restart Apache
after changing environment settings.

### 7. Open the Application

Public homepage:

```text
http://localhost/utm_space_online_rooms_and_facilities_booking_system/homepage.php
```

User login:

```text
http://localhost/utm_space_online_rooms_and_facilities_booking_system/pages/auth/login.html
```

Management login and role redirect:

```text
http://localhost/utm_space_online_rooms_and_facilities_booking_system/manager_admin/
```

Access to management pages depends on the role stored for the authenticated
user.

## Email Configuration

The system can send password reset codes, booking notifications, and booking
return reminders. Email is optional for basic local use.

Add the following values to the Apache or hosting environment:

```apache
SetEnv MAIL_HOST "smtp-relay.brevo.com"
SetEnv MAIL_PORT "587"
SetEnv MAIL_USERNAME "your-brevo-smtp-login"
SetEnv MAIL_PASSWORD "your-brevo-smtp-key"
SetEnv MAIL_ENCRYPTION "tls"
SetEnv MAIL_FROM_EMAIL "verified-sender@example.com"
SetEnv MAIL_FROM_NAME "UTM Space Booking"
SetEnv MAIL_REPLY_TO_EMAIL ""
SetEnv MAIL_REPLY_TO_NAME ""
SetEnv MAIL_TIMEOUT "15"
SetEnv BREVO_API_KEY "your-brevo-api-key"
```

`MAIL_FROM_EMAIL` must be a sender address or domain verified by Brevo. SMTP is
attempted first. If SMTP fails and `BREVO_API_KEY` is configured, the
application attempts delivery through the Brevo Transactional Email API.

Use separate credentials for development and production. Never commit real
SMTP passwords, API keys, or application secrets. More details are available
in [`docs/email-configuration.md`](docs/email-configuration.md).

## Booking Return Reminder Task

`cron/booking_return_reminder.php` sends reminders shortly before approved
bookings end. It should run every one or two minutes.

For the documented XAMPP setup, create a Windows Task Scheduler task that runs:

```text
powershell.exe
```

With these arguments:

```text
-NoProfile -ExecutionPolicy Bypass -File "C:\xampp\htdocs\utm_space_online_rooms_and_facilities_booking_system\cron\run_booking_return_reminder.ps1"
```

The PowerShell wrapper uses `C:\xampp\php\php.exe` and imports configured
Windows user or machine environment variables before running the PHP script.
Ensure `APP_SECRET`, database settings, and email settings are available to the
scheduled task.

For a manual command-line check:

```powershell
C:\xampp\php\php.exe cron\booking_return_reminder.php
```

Runtime information is written to:

- `logs/mail.log`
- `logs/booking_return_reminder.log`

## Project Structure

```text
.
|-- api/                    # Authentication, booking, and user API endpoints
|-- assets/                 # JavaScript and image assets
|-- config/                 # Application, database, and mail configuration
|-- cron/                   # Booking return reminder scripts
|-- database/               # MariaDB/MySQL schema and sample data
|-- docs/                   # Additional project documentation
|-- includes/               # Shared layout and booking support code
|-- manager_admin/          # Facility manager and administrator interfaces
|-- pages/
|   |-- app/                # Authenticated user application
|   `-- auth/               # Login, registration, and password reset pages
|-- vendor/                 # Composer-installed dependencies
|-- .env.example            # Environment variable reference
|-- composer.json           # PHP dependency and version requirements
|-- homepage.php            # Public homepage
`-- README.md
```

## Testing

The browser versions listed above describe the manual compatibility test
environment. The repository does not currently contain an automated test
suite.

Recommended manual checks after installation:

1. Open the homepage and browse rooms and facilities.
2. Register or sign in as a standard user.
3. Create a booking and confirm that it appears in booking history.
4. Sign in with a management role and review the booking request.
5. Upload and verify a payment receipt where applicable.
6. Submit an issue report and review it from the management interface.
7. When email is configured, test password reset and notification delivery.
8. Run the return reminder script and inspect its log output.

## Troubleshooting

### Database Connection Failed

- Confirm MySQL is running in XAMPP.
- Confirm that `utm_space_booking_system` exists and the SQL file was imported.
- Check `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS`.
- Restart Apache after changing environment variables.

### Composer or PHPMailer Is Missing

Run:

```powershell
C:\xampp\php\php.exe composer.phar install
```

Then confirm that `vendor/autoload.php` exists.

### Environment Changes Are Not Detected

The application does not automatically parse `.env.example` or `.env`.
Configure variables in Apache or Windows, then restart Apache. Restart a
scheduled task or terminal session when its environment has changed.

### Email Is Not Delivered

- Confirm that the Brevo sender is verified.
- Use the SMTP key for `MAIL_PASSWORD`, not the Brevo API key.
- Confirm that `curl` and `openssl` are enabled.
- Check `logs/mail.log` and the Brevo transactional email log.
- Review [`docs/email-configuration.md`](docs/email-configuration.md).

### Apache or MySQL Does Not Start

Check the XAMPP logs for port conflicts. Common conflicts involve Apache ports
`80` or `443` and MySQL port `3306`.

## Security and Production Notes

- Do not use the sample database records as production accounts.
- Remove or sanitize personal information in the SQL dump before deployment.
- Replace all example secrets and rotate any credential that has been exposed.
- Do not store production credentials in PHP source files or commit them to
  version control.
- Use HTTPS in production.
- Restrict database access to the application host and use a dedicated
  least-privilege database account.
- Configure secure file permissions for logs and application files.
- Back up the database before applying schema or deployment changes.
- Review PHP and Apache production settings, including error display, session
  cookies, upload limits, and request size limits.

## License

This project is licensed under the MIT License. See [`LICENSE`](LICENSE) for
the complete license text.
