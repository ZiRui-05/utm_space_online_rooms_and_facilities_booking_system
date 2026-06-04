<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/issue_reports_schema.php';

$user = require_role(['admin', 'facility_manager']);
ensure_issue_reports_table($conn);

$issueId = (int)($_GET['id'] ?? 0);
if ($issueId <= 0) {
    http_response_code(404);
    exit('Attachment not found.');
}

$stmt = $conn->prepare('SELECT issue_id, reported_by, attachment_name, attachment_mime, attachment_base64 FROM issue_reports WHERE issue_id = ? LIMIT 1');
$stmt->bind_param('i', $issueId);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report || empty($report['attachment_base64']) || (($user['role'] ?? '') !== 'admin' && (int)$report['reported_by'] !== (int)$user['user_id'])) {
    http_response_code(404);
    exit('Attachment not found.');
}

$filename = preg_replace('/[^A-Za-z0-9._ -]/', '_', $report['attachment_name'] ?: ('issue-report-' . $issueId));
header('Content-Type: ' . ($report['attachment_mime'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo base64_decode($report['attachment_base64']);
exit;
?>
