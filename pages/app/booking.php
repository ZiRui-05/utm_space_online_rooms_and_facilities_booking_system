<?php
global $pdo;
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
                'status' => strtolower((string)$row['booking_status']),
                'kind' => 'booking',
            ];
        }, $rows);

        $scheduleSql = "SELECT start_time, end_time, status
                        FROM schedules
                        WHERE resource_type = ?
                          AND {$idColumn} = ?
                          AND status IN ('blocked', 'maintenance')
                          AND DATE(start_time) = ?
                        ORDER BY start_time ASC";
        $scheduleStmt = $pdo->prepare($scheduleSql);
        $scheduleStmt->execute([$resourceType, $resourceId, $date]);
        $scheduleRows = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($scheduleRows as $row) {
            $slots[] = [
                'start' => date('H:i', strtotime((string)$row['start_time'])),
                'end' => date('H:i', strtotime((string)$row['end_time'])),
                'status' => strtolower((string)$row['status']),
                'kind' => 'schedule',
            ];
        }

        $weekday = (int)(new DateTimeImmutable($date))->format('N');
        $ruleSql = "SELECT start_hour, end_hour, status
                    FROM weekly_schedule_rules
                    WHERE resource_type = ?
                      AND {$idColumn} = ?
                      AND weekday = ?
                      AND status IN ('blocked', 'maintenance')";
        $ruleStmt = $pdo->prepare($ruleSql);
        $ruleStmt->execute([$resourceType, $resourceId, $weekday]);
        $ruleRows = $ruleStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($ruleRows as $row) {
            $slots[] = [
                'start' => sprintf('%02d:00', (int)$row['start_hour']),
                'end' => sprintf('%02d:00', (int)$row['end_hour']),
                'status' => strtolower((string)$row['status']),
                'kind' => 'weekly_rule',
            ];
        }

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
$resourceId = (int)($_GET['resource_id'] ?? 0);
if ($resourceId <= 0) {
    $resourceId = $resourceType === 'room' ? (int)($_GET['room_id'] ?? 0) : (int)($_GET['facility_id'] ?? 0);
}
$resourceName = trim($_GET['resource_name'] ?? '');
$bookingLabel = $resourceType === 'room' ? 'Book Room' : 'Book Facility';
$selectedDateParam = trim($_GET['booking_date'] ?? '');
$selectedStartParam = trim($_GET['start_time'] ?? '');
$selectedEndParam = trim($_GET['end_time'] ?? '');
$currentRole = 'guest';
$currentDepartment = '';
$spaceUtmDepartment = 'SPACE UTM (School of Professional and Continuing Education)';
$resourceOptions = [];
$selectedResource = null;

