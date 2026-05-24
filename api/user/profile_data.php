<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../../config/db.php';

$userId = (int)$_SESSION['user']['user_id'];


function processAvatarImage(string $rawData): array
{
    $decoded = base64_decode($rawData, true);
    if ($decoded === false) {
        throw new RuntimeException('Invalid avatar image format.');
    }

    $imageInfo = @getimagesizefromstring($decoded);
    if ($imageInfo === false) {
        throw new RuntimeException('Unsupported avatar image file.');
    }

    $targetWidth = 413;
    $targetHeight = 591;
    $maxBytes = 100 * 1024;

    if (
        function_exists('imagecreatefromstring') &&
        function_exists('imagecreatetruecolor') &&
        function_exists('imagecopyresampled') &&
        function_exists('imagejpeg')
    ) {
        $source = @imagecreatefromstring($decoded);
        if ($source === false) {
            throw new RuntimeException('Failed to read avatar image.');
        }

        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            imagesx($source),
            imagesy($source)
        );

        $jpegData = null;
        for ($quality = 90; $quality >= 45; $quality -= 5) {
            ob_start();
            imagejpeg($target, null, $quality);
            $candidate = ob_get_clean();
            if (strlen($candidate) < $maxBytes) {
                $jpegData = $candidate;
                break;
            }
        }

        imagedestroy($source);
        imagedestroy($target);

        if ($jpegData === null) {
            throw new RuntimeException('Avatar must be less than 100KB after processing.');
        }

        return [
            'profile_image_base64' => base64_encode($jpegData),
            'profile_image_mime' => 'image/jpeg',
        ];
    }

    // Fallback for environments without GD: trust client-side processing but validate constraints.
    $width = (int)($imageInfo[0] ?? 0);
    $height = (int)($imageInfo[1] ?? 0);
    $mime = (string)($imageInfo['mime'] ?? '');

    if ($width !== $targetWidth || $height !== $targetHeight) {
        throw new RuntimeException('Avatar must be exactly 413 x 591 pixels.');
    }

    if ($mime !== 'image/jpeg') {
        throw new RuntimeException('Avatar must be JPEG format.');
    }

    if (strlen($decoded) >= $maxBytes) {
        throw new RuntimeException('Avatar must be less than 100KB.');
    }

    return [
        'profile_image_base64' => base64_encode($decoded),
        'profile_image_mime' => 'image/jpeg',
    ];
}


