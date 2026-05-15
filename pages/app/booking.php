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

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
    Book Facility - UNIRESERVE
</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

:root{
    --primary-color:#8b1538;
    --primary-hover:#a01d48;
    --text-dark:#333;
    --text-light:#666;
    --border-light:#e0e0e0;
    --white:#fff;
    --bg-light:#f5f5f5;
    --success:#388e3c;
    --danger:#d32f2f;
}

body{
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Roboto','Oxygen','Ubuntu',sans-serif;
    color:var(--text-dark);
    background:var(--bg-light);
}

/* BREADCRUMB */

.breadcrumb{
    padding:16px 30px;
    background:var(--white);
    border-bottom:1px solid var(--border-light);
    font-size:13px;
}

.breadcrumb a{
    color:var(--primary-color);
    text-decoration:none;
}

/* CONTAINER */

.container{
    max-width:1000px;
    margin:30px auto;
    padding:0 30px;
}

/* FORM */

.booking-form{
    background:var(--white);
    border-radius:12px;
    padding:40px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.booking-form h2{
    margin-bottom:30px;
    color:var(--primary-color);
}

/* FORM GROUP */

.form-group{
    margin-bottom:24px;
}

.form-group label{
    display:block;
    font-size:13px;
    font-weight:600;
    margin-bottom:8px;
    text-transform:uppercase;
}

/* FORM ROW */

.form-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
    margin-bottom: 24px;
}

.form-group .input-wrapper input[type="date"] {
    max-width: 100%; 
}

/* INPUT */

.input-wrapper{
    position:relative;
    display:flex;
    align-items:center;
}

.input-icon{
    position:absolute;
    left:14px;
    font-size:15px;
}

.input-wrapper input,
.input-wrapper select,
.input-wrapper textarea{
    width:100%;
    padding:12px 14px 12px 42px;
    border:1px solid var(--border-light);
    border-radius:6px;
    font-size:14px;
    outline:none;
}

.input-wrapper textarea{
    min-height:120px;
    resize:none;
    padding-left:14px;
}

/* FACILITY LIST */

.facility-list{
    border:1px solid var(--border-light);
    border-radius:6px;
    max-height:220px;
    overflow-y:auto;
}

.facility-item{
    padding:14px;
    border-bottom:1px solid var(--border-light);
    display:flex;
    gap:12px;
    align-items:center;
}

/* SLOT */

.slot-status{
    padding:16px;
    border:1px solid var(--border-light);
    border-radius:6px;
    background:var(--bg-light);
    color:var(--text-light);
    line-height:1.6;
}

.slot-status ul{
    margin-left:18px;
    margin-top:8px;
}

/* COST */

.total-cost{
    background:var(--bg-light);
    padding:20px;
    border-radius:8px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:24px;
}

.total-cost-value{
    color:var(--primary-color);
    font-size:28px;
    font-weight:700;
}

/* BUTTONS */

.button-group{
    display:flex;
    justify-content:flex-end;
    gap:14px;
}

.btn-submit,
.btn-cancel{
    padding:12px 28px;
    border-radius:6px;
    font-weight:600;
    cursor:pointer;
    transition:.3s;
}

.btn-submit{
    background:var(--primary-color);
    color:white;
    border:none;
}

.btn-submit:hover{
    background:var(--primary-hover);
}

.btn-cancel{
    background:white;
    border:1px solid var(--border-light);
}

.btn-cancel:hover{
    background:#f3f3f3;
}

/* MOBILE */

@media(max-width:768px){

    .form-row{
        grid-template-columns:1fr;
    }

    .booking-form{
        padding:24px;
    }

    .button-group{
        flex-direction:column;
    }

}

</style>

</head>

<body>

<?php
$currentPage = 'booking';
include __DIR__ . '/../../includes/header.php';
?>

<!-- BREADCRUMB -->

<div class="breadcrumb">

    <a href="../../homepage.php">
        Home
    </a>

    <span> > </span>

    <span>
        Book Facility
    </span>

</div>

<!-- MAIN -->

<div class="container">

    <div class="booking-form">

        <h2>
            Book a Facility
        </h2>

<form id="booking-form" onsubmit="submitBooking(event)">
    <div class="form-group">
        <label>Selected Facility</label>
        <div class="facility-list" id="resource-list"></div>
    </div>

    <div class="form-group">
        <label>Date Picker</label>
        <div class="input-wrapper">
            <span class="input-icon">📅</span>
            <input type="date" id="booking-date" required onchange="checkTimeSlots()">
        </div>
    </div>

<div class="form-row">
    <div class="form-group">
        <label>Start Time (24h)</label>
        <div class="input-wrapper">
            <span class="input-icon">🕐</span>
            <select id="booking-time" class="time-select" required onchange="checkTimeSlots()"></select>
        </div>
    </div>
    <div class="form-group">
        <label>End Time (24h)</label>
        <div class="input-wrapper">
            <span class="input-icon">🕑</span>
            <select id="booking-end-time" class="time-select" required onchange="checkTimeSlots()"></select>
        </div>
    </div>
