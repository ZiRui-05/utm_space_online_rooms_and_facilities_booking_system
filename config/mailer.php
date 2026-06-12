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
        'brevo_api_key' => trim((string)(getenv('BREVO_API_KEY') ?: '')),
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

function sendMailWithBrevoApi(
    array $config,
    string $toEmail,
    string $toName,
    string $subject,
    string $textContent,
    string $htmlContent
): array {
    if ($config['brevo_api_key'] === '') {
        return [
            'success' => false,
            'message' => 'Brevo API fallback is not configured',
            'code' => 'api_not_configured',
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'PHP cURL extension is required for Brevo API delivery',
            'code' => 'curl_missing',
        ];
    }

    $recipient = ['email' => $toEmail];
    if ($toName !== '') {
        $recipient['name'] = $toName;
    }

    $payload = [
        'sender' => [
            'email' => $config['from_email'],
            'name' => $config['from_name'],
        ],
        'to' => [$recipient],
        'subject' => $subject,
        'textContent' => $textContent,
        'htmlContent' => $htmlContent !== ''
            ? $htmlContent
            : nl2br(htmlspecialchars($textContent, ENT_QUOTES, 'UTF-8')),
    ];

    if ($config['reply_to_email'] !== '') {
        $payload['replyTo'] = [
            'email' => $config['reply_to_email'],
            'name' => $config['reply_to_name'] !== ''
                ? $config['reply_to_name']
                : $config['from_name'],
        ];
    }

    try {
        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    } catch (JsonException $e) {
        return [
            'success' => false,
            'message' => 'Unable to encode Brevo API request',
            'code' => 'api_payload_failed',
            'detail' => $e->getMessage(),
        ];
    }

    $curl = curl_init('https://api.brevo.com/v3/smtp/email');
    if ($curl === false) {
        return [
            'success' => false,
            'message' => 'Unable to initialize Brevo API request',
            'code' => 'api_init_failed',
        ];
    }

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => max(1, min(10, $config['timeout'])),
        CURLOPT_TIMEOUT => max(1, $config['timeout']),
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'api-key: ' . $config['brevo_api_key'],
        ],
        CURLOPT_POSTFIELDS => $jsonPayload,
    ]);

    $responseBody = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if ($responseBody === false) {
        return [
            'success' => false,
            'message' => 'Brevo API request failed',
            'code' => 'api_connection_failed',
            'detail' => $curlError,
        ];
    }

    $response = json_decode($responseBody, true);
    if ($statusCode >= 200 && $statusCode < 300) {
        return [
            'success' => true,
            'message' => 'Email accepted by Brevo API',
            'code' => 'sent',
            'message_id' => is_array($response) ? (string)($response['messageId'] ?? '') : '',
        ];
    }

    $apiMessage = is_array($response) ? trim((string)($response['message'] ?? '')) : '';

    return [
        'success' => false,
        'message' => 'Brevo API rejected the email request',
        'code' => 'api_rejected',
        'detail' => $apiMessage !== '' ? $apiMessage : 'HTTP ' . $statusCode,
        'http_status' => $statusCode,
    ];
}

