<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please sign in before requesting account assistance.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/notifications.php';

$userId = (int)$_SESSION['user']['user_id'];
$requiredProfileFields = [
    'full_name' => 'Full name',
    'utm_id' => 'UTM ID',
    'ic_no' => 'IC number',
    'phone_number' => 'Phone number',
    'department' => 'Department',
    'gender' => 'Gender',
    'address' => 'Address',
];

$stmt = $pdo->prepare(
    "SELECT full_name, utm_id, ic_no, phone_number, department, gender, address,
            verification_status, account_status,
            CASE
                WHEN utm_card_base64 IS NOT NULL AND utm_card_base64 <> ''
                 AND utm_card_back_base64 IS NOT NULL AND utm_card_back_base64 <> ''
                THEN 1 ELSE 0
            END AS has_utm_card
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Account not found.']);
    exit;
}

$missingFields = [];
foreach ($requiredProfileFields as $fieldKey => $label) {
    if (trim((string)($profile[$fieldKey] ?? '')) === '') {
        $missingFields[] = $label;
    }
}

$verificationStatus = strtolower((string)($profile['verification_status'] ?? 'unverified'));
$accountStatus = strtolower((string)($profile['account_status'] ?? 'inactive'));
$hasUtmCard = (int)($profile['has_utm_card'] ?? 0) === 1;

if ($accountStatus === 'active' && $verificationStatus === 'verified' && $missingFields === []) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'code' => 'account_ready',
        'message' => 'Your account is complete and verified. Please try submitting the booking again.',
    ]);
    exit;
}

$adminsStmt = $pdo->query("SELECT user_id FROM users WHERE role = 'admin' AND account_status = 'active'");
$adminIds = array_map('intval', $adminsStmt ? $adminsStmt->fetchAll(PDO::FETCH_COLUMN) : []);

if ($adminIds === []) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'code' => 'no_available_admin',
        'message' => 'No active administrator is available right now. Please try again later.',
    ]);
    exit;
}

ensure_user_notifications_table_pdo($pdo);
$notificationTitle = 'Account assistance requested (User #' . $userId . ')';
$placeholders = implode(',', array_fill(0, count($adminIds), '?'));
$duplicateStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM user_notifications
     WHERE user_id IN ($placeholders)
       AND notification_type = 'account_assistance'
       AND title = ?
       AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
);
$duplicateStmt->execute([...$adminIds, $notificationTitle]);

if ((int)$duplicateStmt->fetchColumn() > 0) {
    echo json_encode([
        'success' => true,
        'code' => 'recently_notified',
        'message' => 'An administrator was already notified about your account within the last 24 hours.',
    ]);
    exit;
}

$issues = [];
if ($accountStatus !== 'active') {
    $issues[] = 'account status is ' . ($accountStatus !== '' ? $accountStatus : 'inactive');
}
if ($missingFields !== []) {
    $issues[] = 'missing profile fields: ' . implode(', ', $missingFields);
}
if ($verificationStatus !== 'verified') {
    $issues[] = $hasUtmCard
        ? 'UTM card uploaded and awaiting verification'
        : 'front and back UTM card images have not both been uploaded';
}

$fullName = trim((string)($profile['full_name'] ?? ''));
$utmId = trim((string)($profile['utm_id'] ?? ''));
$identity = 'User #' . $userId
    . ($fullName !== '' ? ' (' . $fullName . ')' : '')
    . ($utmId !== '' ? ', UTM ID: ' . $utmId : '');
$notificationMessage = $identity . ' requested help with booking access. Issues: ' . implode('; ', $issues) . '.';

foreach ($adminIds as $adminId) {
    create_user_notification_pdo(
        $pdo,
        $adminId,
        null,
        $notificationTitle,
        $notificationMessage,
        'account_assistance'
    );
}

echo json_encode([
    'success' => true,
    'code' => 'admin_notified',
    'message' => 'An administrator has been notified. You can continue updating your profile while waiting for assistance.',
]);
