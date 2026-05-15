<?php
if (isset($_GET['check_slots'])) {
    header('Content-Type: application/json');

    $resourceType = strtolower(trim($_GET['resource_type'] ?? ''));
    $resourceId = (int)($_GET['resource_id'] ?? 0);
    $date = trim($_GET['date'] ?? '');

    if (!in_array($resourceType, ['room', 'facility'], true) || $resourceId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
        exit;
    }

    try {
        require_once __DIR__ . '/../../config/db.php';
        $idColumn = $resourceType === 'room' ? 'room_id' : 'facility_id';

        $sql = "SELECT booking_start, booking_end, booking_status
                FROM bookings
                WHERE resource_type = ?
                  AND {$idColumn} = ?
                  AND booking_status IN ('pending', 'approved')
                  AND DATE(booking_start) = ?
                ORDER BY booking_start ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$resourceType, $resourceId, $date]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $slots = array_map(static function ($row) {
            return [
                'start' => date('H:i', strtotime((string)$row['booking_start'])),
                'end' => date('H:i', strtotime((string)$row['booking_end'])),
                'status' => ucfirst((string)$row['booking_status']),
            ];
        }, $rows);

        echo json_encode(['success' => true, 'slots' => $slots]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to check time slots']);
    }
    exit;
}

$resourceType = strtolower(trim($_GET['resource_type'] ?? 'facility'));
if (!in_array($resourceType, ['room', 'facility'], true)) {
    $resourceType = 'facility';
}
$resourceId = $resourceType === 'room' ? (int)($_GET['room_id'] ?? 0) : (int)($_GET['facility_id'] ?? 0);
$resourceName = trim($_GET['resource_name'] ?? '');
$currentRole = 'guest';
$resourceOptions = [];
$selectedResource = null;

try {
    require_once __DIR__ . '/../../config/db.php';

    $stmt = $pdo->prepare('SELECT role FROM users WHERE user_id = ? LIMIT 1');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid > 0) {
        $stmt->execute([$uid]);
        $currentRole = strtolower((string)($stmt->fetchColumn() ?: 'guest'));
    }

    if ($resourceType === 'room') {
        $rows = $pdo->query("SELECT room_id AS id, room_name AS name, room_type AS type, capacity, price_per_day FROM rooms ORDER BY room_name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        $rows = $pdo->query("SELECT facility_id AS id, facility_name AS name, facility_type AS type, capacity, price_per_day FROM facilities ORDER BY facility_name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    foreach ($rows as $row) {
        $resourceOptions[] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'type' => (string)$row['type'],
            'capacity' => (int)($row['capacity'] ?? 0),
            'price' => (float)($row['price_per_day'] ?? 0),
        ];
    }
} catch (Throwable $e) {
    $resourceOptions = [];
}