try {
    require_once __DIR__ . '/../../config/db.php';

    $stmt = $pdo->prepare('SELECT role, department FROM users WHERE user_id = ? LIMIT 1');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $uid = (int)($_SESSION['user']['user_id'] ?? ($_SESSION['user_id'] ?? 0));
    if ($uid > 0) {
        $stmt->execute([$uid]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $currentRole = strtolower((string)($currentUser['role'] ?? 'guest'));
        $currentDepartment = trim((string)($currentUser['department'] ?? ''));
    }

    if ($resourceType === 'room') {
        $rows = $pdo->query("SELECT room_id AS id, room_name AS name, room_type AS type, capacity, price_per_day, resource_status FROM rooms ORDER BY room_name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        $rows = $pdo->query("SELECT facility_id AS id, facility_name AS name, facility_type AS type, capacity, price_per_day, resource_status FROM facilities ORDER BY facility_name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    foreach ($rows as $row) {
        $resourceOptions[] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'type' => (string)$row['type'],
            'capacity' => (int)($row['capacity'] ?? 0),
            'price' => (float)($row['price_per_day'] ?? 0),
            'status' => strtolower((string)($row['resource_status'] ?? 'available')),
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
    <?= htmlspecialchars($bookingLabel, ENT_QUOTES, 'UTF-8') ?> - UNIRESERVE
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

.facility-item.is-unavailable{
    background:#f7f7f7;
    color:var(--text-light);
}

.resource-status-badge{
    display:inline-block;
    margin-top:6px;
    padding:3px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
}

.resource-status-badge.status-available{
    background:#e8f5e9;
    color:var(--success);
}

.resource-status-badge.status-unavailable,
.resource-status-badge.status-maintenance{
    background:#ffebee;
    color:var(--danger);
}

/* TIME SLOTS */

.time-slot-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(165px,1fr));
    gap:10px;
}

.time-slot-card{
    border:1px solid var(--border-light);
    border-radius:8px;
    background:#fff;
    padding:12px;
    cursor:pointer;
    text-align:left;
    transition:all .2s ease;
}

.time-slot-card:hover{
    border-color:var(--primary-color);
    transform:translateY(-1px);
}

.time-slot-card.is-selected{
    border-color:var(--primary-color);
    box-shadow:0 0 0 2px rgba(139,21,56,.15);
    background:#fff8fb;
}

.time-slot-card.is-disabled{
    opacity:.72;
    cursor:not-allowed;
}

.time-slot-card.status-free{
    background:#f1f8f3;
}

.time-slot-card.status-pending{
    background:#fff8e1;
    border-color:#efd58f;
}

.time-slot-card.status-booked,
.time-slot-card.status-blocked,
.time-slot-card.status-maintenance{
    background:#ffebee;
    border-color:#f2b8bd;
}

.time-slot-card.status-maintenance{
    background:#eef0f3;
}

.time-slot-time{
    font-size:14px;
    font-weight:700;
    margin-bottom:4px;
}

.time-slot-meta{
    color:var(--text-light);
    font-size:12px;
    font-weight:700;
}

.slot-helper{
    color:var(--text-light);
    font-size:13px;
    margin-top:10px;
}

.selection-result{
    margin-top:14px;
    padding:14px;
    border-radius:8px;
    border:1px solid var(--border-light);
    background:var(--bg-light);
    color:var(--text-light);
    line-height:1.5;
}

.selection-result.success{
    background:#e8f5e9;
    border-color:#b7dfbb;
    color:var(--success);
}

.selection-result.error{
    background:#ffebee;
    border-color:#f2b8bd;
    color:var(--danger);
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


.login-required-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 16px;
}

.login-required-modal.is-open {
    display: flex;
}

.login-required-content {
    width: min(420px, 100%);
    background: var(--white);
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 10px 30px rgba(0,0,0,.2);
}

.login-required-content h3 {
    margin-bottom: 10px;
    color: var(--primary-color);
}

.login-required-content p {
    margin-bottom: 18px;
    color: var(--text-light);
    line-height: 1.45;
}

.login-required-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

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
        <?= htmlspecialchars($bookingLabel, ENT_QUOTES, 'UTF-8') ?>
    </span>

</div>

<!-- MAIN -->

<div class="container">

    <div class="booking-form">

        <h2>
            <?= htmlspecialchars($bookingLabel, ENT_QUOTES, 'UTF-8') ?>
        </h2>

<form id="booking-form" onsubmit="submitBooking(event)">
    <div class="form-group">
        <label>Selected <?= $resourceType === 'room' ? 'Room' : 'Facility' ?></label>
        <div class="facility-list" id="resource-list"></div>
    </div>

    <div class="form-group">
        <label>Date Picker</label>
        <div class="input-wrapper">
            <span class="input-icon">📅</span>
            <input type="date" id="booking-date" required>
        </div>
    </div>

    <div class="form-group">
        <label>Time Slots</label>
        <div id="time-slot-grid" class="time-slot-grid"></div>
        <div class="slot-helper" id="slot-helper">Choose continuous available slots only.</div>
        <div id="selected-time-result" class="selection-result">No time selected yet.</div>
    </div>


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

<div id="login-required-modal" class="login-required-modal" aria-hidden="true">
    <div class="login-required-content" role="dialog" aria-modal="true" aria-labelledby="login-required-title">
        <h3 id="login-required-title">Login Required</h3>
        <p>You need to login before making a booking request.</p>
        <div class="login-required-actions">
            <button type="button" class="btn-cancel" onclick="closeLoginRequiredModal()">Cancel</button>
            <button type="button" class="btn-submit" onclick="goToLoginPage()">Go to Login Page</button>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
const resourceType = <?= json_encode($resourceType) ?>;
const resourceLabel = resourceType === 'room' ? 'room' : 'facility';
const options = <?= json_encode($resourceOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const initialSelectedId = <?= (int)($selectedResource['id'] ?? 0) ?>;
const role = <?= json_encode($currentRole) ?>;
const department = <?= json_encode($currentDepartment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const spaceUtmDepartment = <?= json_encode($spaceUtmDepartment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const normalizedRole = String(role || '').trim().toLowerCase();
const normalizedDepartment = String(department || '').trim();
const isNonGuestUser = normalizedRole !== '' && normalizedRole !== 'guest';
const isSpaceUtmUser = normalizedDepartment === spaceUtmDepartment;
const initialBookingDate = <?= json_encode($selectedDateParam) ?>;
const initialStartTime = <?= json_encode($selectedStartParam) ?>;
const initialEndTime = <?= json_encode($selectedEndParam) ?>;
let slotStatusCache = [];

document.addEventListener('DOMContentLoaded', () => {
    setupDateConstraints();
    renderResources();
    applyIncomingBookingSelection();
    updateCost();
    renderTimeSlotCards();
});

function formatLocalDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function minutesFromTime(time) {
    const [hours, minutes] = String(time || '').split(':').map(Number);
    if (!Number.isFinite(hours) || !Number.isFinite(minutes)) return NaN;
    return hours * 60 + minutes;
}

function formatTime(totalMinutes) {
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

function setupDateConstraints() {
    const dateInput = document.getElementById('booking-date');
    if (!dateInput) return;

    const today = new Date();
    const maxDate = new Date(today);
    maxDate.setDate(today.getDate() + 2);

    dateInput.min = formatLocalDate(today);
    dateInput.max = formatLocalDate(maxDate);

    dateInput.addEventListener('change', () => {
        if (!dateInput.value) return;
        if (dateInput.value < dateInput.min || dateInput.value > dateInput.max) {
            alert('Booking date must be within 3 days including today.');
            dateInput.value = '';
        }
        renderTimeSlotCards();
    });
}

function applyIncomingBookingSelection() {
    const dateInput = document.getElementById('booking-date');

    if (dateInput && initialBookingDate && initialBookingDate >= dateInput.min && initialBookingDate <= dateInput.max) {
        dateInput.value = initialBookingDate;
    }
}

function renderResources() {
    const container = document.getElementById('resource-list');
    if (!container) return;

    if (!options.length) {
        container.innerHTML = `<div class="facility-item">No ${resourceLabel}s are available.</div>`;
        return;
    }

    container.innerHTML = options.map(option => {
        const checked = Number(option.id) === Number(initialSelectedId) ? 'checked' : '';
        const status = String(option.status || 'available').toLowerCase();
        const isAvailable = status === 'available';
        const disabled = isAvailable ? '' : 'disabled';
        const itemClass = isAvailable ? '' : 'is-unavailable';
        return `
            <label class="facility-item ${itemClass}">
                <input type="radio" name="resource-option" value="${Number(option.id)}" ${checked} ${disabled}>
                <div>
                    <strong>${escapeHtml(option.name)}</strong>
                    <div style="font-size:13px;color:var(--text-light);">
                        ${escapeHtml(option.type || resourceLabel)} · Capacity ${Number(option.capacity || 0)}
                    </div>
                    <span class="resource-status-badge status-${escapeHtml(status)}">${escapeHtml(status)}</span>
                </div>
            </label>
        `;
    }).join('');

    if (!document.querySelector('input[name="resource-option"]:checked')) {
        const first = document.querySelector('input[name="resource-option"]:not(:disabled)');
        if (first) first.checked = true;
    }

    container.querySelectorAll('input[name="resource-option"]').forEach(input => {
        input.addEventListener('change', () => {
            updateCost();
            renderTimeSlotCards();
        });
    });
}

function selectedOption() {
    const checked = document.querySelector('input[name="resource-option"]:checked');
    if (!checked) return null;
    return options.find(option => Number(option.id) === Number(checked.value)) || null;
}

function isResourceAvailable(option) {
    return String(option?.status || 'available').toLowerCase() === 'available';
}

function updateCost() {
    const selected = selectedOption();
    const totalCost = document.getElementById('total-cost');
    const originalPrice = document.getElementById('original-price');
    const discountNote = document.getElementById('discount-note');

    if (!totalCost || !originalPrice || !discountNote) return;

    if (!selected) {
        if (isSpaceUtmUser) {
            originalPrice.innerHTML = '<span style="text-decoration:line-through;">RM 0.00</span>';
            discountNote.innerHTML = '<span style="display:inline-block;background:#16a34a;color:#fff;padding:2px 8px;border-radius:999px;font-weight:600;">100% Discount</span>';
            totalCost.textContent = 'Free';
        } else {
            totalCost.textContent = 'RM 0';
            originalPrice.textContent = '';
            discountNote.textContent = '';
        }
        return;
    }

    const rawPrice = Number(selected.price || 0);
    if (isSpaceUtmUser) {
        originalPrice.innerHTML = rawPrice > 0
            ? `<span style="text-decoration:line-through;">RM ${rawPrice.toFixed(2)}</span>`
            : '<span style="text-decoration:line-through;">RM 0.00</span>';
        discountNote.innerHTML = '<span style="display:inline-block;background:#16a34a;color:#fff;padding:2px 8px;border-radius:999px;font-weight:600;">100% Discount</span>';
        totalCost.textContent = 'Free';
    } else {
        originalPrice.textContent = '';
        discountNote.textContent = '';
        totalCost.textContent = `RM ${rawPrice.toFixed(2)}`;
    }
}

async function loadSlotStatuses(date, selected) {
    if (!date || !selected) {
        slotStatusCache = [];
        return;
    }

    try {
        const response = await fetch(`booking.php?check_slots=1&resource_type=${encodeURIComponent(resourceType)}&resource_id=${encodeURIComponent(selected.id)}&date=${encodeURIComponent(date)}`);
        const data = await response.json();
        slotStatusCache = response.ok && data.success ? (data.slots || []) : [];
    } catch (error) {
        slotStatusCache = [];
    }
}

async function renderTimeSlotCards() {
    const grid = document.getElementById('time-slot-grid');
    const resultBox = document.getElementById('selected-time-result');
    const dateInput = document.getElementById('booking-date');
    const selected = selectedOption();
    const date = dateInput?.value || '';
    if (!grid) return;

    grid.innerHTML = '<div class="slot-helper">Loading time slots...</div>';
    if (resultBox) {
        resultBox.className = 'selection-result';
        resultBox.textContent = 'No time selected yet.';
    }

    if (!selected || !date) {
        slotStatusCache = [];
        grid.innerHTML = `<div class="slot-helper">Select ${resourceLabel} and date to view time slots.</div>`;
        return;
    }

    if (!isResourceAvailable(selected)) {
        const statusLabel = String(selected.status || 'unavailable').toUpperCase();
        slotStatusCache = [];
        grid.innerHTML = `<div class="slot-helper">This ${resourceLabel} is ${escapeHtml(statusLabel)} and cannot be booked.</div>`;
        if (resultBox) {
            resultBox.className = 'selection-result error';
            resultBox.textContent = `Booking is blocked because the selected ${resourceLabel} is not available.`;
        }
        return;
    }

    await loadSlotStatuses(date, selected);

    let cards = '';
    for (let minute = 8 * 60; minute <= 16 * 60; minute += 60) {
        const start = formatTime(minute);
        const end = formatTime(minute + 59);
        const endExclusive = formatTime(minute + 60);
        const status = getSlotStatus(start, endExclusive);
        const disabled = status !== 'free' ? 'is-disabled' : '';
        cards += `
            <button type="button" class="time-slot-card status-${status} ${disabled}" data-minute="${minute}" data-start="${start}" data-end="${endExclusive}">
                <div class="time-slot-time">${start} - ${end}</div>
                <div class="time-slot-meta">${status.toUpperCase()}</div>
            </button>
        `;
    }

    grid.innerHTML = cards;
    grid.querySelectorAll('.time-slot-card').forEach(card => card.addEventListener('click', () => handleSlotClick(card)));
    applyIncomingTimeSelection();
    renderSelectedTimePreview();
}

function getSlotStatus(startValue, endValue) {
    const requestStart = minutesFromTime(startValue);
    const requestEnd = minutesFromTime(endValue);

    for (const slot of slotStatusCache) {
        const slotStart = minutesFromTime(slot.start);
        const slotEnd = minutesFromTime(slot.end);
        if (slotStart < requestEnd && slotEnd > requestStart) {
            const status = String(slot.status || '').toLowerCase();
            if (status === 'pending') return 'pending';
            if (status === 'blocked') return 'blocked';
            if (status === 'maintenance') return 'maintenance';
            return 'booked';
        }
    }

    return 'free';
}

function applyIncomingTimeSelection() {
    if (!initialStartTime || !initialEndTime) return;
    const currentDate = document.getElementById('booking-date')?.value || '';
    if (initialBookingDate && currentDate !== initialBookingDate) return;
    const startMinute = minutesFromTime(initialStartTime);
    const endMinute = minutesFromTime(initialEndTime);
    if (!Number.isFinite(startMinute) || !Number.isFinite(endMinute)) return;

    document.querySelectorAll('#time-slot-grid .time-slot-card.status-free').forEach(card => {
        const cardMinute = Number(card.dataset.minute);
        if (cardMinute >= startMinute && cardMinute < endMinute) {
            card.classList.add('is-selected');
        }
    });
}

function handleSlotClick(clickedCard) {
    if (!clickedCard || !clickedCard.classList.contains('status-free')) return;
    const allCards = Array.from(document.querySelectorAll('#time-slot-grid .time-slot-card'));
    const clickedMinute = Number(clickedCard.dataset.minute);
    let selectedMinutes = allCards
        .filter(card => card.classList.contains('is-selected'))
        .map(card => Number(card.dataset.minute))
        .sort((a, b) => a - b);

    if (selectedMinutes.includes(clickedMinute)) {
        clickedCard.classList.remove('is-selected');
    } else if (selectedMinutes.length === 0) {
        clickedCard.classList.add('is-selected');
    } else {
        const min = Math.min(...selectedMinutes);
        const max = Math.max(...selectedMinutes);
        if (clickedMinute === min - 60 || clickedMinute === max + 60) {
            if (normalizedRole === 'student' && selectedMinutes.length >= 3) return;
            clickedCard.classList.add('is-selected');
        }
    }

    renderSelectedTimePreview();
}

function validateBookingSelection(showMessage = true) {
    const selected = selectedOption();
    const dateInput = document.getElementById('booking-date');
    const resultBox = document.getElementById('selected-time-result');
    const date = dateInput?.value || '';
    const selectedCards = Array.from(document.querySelectorAll('#time-slot-grid .time-slot-card.is-selected'));

    function fail(message) {
        if (showMessage && resultBox) {
            resultBox.className = 'selection-result error';
            resultBox.textContent = message;
        }
        return { valid: false, message };
    }

    if (!selected) return fail(`Select a ${resourceLabel} first.`);
    if (!isResourceAvailable(selected)) return fail(`This ${resourceLabel} is not available for booking.`);
    if (!date || !dateInput || date < dateInput.min || date > dateInput.max) return fail('Choose a booking date within 3 days including today.');

    const day = new Date(`${date}T00:00:00`).getDay();
    if (day === 0 || day === 6) return fail('Bookings are only available on weekdays (Monday to Friday).');
    if (!selectedCards.length) return fail('Please choose at least one available time slot.');
    if (normalizedRole === 'student' && selectedCards.length > 3) return fail('Students can select at most 3 time slots.');

    const minutes = selectedCards.map(card => Number(card.dataset.minute)).sort((a, b) => a - b);
    for (let i = 1; i < minutes.length; i++) {
        if (minutes[i] - minutes[i - 1] !== 60) return fail('Please select continuous time slots only.');
    }

    const start = formatTime(minutes[0]);
    const end = formatTime(minutes[minutes.length - 1] + 60);
    return { valid: true, selected, date, start, end };
}

function renderSelectedTimePreview() {
    const resultBox = document.getElementById('selected-time-result');
    if (!resultBox) return;

    const selection = validateBookingSelection(false);
    if (!selection.valid) {
        resultBox.className = 'selection-result';
        resultBox.textContent = 'No time selected yet.';
        return;
    }

    resultBox.className = 'selection-result success';
    resultBox.textContent = `Selected: ${selection.date} · ${selection.start} - ${selection.end}`;
}

async function submitBooking(event) {
    event.preventDefault();

    if (!isNonGuestUser) {
        openLoginRequiredModal();
        return;
    }

    const selection = validateBookingSelection(true);
    if (!selection.valid) return;

    const comments = document.getElementById('comments')?.value.trim() || '';
    const formData = new FormData();
    formData.append('resource_type', resourceType);
    formData.append('resource_id', String(selection.selected.id));
    formData.append('booking_date', selection.date);
    formData.append('start_time', selection.start);
    formData.append('end_time', selection.end);
    formData.append('comments', comments);
    formData.append('purpose', comments || 'General booking request');

    const submitButton = document.querySelector('.btn-submit');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
    }

    try {
        const response = await fetch('../../api/booking/create_booking.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            alert(result.message || 'Failed to create booking.');
            return;
        }

        alert(result.message || 'Booking created successfully.');
        window.location.href = 'profile.php';
    } catch (error) {
        alert('Failed to create booking.');
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = 'Submit Booking Request';
        }
    }
}


function openLoginRequiredModal() {
    const modal = document.getElementById('login-required-modal');
    if (!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
}

function closeLoginRequiredModal() {
    const modal = document.getElementById('login-required-modal');
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
}

function goToLoginPage() {
    window.location.href = '../auth/login.php';
}

function goHome() {
    window.location.href = '../../homepage.php';
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#039;',
        '"': '&quot;'
    }[char]));
}
</script>
</body>
</html>
