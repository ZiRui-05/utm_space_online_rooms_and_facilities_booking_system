# Project References and External Resources

This reference list was prepared by auditing the project's source code,
dependency files, documentation, and deployment information. All online
resources were accessed on 12 June 2026.

## Confirmed External Resources

| Resource | Use in the project | Evidence in the repository |
| --- | --- | --- |
| PHP | Main server-side programming language | PHP source files throughout `api/`, `includes/`, `pages/`, and `manager_admin/`; PHP `>=8.0` in `composer.json` |
| Apache HTTP Server | Local and hosted web server environment | Documented in `README.md`; Apache environment variables are used for configuration |
| MariaDB/MySQL | Relational database for users, bookings, facilities, payments, schedules, and reports | `database/utm_space_booking_system.sql`, `config/db.php`, and `includes/management_db.php` |
| XAMPP | Local development package containing Apache, MariaDB, and PHP | Development and installation environment documented in `README.md` |
| phpMyAdmin | Database creation and SQL import tool | phpMyAdmin-generated SQL header and import instructions in `README.md` |
| Composer | PHP dependency management and autoloading | `composer.json`, `composer.lock`, `composer.phar`, and `vendor/autoload.php` |
| PHPMailer 6.12.0 | SMTP email delivery for password resets, booking notifications, and reminders | Locked in `composer.lock` and used in `config/mailer.php` |
| Brevo | SMTP service and fallback Transactional Email API | `config/mailer.php`, `.env.example`, and `docs/email-configuration.md` |
| Tailwind CSS Play CDN | Utility CSS styling for user and management headers/pages | `cdn.tailwindcss.com` loaded in shared header files |
| Google Fonts (Inter) | Interface typeface | `fonts.googleapis.com` links in shared header files |
| Google Material Symbols | Interface icons | Material Symbols stylesheet loaded from Google Fonts in shared header files |
| UI Avatars | Generates fallback profile images from users' initials | `ui-avatars.com/api` calls in user and management pages |
| GoDaddy Web Hosting | Production hosting platform | Deployment information supplied by the project team |

## References

Apache Friends (2024) *XAMPP: Apache + MariaDB + PHP + Perl*. Available at:
https://www.apachefriends.org/ (Accessed: 12 June 2026).

Apache Software Foundation (2026) *Apache HTTP Server Version 2.4
documentation*. Available at: https://httpd.apache.org/docs/2.4/ (Accessed:
12 June 2026).

Brevo (n.d.) *Send a transactional email: API reference*. Available at:
https://developers.brevo.com/reference/send-transac-email (Accessed:
12 June 2026).

Composer (n.d.) *Composer documentation*. Available at:
https://getcomposer.org/doc/ (Accessed: 12 June 2026).

GoDaddy (n.d.) *Web hosting*. Available at:
https://www.godaddy.com/hosting/web-hosting (Accessed: 12 June 2026).

Google (n.d.) *Get started with the Google Fonts API*. Available at:
https://developers.google.com/fonts/docs/getting_started (Accessed:
12 June 2026).

Google (2024) *Material Symbols guide*. Available at:
https://developers.google.com/fonts/docs/material_symbols (Accessed:
12 June 2026).

MariaDB (2026) *MariaDB Server documentation*. Available at:
https://mariadb.com/docs/server/ (Accessed: 12 June 2026).

PHPMailer contributors (2025) *PHPMailer version 6.12.0*. Available at:
https://github.com/PHPMailer/PHPMailer/tree/v6.12.0 (Accessed:
12 June 2026).

PHP Documentation Group (2026) *PHP manual*. Available at:
https://www.php.net/manual/en/ (Accessed: 12 June 2026).

phpMyAdmin devel team (2026) *phpMyAdmin documentation*. Available at:
https://docs.phpmyadmin.net/en/latest/ (Accessed: 12 June 2026).

Tailwind Labs (2025) *Try Tailwind CSS using the Play CDN*. Available at:
https://v3.tailwindcss.com/docs/installation/play-cdn (Accessed:
12 June 2026).

UI Avatars (n.d.) *Generate avatars with initials*. Available at:
https://ui-avatars.com/ (Accessed: 12 June 2026).

## Image Attribution to Complete

The following local image files are used by the project, but their original
creator, source URL, and licence are not recorded in the repository:

- `assets/images/Dewan-Astana.jpg`
- `assets/images/stadium.jpg`
- `assets/images/T05.jpg`
- `assets/images/T06.jpg`
- `assets/images/utm-gate.jpg`
- Root-level `T05.jpg`
- Root-level `T06.jpg`

Before submission, add a Harvard reference for each externally sourced image.
If a photograph was taken by a team member, identify it as the team's own
photograph instead of citing an external website. A suitable format is:

Author/Organisation (Year) *Title or description of image* [Photograph].
Available at: URL (Accessed: day month year).

## Deployment Note

The project currently loads Tailwind CSS through the version 3 Play CDN.
Tailwind Labs states that the Play CDN is intended for development and is not
the best choice for production. For the GoDaddy deployment, a compiled and
locally hosted Tailwind CSS file should be considered.
