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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require __DIR__ . '/../../config/db.php';

$userId = (int)$_SESSION['user']['user_id'];
$title = trim((string)($_POST['issue_title'] ?? ''));
$issueType = trim((string)($_POST['issue_type'] ?? 'general'));
$priority = trim((string)($_POST['priority'] ?? 'medium'));
$description = trim((string)($_POST['description'] ?? ''));

$allowedTypes = ['maintenance', 'safety', 'cleanliness', 'equipment', 'access', 'other'];
$allowedPriorities = ['low', 'medium', 'high', 'urgent'];

if ($title === '' || $description === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Issue title and description are required.']);
    exit;
}

if (in_array($issueType, ['booking', 'payment', 'general'], true)) {
    $issueType = 'other';
}
if (!in_array($issueType, $allowedTypes, true)) {
    $issueType = 'other';
}
if (!in_array($priority, $allowedPriorities, true)) {
    $priority = 'medium';
}

$attachmentName = null;
$attachmentMime = null;
$attachmentBase64 = null;

if (!empty($_FILES['attachment']['tmp_name']) && is_uploaded_file($_FILES['attachment']['tmp_name'])) {
    if ((int)$_FILES['attachment']['size'] > 5 * 1024 * 1024) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Attachment must be 5MB or below.']);
        exit;
    }

    $attachmentName = basename((string)$_FILES['attachment']['name']);
    $attachmentMime = mime_content_type($_FILES['attachment']['tmp_name']) ?: 'application/octet-stream';
    $extension = strtolower(pathinfo($attachmentName, PATHINFO_EXTENSION));
    $allowedMime = ['image/jpeg','image/png','image/webp','image/gif','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $allowedExt = ['jpg','jpeg','png','webp','gif','pdf','doc','docx'];

    if (!in_array($attachmentMime, $allowedMime, true) && !in_array($extension, $allowedExt, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Only image, PDF, DOC or DOCX attachments are allowed.']);
        exit;
    }

    $attachmentBase64 = base64_encode(file_get_contents($_FILES['attachment']['tmp_name']));
}

$stmt = $pdo->prepare('INSERT INTO issue_reports (reported_by, issue_title, issue_type, description, related_resource_type, room_id, facility_id, priority, attachment_name, attachment_mime, attachment_base64) VALUES (:reported_by, :issue_title, :issue_type, :description, NULL, NULL, NULL, :priority, :attachment_name, :attachment_mime, :attachment_base64)');
$stmt->execute([
    'reported_by' => $userId,
    'issue_title' => $title,
    'issue_type' => $issueType,
    'description' => $description,
    'priority' => $priority,
    'attachment_name' => $attachmentName,
    'attachment_mime' => $attachmentMime,
    'attachment_base64' => $attachmentBase64,
]);

echo json_encode(['success' => true, 'message' => 'Issue report submitted.']);
