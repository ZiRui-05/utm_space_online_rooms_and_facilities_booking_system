<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
$user = require_role(['admin']);
$self_file='admin_manage_users.php'; $detail_file='admin_user_detail.php';
$roles=['student','staff','facility_manager','admin'];
$accountStatuses=['active','inactive','suspended'];
$departments=[
    'Faculty of Artificial Intelligence (FAI)',
    'Faculty of Built Environment and Surveying',
    'Faculty of Chemical & Energy Engineering',
    'Faculty of Computing',
    'Faculty of Educational Sciences and Technology (FEST)',
    'Faculty of Electrical Engineering',
    'Faculty of Management',
    'Faculty of Mechanical Engineering',
    'Faculty of Civil Engineering',
    'Faculty of Science',
    'Faculty of Social Sciences and Humanities',
    'SPACE UTM (School of Professional and Continuing Education)'
];

function image_upload_payload(string $field): array {
    if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) return [];
    if (!empty($_FILES[$field]['error'])) throw new RuntimeException('Image upload failed. Please choose another file.');
    if ((int)$_FILES[$field]['size'] > 2 * 1024 * 1024) throw new RuntimeException('Image must be 2MB or smaller.');
    $raw = file_get_contents($_FILES[$field]['tmp_name']);
    $info = @getimagesizefromstring($raw);
    if (!$info) throw new RuntimeException('Uploaded file must be a valid image.');
    $mime = (string)($info['mime'] ?? '');
    if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) throw new RuntimeException('Image must be JPG, PNG, or WEBP.');
    return ['base64'=>base64_encode($raw), 'mime'=>$mime];
}

if (isset($_GET['delete'])) {
    $id=(int)$_GET['delete'];
    if($id !== (int)$user['user_id']){ $stmt=$conn->prepare('DELETE FROM users WHERE user_id=?'); $stmt->bind_param('i',$id); $stmt->execute(); }
    header('Location: '.$self_file.'?success='.urlencode('Account deleted')); exit;
}

$edit=null;
if(isset($_GET['edit'])){
    $id=(int)$_GET['edit']; $stmt=$conn->prepare('SELECT * FROM users WHERE user_id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $edit=$stmt->get_result()->fetch_assoc();
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['user_id']??0);
    $name=trim($_POST['full_name']??'');
    $email=trim($_POST['email']??'');
    $utmId=trim($_POST['utm_id']??'');
    $icNo=trim($_POST['ic_no']??'');
    $phone=trim($_POST['phone_number']??'');
    $department=in_array($_POST['department']??'', $departments, true) ? $_POST['department'] : '';
    $status=in_array($_POST['account_status']??'', $accountStatuses, true) ? $_POST['account_status'] : 'active';
    $role=in_array($_POST['role']??'', $roles, true) ? $_POST['role'] : 'student';
    $pass=$_POST['password']??'';
    if($name==='' || $email===''){ header('Location: '.$self_file.'?error='.urlencode('Name and email are required')); exit; }

    try { $profileImage = image_upload_payload('profile_picture'); }
    catch (RuntimeException $e) { header('Location: '.$self_file.'?error='.urlencode($e->getMessage())); exit; }

    if($id>0){
        $beforeStmt=$conn->prepare('SELECT full_name,email,role,account_status FROM users WHERE user_id=? LIMIT 1');
        $beforeStmt->bind_param('i',$id);
        $beforeStmt->execute();
        $beforeUser=$beforeStmt->get_result()->fetch_assoc() ?: [];
        $set=['full_name=?','email=?','utm_id=?','ic_no=?','role=?','phone_number=?','department=?','account_status=?'];
        $types='ssssssss';
        $params=[$name,$email,$utmId,$icNo,$role,$phone,$department,$status];
        if($pass!==''){ $hash=password_hash($pass,PASSWORD_DEFAULT); $set[]='password_hash=?'; $types.='s'; $params[]=$hash; }
        if($profileImage){ $set[]='profile_image_base64=?'; $set[]='profile_image_mime=?'; $types.='ss'; $params[]=$profileImage['base64']; $params[]=$profileImage['mime']; }
        $sql='UPDATE users SET '.implode(', ',$set).' WHERE user_id=?'; $types.='i'; $params[]=$id;
        $stmt=$conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute();
        $changes=[];
        if(($beforeUser['role']??'') !== $role) $changes[]='role changed to '.ucwords(str_replace('_',' ',$role));
        if(($beforeUser['account_status']??'') !== $status) $changes[]='account status changed to '.$status;
        if($pass!=='') $changes[]='password reset by admin';
        if($profileImage) $changes[]='profile picture updated by admin';
        $detail=$changes ? implode(', ', $changes) : 'your account details were updated';
        create_user_notification_mysqli($conn,$id,null,'Account updated','Admin updated your account: '.$detail.'.','account');
        $msg='Account updated';
    } else {
        $hash=password_hash($pass ?: 'password123', PASSWORD_DEFAULT);
        if($profileImage){
            $stmt=$conn->prepare('INSERT INTO users(utm_id,ic_no,full_name,email,password_hash,role,phone_number,department,profile_image_base64,profile_image_mime,email_verified,verification_status,account_status) VALUES(?,?,?,?,?,?,?,?,?,?,1,\'unverified\',?)');
            $stmt->bind_param('sssssssssss',$utmId,$icNo,$name,$email,$hash,$role,$phone,$department,$profileImage['base64'],$profileImage['mime'],$status);
        } else {
            $stmt=$conn->prepare('INSERT INTO users(utm_id,ic_no,full_name,email,password_hash,role,phone_number,department,email_verified,verification_status,account_status) VALUES(?,?,?,?,?,?,?,?,1,\'unverified\',?)');
            $stmt->bind_param('sssssssss',$utmId,$icNo,$name,$email,$hash,$role,$phone,$department,$status);
        }
        $stmt->execute();
        $newUserId=(int)$conn->insert_id;
        create_user_notification_mysqli($conn,$newUserId,null,'Account created','Your SPACEBOOK account has been created. Please sign in and complete your profile.','account');
        $msg='User account added';
    }
    header('Location: '.$self_file.'?success='.urlencode($msg)); exit;
}