</div>

    <div class="form-group">
        <label>Time Slot Check</label>
        <div id="slot-check" class="slot-status">Select date/time to check slot availability.</div>
    </div>
    
    </form>

            <!-- TOTAL -->

            <div class="total-cost">

                <div>

                    <div>
                        Total Cost:
                    </div>

                    <div id="original-price"
                    style="font-size:13px;
                    font-weight:500;
                    color:var(--text-light);">
                    </div>

                    <div id="discount-note"
                    style="font-size:12px;
                    color:var(--success);">
                    </div>

                </div>

                <div class="total-cost-value"
                id="total-cost">
                    RM 0
                </div>
            </div>

            <!-- COMMENTS -->

            <div class="form-group">

                <label>
                    Comments / Description
                </label>
                <div class="input-wrapper">

                    <textarea id="comments"
                    placeholder="Add any special requirements or comments...">
                    </textarea>
                </div>
            </div>

            <!-- BUTTON -->

            <div class="button-group">
                <button type="button"
                class="btn-cancel"
                onclick="goHome()">
                    Cancel
                </button>
                <button type="submit" class="btn-submit">Submit Booking Request</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
const resourceType = <?= json_encode($resourceType) ?>;
const options = <?= json_encode($resourceOptions, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const initialSelectedId = <?= (int)($selectedResource['id'] ?? 0) ?>;
const role = <?= json_encode($currentRole) ?>;
const freeRoles = ['student','staff','admin','facility_manager'];

// Initialize Constraints on Page Load
document.addEventListener('DOMContentLoaded', () => {
    setupDateConstraints();
    setupTimeConstraints();
    renderResources();
    updateCost();
});

function setupDateConstraints() {
    const dateInput = document.getElementById('booking-date');
    const today = new Date();
    
    // Student/User range: Current date + 2 days (Total 3 days)
    const maxDate = new Date();
    maxDate.setDate(today.getDate() + 2);

    // Format to YYYY-MM-DD
    const formatDate = (d) => d.toISOString().split('T')[0];

    dateInput.min = formatDate(today);
    dateInput.max = formatDate(maxDate);

    // Block Weekends (Saturday/Sunday)
    dateInput.addEventListener('input', (e) => {
        const day = new Date(e.target.value).getUTCDay();
        if ([0, 6].includes(day)) {
            alert('Bookings are only available from Monday to Friday.');
            e.target.value = '';
        }
    });
}

function populateTimeDropdowns() {
    const startSelect = document.getElementById('booking-time');
    const endSelect = document.getElementById('booking-end-time');
    let options = '<option value="">--:--</option>';

    // Loop from 8 to 17 (5 PM)
    for (let h = 8; h <= 17; h++) {
        for (let m = 0; m < 60; m += 15) {
            // Stop exactly at 17:00
            if (h === 17 && m > 0) break; 
            
            let hh = h.toString().padStart(2, '0');
            let mm = m.toString().padStart(2, '0');
            let time = `${hh}:${mm}`;
            options += `<option value="${time}">${time}</option>`;
        }
    }
    startSelect.innerHTML = options;
    endSelect.innerHTML = options;
}

// Call this in your DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    populateTimeDropdowns();
    setupDateConstraints();
    // ... rest of your init code
});

function validateDuration() {
    const start = document.getElementById('booking-time').value;
    const end = document.getElementById('booking-end-time').value;
    const el = document.getElementById('slot-check');

    if (start && end) {
        const startTime = new Date(`1970-01-01T${start}:00`);
        const endTime = new Date(`1970-01-01T${end}:00`);
        const diffInMinutes = (endTime - startTime) / (1000 * 60);

        if (diffInMinutes < 60) {
            el.innerHTML = '<span style="color:var(--danger)">Minimum booking duration is 1 hour.</span>';
            return false;
        }
    }
    return true;
}

// Update checkTimeSlots to include these new rules
async function checkTimeSlots() {
    const s = selectedOption();
    const date = document.getElementById('booking-date').value;
    const t = document.getElementById('booking-time').value;
    const end = document.getElementById('booking-end-time').value;
    const el = document.getElementById('slot-check');

    if (!s || !date || !t || !end) {
        el.textContent = 'Select facility, date and time to check slot availability.';
        return;
    }

    if (end <= t) {
        el.innerHTML = '<span style="color:var(--danger)">End time must be later than start time.</span>';
        return;
    }

    if (!validateDuration()) return;

    const res = await fetch(`booking.php?check_slots=1&resource_type=${encodeURIComponent(resourceType)}&resource_id=${s.id}&date=${encodeURIComponent(date)}`);
    const data = await res.json();
    
    if (!data.success) { el.textContent = 'Unable to check availability.'; return; }
    
    if (!data.slots.length) {
        el.innerHTML = '<strong style="color:var(--success)">Available</strong><div>No existing bookings for this date.</div>';
        return;
    }
    el.innerHTML = `<strong>Booked Slots</strong><ul>${data.slots.map(x => `<li>${x.start} - ${x.end}</li>`).join('')}</ul>`;
}

// Rest of your functions (formatCost, renderResources, updateCost, submitBooking, etc.)
// ...
</script></body></html>