function processUtmCardImage(string $rawData, string $declaredMime = ''): array
{
    $decoded = base64_decode($rawData, true);
    if ($decoded === false) {
        throw new RuntimeException('Invalid UTM card image format.');
    }

    if (strlen($decoded) > 2 * 1024 * 1024) {
        throw new RuntimeException('UTM card image must be 2MB or smaller.');
    }

    $imageInfo = @getimagesizefromstring($decoded);
    if ($imageInfo === false) {
        throw new RuntimeException('UTM card must be uploaded as an image file.');
    }

    $actualMime = (string)($imageInfo['mime'] ?? '');
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($actualMime, $allowedMimeTypes, true)) {
        throw new RuntimeException('UTM card must be JPG, PNG, or WEBP.');
    }

    if ($declaredMime !== '' && !in_array($declaredMime, $allowedMimeTypes, true)) {
        throw new RuntimeException('Unsupported UTM card file type.');
    }

    return [
        'utm_card_base64' => base64_encode($decoded),
        'utm_card_mime' => $actualMime,
    ];
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, utm_id, ic_no, phone_number, department, gender, address, verification_status, profile_image_base64, profile_image_mime, CASE WHEN utm_card_base64 IS NULL OR utm_card_base64 = '' THEN 0 ELSE 1 END AS has_utm_card_front, CASE WHEN utm_card_back_base64 IS NULL OR utm_card_back_base64 = '' THEN 0 ELSE 1 END AS has_utm_card_back, CASE WHEN (utm_card_base64 IS NULL OR utm_card_base64 = '' OR utm_card_back_base64 IS NULL OR utm_card_back_base64 = '') THEN 0 ELSE 1 END AS has_utm_card FROM users WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $bookingStmt = $pdo->prepare(
        'SELECT b.booking_id, b.resource_type, b.booking_start, b.booking_end, b.booking_status, b.created_at,
                r.room_name, f.facility_name
         FROM bookings b
         LEFT JOIN rooms r ON b.room_id = r.room_id
         LEFT JOIN facilities f ON b.facility_id = f.facility_id
         WHERE b.user_id = :user_id
         ORDER BY b.booking_start DESC, b.created_at DESC'
    );
    $bookingStmt->execute(['user_id' => $userId]);
    $bookings = $bookingStmt->fetchAll();

    echo json_encode(['success' => true, 'user' => $user, 'bookings' => $bookings]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $phoneNumber = trim($input['phone_number'] ?? '');
    $department = trim($input['department'] ?? '');
    $gender = trim($input['gender'] ?? '');
    $address = trim($input['address'] ?? '');
    $avatarBase64 = trim($input['avatar_base64'] ?? '');
    $utmCardBase64 = trim($input['utm_card_base64'] ?? $input['utm_card_front_base64'] ?? '');
    $utmCardMime = trim($input['utm_card_mime'] ?? $input['utm_card_front_mime'] ?? '');
    $utmCardBackBase64 = trim($input['utm_card_back_base64'] ?? '');
    $utmCardBackMime = trim($input['utm_card_back_mime'] ?? '');

    $allowedGenders = ['', 'Male', 'Female', 'Other'];
    if (!in_array($gender, $allowedGenders, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please choose a valid gender.']);
        exit;
    }

    $params = [
        'phone_number' => $phoneNumber === '' ? null : $phoneNumber,
        'department' => $department === '' ? null : $department,
        'gender' => $gender === '' ? null : $gender,
        'address' => $address === '' ? null : $address,
        'user_id' => $userId,
    ];

    $setParts = [
        'phone_number = :phone_number',
        'department = :department',
        'gender = :gender',
        'address = :address',
    ];

    if ($avatarBase64 !== '') {
        try {
            $avatarPayload = processAvatarImage($avatarBase64);
            $params['profile_image_base64'] = $avatarPayload['profile_image_base64'];
            $params['profile_image_mime'] = $avatarPayload['profile_image_mime'];
            $setParts[] = 'profile_image_base64 = :profile_image_base64';
            $setParts[] = 'profile_image_mime = :profile_image_mime';
        } catch (RuntimeException $error) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $error->getMessage()]);
            exit;
        }
    }

    if ($utmCardBase64 !== '' || $utmCardBackBase64 !== '') {
        if ($utmCardBase64 === '' || $utmCardBackBase64 === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Please upload both front and back images of your UTM card.']);
            exit;
        }
        try {
            $cardPayload = processUtmCardImage($utmCardBase64, $utmCardMime);
            $cardBackPayload = processUtmCardImage($utmCardBackBase64, $utmCardBackMime);
            $params['utm_card_base64'] = $cardPayload['utm_card_base64'];
            $params['utm_card_mime'] = $cardPayload['utm_card_mime'];
            $params['utm_card_back_base64'] = $cardBackPayload['utm_card_base64'];
            $params['utm_card_back_mime'] = $cardBackPayload['utm_card_mime'];
            $setParts[] = 'utm_card_base64 = :utm_card_base64';
            $setParts[] = 'utm_card_mime = :utm_card_mime';
            $setParts[] = 'utm_card_back_base64 = :utm_card_back_base64';
            $setParts[] = 'utm_card_back_mime = :utm_card_back_mime';
            $setParts[] = "verification_status = 'unverified'";
        } catch (RuntimeException $error) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $error->getMessage()]);
            exit;
        }
    }

    $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $setParts) . ' WHERE user_id = :user_id');
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'Profile updated']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
