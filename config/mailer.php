<?php
require_once __DIR__ . '/app.php';

function getMailerConfig(): array
{
    return [
        'host' => getenv('MAIL_HOST') ?: 'smtp-relay.brevo.com',
        'port' => (int)(getenv('MAIL_PORT') ?: 587),
        'username' => trim((string)(getenv('MAIL_USERNAME') ?: '')),
        'password' => (string)(getenv('MAIL_PASSWORD') ?: ''),
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from_email' => trim((string)(getenv('MAIL_FROM_EMAIL') ?: '')),
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'UTM Space Booking',
        'reply_to_email' => getenv('MAIL_REPLY_TO_EMAIL') ?: '',
        'reply_to_name' => getenv('MAIL_REPLY_TO_NAME') ?: '',
        'timeout' => (int)(getenv('MAIL_TIMEOUT') ?: 15),
    ];
}

function writeMailLog(string $event, array $context = []): void
{
    $logDirectory = __DIR__ . '/../logs';
    if (!is_dir($logDirectory) && !mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
        error_log('Unable to create mail log directory: ' . $logDirectory);
        return;
    }

    $record = array_merge([
        'timestamp' => date(DATE_ATOM),
        'event' => $event,
    ], $context);

    file_put_contents(
        $logDirectory . '/mail.log',
        json_encode($record, JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

function sendMail(string $toEmail, string $toName, string $subject, string $textContent, string $htmlContent = ''): array
{
    $requestId = bin2hex(random_bytes(8));
    $recipientHash = hash('sha256', strtolower(trim($toEmail)));
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        $message = 'Missing Composer autoload. Run: composer install';
        writeMailLog('mail_failed', ['request_id' => $requestId, 'recipient_hash' => $recipientHash, 'code' => 'autoload_missing']);
        return ['success' => false, 'message' => $message, 'code' => 'autoload_missing', 'request_id' => $requestId];
    }

    require_once $autoloadPath;

    $config = getMailerConfig();
    $required = [
        'MAIL_USERNAME' => $config['username'],
        'MAIL_PASSWORD' => $config['password'],
        'MAIL_FROM_EMAIL' => $config['from_email'],
    ];
    $missing = array_keys(array_filter($required, static fn($value): bool => trim((string)$value) === ''));
    if ($missing !== []) {
        $message = 'Missing mail configuration: ' . implode(', ', $missing);
        writeMailLog('mail_failed', [
            'request_id' => $requestId,
            'recipient_hash' => $recipientHash,
            'code' => 'configuration_missing',
            'missing' => $missing,
        ]);
        return ['success' => false, 'message' => $message, 'code' => 'configuration_missing', 'request_id' => $requestId];
    }

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL) || !filter_var($config['from_email'], FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid sender or recipient email address';
        writeMailLog('mail_failed', ['request_id' => $requestId, 'recipient_hash' => $recipientHash, 'code' => 'invalid_address']);
        return ['success' => false, 'message' => $message, 'code' => 'invalid_address', 'request_id' => $requestId];
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $encryption = strtolower(trim((string)$config['encryption']));
        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl' || $encryption === 'smtps') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }
        $mail->Port = $config['port'];
        $mail->Timeout = max(1, $config['timeout']);
        $mail->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;

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
        writeMailLog('mail_sent', [
            'request_id' => $requestId,
            'recipient_hash' => $recipientHash,
            'message_id' => $mail->getLastMessageID(),
        ]);
        return ['success' => true, 'message' => 'Email sent', 'code' => 'sent', 'request_id' => $requestId];
    } catch (Throwable $e) {
        writeMailLog('mail_failed', [
            'request_id' => $requestId,
            'recipient_hash' => $recipientHash,
            'code' => 'smtp_failed',
            'detail' => $e->getMessage(),
        ]);
        return [
            'success' => false,
            'message' => 'SMTP send failed: ' . $e->getMessage(),
            'code' => 'smtp_failed',
            'request_id' => $requestId,
        ];
    }
}