$roleFilter=$_GET['role'] ?? '';
$sql='SELECT * FROM users'; $where=[]; $types=''; $params=[];
if(in_array($roleFilter,$roles,true)){ $where[]='role=?'; $types.='s'; $params[]=$roleFilter; }
if($where) $sql.=' WHERE '.implode(' AND ',$where);
$sql.=' ORDER BY role, full_name';
$stmt=$conn->prepare($sql); if($params) $stmt->bind_param($types, ...$params); $stmt->execute(); $users=$stmt->get_result();
$page_title='Admin Manage User Accounts'; $active_page='users'; include __DIR__ . '/includes/header.php';
?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Admin - User Accounts</h1><p class="text-slate-500 mt-2">Add, view, edit, and delete user accounts only. Click the account name to view full account details.</p></div>
<form class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-6 grid md:grid-cols-4 gap-4"><select class="input" name="role"><option value="">All Roles</option><?php foreach($roles as $r): ?><option value="<?= h($r) ?>" <?= $roleFilter===$r?'selected':'' ?>><?= h(ucwords(str_replace('_',' ',$r))) ?></option><?php endforeach; ?></select><button class="btn-primary">Apply Filter</button><a class="btn-light text-center" href="admin_manage_users.php">Reset</a><a class="btn-light text-center" href="admin_verify_cards.php">Verify Student Cards</a></form>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6"><form method="post" enctype="multipart/form-data" class="bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm space-y-4"><h2 class="text-xl font-black text-[#36000f]"><?= $edit?'Update Account':'Add User / Admin Account' ?></h2><input type="hidden" name="user_id" value="<?= h($edit['user_id']??0) ?>"><input class="input" name="full_name" required placeholder="Full name" value="<?= h($edit['full_name']??'') ?>"><input class="input" name="email" type="email" required placeholder="Email" value="<?= h($edit['email']??'') ?>"><input class="input" name="utm_id" placeholder="UTM ID" value="<?= h($edit['utm_id']??'') ?>"><input class="input" name="ic_no" placeholder="IC No" value="<?= h($edit['ic_no']??'') ?>"><input class="input" name="phone_number" placeholder="Phone number" value="<?= h($edit['phone_number']??'') ?>"><select class="input" name="department"><option value="">Select Department</option><?php foreach($departments as $department): ?><option value="<?= h($department) ?>" <?= ($edit['department']??'')===$department?'selected':'' ?>><?= h($department) ?></option><?php endforeach; ?></select><label class="block text-xs uppercase font-bold text-slate-500">Profile Picture</label><input class="input" name="profile_picture" type="file" accept="image/jpeg,image/png,image/webp"><input class="input" name="password" type="password" placeholder="Password <?= $edit?'(leave blank to keep old)':'(default password123 if blank)' ?>"><select class="input" name="role"><?php foreach($roles as $r): ?><option value="<?= h($r) ?>" <?= ($edit['role']??'admin')===$r?'selected':'' ?>><?= h(ucwords(str_replace('_',' ',$r))) ?></option><?php endforeach; ?></select><select class="input" name="account_status"><?php foreach($accountStatuses as $s): ?><option value="<?= $s ?>" <?= ($edit['account_status']??'active')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select><button class="btn-primary w-full"><?= $edit?'Update Account':'Add Account' ?></button></form>
<div class="xl:col-span-2 bg-white rounded-xl border border-[#dcc0c2] shadow-sm overflow-x-auto"><table class="w-full"><thead><tr><th class="table-th">Account</th><th class="table-th">Role</th><th class="table-th">Phone</th><th class="table-th">Status</th><th class="table-th">Action</th></tr></thead><tbody><?php while($u=$users->fetch_assoc()): ?><tr><td class="table-td font-bold"><a class="text-red-900 hover:underline" href="<?= h($detail_file) ?>?id=<?= h($u['user_id']) ?>"><?= h($u['full_name']) ?></a><p class="text-xs text-slate-500"><?= h($u['email']) ?></p></td><td class="table-td"><?= h(ucwords(str_replace('_',' ',$u['role']))) ?></td><td class="table-td"><?= h($u['phone_number']) ?></td><td class="table-td"><span class="badge badge-<?= h($u['account_status']) ?>"><?= h($u['account_status']) ?></span></td><td class="table-td"><a class="text-red-900 font-bold" href="?edit=<?= h($u['user_id']) ?>">Edit</a><?php if((int)$u['user_id'] !== (int)$user['user_id']): ?> · <a class="text-red-700 font-bold" onclick="return confirm('Delete this account?')" href="?delete=<?= h($u['user_id']) ?>">Delete</a><?php endif; ?></td></tr><?php endwhile; ?></tbody></table></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
