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
BREVO_API_KEY=your-brevo-api-key
```

`MAIL_FROM_EMAIL` must be a sender or domain verified in Brevo. The SMTP
password starts with `xsmtpsib-`; a Brevo API key starting with `xkeysib-`
must not be used for SMTP authentication.

The application tries SMTP first. If SMTP is unavailable or rejects the send,
it falls back to Brevo's Transactional Email API when `BREVO_API_KEY` is set.
If the SMTP relay already accepted the message, a later bounce or block does
not trigger the API fallback; inspect the Brevo transactional log for that
message ID and recipient.

## Production deployment

Set these values through the hosting control panel, web server environment,
container secrets, or process manager. Do not upload a populated `.env` file
or place credentials in PHP source code.

Run `cron/booking_return_reminder.php` every one or two minutes using the same
environment variables. Ensure PHP, the database server, and the host use
`Asia/Kuala_Lumpur` or store consistently converted UTC timestamps.

Runtime delivery information is written to `logs/mail.log` and
`logs/booking_return_reminder.log`. The logs do not contain plaintext recipient
addresses. An accepted request is logged as `mail_accepted` with its `transport`
(`smtp` or `brevo_api`). Provider acceptance does not guarantee final delivery;
failed transport attempts are recorded before fallback.

## Key rotation

Revoke any SMTP or API key that has been committed, pasted into a ticket, or
shared in chat. Create distinct keys for local and production, update the
environment variables, restart the web process, then verify password reset and
booking approval delivery in the Brevo transactional log.
