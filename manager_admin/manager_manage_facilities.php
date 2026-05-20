<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['facility_manager']);
if (isset($_GET['delete'])) { $id=(int)$_GET['delete']; $stmt=$conn->prepare('DELETE FROM facilities WHERE facility_id=?'); $stmt->bind_param('i',$id); $stmt->execute(); header('Location: manager_manage_facilities.php?success=Facility deleted'); exit; }
$edit = null; if(isset($_GET['edit'])){ $id=(int)$_GET['edit']; $stmt=$conn->prepare('SELECT * FROM facilities WHERE facility_id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $edit=$stmt->get_result()->fetch_assoc(); }
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['facility_id']??0); $name=trim($_POST['facility_name']); $code=trim($_POST['facility_code']); $type=trim($_POST['facility_type']); $loc=trim($_POST['location']); $cap=(int)$_POST['capacity']; $desc=trim($_POST['description']); $price=(float)$_POST['price_per_day']; $status=$_POST['resource_status'];
    if($id>0){ $stmt=$conn->prepare('UPDATE facilities SET facility_name=?, facility_code=?, facility_type=?, location=?, capacity=?, description=?, price_per_day=?, resource_status=? WHERE facility_id=?'); $stmt->bind_param('ssssisdsi',$name,$code,$type,$loc,$cap,$desc,$price,$status,$id); $stmt->execute(); $msg='Facility updated'; }
    else { $stmt=$conn->prepare('INSERT INTO facilities(facility_name,facility_code,facility_type,location,capacity,description,price_per_day,resource_status) VALUES(?,?,?,?,?,?,?,?)'); $stmt->bind_param('ssssisds',$name,$code,$type,$loc,$cap,$desc,$price,$status); $stmt->execute(); $msg='Facility added'; }
    header('Location: manager_manage_facilities.php?success='.urlencode($msg)); exit;
}
$search=trim($_GET['search'] ?? '');
$statusFilter=trim($_GET['status'] ?? '');
$sql="SELECT * FROM facilities WHERE 1=1";
if($search!==''){ $sql.=" AND (facility_name LIKE '%".$conn->real_escape_string($search)."%' OR location LIKE '%".$conn->real_escape_string($search)."%')"; }
if($statusFilter!==''){ $sql.=" AND resource_status='".$conn->real_escape_string($statusFilter)."'"; }
$sql.=' ORDER BY facility_name';
$facilities=$conn->query($sql); $page_title='Facility Manager Manage Facilities'; $active_page='facilities'; include __DIR__ . '/includes/header.php';
?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Facility Manager - Facility Management</h1><p class="text-slate-500 mt-2">Search facilities, filter availability and manage facility records.</p></div>
<div class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-6">
<form class="grid md:grid-cols-3 gap-4">
<input class="input" name="search" value="<?= h($search) ?>" placeholder="Search facility or location">
<select class="input" name="status"><option value="">All Status</option><?php foreach(['available','unavailable','maintenance'] as $s): ?><option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
<button class="btn-primary">Search / Filter</button>
</form></div>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6"><form method="post" class="bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm space-y-4"><h2 class="text-xl font-black text-[#36000f]"><?= $edit?'Update Facility':'Add New Facility' ?></h2><input type="hidden" name="facility_id" value="<?= h($edit['facility_id']??0) ?>"><input class="input" name="facility_name" required placeholder="Facility name" value="<?= h($edit['facility_name']??'') ?>"><input class="input" name="facility_code" placeholder="Facility code" value="<?= h($edit['facility_code']??'') ?>"><input class="input" name="facility_type" placeholder="Facility type" value="<?= h($edit['facility_type']??'') ?>"><input class="input" name="location" required placeholder="Location" value="<?= h($edit['location']??'') ?>"><input class="input" name="capacity" type="number" placeholder="Capacity" value="<?= h($edit['capacity']??'') ?>"><input class="input" name="price_per_day" type="number" step="0.01" placeholder="Price per day" value="<?= h($edit['price_per_day']??'0.00') ?>"><select class="input" name="resource_status"><?php foreach(['available','unavailable','maintenance'] as $s): ?><option value="<?= $s ?>" <?= ($edit['resource_status']??'available')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select><textarea class="input" name="description" placeholder="Description"><?= h($edit['description']??'') ?></textarea><button class="btn-primary w-full"><?= $edit?'Update Facility':'Add Facility' ?></button></form>
<div class="xl:col-span-2 bg-white rounded-xl border border-[#dcc0c2] shadow-sm overflow-hidden"><table class="w-full"><thead><tr><th class="table-th">Facility List</th><th class="table-th">Availability</th><th class="table-th">Capacity</th><th class="table-th">Action</th></tr></thead><tbody><?php while($f=$facilities->fetch_assoc()): ?><tr><td class="table-td font-bold"><?= h($f['facility_name']) ?><p class="text-xs text-slate-500"><?= h($f['location']) ?></p></td><td class="table-td"><span class="badge badge-<?= h($f['resource_status']) ?>"><?= h($f['resource_status']) ?></span></td><td class="table-td"><?= h($f['capacity']) ?></td><td class="table-td"><a class="text-red-900 font-bold" href="?edit=<?= h($f['facility_id']) ?>">Update</a> · <a class="text-red-700 font-bold" onclick="return confirm('Delete this facility?')" href="?delete=<?= h($f['facility_id']) ?>">Delete</a></td></tr><?php endwhile; ?></tbody></table></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>