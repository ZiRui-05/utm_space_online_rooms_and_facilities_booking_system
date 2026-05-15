<?php

function getMailerConfig(): array
{
    return [
        'host' => getenv('MAIL_HOST') ?: 'smtp-relay.brevo.com',
        'port' => (int)(getenv('MAIL_PORT') ?: 587),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'no-reply@projectutmspacebooking.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'UTM Space Booking',
        'reply_to_email' => getenv('MAIL_REPLY_TO_EMAIL') ?: '',
        'reply_to_name' => getenv('MAIL_REPLY_TO_NAME') ?: '',
    ];
}

function sendMail(string $toEmail, string $toName, string $subject, string $textContent, string $htmlContent = ''): array
{
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        return ['success' => false, 'message' => 'Missing Composer autoload. Run: composer install'];
    }

    require_once $autoloadPath;

    $config = getMailerConfig();
    if ($config['username'] === '' || $config['password'] === '') {
        return ['success' => false, 'message' => 'Missing MAIL_USERNAME or MAIL_PASSWORD'];
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'] === 'tls'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $config['port'];

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);

        if ($config['reply_to_email'] !== '') {
            $mail->addReplyTo(
                $config['reply_to_email'],
                $config['reply_to_name'] !== '' ? $config['reply_to_name'] : $config['from_name']
            );
        }

        $mail->Subject = $subject;
        $mail->Body = $htmlContent !== '' ? $htmlContent : nl2br(htmlspecialchars($textContent, ENT_QUOTES, 'UTF-8'));
        $mail->AltBody = $textContent;
        $mail->isHTML(true);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent'];
    } catch (Throwable $e) {
        return ['success' => false, 'message' => 'SMTP send failed: ' . $e->getMessage()];
    }
}
