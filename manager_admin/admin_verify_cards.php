<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['admin']);
$self_file='admin_verify_cards.php';

if (isset($_GET['verify_card']) || isset($_GET['unverify_card'])) {
    $id = (int)($_GET['verify_card'] ?? $_GET['unverify_card']);
    $newStatus = isset($_GET['verify_card']) ? 'verified' : 'unverified';
    $stmt=$conn->prepare("UPDATE users SET verification_status=? WHERE user_id=? AND utm_card_base64 IS NOT NULL AND utm_card_base64 <> '' AND utm_card_back_base64 IS NOT NULL AND utm_card_back_base64 <> ''");
    $stmt->bind_param('si',$newStatus,$id); $stmt->execute();
    header('Location: '.$self_file.'?success='.urlencode('Student card verification status updated')); exit;
}

$statusFilter=$_GET['status'] ?? 'pending';
$where="utm_card_base64 IS NOT NULL AND utm_card_base64 <> '' AND utm_card_back_base64 IS NOT NULL AND utm_card_back_base64 <> ''";
if($statusFilter==='pending') $where .= " AND verification_status='unverified'";
elseif($statusFilter==='verified') $where .= " AND verification_status='verified'";
$stmt=$conn->prepare("SELECT user_id, full_name, email, phone_number, utm_id, role, verification_status, utm_card_base64, utm_card_mime, utm_card_back_base64, utm_card_back_mime FROM users WHERE $where ORDER BY CASE WHEN verification_status='unverified' THEN 0 ELSE 1 END, full_name");
$stmt->execute(); $users=$stmt->get_result();
$page_title='Verify Student Cards'; $active_page='verify_cards'; include __DIR__ . '/includes/header.php';
?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Verify Student Card</h1><p class="text-slate-500 mt-2">Review front and back UTM card pictures submitted by students. Account add/edit/delete remains in User Accounts.</p></div>
<form class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-6 grid md:grid-cols-4 gap-4"><select class="input" name="status"><option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Pending / Unverified Cards</option><option value="verified" <?= $statusFilter==='verified'?'selected':'' ?>>Verified Cards</option><option value="all" <?= $statusFilter==='all'?'selected':'' ?>>All Uploaded Cards</option></select><button class="btn-primary">Apply Filter</button><a class="btn-light text-center" href="admin_verify_cards.php">Reset</a><a class="btn-light text-center" href="admin_manage_users.php">Back to Accounts</a></form>
<div class="bg-white rounded-xl border border-[#dcc0c2] shadow-sm overflow-x-auto"><table class="w-full"><thead><tr><th class="table-th">Student</th><th class="table-th">UTM Card Front</th><th class="table-th">UTM Card Back</th><th class="table-th">Current Status</th><th class="table-th">Admin Action</th></tr></thead><tbody><?php if($users->num_rows===0): ?><tr><td colspan="5" class="table-td text-center text-slate-500">No complete front/back student card upload found for this filter.</td></tr><?php endif; ?><?php while($u=$users->fetch_assoc()): ?><tr><td class="table-td font-bold"><a class="text-red-900 hover:underline" href="admin_user_detail.php?id=<?= h($u['user_id']) ?>"><?= h($u['full_name']) ?></a><p class="text-xs text-slate-500"><?= h($u['email']) ?> Â· <?= h($u['phone_number']) ?></p><p class="text-xs text-slate-500">UTM ID: <?= h($u['utm_id']) ?> Â· Role: <?= h(ucwords(str_replace('_',' ',$u['role']))) ?></p></td><td class="table-td"><a href="data:<?= h($u['utm_card_mime'] ?: 'image/jpeg') ?>;base64,<?= h($u['utm_card_base64']) ?>" target="_blank"><img src="data:<?= h($u['utm_card_mime'] ?: 'image/jpeg') ?>;base64,<?= h($u['utm_card_base64']) ?>" alt="UTM Card Front" class="max-h-40 rounded-lg border border-slate-200"></a><p class="text-xs text-slate-500 mt-2">Click image to open larger view.</p></td><td class="table-td"><a href="data:<?= h($u['utm_card_back_mime'] ?: 'image/jpeg') ?>;base64,<?= h($u['utm_card_back_base64']) ?>" target="_blank"><img src="data:<?= h($u['utm_card_back_mime'] ?: 'image/jpeg') ?>;base64,<?= h($u['utm_card_back_base64']) ?>" alt="UTM Card Back" class="max-h-40 rounded-lg border border-slate-200"></a><p class="text-xs text-slate-500 mt-2">Click image to open larger view.</p></td><td class="table-td"><span class="badge badge-<?= h($u['verification_status'] ?? 'unverified') ?>"><?= h($u['verification_status'] ?? 'unverified') ?></span></td><td class="table-td"><?php if(($u['verification_status']??'unverified')==='verified'): ?><a class="btn-warning" href="?unverify_card=<?= h($u['user_id']) ?>" onclick="return confirm('Mark this student card as unverified?')">Mark Unverified</a><?php else: ?><a class="btn-primary" href="?verify_card=<?= h($u['user_id']) ?>" onclick="return confirm('Verify both sides of this student card?')">Verify Card</a><?php endif; ?></td></tr><?php endwhile; ?></tbody></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
