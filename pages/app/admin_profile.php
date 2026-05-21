<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['admin']);
$stmt = $conn->prepare('SELECT * FROM users WHERE user_id=? LIMIT 1');
$stmt->bind_param('i', $user['user_id']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$page_title='Admin Profile'; $active_page='dashboard'; include __DIR__ . '/includes/header.php';
$img = !empty($profile['profile_image_base64']) ? 'data:' . h($profile['profile_image_mime'] ?: 'image/png') . ';base64,' . $profile['profile_image_base64'] : 'https://ui-avatars.com/api/?name=' . urlencode($profile['full_name'] ?? 'Admin') . '&background=5c001f&color=fff';
?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Admin Profile</h1><p class="text-slate-500 mt-2">Your administrator account information.</p></div>
<div class="bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm max-w-3xl">
    <div class="flex gap-6 items-start"><img src="<?= $img ?>" class="w-32 h-32 rounded-xl object-cover border" alt="Profile image"><div class="grid sm:grid-cols-2 gap-4 flex-1">
        <div><p class="text-xs uppercase font-bold text-slate-500">Full name</p><p class="font-bold"><?= h($profile['full_name'] ?? '') ?></p></div>
        <div><p class="text-xs uppercase font-bold text-slate-500">Email</p><p class="font-bold"><?= h($profile['email'] ?? '') ?></p></div>
        <div><p class="text-xs uppercase font-bold text-slate-500">Phone</p><p><?= h($profile['phone_number'] ?? '-') ?></p></div>
        <div><p class="text-xs uppercase font-bold text-slate-500">Role</p><span class="badge badge-active">Admin</span></div>
        <div><p class="text-xs uppercase font-bold text-slate-500">Account status</p><span class="badge badge-<?= h($profile['account_status'] ?? 'active') ?>"><?= h($profile['account_status'] ?? '') ?></span></div>
        <div><p class="text-xs uppercase font-bold text-slate-500">Verification</p><span class="badge badge-<?= h($profile['verification_status'] ?? 'unverified') ?>"><?= h($profile['verification_status'] ?? 'unverified') ?></span></div>
    </div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
