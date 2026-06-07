<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
$user = require_role(['facility_manager']);
$allowedRole = 'facility_manager';
$back = 'manager_manage_users.php';
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification = in_array($_POST['verification_status'] ?? '', ['unverified','verified'], true) ? $_POST['verification_status'] : 'unverified';
    $stmt = $conn->prepare('UPDATE users SET verification_status=? WHERE user_id=? AND role=?');
    $stmt->bind_param('sis', $verification, $id, $allowedRole);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        create_user_notification_mysqli($conn, $id, null, 'Account verification updated', 'Your account verification status is now ' . $verification . '.', 'account');
    }
    header('Location: manager_user_detail.php?id=' . $id . '&success=' . urlencode('Verification status updated'));
    exit;
}
$stmt = $conn->prepare("SELECT user_id, full_name, email, phone_number, utm_id, ic_no, role, account_status, verification_status,
    CASE WHEN profile_image_base64 IS NULL OR profile_image_base64 = '' THEN 0 ELSE 1 END has_profile_image,
    CASE WHEN utm_card_base64 IS NULL OR utm_card_base64 = '' THEN 0 ELSE 1 END has_utm_card_front,
    CASE WHEN utm_card_back_base64 IS NULL OR utm_card_back_base64 = '' THEN 0 ELSE 1 END has_utm_card_back
    FROM users WHERE user_id=? AND role=? LIMIT 1");
$stmt->bind_param('is', $id, $allowedRole);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
if (!$u) {
    header('Location: ' . $back . '?error=' . urlencode('User not found'));
    exit;
}
$page_title = 'User Detail';
$active_page = 'users';
include __DIR__ . '/includes/header.php';
$placeholder = 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
$profile = (int)$u['has_profile_image'] === 1
    ? 'user_image.php?id=' . (int)$u['user_id'] . '&kind=profile'
    : 'https://ui-avatars.com/api/?name=' . urlencode($u['full_name'] ?? 'User') . '&background=5c001f&color=fff';
?>
<div class="mb-8 flex justify-between items-end"><div><h1 class="text-4xl font-black text-[#36000f]">User Detail</h1><p class="text-slate-500 mt-2">View profile picture, UTM card and edit verification status.</p></div><a class="btn-light" href="<?= h($back) ?>">Back</a></div>
<div class="grid lg:grid-cols-3 gap-6"><div class="bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm"><h2 class="text-xl font-black text-[#36000f] mb-4">Profile Picture</h2><img src="<?= $placeholder ?>" data-async-src="<?= h($profile) ?>" loading="lazy" decoding="async" class="w-full max-h-72 object-cover rounded-xl border" alt="Profile picture"></div><div class="lg:col-span-2 bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm"><h2 class="text-xl font-black text-[#36000f] mb-4">Account Information</h2><div class="grid sm:grid-cols-2 gap-4"><div><p class="text-xs uppercase font-bold text-slate-500">User ID</p><p class="font-bold"><?= h($u['user_id']) ?></p></div><div><p class="text-xs uppercase font-bold text-slate-500">Full name</p><p class="font-bold"><?= h($u['full_name']) ?></p></div><div><p class="text-xs uppercase font-bold text-slate-500">Email</p><p><?= h($u['email']) ?></p></div><div><p class="text-xs uppercase font-bold text-slate-500">Phone</p><p><?= h($u['phone_number']) ?></p></div><div><p class="text-xs uppercase font-bold text-slate-500">UTM ID</p><p><?= h($u['utm_id']) ?></p></div><div><p class="text-xs uppercase font-bold text-slate-500">IC No</p><p><?= h($u['ic_no']) ?></p></div><div><p class="text-xs uppercase font-bold text-slate-500">Role</p><p><?= h(ucwords(str_replace('_',' ',$u['role']))) ?></p></div><div><p class="text-xs uppercase font-bold text-slate-500">Account status</p><span class="badge badge-<?= h($u['account_status']) ?>"><?= h($u['account_status']) ?></span></div></div><form method="post" class="mt-6 flex gap-3 items-end"><div><label class="text-xs uppercase font-bold text-slate-500">Verification Status</label><select class="input" name="verification_status"><option value="unverified" <?= ($u['verification_status']??'unverified')==='unverified'?'selected':'' ?>>Unverified</option><option value="verified" <?= ($u['verification_status']??'unverified')==='verified'?'selected':'' ?>>Verified</option></select></div><button class="btn-primary">Update Verification</button></form></div></div>
<div class="mt-6 bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm"><h2 class="text-xl font-black text-[#36000f] mb-4">UTM Card</h2><div class="grid md:grid-cols-2 gap-6"><?php if((int)$u['has_utm_card_front'] === 1): ?><div><p class="text-xs uppercase font-bold text-slate-500 mb-2">Front Side</p><a href="user_image.php?id=<?= h($u['user_id']) ?>&kind=utm_front" target="_blank"><img src="<?= $placeholder ?>" data-async-src="user_image.php?id=<?= h($u['user_id']) ?>&kind=utm_front" loading="lazy" decoding="async" class="max-w-sm w-full rounded-xl border" alt="UTM card front"></a></div><?php else: ?><div><p class="text-slate-500">No front card uploaded.</p></div><?php endif; ?><?php if((int)$u['has_utm_card_back'] === 1): ?><div><p class="text-xs uppercase font-bold text-slate-500 mb-2">Back Side</p><a href="user_image.php?id=<?= h($u['user_id']) ?>&kind=utm_back" target="_blank"><img src="<?= $placeholder ?>" data-async-src="user_image.php?id=<?= h($u['user_id']) ?>&kind=utm_back" loading="lazy" decoding="async" class="max-w-sm w-full rounded-xl border" alt="UTM card back"></a></div><?php else: ?><div><p class="text-slate-500">No back card uploaded.</p></div><?php endif; ?></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
