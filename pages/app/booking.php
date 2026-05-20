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
$resourceOptions = [];
$selectedResource = null;

try {
    require_once __DIR__ . '/../../config/db.php';

    $stmt = $pdo->prepare('SELECT role FROM users WHERE user_id = ? LIMIT 1');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $uid = (int)($_SESSION['user']['user_id'] ?? ($_SESSION['user_id'] ?? 0));
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
const normalizedRole = String(role || '').trim().toLowerCase();
const isNonGuestUser = normalizedRole !== '' && normalizedRole !== 'guest';
const initialBookingDate = <?= json_encode($selectedDateParam) ?>;
const initialStartTime = <?= json_encode($selectedStartParam) ?>;
const initialEndTime = <?= json_encode($selectedEndParam) ?>;

document.addEventListener('DOMContentLoaded', () => {
    setupDateConstraints();
    populateTimeDropdowns();
    renderResources();
    applyIncomingBookingSelection();
    updateCost();
    checkTimeSlots();
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
        checkTimeSlots();
    });
}

function populateTimeDropdowns() {
    const startSelect = document.getElementById('booking-time');
    const endSelect = document.getElementById('booking-end-time');
    if (!startSelect || !endSelect) return;

    let startOptions = '<option value="">--:--</option>';
    let endOptions = '<option value="">--:--</option>';

    for (let minute = 8 * 60; minute <= 16 * 60; minute += 15) {
        const value = formatTime(minute);
        startOptions += `<option value="${value}">${value}</option>`;
    }

    for (let minute = 9 * 60; minute <= 17 * 60; minute += 15) {
        const value = formatTime(minute);
        endOptions += `<option value="${value}">${value}</option>`;
    }

    startSelect.innerHTML = startOptions;
    endSelect.innerHTML = endOptions;
    startSelect.addEventListener('change', () => {
        syncEndTimeOptions();
        checkTimeSlots();
    });
    endSelect.addEventListener('change', checkTimeSlots);
    syncEndTimeOptions();
}

function applyIncomingBookingSelection() {
    const dateInput = document.getElementById('booking-date');
    const startSelect = document.getElementById('booking-time');
    const endSelect = document.getElementById('booking-end-time');

    if (dateInput && initialBookingDate && initialBookingDate >= dateInput.min && initialBookingDate <= dateInput.max) {
        dateInput.value = initialBookingDate;
    }
    if (startSelect && initialStartTime && [...startSelect.options].some(option => option.value === initialStartTime)) {
        startSelect.value = initialStartTime;
    }
    syncEndTimeOptions();
    if (endSelect && initialEndTime && [...endSelect.options].some(option => option.value === initialEndTime && !option.disabled)) {
        endSelect.value = initialEndTime;
    }
}

function syncEndTimeOptions() {
    const startSelect = document.getElementById('booking-time');
    const endSelect = document.getElementById('booking-end-time');
    if (!startSelect || !endSelect) return;

    const startMinutes = minutesFromTime(startSelect.value);
    const minimumEnd = Number.isFinite(startMinutes) ? startMinutes + 60 : NaN;

    [...endSelect.options].forEach(option => {
        if (!option.value) {
            option.disabled = false;
            return;
        }
        const endMinutes = minutesFromTime(option.value);
        option.disabled = Number.isFinite(minimumEnd) && endMinutes < minimumEnd;
    });

    if (endSelect.value && endSelect.selectedOptions[0]?.disabled) {
        endSelect.value = '';
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
        return `
            <label class="facility-item">
                <input type="radio" name="resource-option" value="${Number(option.id)}" ${checked}>
                <div>
                    <strong>${escapeHtml(option.name)}</strong>
                    <div style="font-size:13px;color:var(--text-light);">
                        ${escapeHtml(option.type || resourceLabel)} · Capacity ${Number(option.capacity || 0)}
                    </div>
                </div>
            </label>
        `;
    }).join('');

    if (!document.querySelector('input[name="resource-option"]:checked')) {
        const first = document.querySelector('input[name="resource-option"]');
        if (first) first.checked = true;
    }

    container.querySelectorAll('input[name="resource-option"]').forEach(input => {
        input.addEventListener('change', () => {
            updateCost();
            checkTimeSlots();
        });
    });
}

