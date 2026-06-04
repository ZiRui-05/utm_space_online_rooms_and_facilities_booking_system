<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/issue_reports_schema.php';

$user = require_role(['admin']);
ensure_issue_reports_table($conn);

$self_file = 'admin_issue_reports.php';
$attachment_file = 'issue_report_attachment.php';
$issueTypes = ['maintenance' => 'Maintenance', 'safety' => 'Safety', 'cleanliness' => 'Cleanliness', 'equipment' => 'Equipment', 'access' => 'Access', 'other' => 'Other'];
$priorities = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
$statuses = ['pending' => 'Pending', 'in_review' => 'In Review', 'resolved' => 'Resolved', 'closed' => 'Closed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_id'], $_POST['admin_issue_action'])) {
    $issueId = (int)$_POST['issue_id'];
    $action = $_POST['admin_issue_action'];
    if ($issueId <= 0 || !in_array($action, ['hide', 'unhide', 'delete'], true)) {
        header('Location: ' . $self_file . '?error=' . urlencode('Invalid issue action.'));
        exit;
    }
    if ($action === 'delete') {
        $stmt = $conn->prepare('DELETE FROM issue_reports WHERE issue_id = ?');
        $stmt->bind_param('i', $issueId);
        $stmt->execute();
        header('Location: ' . $self_file . '?success=' . urlencode('Issue report deleted.'));
        exit;
    }
    $hidden = $action === 'hide' ? 1 : 0;
    $hiddenBy = $hidden ? (int)$user['user_id'] : null;
    $stmt = $conn->prepare('UPDATE issue_reports SET issue_hidden = ?, hidden_by = ?, hidden_at = ' . ($hidden ? 'NOW()' : 'NULL') . ' WHERE issue_id = ?');
    $stmt->bind_param('iii', $hidden, $hiddenBy, $issueId);
    $stmt->execute();
    header('Location: ' . $self_file . '?success=' . urlencode($hidden ? 'Issue report hidden from managers.' : 'Issue report restored for managers.'));
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$priorityFilter = $_GET['priority'] ?? 'all';
$typeFilter = $_GET['issue_type'] ?? 'all';
$visibilityFilter = $_GET['visibility'] ?? 'visible';
$where = [];
$types = '';
$params = [];
if ($statusFilter !== 'all' && array_key_exists($statusFilter, $statuses)) {
    $where[] = 'ir.issue_status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}
if ($priorityFilter !== 'all' && array_key_exists($priorityFilter, $priorities)) {
    $where[] = 'ir.priority = ?';
    $types .= 's';
    $params[] = $priorityFilter;
}
if ($typeFilter !== 'all' && array_key_exists($typeFilter, $issueTypes)) {
    $where[] = 'ir.issue_type = ?';
    $types .= 's';
    $params[] = $typeFilter;
}
if ($visibilityFilter === 'visible') {
    $where[] = 'ir.issue_hidden = 0';
} elseif ($visibilityFilter === 'hidden') {
    $where[] = 'ir.issue_hidden = 1';
}

$sql = "SELECT ir.issue_id, ir.issue_title, ir.issue_type, ir.description, ir.priority, ir.issue_status, ir.issue_hidden, ir.hidden_at, ir.created_at, ir.admin_remarks, ir.attachment_name,
        CASE WHEN ir.attachment_base64 IS NULL OR ir.attachment_base64 = '' THEN 0 ELSE 1 END has_attachment,
        u.full_name, u.email, reviewer.full_name reviewed_name, hidden_by_user.full_name hidden_by_name,
        COALESCE(r.room_name, f.facility_name) resource_name,
        COALESCE(r.location, f.location) resource_location
    FROM issue_reports ir
    JOIN users u ON u.user_id = ir.reported_by
    LEFT JOIN users reviewer ON reviewer.user_id = ir.reviewed_by
    LEFT JOIN users hidden_by_user ON hidden_by_user.user_id = ir.hidden_by
    LEFT JOIN rooms r ON r.room_id = ir.room_id
    LEFT JOIN facilities f ON f.facility_id = ir.facility_id"
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY ir.created_at DESC';
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reports = $stmt->get_result();

$page_title = 'Admin Issue Controls';
$active_page = 'issues';
include __DIR__ . '/includes/header.php';
?>
<div class="mb-8 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
    <div>
        <h1 class="text-4xl font-black text-[#36000f]">Issue Reports</h1>
        <p class="text-slate-500 mt-2">Monitor reports and hide or delete records when needed. Managers handle status and resolution.</p>
    </div>
    <a class="btn-warning" href="admin_dashboard.php">Admin Dashboard</a>
</div>

<form class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-6 grid md:grid-cols-5 gap-4">
    <select class="input" name="status">
        <option value="all">All Status</option>
        <?php foreach ($statuses as $value => $label): ?><option value="<?= h($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
    </select>
    <select class="input" name="priority">
        <option value="all">All Priority</option>
        <?php foreach ($priorities as $value => $label): ?><option value="<?= h($value) ?>" <?= $priorityFilter === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
    </select>
    <select class="input" name="issue_type">
        <option value="all">All Issue Types</option>
        <?php foreach ($issueTypes as $value => $label): ?><option value="<?= h($value) ?>" <?= $typeFilter === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
    </select>
    <select class="input" name="visibility">
        <option value="visible" <?= $visibilityFilter === 'visible' ? 'selected' : '' ?>>Visible to Managers</option>
        <option value="hidden" <?= $visibilityFilter === 'hidden' ? 'selected' : '' ?>>Hidden</option>
        <option value="all" <?= $visibilityFilter === 'all' ? 'selected' : '' ?>>All Visibility</option>
    </select>
    <button class="btn-primary">Apply Filter</button>
</form>

<div class="bg-white rounded-xl border border-[#dcc0c2] shadow-sm overflow-x-auto">
    <div class="p-5 border-b border-slate-200 flex items-center justify-between">
        <h2 class="text-xl font-black text-[#36000f]">Submitted Issue Reports</h2>
        <span class="text-sm text-slate-500"><?= h($reports->num_rows) ?> shown</span>
    </div>
    <table class="w-full">
        <thead><tr><th class="table-th">Issue</th><th class="table-th">Reporter</th><th class="table-th">Related To</th><th class="table-th">Priority</th><th class="table-th">Status</th><th class="table-th">Attachment</th><th class="table-th">Admin Action</th></tr></thead>
        <tbody>
        <?php if ($reports->num_rows === 0): ?><tr><td class="table-td text-center text-slate-500" colspan="7">No issue reports found.</td></tr><?php endif; ?>
        <?php while ($report = $reports->fetch_assoc()): ?>
            <tr>
                <td class="table-td font-bold min-w-[260px]">#<?= h($report['issue_id']) ?> <?= h($report['issue_title']) ?><?php if ((int)$report['issue_hidden'] === 1): ?> <span class="badge badge-closed">Hidden</span><?php endif; ?><p class="text-xs text-slate-500 mt-1"><?= h($issueTypes[$report['issue_type']] ?? $report['issue_type']) ?> · <?= h(date('d M Y, h:i A', strtotime($report['created_at']))) ?></p><p class="text-sm text-slate-600 mt-2"><?= h($report['description']) ?></p><?php if ((int)$report['issue_hidden'] === 1): ?><p class="text-xs text-slate-500 mt-2">Hidden<?= $report['hidden_by_name'] ? ' by ' . h($report['hidden_by_name']) : '' ?><?= $report['hidden_at'] ? ' on ' . h(date('d M Y, h:i A', strtotime($report['hidden_at']))) : '' ?></p><?php endif; ?></td>
                <td class="table-td"><?= h($report['full_name']) ?><p class="text-xs text-slate-500"><?= h($report['email']) ?></p></td>
                <td class="table-td"><?= h($report['resource_name'] ?: 'Not specified') ?><p class="text-xs text-slate-500"><?= h($report['resource_location'] ?: '') ?></p></td>
                <td class="table-td"><span class="badge badge-<?= h($report['priority'] === 'urgent' ? 'rejected' : ($report['priority'] === 'high' ? 'pending' : 'maintenance')) ?>"><?= h(ucfirst($report['priority'])) ?></span></td>
                <td class="table-td"><span class="badge badge-<?= h($report['issue_status']) ?>"><?= h(ucwords(str_replace('_', ' ', $report['issue_status']))) ?></span><?php if ($report['reviewed_name']): ?><p class="text-xs text-slate-500 mt-1">By <?= h($report['reviewed_name']) ?></p><?php endif; ?></td>
                <td class="table-td"><?php if ((int)$report['has_attachment'] === 1): ?><a class="text-red-900 font-bold" href="<?= h($attachment_file) ?>?id=<?= h($report['issue_id']) ?>">Download</a><p class="text-xs text-slate-500"><?= h($report['attachment_name']) ?></p><?php else: ?><span class="text-xs text-slate-400">No attachment</span><?php endif; ?></td>
                <td class="table-td min-w-[250px]">
                    <form method="post" class="flex flex-wrap gap-2">
                        <input type="hidden" name="issue_id" value="<?= h($report['issue_id']) ?>">
                        <?php if ((int)$report['issue_hidden'] === 1): ?>
                            <button class="btn-light text-xs py-2" name="admin_issue_action" value="unhide">Unhide</button>
                        <?php else: ?>
                            <button class="btn-warning text-xs py-2" name="admin_issue_action" value="hide">Hide</button>
                        <?php endif; ?>
                        <button class="btn-primary text-xs py-2 bg-red-800" name="admin_issue_action" value="delete" onclick="return confirm('Delete this issue report permanently?')">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
