<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['facility_manager']);
$self_file='manager_manage_users.php'; $detail_file='manager_user_detail.php'; $allowedRole='facility_manager';
if (isset($_GET['delete'])) { $id=(int)$_GET['delete']; if($id !== (int)$user['user_id']){ $stmt=$conn->prepare('DELETE FROM users WHERE user_id=? AND role=?'); $stmt->bind_param('is',$id,$allowedRole); $stmt->execute(); } header('Location: '.$self_file.'?success='.urlencode('Account deleted')); exit; }
$edit=null; if(isset($_GET['edit'])){ $id=(int)$_GET['edit']; $stmt=$conn->prepare('SELECT * FROM users WHERE user_id=? AND role=?'); $stmt->bind_param('is',$id,$allowedRole); $stmt->execute(); $edit=$stmt->get_result()->fetch_assoc(); }
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['user_id']??0); $name=trim($_POST['full_name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone_number']??''); $status=$_POST['account_status']??'active'; $pass=$_POST['password']??'';
    if($name==='' || $email===''){ header('Location: '.$self_file.'?error='.urlencode('Name and email are required')); exit; }
    if($id>0){
        if($pass!==''){ $hash=password_hash($pass,PASSWORD_DEFAULT); $stmt=$conn->prepare('UPDATE users SET full_name=?, email=?, phone_number=?, account_status=?, password_hash=? WHERE user_id=? AND role=?'); $stmt->bind_param('sssssis',$name,$email,$phone,$status,$hash,$id,$allowedRole); }
        else { $stmt=$conn->prepare('UPDATE users SET full_name=?, email=?, phone_number=?, account_status=? WHERE user_id=? AND role=?'); $stmt->bind_param('ssssis',$name,$email,$phone,$status,$id,$allowedRole); }
        $stmt->execute(); $msg='Account updated';
    } else {
        $hash=password_hash($pass ?: 'password123', PASSWORD_DEFAULT); $verification='verified';
        $stmt=$conn->prepare('INSERT INTO users(full_name,email,password_hash,role,phone_number,email_verified,verification_status,account_status) VALUES(?,?,?,?,?,1,?,?)');
        $stmt->bind_param('sssssss',$name,$email,$hash,$allowedRole,$phone,$verification,$status); $stmt->execute(); $msg='Facility manager account added';
    }
    header('Location: '.$self_file.'?success='.urlencode($msg)); exit;
}
$stmt=$conn->prepare('SELECT * FROM users WHERE role=? ORDER BY full_name'); $stmt->bind_param('s',$allowedRole); $stmt->execute(); $users=$stmt->get_result();
$page_title='Facility Manager Accounts'; $active_page='users'; include __DIR__ . '/includes/header.php';
?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Facility Manager - Manager Accounts</h1><p class="text-slate-500 mt-2">Only Facility Manager accounts can be created or edited here.</p></div>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6"><form method="post" class="bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm space-y-4"><h2 class="text-xl font-black text-[#36000f]"><?= $edit?'Update Account':'Add Facility manager account' ?></h2><input type="hidden" name="user_id" value="<?= h($edit['user_id']??0) ?>"><input class="input" name="full_name" required placeholder="Full name" value="<?= h($edit['full_name']??'') ?>"><input class="input" name="email" type="email" required placeholder="Email" value="<?= h($edit['email']??'') ?>"><input class="input" name="phone_number" placeholder="Phone number" value="<?= h($edit['phone_number']??'') ?>"><input class="input" name="password" type="password" placeholder="Password <?= $edit?'(leave blank to keep old)':'(default password123 if blank)' ?>"><select class="input" disabled><option>Facility Manager</option></select><select class="input" name="account_status"><?php foreach(['active','inactive','suspended'] as $s): ?><option value="<?= $s ?>" <?= ($edit['account_status']??'active')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select><button class="btn-primary w-full"><?= $edit?'Update Account':'Add Facility manager account' ?></button></form>
<div class="xl:col-span-2 bg-white rounded-xl border border-[#dcc0c2] shadow-sm overflow-x-auto"><table class="w-full"><thead><tr><th class="table-th">User</th><th class="table-th">Role</th><th class="table-th">Verification</th><th class="table-th">Status</th><th class="table-th">Action</th></tr></thead><tbody><?php while($u=$users->fetch_assoc()): ?><tr><td class="table-td font-bold"><?= h($u['full_name']) ?><p class="text-xs text-slate-500"><?= h($u['email']) ?> · <?= h($u['phone_number']) ?></p></td><td class="table-td"><?= h(ucwords(str_replace('_',' ',$u['role']))) ?></td><td class="table-td"><span class="badge badge-<?= h($u['verification_status'] ?? 'unverified') ?>"><?= h($u['verification_status'] ?? 'unverified') ?></span></td><td class="table-td"><span class="badge badge-<?= h($u['account_status']) ?>"><?= h($u['account_status']) ?></span></td><td class="table-td"><a class="text-red-900 font-bold" href="<?= h($detail_file) ?>?id=<?= h($u['user_id']) ?>">View</a> · <a class="text-red-900 font-bold" href="?edit=<?= h($u['user_id']) ?>">Edit</a><?php if((int)$u['user_id'] !== (int)$user['user_id']): ?> · <a class="text-red-700 font-bold" onclick="return confirm('Delete this account?')" href="?delete=<?= h($u['user_id']) ?>">Delete</a><?php endif; ?></td></tr><?php endwhile; ?></tbody></table></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