function selectedOption() {
    const checked = document.querySelector('input[name="resource-option"]:checked');
    if (!checked) return null;
    return options.find(option => Number(option.id) === Number(checked.value)) || null;
}

function updateCost() {
    const selected = selectedOption();
    const totalCost = document.getElementById('total-cost');
    const originalPrice = document.getElementById('original-price');
    const discountNote = document.getElementById('discount-note');

    if (!totalCost || !originalPrice || !discountNote) return;

    if (!selected) {
        if (isNonGuestUser) {
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
    if (isNonGuestUser) {
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

function validateBookingSelection(showMessage = true) {
    const selected = selectedOption();
    const dateInput = document.getElementById('booking-date');
    const startSelect = document.getElementById('booking-time');
    const endSelect = document.getElementById('booking-end-time');
    const el = document.getElementById('slot-check');

    const date = dateInput?.value || '';
    const start = startSelect?.value || '';
    const end = endSelect?.value || '';
    const startMinutes = minutesFromTime(start);
    const endMinutes = minutesFromTime(end);

    function fail(message) {
        if (showMessage && el) el.innerHTML = `<span style="color:var(--danger)">${escapeHtml(message)}</span>`;
        return { valid: false, message };
    }

    if (!selected) return fail(`Select a ${resourceLabel} first.`);
    if (!date || !dateInput || date < dateInput.min || date > dateInput.max) return fail('Choose a booking date within 3 days including today.');
    if (!start || !end) return fail('Select start and end time.');
    if (startMinutes < 8 * 60 || startMinutes > 16 * 60 || endMinutes < 9 * 60 || endMinutes > 17 * 60) return fail('Time must stay within 08:00 to 17:00.');
    if (startMinutes % 15 !== 0 || endMinutes % 15 !== 0) return fail('Time must use 15-minute units only.');
    if (endMinutes - startMinutes < 60) return fail('Minimum booking duration is 1 hour.');

    return { valid: true, selected, date, start, end };
}

async function checkTimeSlots() {
    const el = document.getElementById('slot-check');
    if (!el) return;

    const selection = validateBookingSelection(false);
    if (!selection.valid) {
        el.textContent = `Select ${resourceLabel}, date, start time, and end time to check slot availability.`;
        return;
    }

    el.textContent = 'Checking booked slots...';

    try {
        const response = await fetch(`booking.php?check_slots=1&resource_type=${encodeURIComponent(resourceType)}&resource_id=${encodeURIComponent(selection.selected.id)}&date=${encodeURIComponent(selection.date)}`);
        const data = await response.json();
        if (!response.ok || !data.success) {
            el.textContent = 'Unable to check availability.';
            return;
        }

        const requestStart = minutesFromTime(selection.start);
        const requestEnd = minutesFromTime(selection.end);
        const overlappingSlots = (data.slots || []).filter(slot => {
            const slotStart = minutesFromTime(slot.start);
            const slotEnd = minutesFromTime(slot.end);
            return slotStart < requestEnd && slotEnd > requestStart;
        });

        if (!overlappingSlots.length) {
            el.innerHTML = '<strong style="color:var(--success)">Available</strong><div>No booked or pending slot overlaps this requested time.</div>';
            return;
        }

        el.innerHTML = `<strong style="color:var(--danger)">Time Conflict</strong><div>This slot overlaps:</div><ul>${overlappingSlots.map(slot => `<li>${escapeHtml(slot.start)} - ${escapeHtml(slot.end)} (${escapeHtml(slot.status || 'Booked')})</li>`).join('')}</ul>`;
    } catch (error) {
        el.textContent = 'Unable to check availability.';
    }
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
