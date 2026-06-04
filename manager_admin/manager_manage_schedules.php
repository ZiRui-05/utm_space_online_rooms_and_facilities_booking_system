<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['facility_manager']);
$self_file='manager_manage_schedules.php';
$nowMalaysiaTs = time();
$nowMalaysiaSql = date('Y-m-d H:i:s', $nowMalaysiaTs);
$todayMalaysia = date('Y-m-d', $nowMalaysiaTs);
$conn->query("CREATE TABLE IF NOT EXISTS schedules (schedule_id INT AUTO_INCREMENT PRIMARY KEY, resource_type ENUM('room','facility') NOT NULL, room_id INT NULL, facility_id INT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, status ENUM('available','blocked','maintenance') NOT NULL DEFAULT 'available', notes VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
$conn->query("CREATE TABLE IF NOT EXISTS weekly_schedule_rules (rule_id INT AUTO_INCREMENT PRIMARY KEY, resource_type ENUM('room','facility') NOT NULL, room_id INT NULL, facility_id INT NULL, weekday TINYINT NOT NULL, start_hour TINYINT NOT NULL, end_hour TINYINT NOT NULL, status ENUM('blocked','maintenance') NOT NULL DEFAULT 'blocked', notes VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_weekly_rule_room (resource_type, room_id, weekday, start_hour, end_hour), UNIQUE KEY uniq_weekly_rule_facility (resource_type, facility_id, weekday, start_hour, end_hour))");
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
        $weekday=(int)date('N', strtotime($d));
        if($status==='available'){
            if($resource_type==='room') $stmt=$conn->prepare("DELETE FROM weekly_schedule_rules WHERE resource_type='room' AND room_id=? AND weekday=? AND start_hour=? AND end_hour=?");
            else $stmt=$conn->prepare("DELETE FROM weekly_schedule_rules WHERE resource_type='facility' AND facility_id=? AND weekday=? AND start_hour=? AND end_hour=?");
            $end_hour=$hour+1;
            $stmt->bind_param('iiii',$resource_id,$weekday,$hour,$end_hour);
        } else {
            if($resource_type==='room') $stmt=$conn->prepare("INSERT INTO weekly_schedule_rules(resource_type,room_id,facility_id,weekday,start_hour,end_hour,status,notes) VALUES('room',?,NULL,?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status), notes=VALUES(notes), updated_at=CURRENT_TIMESTAMP");
            else $stmt=$conn->prepare("INSERT INTO weekly_schedule_rules(resource_type,room_id,facility_id,weekday,start_hour,end_hour,status,notes) VALUES('facility',NULL,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status), notes=VALUES(notes), updated_at=CURRENT_TIMESTAMP");
            $end_hour=$hour+1;
            $stmt->bind_param('iiiiss',$resource_id,$weekday,$hour,$end_hour,$status,$notes);
        }
        $stmt->execute(); $saved++;
    }
    $msg="Saved $saved timeslot(s) as recurring weekly rule".($skipped?"; skipped $skipped booked/invalid slot(s)":'');
        header('Location: '.$self_file.'?resource_type='.urlencode($resource_type).'&resource_id='.$resource_id.'&selected_date='.urlencode($selected_date).'&success='.urlencode($msg)); exit;
}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_activity'])){
    $activity_id=(int)($_POST['activity_id'] ?? 0);
    $activity_date=$selected_date;
    $start_hour=(int)($_POST['activity_start_hour'] ?? -1);
    $end_hour=(int)($_POST['activity_end_hour'] ?? -1);
    $status = in_array($_POST['activity_status'] ?? '', ['blocked','maintenance'], true) ? $_POST['activity_status'] : 'blocked';
    $notes = trim($_POST['activity_notes'] ?? '');
    if($resource_id<=0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$activity_date) || $start_hour<8 || $start_hour>17 || $end_hour<9 || $end_hour>18 || $start_hour>=$end_hour){
        header('Location: '.$self_file.'?resource_type='.urlencode($resource_type).'&resource_id='.$resource_id.'&selected_date='.urlencode($selected_date).'&error='.urlencode('Invalid activity settings').'#date-specific-activities'); exit;
    }
    $start=$activity_date.' '.str_pad((string)$start_hour,2,'0',STR_PAD_LEFT).':00:00';
    $end=$activity_date.' '.str_pad((string)$end_hour,2,'0',STR_PAD_LEFT).':00:00';
    if(strtotime($start) < $nowMalaysiaTs){ header('Location: '.$self_file.'?resource_type='.urlencode($resource_type).'&resource_id='.$resource_id.'&selected_date='.urlencode($selected_date).'&error='.urlencode('Cannot set past activity').'#date-specific-activities'); exit; }
    if($resource_type==='room') $chk=$conn->prepare("SELECT booking_id FROM bookings WHERE resource_type='room' AND room_id=? AND booking_status IN ('pending','approved','completed') AND booking_start < ? AND booking_end > ? LIMIT 1");
    else $chk=$conn->prepare("SELECT booking_id FROM bookings WHERE resource_type='facility' AND facility_id=? AND booking_status IN ('pending','approved','completed') AND booking_start < ? AND booking_end > ? LIMIT 1");
    $chk->bind_param('iss',$resource_id,$end,$start); $chk->execute();
    if($chk->get_result()->fetch_assoc()){ header('Location: '.$self_file.'?resource_type='.urlencode($resource_type).'&resource_id='.$resource_id.'&selected_date='.urlencode($selected_date).'&error='.urlencode('Activity overlaps an existing booking').'#date-specific-activities'); exit; }
    if($resource_type==='room'){
        if($activity_id>0) $stmt=$conn->prepare("UPDATE schedules SET start_time=?, end_time=?, status=?, notes=? WHERE schedule_id=? AND resource_type='room' AND room_id=?");
        else $stmt=$conn->prepare("INSERT INTO schedules(resource_type,room_id,facility_id,start_time,end_time,status,notes) VALUES('room',?,NULL,?,?,?,?)");
    } else {
        if($activity_id>0) $stmt=$conn->prepare("UPDATE schedules SET start_time=?, end_time=?, status=?, notes=? WHERE schedule_id=? AND resource_type='facility' AND facility_id=?");
        else $stmt=$conn->prepare("INSERT INTO schedules(resource_type,room_id,facility_id,start_time,end_time,status,notes) VALUES('facility',NULL,?,?,?,?,?)");
    }
    if($activity_id>0) $stmt->bind_param('ssssii',$start,$end,$status,$notes,$activity_id,$resource_id);
    else $stmt->bind_param('issss',$resource_id,$start,$end,$status,$notes);
    $stmt->execute();
    header('Location: '.$self_file.'?resource_type='.urlencode($resource_type).'&resource_id='.$resource_id.'&selected_date='.urlencode($selected_date).'&success='.urlencode($activity_id>0?'Activity updated':'Activity added').'#date-specific-activities'); exit;
}
if(isset($_GET['delete_activity'])){
    $id=(int)$_GET['delete_activity'];
    if($resource_type==='room') $stmt=$conn->prepare("DELETE FROM schedules WHERE schedule_id=? AND resource_type='room' AND room_id=?");
    else $stmt=$conn->prepare("DELETE FROM schedules WHERE schedule_id=? AND resource_type='facility' AND facility_id=?");
    $stmt->bind_param('ii',$id,$resource_id); $stmt->execute();
    header('Location: '.$self_file.'?resource_type='.urlencode($resource_type).'&resource_id='.$resource_id.'&selected_date='.urlencode($selected_date).'&success='.urlencode('Activity removed').'#date-specific-activities'); exit;
}
if(isset($_GET['delete_weekly'])){
    $weekday=(int)($_GET['weekday'] ?? 0);
    $start_hour=(int)($_GET['start_hour'] ?? -1);
    $end_hour=$start_hour+1;
    if($weekday>=1 && $weekday<=7 && $start_hour>=8 && $start_hour<=17){
        if($resource_type==='room') $stmt=$conn->prepare("DELETE FROM weekly_schedule_rules WHERE resource_type='room' AND room_id=? AND weekday=? AND start_hour=? AND end_hour=?");
        else $stmt=$conn->prepare("DELETE FROM weekly_schedule_rules WHERE resource_type='facility' AND facility_id=? AND weekday=? AND start_hour=? AND end_hour=?");
        $stmt->bind_param('iiii',$resource_id,$weekday,$start_hour,$end_hour); $stmt->execute();
        header('Location: '.$self_file.'?resource_type='.urlencode($resource_type).'&resource_id='.$resource_id.'&selected_date='.urlencode($selected_date).'&success='.urlencode('Weekly rule removed')); exit;
    }
}
$rooms=$conn->query('SELECT room_id, room_name FROM rooms ORDER BY room_name')->fetch_all(MYSQLI_ASSOC);
$facilities=$conn->query('SELECT facility_id, facility_name FROM facilities ORDER BY facility_name')->fetch_all(MYSQLI_ASSOC);
if($resource_id<=0){ $resource_id=(int)(($resource_type==='room' ? ($rooms[0]['room_id']??0) : ($facilities[0]['facility_id']??0))); }
$weekly_rules=[];
if($resource_id>0){
    if($resource_type==='room') $stmt=$conn->prepare("SELECT weekday,start_hour,status,notes FROM weekly_schedule_rules WHERE resource_type='room' AND room_id=?");
    else $stmt=$conn->prepare("SELECT weekday,start_hour,status,notes FROM weekly_schedule_rules WHERE resource_type='facility' AND facility_id=?");
    $stmt->bind_param('i',$resource_id); $stmt->execute(); $res=$stmt->get_result();
    while($r=$res->fetch_assoc()){ $weekly_rules[((int)$r['weekday']).'|'.((int)$r['start_hour'])]=$r; }
}
$upcoming=[]; $activities=[]; $editing_activity=null;
if($resource_id>0){
    if($resource_type==='room') $stmt=$conn->prepare("(SELECT 'Booking' kind, booking_start start_time, booking_end end_time, booking_status status, purpose notes FROM bookings WHERE resource_type='room' AND room_id=? AND booking_end>=?) UNION ALL (SELECT 'Schedule' kind, start_time, end_time, status, notes FROM schedules WHERE resource_type='room' AND room_id=? AND end_time>=?) ORDER BY start_time LIMIT 12");
    else $stmt=$conn->prepare("(SELECT 'Booking' kind, booking_start start_time, booking_end end_time, booking_status status, purpose notes FROM bookings WHERE resource_type='facility' AND facility_id=? AND booking_end>=?) UNION ALL (SELECT 'Schedule' kind, start_time, end_time, status, notes FROM schedules WHERE resource_type='facility' AND facility_id=? AND end_time>=?) ORDER BY start_time LIMIT 12");
    $stmt->bind_param('isis',$resource_id,$nowMalaysiaSql,$resource_id,$nowMalaysiaSql); $stmt->execute(); $upcoming=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if($resource_type==='room') $stmt=$conn->prepare("SELECT * FROM schedules WHERE resource_type='room' AND room_id=? AND end_time>=? ORDER BY start_time LIMIT 20");
    else $stmt=$conn->prepare("SELECT * FROM schedules WHERE resource_type='facility' AND facility_id=? AND end_time>=? ORDER BY start_time LIMIT 20");
    $stmt->bind_param('is',$resource_id,$nowMalaysiaSql); $stmt->execute(); $activities=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $edit_id=(int)($_GET['edit_activity'] ?? 0); foreach($activities as $a){ if((int)$a['schedule_id']===$edit_id){ $editing_activity=$a; break; } }
}
$page_title='Facility Manager Manage Schedules'; $active_page='schedules'; include __DIR__ . '/includes/header.php';
$weekdays=['Monday','Tuesday','Wednesday','Thursday','Friday']; $hours=range(8,17);
?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Facility Manager - Weekly Schedule Control</h1><p class="text-slate-500 mt-2">Schedule table shows booked slots plus admin/facility-manager blocked or maintenance slots.</p></div>
<form class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-6 grid md:grid-cols-4 gap-4">
<select class="input" name="resource_type" onchange="this.form.resource_id.value='';this.form.submit()"><option value="room" <?= $resource_type==='room'?'selected':'' ?>>Rooms</option><option value="facility" <?= $resource_type==='facility'?'selected':'' ?>>Facilities</option></select>
<select class="input" name="resource_id" onchange="this.form.submit()"><?php $items=$resource_type==='room'?$rooms:$facilities; foreach($items as $it): $id=$resource_type==='room'?$it['room_id']:$it['facility_id']; $name=$resource_type==='room'?$it['room_name']:$it['facility_name']; ?><option value="<?= h($id) ?>" <?= (int)$resource_id===(int)$id?'selected':'' ?>><?= h($name) ?></option><?php endforeach; ?></select>
<button class="btn-primary">Load Table</button>
</form>
<form method="post" id="slotForm" class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-8">
<input type="hidden" name="resource_type" value="<?= h($resource_type) ?>"><input type="hidden" name="resource_id" value="<?= h($resource_id) ?>"><input type="hidden" name="selected_date" value="<?= h($selected_date) ?>"><input type="hidden" name="save_slots" value="1"><div id="slotInputs"></div>
<div class="flex flex-wrap gap-3 items-end mb-4"><div><label class="text-xs font-bold uppercase text-slate-500">Selected slot status</label><select class="input" name="status"><option value="blocked">Blocked</option><option value="maintenance">Maintenance</option><option value="available">Clear to Available</option></select></div><div class="flex-1 min-w-[220px]"><label class="text-xs font-bold uppercase text-slate-500">Notes</label><input class="input" name="notes" placeholder="Reason, e.g. maintenance / exam setup"></div><button class="btn-primary" type="submit">Confirm Selected Slots</button></div>
<div class="overflow-x-auto"><table class="border-collapse w-full"><thead><tr><th class="table-th sticky left-0 bg-slate-50">Weekday</th><?php foreach($hours as $h): ?><th class="table-th text-center"><?= sprintf('%02d.00-%02d.00',$h,$h+1) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach($weekdays as $i=>$day): $date=$table_dates[$i]; ?><tr><th class="table-th sticky left-0 bg-slate-50"><?= h($day) ?></th><?php foreach($hours as $h): $key=$date.'|'.$h; $weekdayNo=(int)date('N',strtotime($date)); $cls='slot-free'; $text='Available'; $disabled=false; if(isset($weekly_rules[$weekdayNo.'|'.$h])){ $rule=$weekly_rules[$weekdayNo.'|'.$h]; $cls='slot-'.$rule['status']; $text=ucfirst($rule['status']).'<br><a class="underline text-[10px]" onclick="event.stopPropagation();return confirm(\'Remove this weekly rule?\')" href="?resource_type='.h($resource_type).'&resource_id='.h((string)$resource_id).'&selected_date='.h($selected_date).'&delete_weekly=1&weekday='.$weekdayNo.'&start_hour='.$h.'">remove</a>'; } ?><td class="slot-cell <?= $cls ?>" data-slot="<?= h($key) ?>" data-disabled="<?= $disabled?'1':'0' ?>"><?= $text ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
<p class="text-xs text-slate-500 mt-3">Legend: blue = booked from booking.php, red/grey = blocked or maintenance schedule. Click empty/blocked/maintenance cells, then confirm.</p>
</form>
<div id="date-specific-activities" class="scroll-mt-24 bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-8"><h2 class="text-xl font-black text-[#36000f] mb-4">Date-specific Upcoming Activities</h2>
<form action="<?= h($self_file) ?>#date-specific-activities" class="grid md:grid-cols-6 gap-3 items-end mb-4">
<input type="hidden" name="resource_type" value="<?= h($resource_type) ?>">
<input type="hidden" name="resource_id" value="<?= h($resource_id) ?>">
<div><label class="text-xs font-bold uppercase text-slate-500">Date</label><input class="input" type="date" name="selected_date" min="<?= h($todayMalaysia) ?>" value="<?= h($selected_date) ?>" onchange="this.form.submit()"></div>
</form>
<form method="post" class="grid md:grid-cols-6 gap-3 items-end mb-4"><input type="hidden" name="resource_type" value="<?= h($resource_type) ?>"><input type="hidden" name="resource_id" value="<?= h($resource_id) ?>"><input type="hidden" name="selected_date" value="<?= h($selected_date) ?>"><input type="hidden" name="save_activity" value="1"><input type="hidden" name="activity_id" value="<?= h((string)($editing_activity['schedule_id'] ?? '')) ?>"><div><label class="text-xs font-bold uppercase text-slate-500">Start</label><select class="input" name="activity_start_hour"><?php for($h=8;$h<=17;$h++): ?><option value="<?= $h ?>" <?= isset($editing_activity) && (int)date('G',strtotime($editing_activity['start_time']))===$h?'selected':'' ?>><?= sprintf('%02d:00',$h) ?></option><?php endfor; ?></select></div><div><label class="text-xs font-bold uppercase text-slate-500">End</label><select class="input" name="activity_end_hour"><?php for($h=9;$h<=18;$h++): ?><option value="<?= $h ?>" <?= isset($editing_activity) && (int)date('G',strtotime($editing_activity['end_time']))===$h?'selected':'' ?>><?= sprintf('%02d:00',$h) ?></option><?php endfor; ?></select></div><div><label class="text-xs font-bold uppercase text-slate-500">Status</label><select class="input" name="activity_status"><option value="blocked" <?= isset($editing_activity) && $editing_activity['status']==='blocked'?'selected':'' ?>>Blocked</option><option value="maintenance" <?= isset($editing_activity) && $editing_activity['status']==='maintenance'?'selected':'' ?>>Maintenance</option></select></div><div class="md:col-span-2"><label class="text-xs font-bold uppercase text-slate-500">Notes</label><input class="input" name="activity_notes" value="<?= h($editing_activity['notes'] ?? '') ?>" placeholder="Special events / temporary usage" required></div><button class="btn-primary" type="submit"><?= isset($editing_activity)?'Update Activity':'Add Activity' ?></button></form>
<div class="overflow-x-auto"><table class="w-full"><thead><tr><th class="table-th">Date</th><th class="table-th">Start</th><th class="table-th">End</th><th class="table-th">Status</th><th class="table-th">Notes</th><th class="table-th">Action</th></tr></thead><tbody><?php if(empty($activities)): ?><tr><td colspan="6" class="table-td text-center text-slate-500">No upcoming activities.</td></tr><?php endif; ?><?php foreach($activities as $a): ?><tr><td class="table-td"><?= h(date('Y-m-d',strtotime($a['start_time']))) ?></td><td class="table-td"><?= h(date('H:i',strtotime($a['start_time']))) ?></td><td class="table-td"><?= h(date('H:i',strtotime($a['end_time']))) ?></td><td class="table-td"><span class="badge badge-<?= h($a['status']) ?>"><?= h($a['status']) ?></span></td><td class="table-td"><?= h($a['notes']) ?></td><td class="table-td"><a class="underline mr-3" href="?resource_type=<?= h($resource_type) ?>&resource_id=<?= h((string)$resource_id) ?>&selected_date=<?= h($selected_date) ?>&edit_activity=<?= h((string)$a['schedule_id']) ?>#date-specific-activities">edit</a><a class="underline text-red-700" onclick="return confirm('Delete this activity?')" href="?resource_type=<?= h($resource_type) ?>&resource_id=<?= h((string)$resource_id) ?>&selected_date=<?= h($selected_date) ?>&delete_activity=<?= h((string)$a['schedule_id']) ?>#date-specific-activities">delete</a></td></tr><?php endforeach; ?></tbody></table></div></div>

<script>const selected=new Set();const box=document.getElementById('slotInputs');document.querySelectorAll('.slot-cell').forEach(td=>{td.addEventListener('click',()=>{if(td.dataset.disabled==='1')return;const v=td.dataset.slot;if(selected.has(v)){selected.delete(v);td.classList.remove('slot-selected')}else{selected.add(v);td.classList.add('slot-selected')}box.innerHTML=[...selected].map(s=>`<input type="hidden" name="selected_slots[]" value="${s.replace(/"/g,'&quot;')}">`).join('')})});document.getElementById('slotForm').addEventListener('submit',e=>{if(selected.size===0){e.preventDefault();alert('Please select at least one timeslot.')}});</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
