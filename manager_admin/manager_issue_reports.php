<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/issue_reports_schema.php';
require_once __DIR__ . '/../includes/notifications.php';

$user = require_role(['facility_manager']);
ensure_issue_reports_table($conn);

$self_file = 'manager_issue_reports.php';
$attachment_file = 'issue_report_attachment.php';
$issueTypes = ['maintenance' => 'Maintenance', 'safety' => 'Safety', 'cleanliness' => 'Cleanliness', 'equipment' => 'Equipment', 'access' => 'Access', 'other' => 'Other'];
$priorities = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
$statuses = ['pending' => 'Pending', 'in_review' => 'In Review', 'resolved' => 'Resolved', 'closed' => 'Closed'];

$rooms = $conn->query("SELECT room_id, room_name, room_code, location FROM rooms ORDER BY room_name");
$facilities = $conn->query("SELECT facility_id, facility_name, facility_code, location FROM facilities ORDER BY facility_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_id'], $_POST['issue_status'])) {
    $issueId = (int)$_POST['issue_id'];
    $status = $_POST['issue_status'];
    $remarks = trim($_POST['admin_remarks'] ?? '');
    if (!array_key_exists($status, $statuses)) {
        header('Location: ' . $self_file . '?error=' . urlencode('Invalid issue status.'));
        exit;
    }
    $reviewedBy = (int)$user['user_id'];
    $reportStmt = $conn->prepare('SELECT reported_by, issue_status, issue_title FROM issue_reports WHERE issue_id = ? AND issue_hidden = 0 LIMIT 1');
    $reportStmt->bind_param('i', $issueId);
    $reportStmt->execute();
    $reportBefore = $reportStmt->get_result()->fetch_assoc() ?: [];
    $stmt = $conn->prepare('UPDATE issue_reports SET issue_status = ?, admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE issue_id = ? AND issue_hidden = 0');
    $stmt->bind_param('ssii', $status, $remarks, $reviewedBy, $issueId);
    $stmt->execute();
    if ($stmt->affected_rows > 0 && !empty($reportBefore['reported_by'])) {
        create_user_notification_mysqli($conn, (int)$reportBefore['reported_by'], null, 'Issue report updated', 'Your issue report #' . $issueId . ' (' . ($reportBefore['issue_title'] ?? 'Issue') . ') is now ' . ucwords(str_replace('_', ' ', $status)) . '.', 'issue_report');
    }
    header('Location: ' . $self_file . '?success=' . urlencode('Issue report updated.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['issue_title'] ?? '');
    $issueType = $_POST['issue_type'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $related = $_POST['related_resource'] ?? 'none';
    $resourceType = 'none';
    $roomId = null;
    $facilityId = null;

    if ($title === '' || strlen($title) > 150) {
        header('Location: ' . $self_file . '?error=' . urlencode('Issue title is required and must be 150 characters or below.'));
        exit;
    }
    if (!array_key_exists($issueType, $issueTypes)) {
        header('Location: ' . $self_file . '?error=' . urlencode('Please select a valid issue type.'));
        exit;
    }
    if ($description === '') {
        header('Location: ' . $self_file . '?error=' . urlencode('Description is required.'));
        exit;
    }
    if (!array_key_exists($priority, $priorities)) {
        header('Location: ' . $self_file . '?error=' . urlencode('Please select a valid priority.'));
        exit;
    }

    if (str_starts_with($related, 'room:')) {
        $resourceType = 'room';
        $roomId = (int)substr($related, 5);
    } elseif (str_starts_with($related, 'facility:')) {
        $resourceType = 'facility';
        $facilityId = (int)substr($related, 9);
    }

    $attachmentName = null;
    $attachmentMime = null;
    $attachmentBase64 = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            header('Location: ' . $self_file . '?error=' . urlencode('Attachment upload failed. Please try again.'));
            exit;
        }
        if ((int)$_FILES['attachment']['size'] > 5 * 1024 * 1024) {
            header('Location: ' . $self_file . '?error=' . urlencode('Attachment must be 5MB or below.'));
            exit;
        }
        $attachmentName = basename($_FILES['attachment']['name']);
        $attachmentMime = mime_content_type($_FILES['attachment']['tmp_name']) ?: 'application/octet-stream';
        $extension = strtolower(pathinfo($attachmentName, PATHINFO_EXTENSION));
        $allowedMime = ['image/jpeg','image/png','image/webp','image/gif','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $allowedExt = ['jpg','jpeg','png','webp','gif','pdf','doc','docx'];
        if (!in_array($attachmentMime, $allowedMime, true) && !in_array($extension, $allowedExt, true)) {
            header('Location: ' . $self_file . '?error=' . urlencode('Only image, PDF, DOC or DOCX attachments are allowed.'));
            exit;
        }
        $attachmentBase64 = base64_encode(file_get_contents($_FILES['attachment']['tmp_name']));
    }

    $reportedBy = (int)$user['user_id'];
    $stmt = $conn->prepare('INSERT INTO issue_reports (reported_by, issue_title, issue_type, description, related_resource_type, room_id, facility_id, priority, attachment_name, attachment_mime, attachment_base64) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssiissss', $reportedBy, $title, $issueType, $description, $resourceType, $roomId, $facilityId, $priority, $attachmentName, $attachmentMime, $attachmentBase64);
    $stmt->execute();
    $newIssueId = (int)$conn->insert_id;
    create_user_notification_mysqli($conn, $reportedBy, null, 'Issue report submitted', 'Your issue report #' . $newIssueId . ' has been submitted. Status is Pending.', 'issue_report');
    header('Location: ' . $self_file . '?success=' . urlencode('Issue report submitted. Status is Pending.'));
    exit;
}

$stmt = $conn->prepare("SELECT ir.issue_id, ir.issue_title, ir.issue_type, ir.description, ir.priority, ir.issue_status, ir.admin_remarks, ir.created_at, ir.attachment_name,
    CASE WHEN ir.attachment_base64 IS NULL OR ir.attachment_base64 = '' THEN 0 ELSE 1 END has_attachment,
    u.full_name, u.email, reviewer.full_name reviewed_name, COALESCE(r.room_name, f.facility_name) resource_name, COALESCE(r.location, f.location) resource_location
    FROM issue_reports ir
    JOIN users u ON u.user_id = ir.reported_by
    LEFT JOIN users reviewer ON reviewer.user_id = ir.reviewed_by
    LEFT JOIN rooms r ON r.room_id = ir.room_id
    LEFT JOIN facilities f ON f.facility_id = ir.facility_id
    WHERE ir.issue_hidden = 0
    ORDER BY ir.created_at DESC");
$stmt->execute();
$reports = $stmt->get_result();

$page_title = 'Facility Manager Issue Reports';
$active_page = 'issues';
include __DIR__ . '/includes/header.php';
?>
<div class="mb-8 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
    <div>
        <h1 class="text-4xl font-black text-[#36000f]">Issue Reports</h1>
        <p class="text-slate-500 mt-2">Submit, view and resolve shared room and facility issues across the manager team.</p>
    </div>
    <a class="btn-light" href="facility_manager_dashboard.php">Back to Dashboard</a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <form method="post" enctype="multipart/form-data" class="bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm space-y-4">
        <div>
            <h2 class="text-xl font-black text-[#36000f]">New Issue Report</h2>
            <p class="text-sm text-slate-500 mt-1">Submitted reports are visible to all facility managers.</p>
        </div>
        <div>
            <label class="text-sm font-bold text-slate-600">Issue Title</label>
            <input class="input mt-1" name="issue_title" required maxlength="150" placeholder="Example: Projector not working">
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-bold text-slate-600">Issue Type</label>
                <select class="input mt-1" name="issue_type" required>
                    <?php foreach ($issueTypes as $value => $label): ?><option value="<?= h($value) ?>"><?= h($label) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-sm font-bold text-slate-600">Priority</label>
                <select class="input mt-1" name="priority" required>
                    <?php foreach ($priorities as $value => $label): ?><option value="<?= h($value) ?>" <?= $value === 'medium' ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="text-sm font-bold text-slate-600">Related Room / Facility</label>
            <select class="input mt-1" name="related_resource">
                <option value="none">No specific room or facility</option>
                <optgroup label="Rooms">
                    <?php while ($room = $rooms->fetch_assoc()): ?>
                        <option value="room:<?= h($room['room_id']) ?>"><?= h($room['room_name']) ?><?= $room['room_code'] ? ' (' . h($room['room_code']) . ')' : '' ?> - <?= h($room['location']) ?></option>
                    <?php endwhile; ?>
                </optgroup>
                <optgroup label="Facilities">
                    <?php while ($facility = $facilities->fetch_assoc()): ?>
                        <option value="facility:<?= h($facility['facility_id']) ?>"><?= h($facility['facility_name']) ?><?= $facility['facility_code'] ? ' (' . h($facility['facility_code']) . ')' : '' ?> - <?= h($facility['location']) ?></option>
                    <?php endwhile; ?>
                </optgroup>
            </select>
        </div>
        <div>
            <label class="text-sm font-bold text-slate-600">Description</label>
            <textarea class="input mt-1 min-h-[150px]" name="description" required placeholder="Describe the problem, location detail, and impact."></textarea>
        </div>
        <div>
            <label class="text-sm font-bold text-slate-600">Supporting Image / Document</label>
            <input class="input mt-1" name="attachment" type="file" accept="image/*,.pdf,.doc,.docx">
            <p class="text-xs text-slate-500 mt-1">Accepted: images, PDF, DOC, DOCX. Maximum 5MB.</p>
        </div>
        <button class="btn-primary w-full">Submit Issue Report</button>
    </form>

    <div class="xl:col-span-2 bg-white rounded-xl border border-[#dcc0c2] shadow-sm overflow-x-auto">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-xl font-black text-[#36000f]">Submitted Reports</h2>
            <span class="text-sm text-slate-500"><?= h($reports->num_rows) ?> total</span>
        </div>
        <table class="w-full">
            <thead><tr><th class="table-th">Issue</th><th class="table-th">Reporter</th><th class="table-th">Related To</th><th class="table-th">Priority</th><th class="table-th">Status</th><th class="table-th">Attachment</th><th class="table-th">Manager Action</th></tr></thead>
            <tbody>
            <?php if ($reports->num_rows === 0): ?><tr><td class="table-td text-center text-slate-500" colspan="7">No visible issue reports submitted yet.</td></tr><?php endif; ?>
            <?php while ($report = $reports->fetch_assoc()): ?>
                <tr>
                    <td class="table-td font-bold">#<?= h($report['issue_id']) ?> <?= h($report['issue_title']) ?><p class="text-xs text-slate-500 mt-1"><?= h($issueTypes[$report['issue_type']] ?? $report['issue_type']) ?> · <?= h(date('d M Y, h:i A', strtotime($report['created_at']))) ?></p><p class="text-sm text-slate-600 mt-2"><?= h($report['description']) ?></p></td>
                    <td class="table-td"><?= h($report['full_name']) ?><p class="text-xs text-slate-500"><?= h($report['email']) ?></p></td>
                    <td class="table-td"><?= h($report['resource_name'] ?: 'Not specified') ?><p class="text-xs text-slate-500"><?= h($report['resource_location'] ?: '') ?></p></td>
                    <td class="table-td"><span class="badge badge-<?= h($report['priority'] === 'urgent' ? 'rejected' : ($report['priority'] === 'high' ? 'pending' : 'maintenance')) ?>"><?= h(ucfirst($report['priority'])) ?></span></td>
                    <td class="table-td"><span class="badge badge-<?= h($report['issue_status']) ?>"><?= h(ucwords(str_replace('_', ' ', $report['issue_status']))) ?></span><?php if ($report['reviewed_name']): ?><p class="text-xs text-slate-500 mt-1">By <?= h($report['reviewed_name']) ?></p><?php endif; ?></td>
                    <td class="table-td"><?php if ((int)$report['has_attachment'] === 1): ?><a class="text-red-900 font-bold" href="<?= h($attachment_file) ?>?id=<?= h($report['issue_id']) ?>">Download</a><p class="text-xs text-slate-500"><?= h($report['attachment_name']) ?></p><?php else: ?><span class="text-xs text-slate-400">No attachment</span><?php endif; ?></td>
                    <td class="table-td min-w-[250px]">
                        <form method="post" class="space-y-2">
                            <input type="hidden" name="issue_id" value="<?= h($report['issue_id']) ?>">
                            <select class="input text-xs" name="issue_status">
                                <?php foreach ($statuses as $value => $label): ?><option value="<?= h($value) ?>" <?= $report['issue_status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
                            </select>
                            <textarea class="input text-xs min-h-[80px]" name="admin_remarks" placeholder="Resolution notes or next action"><?= h($report['admin_remarks'] ?? '') ?></textarea>
                            <button class="btn-primary text-xs py-2">Update Report</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
