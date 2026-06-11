# Email configuration

The application sends email for password reset codes, first-time booking
approval, and booking return reminders.

## Required environment variables

Use separate Brevo SMTP keys for local development and production.

```text
APP_TIMEZONE=Asia/Kuala_Lumpur
APP_URL=https://your-production-domain.example
APP_SECRET=replace-with-a-long-random-secret
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your-brevo-smtp-login
MAIL_PASSWORD=your-brevo-smtp-key
MAIL_ENCRYPTION=tls
MAIL_FROM_EMAIL=verified-sender@example.com
MAIL_FROM_NAME=UTM Space Booking
MAIL_TIMEOUT=15
```

`MAIL_FROM_EMAIL` must be a sender or domain verified in Brevo. The SMTP
password starts with `xsmtpsib-`; a Brevo API key starting with `xkeysib-`
must not be used for SMTP authentication.

## Production deployment

Set these values through the hosting control panel, web server environment,
container secrets, or process manager. Do not upload a populated `.env` file
or place credentials in PHP source code.

Run `cron/booking_return_reminder.php` every one or two minutes using the same
environment variables. Ensure PHP, the database server, and the host use
`Asia/Kuala_Lumpur` or store consistently converted UTC timestamps.

Runtime delivery information is written to `logs/mail.log` and
`logs/booking_return_reminder.log`. The logs do not contain plaintext recipient
addresses.

## Key rotation

Revoke any SMTP or API key that has been committed, pasted into a ticket, or
shared in chat. Create distinct keys for local and production, update the
environment variables, restart the web process, then verify password reset and
booking approval delivery in the Brevo transactional log.