if ($resourceId > 0) {
    foreach ($resourceOptions as $option) {
        if ($option['id'] === $resourceId) {
            $selectedResource = $option;
            break;
        }
    }
}
if (!$selectedResource && $resourceName !== '') {
    foreach ($resourceOptions as $option) {
        if (strcasecmp($option['name'], $resourceName) === 0) {
            $selectedResource = $option;
            $resourceId = $option['id'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Book Facility - UNIRESERVE</title>
<style>
/* keep existing styles */
*{margin:0;padding:0;box-sizing:border-box}:root{--primary-color:#8b1538;--primary-hover:#a01d48;--text-dark:#333;--text-light:#666;--border-light:#e0e0e0;--white:#fff;--bg-light:#f5f5f5;--success:#388e3c;--danger:#d32f2f}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Roboto','Oxygen','Ubuntu',sans-serif;color:var(--text-dark);background:var(--bg-light)}
.breadcrumb{padding:16px 30px;background:var(--white);border-bottom:1px solid var(--border-light);font-size:13px}.breadcrumb a{color:var(--primary-color);text-decoration:none}
.container{max-width:1000px;margin:30px auto;padding:0 30px}.booking-form{background:var(--white);border-radius:8px;padding:40px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.form-group{margin-bottom:24px}.form-group label{display:block;font-size:13px;font-weight:600;margin-bottom:8px;text-transform:uppercase}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px}.input-wrapper{position:relative;display:flex;align-items:center}.input-icon{position:absolute;left:12px}
.input-wrapper input,.input-wrapper select,.input-wrapper textarea{width:100%;padding:12px 12px 12px 40px;border:1px solid var(--border-light);border-radius:6px}
.facility-list{border:1px solid var(--border-light);border-radius:6px;max-height:220px;overflow-y:auto}.facility-item{padding:12px;border-bottom:1px solid var(--border-light);display:flex;gap:12px;align-items:center}
.slot-status{padding:14px;border:1px solid var(--border-light);border-radius:6px;background:var(--bg-light);color:var(--text-light)}
.slot-status ul{margin-left:18px;margin-top:8px}.total-cost{background:var(--bg-light);padding:20px;border-radius:6px;display:flex;justify-content:space-between;margin-bottom:24px;font-weight:700}.total-cost-value{color:var(--primary-color);font-size:24px}
.button-group{display:flex;gap:12px;justify-content:flex-end}.btn-submit,.btn-cancel{padding:12px 32px;border-radius:6px;font-weight:600;cursor:pointer}.btn-submit{background:var(--primary-color);color:#fff;border:none}.btn-cancel{background:#fff;border:1px solid var(--border-light)}
</style></head>
<body>
<?php $currentPage = 'booking'; include __DIR__ . '/../../includes/header.php'; ?>
<div class="breadcrumb"><a href="../../homepage.php">Home</a><span> > </span><span>Book Facility</span></div>
<div class="container"><div class="booking-form"><h2>Book a Facility</h2>
<form id="booking-form" onsubmit="submitBooking(event)">
<div class="form-group"><label>Selected Facility</label><div class="facility-list" id="resource-list"></div></div>
<div class="form-row"><div class="form-group"><label>Date Picker</label><div class="input-wrapper"><span class="input-icon">📅</span><input type="date" id="booking-date" required onchange="checkTimeSlots()"></div></div>
<div class="form-group"><label>Start Time</label><div class="input-wrapper"><span class="input-icon">🕐</span><input type="time" id="booking-time" required onchange="checkTimeSlots()"></div></div></div>
<div class="form-group"><label>End Time</label><div class="input-wrapper"><span class="input-icon">🕑</span><input type="time" id="booking-end-time" required onchange="checkTimeSlots()"></div></div>
<div class="form-group"><label>Time Slot Check</label><div id="slot-check" class="slot-status">Select date/time to check slot availability.</div></div>
<div class="total-cost"><div><div>Total Cost:</div><div id="original-price" style="font-size:13px;font-weight:500;color:var(--text-light);"></div><div id="discount-note" style="font-size:12px;color:var(--success);"></div></div><div class="total-cost-value" id="total-cost">RM 0</div></div>
<div class="form-group"><label>Write Comments/Description</label><div class="input-wrapper"><textarea id="comments" placeholder="Add any special requirements or comments..." style="padding-left:12px;"></textarea></div></div>
<div class="button-group"><button type="button" class="btn-cancel" onclick="goHome()">Cancel</button><button type="submit" class="btn-submit">Submit Booking Request</button></div>
</form></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
const resourceType = <?= json_encode($resourceType) ?>;
const options = <?= json_encode($resourceOptions, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const initialSelectedId = <?= (int)($selectedResource['id'] ?? 0) ?>;
const role = <?= json_encode($currentRole) ?>;
const freeRoles = ['student','staff','admin','facility_manager'];
function formatCost(price){ return freeRoles.includes(role) ? 'Free' : `RM ${price}`; }
function renderResources(){
 const wrap=document.getElementById('resource-list');
 wrap.innerHTML=options.map(o=>`<label class="facility-item"><input type="radio" name="facility" value="${o.id}" ${o.id===initialSelectedId?'checked':''} onchange="updateCost();checkTimeSlots();"><div><div style="font-weight:600;">${o.name}</div><div style="font-size:12px;color:var(--text-light);">Type: ${o.type||resourceType} | Capacity: ${o.capacity||'-'} | Cost: ${formatCost(o.price)}</div></div></label>`).join('');
}
function selectedOption(){const s=document.querySelector('input[name="facility"]:checked'); return s?options.find(o=>o.id===Number(s.value)):null;}
function updateCost(){
 const s=selectedOption();
 const total=document.getElementById('total-cost');
 const original=document.getElementById('original-price');
 const discount=document.getElementById('discount-note');
 if(!s){ total.textContent='RM 0'; original.textContent=''; discount.textContent=''; return; }
 if(freeRoles.includes(role)){
   total.textContent='Free';
   original.innerHTML=`Original: <span style="text-decoration:line-through;">RM ${s.price}</span>`;
   discount.textContent='Free (100% discount applied)';
 } else {
   total.textContent=`RM ${s.price}`;
   original.textContent='';
   discount.textContent='';
 }
}
async function checkTimeSlots(){
 const s=selectedOption(); const date=document.getElementById('booking-date').value; const t=document.getElementById('booking-time').value; const end=document.getElementById('booking-end-time').value; const el=document.getElementById('slot-check');
 if(!s||!date||!t||!end){el.textContent='Select facility, date and time to check slot availability.';return;}
 if(end<=t){el.textContent='End time must be later than start time.';return;}
 const res=await fetch(`booking.php?check_slots=1&resource_type=${encodeURIComponent(resourceType)}&resource_id=${s.id}&date=${encodeURIComponent(date)}`);
 const data=await res.json();
 if(!data.success){el.textContent='Unable to check availability right now.';return;}
 if(!data.slots.length){el.innerHTML='<strong>Available</strong><div>No upcoming booked/pending slots for selected date.</div>';return;}
 el.innerHTML=`<strong>Upcoming Booked / Pending Slots</strong><ul>${data.slots.map(x=>`<li>${x.start} - ${x.end} (${x.status})</li>`).join('')}</ul>`;
}
async function submitBooking(e){
 e.preventDefault();
 const s=selectedOption();
 const bookingDate=document.getElementById('booking-date').value;
 const startTime=document.getElementById('booking-time').value;
 const endTime=document.getElementById('booking-end-time').value;
 const comments=document.getElementById('comments').value.trim();
 if(!s||!bookingDate||!startTime||!endTime){alert('Please complete all required fields.');return;}
 if(endTime<=startTime){alert('End time must be later than start time.');return;}
 const body=new URLSearchParams({resource_type:resourceType,resource_id:String(s.id),booking_date:bookingDate,start_time:startTime,end_time:endTime,comments,purpose:`Booking for ${s.name}`});
 const res=await fetch('../../api/booking/create_booking.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()});
 const data=await res.json();
 if(!data.success){alert(data.message||'Failed to submit booking request.');return;}
 alert('Booking request submitted successfully!');
 window.location.href='profile.php';
}
function goHome(){window.location.href='../../homepage.php';}
document.addEventListener('DOMContentLoaded',()=>{renderResources();updateCost();checkTimeSlots();});
</script></body></html>