function sendMail(string $toEmail, string $toName, string $subject, string $textContent, string $htmlContent = ''): array
{
    $requestId = bin2hex(random_bytes(8));
    $recipientHash = hash('sha256', strtolower(trim($toEmail)));
    $config = getMailerConfig();
    $smtpConfigured = $config['username'] !== '' && $config['password'] !== '';
    $apiConfigured = $config['brevo_api_key'] !== '';

    if ($config['from_email'] === '' || (!$smtpConfigured && !$apiConfigured)) {
        $missing = [];
        if ($config['from_email'] === '') {
            $missing[] = 'MAIL_FROM_EMAIL';
        }
        if (!$smtpConfigured && !$apiConfigured) {
            $missing[] = 'SMTP credentials or BREVO_API_KEY';
        }

        $message = 'Missing mail configuration: ' . implode(', ', $missing);
        writeMailLog('mail_failed', [
            'request_id' => $requestId,
            'recipient_hash' => $recipientHash,
            'code' => 'configuration_missing',
            'missing' => $missing,
        ]);
        return ['success' => false, 'message' => $message, 'code' => 'configuration_missing', 'request_id' => $requestId];
    }

    if (
        !filter_var($toEmail, FILTER_VALIDATE_EMAIL)
        || !filter_var($config['from_email'], FILTER_VALIDATE_EMAIL)
        || ($config['reply_to_email'] !== '' && !filter_var($config['reply_to_email'], FILTER_VALIDATE_EMAIL))
    ) {
        $message = 'Invalid sender or recipient email address';
        writeMailLog('mail_failed', ['request_id' => $requestId, 'recipient_hash' => $recipientHash, 'code' => 'invalid_address']);
        return ['success' => false, 'message' => $message, 'code' => 'invalid_address', 'request_id' => $requestId];
    }

    $smtpFailure = null;

    if ($smtpConfigured) {
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            $smtpFailure = [
                'message' => 'Missing Composer autoload. Run: composer install',
                'code' => 'autoload_missing',
            ];
        } else {
            require_once $autoloadPath;

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
                writeMailLog('mail_accepted', [
                    'request_id' => $requestId,
                    'recipient_hash' => $recipientHash,
                    'transport' => 'smtp',
                    'message_id' => $mail->getLastMessageID(),
                ]);
                return [
                    'success' => true,
                    'message' => 'Email accepted by SMTP relay',
                    'code' => 'sent',
                    'transport' => 'smtp',
                    'request_id' => $requestId,
                ];
            } catch (Throwable $e) {
                $smtpFailure = [
                    'message' => 'SMTP send failed: ' . $e->getMessage(),
                    'code' => 'smtp_failed',
                    'detail' => $e->getMessage(),
                ];
            }
        }

        writeMailLog('mail_transport_failed', [
            'request_id' => $requestId,
            'recipient_hash' => $recipientHash,
            'transport' => 'smtp',
            'code' => $smtpFailure['code'],
            'detail' => $smtpFailure['detail'] ?? $smtpFailure['message'],
        ]);
    }

    if ($apiConfigured) {
        $apiResult = sendMailWithBrevoApi($config, $toEmail, $toName, $subject, $textContent, $htmlContent);
        if ($apiResult['success']) {
            writeMailLog('mail_accepted', [
                'request_id' => $requestId,
                'recipient_hash' => $recipientHash,
                'transport' => 'brevo_api',
                'fallback_from' => $smtpFailure !== null ? 'smtp' : null,
                'message_id' => $apiResult['message_id'] ?? '',
            ]);

            return [
                'success' => true,
                'message' => $apiResult['message'],
                'code' => 'sent',
                'transport' => 'brevo_api',
                'request_id' => $requestId,
            ];
        }

        writeMailLog('mail_transport_failed', [
            'request_id' => $requestId,
            'recipient_hash' => $recipientHash,
            'transport' => 'brevo_api',
            'code' => $apiResult['code'],
            'detail' => $apiResult['detail'] ?? $apiResult['message'],
            'http_status' => $apiResult['http_status'] ?? null,
        ]);

        writeMailLog('mail_failed', [
            'request_id' => $requestId,
            'recipient_hash' => $recipientHash,
            'code' => 'all_transports_failed',
        ]);

        return [
            'success' => false,
            'message' => 'Email delivery failed through all configured transports',
            'code' => 'all_transports_failed',
            'request_id' => $requestId,
        ];
    }

    writeMailLog('mail_failed', [
        'request_id' => $requestId,
        'recipient_hash' => $recipientHash,
        'code' => $smtpFailure['code'] ?? 'configuration_missing',
        'fallback' => 'not_configured',
    ]);

    return [
        'success' => false,
        'message' => $smtpFailure['message'] ?? 'No mail transport is configured',
        'code' => $smtpFailure['code'] ?? 'configuration_missing',
        'request_id' => $requestId,
    ];
}
