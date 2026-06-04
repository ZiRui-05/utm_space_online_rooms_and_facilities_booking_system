<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['admin']);
header('Location: admin_dashboard.php?error=' . urlencode('Schedule management is only available for facility managers.'));
exit;
$self_file='admin_manage_schedules.php';
$nowMalaysiaTs = time();
$nowMalaysiaSql = date('Y-m-d H:i:s', $nowMalaysiaTs);
$todayMalaysia = date('Y-m-d', $nowMalaysiaTs);
$conn->query("CREATE TABLE IF NOT EXISTS schedules (schedule_id INT AUTO_INCREMENT PRIMARY KEY, resource_type ENUM('room','facility') NOT NULL, room_id INT NULL, facility_id INT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, status ENUM('available','blocked','maintenance') NOT NULL DEFAULT 'available', notes VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
function monday_of_week($date){ $ts=strtotime($date ?: date('Y-m-d')); if($ts===false)$ts=time(); return date('Y-m-d', strtotime('monday this week', $ts)); }
if(isset($_GET['delete'])){ $id=(int)$_GET['delete']; $stmt=$conn->prepare('SELECT start_time FROM schedules WHERE schedule_id=? LIMIT 1'); $stmt->bind_param('i',$id); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); if($row && strtotime($row['start_time']) < $nowMalaysiaTs){ header('Location: '.$self_file.'?error='.urlencode('Cannot remove a past schedule slot')); exit; } $stmt=$conn->prepare('DELETE FROM schedules WHERE schedule_id=?'); $stmt->bind_param('i',$id); $stmt->execute(); header('Location: '.$self_file.'?success='.urlencode('Schedule removed')); exit; }
$resource_type = $_GET['resource_type'] ?? $_POST['resource_type'] ?? 'room'; if(!in_array($resource_type,['room','facility'],true)) $resource_type='room';
$resource_id = (int)($_GET['resource_id'] ?? $_POST['resource_id'] ?? 0);
$selected_date = $_GET['selected_date'] ?? $_POST['selected_date'] ?? ($_GET['week_start'] ?? $_POST['week_start'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date) || strtotime($selected_date) === false) {
    $selected_date = date('Y-m-d');
}
$week_start = monday_of_week($selected_date);
$table_dates=[];
for($i=0;$i<5;$i++){
    $candidate=date('Y-m-d', strtotime($week_start." +$i days"));
    if(strtotime($candidate.' 23:59:59') < strtotime($todayMalaysia.' 00:00:00')){
        $candidate=date('Y-m-d', strtotime($candidate.' +7 days'));
    }
    $table_dates[$i]=$candidate;
}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_slots'])){
    $status = in_array($_POST['status'] ?? '', ['blocked','maintenance'], true) ? $_POST['status'] : 'blocked';
    $notes = trim($_POST['notes'] ?? '');
    $slots = $_POST['selected_slots'] ?? [];
    if($resource_id<=0 || empty($slots)){ header('Location: '.$self_file.'?error='.urlencode('Please choose a resource and at least one timeslot')); exit; }
    $saved=0; $skipped=0;
    foreach($slots as $slot){
        [$d,$h] = array_pad(explode('|',$slot),2,''); $hour=(int)$h;
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$d) || $hour<8 || $hour>17){ $skipped++; continue; }
        $start=$d.' '.str_pad((string)$hour,2,'0',STR_PAD_LEFT).':00:00'; $end=$d.' '.str_pad((string)($hour+1),2,'0',STR_PAD_LEFT).':00:00';
        if(strtotime($start) < $nowMalaysiaTs){ $skipped++; continue; }
        if($resource_type==='room'){
            $chk=$conn->prepare("SELECT booking_id FROM bookings WHERE resource_type='room' AND room_id=? AND booking_status IN ('pending','approved','completed') AND booking_start < ? AND booking_end > ? LIMIT 1");
        } else {
            $chk=$conn->prepare("SELECT booking_id FROM bookings WHERE resource_type='facility' AND facility_id=? AND booking_status IN ('pending','approved','completed') AND booking_start < ? AND booking_end > ? LIMIT 1");
        }
        $chk->bind_param('iss',$resource_id,$end,$start); $chk->execute();
        if($chk->get_result()->fetch_assoc()){ $skipped++; continue; }
        if($resource_type==='room'){
            $del=$conn->prepare("DELETE FROM schedules WHERE resource_type='room' AND room_id=? AND start_time=? AND end_time=?");
            $del->bind_param('iss',$resource_id,$start,$end); $del->execute();
            $stmt=$conn->prepare("INSERT INTO schedules(resource_type,room_id,facility_id,start_time,end_time,status,notes) VALUES('room',?,NULL,?,?,?,?)");
        } else {
            $del=$conn->prepare("DELETE FROM schedules WHERE resource_type='facility' AND facility_id=? AND start_time=? AND end_time=?");
            $del->bind_param('iss',$resource_id,$start,$end); $del->execute();
            $stmt=$conn->prepare("INSERT INTO schedules(resource_type,room_id,facility_id,start_time,end_time,status,notes) VALUES('facility',NULL,?,?,?,?,?)");
        }
        $stmt->bind_param('issss',$resource_id,$start,$end,$status,$notes); $stmt->execute(); $saved++;
    }
    $msg="Saved $saved timeslot(s)".($skipped?"; skipped $skipped booked/invalid slot(s)":'');
    header('Location: '.$self_file.'?resource_type='.urlencode($resource_type).'&resource_id='.$resource_id.'&selected_date='.urlencode($selected_date).'&success='.urlencode($msg)); exit;
}
$rooms=$conn->query('SELECT room_id, room_name FROM rooms ORDER BY room_name')->fetch_all(MYSQLI_ASSOC);
$facilities=$conn->query('SELECT facility_id, facility_name FROM facilities ORDER BY facility_name')->fetch_all(MYSQLI_ASSOC);
if($resource_id<=0){ $resource_id=(int)(($resource_type==='room' ? ($rooms[0]['room_id']??0) : ($facilities[0]['facility_id']??0))); }
$query_start=min($table_dates);
$query_end=date('Y-m-d', strtotime(max($table_dates).' +1 day'));
$booked=[]; $manual=[];
if($resource_id>0){
    if($resource_type==='room'){
        $stmt=$conn->prepare("SELECT booking_id, booking_start, booking_end, booking_status FROM bookings WHERE resource_type='room' AND room_id=? AND DATE(booking_start) >= ? AND DATE(booking_start) < ? AND booking_status IN ('pending','approved','completed')");
    } else {
        $stmt=$conn->prepare("SELECT booking_id, booking_start, booking_end, booking_status FROM bookings WHERE resource_type='facility' AND facility_id=? AND DATE(booking_start) >= ? AND DATE(booking_start) < ? AND booking_status IN ('pending','approved','completed')");
    }
    $stmt->bind_param('iss',$resource_id,$query_start,$query_end); $stmt->execute(); $res=$stmt->get_result();
    while($b=$res->fetch_assoc()){ for($t=strtotime($b['booking_start']); $t<strtotime($b['booking_end']); $t+=3600){ $booked[date('Y-m-d|G',$t)]='#'.$b['booking_id'].' '.$b['booking_status']; } }
    if($resource_type==='room') $stmt=$conn->prepare("SELECT * FROM schedules WHERE resource_type='room' AND room_id=? AND DATE(start_time) >= ? AND DATE(start_time) < ? ORDER BY start_time");
    else $stmt=$conn->prepare("SELECT * FROM schedules WHERE resource_type='facility' AND facility_id=? AND DATE(start_time) >= ? AND DATE(start_time) < ? ORDER BY start_time");
    $stmt->bind_param('iss',$resource_id,$query_start,$query_end); $stmt->execute(); $res=$stmt->get_result();
    while($s=$res->fetch_assoc()){ $manual[date('Y-m-d|G',strtotime($s['start_time']))]=$s; }
}
$upcoming=[];
if($resource_id>0){
    if($resource_type==='room') $stmt=$conn->prepare("(SELECT 'Booking' kind, booking_start start_time, booking_end end_time, booking_status status, purpose notes FROM bookings WHERE resource_type='room' AND room_id=? AND booking_end>=?) UNION ALL (SELECT 'Schedule' kind, start_time, end_time, status, notes FROM schedules WHERE resource_type='room' AND room_id=? AND end_time>=?) ORDER BY start_time LIMIT 12");
    else $stmt=$conn->prepare("(SELECT 'Booking' kind, booking_start start_time, booking_end end_time, booking_status status, purpose notes FROM bookings WHERE resource_type='facility' AND facility_id=? AND booking_end>=?) UNION ALL (SELECT 'Schedule' kind, start_time, end_time, status, notes FROM schedules WHERE resource_type='facility' AND facility_id=? AND end_time>=?) ORDER BY start_time LIMIT 12");
    $stmt->bind_param('isis',$resource_id,$nowMalaysiaSql,$resource_id,$nowMalaysiaSql); $stmt->execute(); $upcoming=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$page_title='Admin Manage Schedules'; $active_page='schedules'; include __DIR__ . '/includes/header.php';
$weekdays=['Monday','Tuesday','Wednesday','Thursday','Friday']; $hours=range(8,17);
?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Admin - Weekly Schedule Control</h1><p class="text-slate-500 mt-2">Schedule table shows booked slots plus admin/facility-manager blocked or maintenance slots.</p></div>
<form class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-6 grid md:grid-cols-4 gap-4">
<select class="input" name="resource_type" onchange="this.form.resource_id.value='';this.form.submit()"><option value="room" <?= $resource_type==='room'?'selected':'' ?>>Rooms</option><option value="facility" <?= $resource_type==='facility'?'selected':'' ?>>Facilities</option></select>
<select class="input" name="resource_id" onchange="this.form.submit()"><?php $items=$resource_type==='room'?$rooms:$facilities; foreach($items as $it): $id=$resource_type==='room'?$it['room_id']:$it['facility_id']; $name=$resource_type==='room'?$it['room_name']:$it['facility_name']; ?><option value="<?= h($id) ?>" <?= (int)$resource_id===(int)$id?'selected':'' ?>><?= h($name) ?></option><?php endforeach; ?></select>
<input class="input" type="date" name="selected_date" min="<?= h($todayMalaysia) ?>" value="<?= h($selected_date) ?>" onchange="this.form.submit()">
<button class="btn-primary">Load Table</button>
<p class="md:col-span-4 text-xs text-slate-500">Selected date: <?= h($selected_date) ?> · Table dates: <?= h(implode(', ', $table_dates)) ?> · Timezone: Malaysia (Asia/Kuala_Lumpur)</p>
</form>
<form method="post" id="slotForm" class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-8">
<input type="hidden" name="resource_type" value="<?= h($resource_type) ?>"><input type="hidden" name="resource_id" value="<?= h($resource_id) ?>"><input type="hidden" name="selected_date" value="<?= h($selected_date) ?>"><input type="hidden" name="save_slots" value="1"><div id="slotInputs"></div>
<div class="flex flex-wrap gap-3 items-end mb-4"><div><label class="text-xs font-bold uppercase text-slate-500">Selected slot status</label><select class="input" name="status"><option value="blocked">Blocked</option><option value="maintenance">Maintenance</option></select></div><div class="flex-1 min-w-[220px]"><label class="text-xs font-bold uppercase text-slate-500">Notes</label><input class="input" name="notes" placeholder="Reason, e.g. maintenance / exam setup"></div><button class="btn-primary" type="submit">Confirm Selected Slots</button></div>
<div class="overflow-x-auto"><table class="border-collapse w-full"><thead><tr><th class="table-th sticky left-0 bg-slate-50">Weekday</th><?php foreach($hours as $h): ?><th class="table-th text-center"><?= sprintf('%02d.00-%02d.00',$h,$h+1) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach($weekdays as $i=>$day): $date=$table_dates[$i]; ?><tr><th class="table-th sticky left-0 bg-slate-50"><?= h($day) ?><p class="text-[10px] normal-case tracking-normal"><?= h($date) ?></p></th><?php foreach($hours as $h): $key=$date.'|'.$h; $slotStart=$date.' '.str_pad((string)$h,2,'0',STR_PAD_LEFT).':00:00'; $isPast=strtotime($slotStart)<$nowMalaysiaTs; $cls='slot-free'; $text='Available'; $disabled=false; if($isPast){ $cls='slot-maintenance'; $text='Past'; $disabled=true; } elseif(isset($booked[$key])){ $cls='slot-booked'; $text='Booked<br><span class="text-[10px]">'.h($booked[$key]).'</span>'; $disabled=true; } elseif(isset($manual[$key])){ $cls='slot-'.$manual[$key]['status']; $text=ucfirst($manual[$key]['status']).'<br><a class="underline text-[10px]" onclick="event.stopPropagation();return confirm(\'Remove this schedule?\')" href="?delete='.h($manual[$key]['schedule_id']).'">remove</a>'; } ?><td class="slot-cell <?= $cls ?>" data-slot="<?= h($key) ?>" data-disabled="<?= $disabled?'1':'0' ?>"><?= $text ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
<p class="text-xs text-slate-500 mt-3">Legend: blue = booked from booking.php, red/grey = blocked or maintenance schedule. Click empty/blocked/maintenance cells, then confirm.</p>
</form>
<div class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm"><h2 class="text-xl font-black text-[#36000f] mb-4">Upcoming Activity</h2><div class="overflow-x-auto"><table class="w-full"><thead><tr><th class="table-th">Type</th><th class="table-th">Start</th><th class="table-th">End</th><th class="table-th">Status</th><th class="table-th">Notes</th></tr></thead><tbody><?php if(empty($upcoming)): ?><tr><td colspan="5" class="table-td text-center text-slate-500">No upcoming booking or schedule.</td></tr><?php endif; ?><?php foreach($upcoming as $a): ?><tr><td class="table-td font-bold"><?= h($a['kind']) ?></td><td class="table-td"><?= h($a['start_time']) ?></td><td class="table-td"><?= h($a['end_time']) ?></td><td class="table-td"><span class="badge badge-<?= h($a['status']) ?>"><?= h($a['status']) ?></span></td><td class="table-td"><?= h($a['notes']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<script>const selected=new Set();const box=document.getElementById('slotInputs');document.querySelectorAll('.slot-cell').forEach(td=>{td.addEventListener('click',()=>{if(td.dataset.disabled==='1')return;const v=td.dataset.slot;if(selected.has(v)){selected.delete(v);td.classList.remove('slot-selected')}else{selected.add(v);td.classList.add('slot-selected')}box.innerHTML=[...selected].map(s=>`<input type="hidden" name="selected_slots[]" value="${s.replace(/"/g,'&quot;')}">`).join('')})});document.getElementById('slotForm').addEventListener('submit',e=>{if(selected.size===0){e.preventDefault();alert('Please select at least one timeslot.')}});</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
