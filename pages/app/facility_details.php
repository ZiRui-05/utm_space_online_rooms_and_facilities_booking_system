<?php
$currentPage = 'home';
$type = strtolower(trim($_GET['type'] ?? 'facility'));
$name = trim($_GET['name'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['room', 'facility'], true)) {
    $type = 'facility';
}

$fallbacks = [
    'facility:stadium' => [
        'name' => 'Stadium Azman Hashim(UTM)',
        'short_name' => 'Stadium',
        'type' => 'Sports Facility',
        'location' => 'UTM Campus',
        'capacity' => 500,
        'description' => 'A multi-purpose stadium equipped with a quality sound system, spectator seating, and sports facilities. Suitable for sporting events, university ceremonies, large gatherings, and outdoor activities.',
        'price_per_day' => 150.00,
        'price_label' => 'RM 150 / hour',
        'image' => 'stadium.jpg',
        'status' => 'available',
    ],
    'room:room t05' => [
        'name' => 'Room T05',
        'short_name' => 'Room T05',
        'type' => 'Study Room',
        'location' => 'Academic Block',
        'capacity' => 8,
        'description' => 'Sound-isolated acoustic pod with whiteboards and collaborative display screens.',
        'price_per_day' => 0.00,
        'price_label' => 'Free (Student)',
        'image' => 'T05.jpg',
        'status' => 'available',
    ],
    'facility:astana hall ktc' => [
        'name' => 'Astana Hall KTC',
        'short_name' => 'Astana Hall KTC',
        'type' => 'Hall',
        'location' => 'KTC',
        'capacity' => 120,
        'description' => 'A functional hall equipped with a sound system, stage area, and seating arrangements. Suitable for student activities, meetings, small events, and college functions.',
        'price_per_day' => 25.00,
        'price_label' => 'RM 25 / hour',
        'image' => 'Dewan-Astana.jpg',
        'status' => 'available',
    ],
];

$key = $type . ':' . strtolower($name);
$details = $fallbacks[$key] ?? [
    'name' => $name !== '' ? $name : 'Facility Details',
    'short_name' => $name !== '' ? $name : 'Facility',
    'type' => ucfirst($type),
    'location' => 'Not specified',
    'capacity' => null,
    'description' => 'No description has been added yet.',
    'price_per_day' => 0.00,
    'price_label' => 'Not specified',
    'image' => '',
    'status' => 'available',
];

$dbLoaded = false;
$resourceId = 0;
$upcomingBookings = [];
$manualSchedules = [];
$weeklyScheduleRules = [];
$availabilityMessage = 'Available for booking request.';
$availabilityClass = 'available';

try {
    require_once __DIR__ . '/../../config/db.php';
    $dbLoaded = isset($pdo) && $pdo instanceof PDO;
} catch (Throwable $e) {
    $dbLoaded = false;
}

if ($dbLoaded) {
    if ($type === 'room') {
        $sql = "SELECT room_id AS id, room_name AS resource_name, room_code AS code, room_type AS resource_type, location, capacity, description, price_per_day, CASE WHEN room_image_base64 IS NULL OR room_image_base64 = '' THEN 0 ELSE 1 END has_image, resource_status FROM rooms WHERE ";
        $params = [];
        if ($id > 0) {
            $sql .= "room_id = ? LIMIT 1";
            $params[] = $id;
        } else {
            $sql .= "room_name = ? OR room_name LIKE ? LIMIT 1";
            $params[] = $name;
            $params[] = '%' . $name . '%';
        }
    } else {
        $sql = "SELECT facility_id AS id, facility_name AS resource_name, facility_code AS code, facility_type AS resource_type, location, capacity, description, price_per_day, CASE WHEN facility_image_base64 IS NULL OR facility_image_base64 = '' THEN 0 ELSE 1 END has_image, resource_status FROM facilities WHERE ";
        $params = [];
        if ($id > 0) {
            $sql .= "facility_id = ? LIMIT 1";
            $params[] = $id;
        } else {
            $sql .= "facility_name = ? OR facility_name LIKE ? LIMIT 1";
            $params[] = $name;
            $params[] = '%' . $name . '%';
        }
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    if ($row) {
        $resourceId = (int)$row['id'];
        $details['name'] = $row['resource_name'] ?: $details['name'];
        $details['short_name'] = $row['resource_name'] ?: $details['short_name'];
        $details['type'] = $row['resource_type'] ?: ucfirst($type);
        $details['location'] = $row['location'] ?: 'Not specified';
        $details['capacity'] = $row['capacity'];
        $details['description'] = $row['description'] ?: $details['description'];
        $details['price_per_day'] = (float)$row['price_per_day'];
        $details['price_label'] = ((float)$row['price_per_day'] <= 0) ? 'Free' : 'RM ' . number_format((float)$row['price_per_day'], 2) . ' / day';
        $details['status'] = $row['resource_status'] ?? $details['status'];
        if ((int)($row['has_image'] ?? 0) === 1) {
            $details['image'] = ($type === 'room' ? 'room_image.php?id=' : 'facility_image.php?id=') . (int)$row['id'];
        }

        $bookingSql = "SELECT booking_start, booking_end, booking_status, purpose
                       FROM bookings
                       WHERE resource_type = ?
                         AND " . ($type === 'room' ? 'room_id' : 'facility_id') . " = ?
                         AND booking_status IN ('pending','approved')
                         AND booking_end >= NOW()
                       ORDER BY booking_start ASC
                       LIMIT 300";
        $bookingStmt = $pdo->prepare($bookingSql);
        $bookingStmt->execute([$type, $resourceId]);
        $upcomingBookings = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);

        $resourceColumn = $type === 'room' ? 'room_id' : 'facility_id';
        $scheduleSql = "SELECT start_time, end_time, status
                        FROM schedules
                        WHERE resource_type = ?
                          AND " . $resourceColumn . " = ?
                          AND status IN ('blocked','maintenance')
                          AND end_time >= NOW()
                        ORDER BY start_time ASC";
        $scheduleStmt = $pdo->prepare($scheduleSql);
        $scheduleStmt->execute([$type, $resourceId]);
        $manualSchedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

        $ruleSql = "SELECT weekday, start_hour, end_hour, status
                    FROM weekly_schedule_rules
                    WHERE resource_type = ?
                      AND " . $resourceColumn . " = ?
                      AND status IN ('blocked','maintenance')";
        $ruleStmt = $pdo->prepare($ruleSql);
        $ruleStmt->execute([$type, $resourceId]);
        $weeklyScheduleRules = $ruleStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if ($details['status'] === 'maintenance') {
    $availabilityMessage = 'Temporarily unavailable because this ' . $type . ' is under maintenance.';
    $availabilityClass = 'maintenance';
} elseif ($details['status'] === 'unavailable') {
    $availabilityMessage = 'Currently unavailable for new booking requests.';
    $availabilityClass = 'unavailable';
} elseif (!empty($upcomingBookings)) {
    $availabilityMessage = 'Available, but please avoid the booked/pending time slots below.';
    $availabilityClass = 'limited';
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$bookingUrl = 'booking.php?resource_type=' . urlencode($type);
if ($resourceId > 0) {
    $bookingUrl .= '&' . ($type === 'room' ? 'room_id=' : 'facility_id=') . $resourceId;
}
$bookingUrl .= '&resource_name=' . urlencode($details['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($details['name']) ?> - UNIRESERVE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary-color:#8b1538; --primary-hover:#a01d48; --accent-color:#ffc107; --text-dark:#333; --text-light:#666; --border-light:#e0e0e0; --white:#fff; --bg-light:#f5f5f5; --success:#388e3c; --warning:#856404; --danger:#d32f2f; }
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Roboto','Oxygen','Ubuntu',sans-serif; color:var(--text-dark); background:var(--bg-light); }
        .breadcrumb { padding:16px 30px; background:var(--white); border-bottom:1px solid var(--border-light); font-size:13px; }
        .breadcrumb a { color:var(--primary-color); text-decoration:none; }
        .container { max-width:1100px; margin:30px auto; padding:0 30px; }
        .details-card { background:var(--white); border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.08); overflow:hidden; display:grid; grid-template-columns: 1fr 1.2fr; }
        .details-image { min-height:360px; background:#ddd; }
        .details-image img { width:100%; height:100%; object-fit:cover; display:block; }
        .image-placeholder { height:100%; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#8b1538,#a01d48); color:#fff; font-size:48px; font-weight:700; }
        .details-content { padding:34px; }
        .type-badge { display:inline-block; padding:6px 12px; border-radius: 999px; background:#fff3cd; color:#856404; font-size:12px; font-weight:700; margin-bottom:14px; }
        h1 { font-size:30px; margin-bottom:10px; }
        .description { color:var(--text-light); line-height:1.7; margin-bottom:22px; }
        .info-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin-bottom:24px; }
        .info-box { border:1px solid var(--border-light); border-radius:8px; padding:14px; background:#fff; }
        .info-label { color:var(--text-light); font-size:12px; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
        .info-value { font-weight:700; font-size:15px; }
        .availability-panel { margin-top:28px; background:var(--white); border-radius:10px; padding:26px; box-shadow:0 2px 10px rgba(0,0,0,.08); }
        .availability-status { display:flex; align-items:center; justify-content:space-between; gap:15px; padding:16px; border-radius:8px; margin:16px 0 22px; }
        .availability-status.available { background:#e8f5e9; color:var(--success); }
        .availability-status.limited { background:#fff3cd; color:var(--warning); }
        .availability-status.maintenance, .availability-status.unavailable { background:#ffebee; color:var(--danger); }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { padding:13px; text-align:left; border-bottom:1px solid var(--border-light); font-size:14px; }
        th { background:var(--bg-light); font-size:12px; text-transform:uppercase; letter-spacing:.4px; }
        .empty-state { color:var(--text-light); background:var(--bg-light); padding:18px; border-radius:8px; margin-top:12px; }
        .button-row { display:flex; gap:12px; margin-top:26px; flex-wrap:wrap; }
        .btn-primary, .btn-secondary { border:none; border-radius:6px; padding:12px 22px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; font-size:14px; }
        .btn-primary { background:var(--primary-color); color:#fff; }
        .btn-primary:hover { background:var(--primary-hover); }
        .btn-secondary { background:#fff; color:var(--text-dark); border:1px solid var(--border-light); }

        .selection-panel { margin-top:28px; background:var(--white); border-radius:10px; padding:26px; box-shadow:0 2px 10px rgba(0,0,0,.08); }
        .selection-panel h2 { margin-bottom:8px; }
        .selection-note { color:var(--text-light); line-height:1.6; margin-bottom:20px; }
        .picker-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; margin-bottom:16px; }
        .picker-field label { display:block; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:var(--text-light); margin-bottom:8px; }
        .picker-field input { width:100%; border:1px solid var(--border-light); border-radius:8px; padding:12px 14px; font-size:14px; background:#fff; color:var(--text-dark); }
        .rule-pill { border:1px solid var(--border-light); border-radius:8px; background:var(--bg-light); padding:12px; font-size:13px; color:var(--text-dark); line-height:1.5; margin-bottom:16px; }
        .time-slot-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(165px,1fr)); gap:10px; margin-bottom:16px; }
        .time-slot-card { border:1px solid var(--border-light); border-radius:8px; background:#fff; padding:12px; cursor:pointer; text-align:left; transition:all .2s ease; }
        .time-slot-card:hover { border-color:var(--primary-color); transform:translateY(-1px); }
        .time-slot-card.is-selected { border-color:var(--primary-color); box-shadow:0 0 0 2px rgba(139,21,56,.15); background:#fff8fb; }
        .time-slot-card.is-disabled { opacity:.55; cursor:not-allowed; background:#f5f5f5; }
        .time-slot-card.status-booked { background:#ffebee; border-color:#f2b8bd; cursor:not-allowed; }
        .time-slot-card.status-pending { background:#fff8e1; border-color:#efd58f; cursor:not-allowed; }
        .time-slot-card.status-free { background:#f1f8f3; }
        .time-slot-time { font-size:14px; font-weight:700; margin-bottom:4px; }
        .time-slot-meta { color:var(--text-light); font-size:12px; }
        .check-row { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-top:14px; }
        .availability-result { margin-top:20px; padding:18px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-light); }
        .availability-result.success { background:#e8f5e9; border-color:#b7dfbb; }
        .availability-result.error { background:#ffebee; border-color:#f2b8bd; }
        .availability-result.neutral { background:#fff8e1; border-color:#efd58f; }
        .availability-result h3 { font-size:18px; margin-bottom:8px; }
        .available-card { margin-top:14px; border:1px solid rgba(0,0,0,.08); border-radius:10px; padding:16px; background:#fff; display:grid; grid-template-columns:1fr auto; gap:14px; align-items:center; }
        .available-card .meta { color:var(--text-light); font-size:14px; margin-top:5px; line-height:1.5; }
        .selected-slot { font-weight:700; color:var(--primary-color); }
        .btn-check { background:var(--primary-color); color:#fff; border:none; border-radius:6px; padding:12px 22px; font-weight:700; cursor:pointer; font-size:14px; }
        .btn-check:hover { background:var(--primary-hover); }
        .helper-text { margin-top:8px; font-size:13px; color:var(--text-light); }

        @media(max-width:800px){ .details-card{grid-template-columns:1fr;} .details-image{min-height:240px;} .info-grid{grid-template-columns:1fr;} .picker-grid{grid-template-columns:1fr;} .available-card{grid-template-columns:1fr;} }
        /* Timetable Grid View Custom Styles */
        .schedule-controls-wrapper { display: flex; align-items: center; justify-content: space-between; margin: 20px 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid var(--border-light); flex-wrap: wrap; gap: 10px; }
        .date-picker-input { padding: 8px 14px; border: 1px solid var(--border-light); border-radius: 6px; font-size: 14px; outline: none; font-family: inherit; }
        .schedule-grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-bottom: 25px; }
        .time-block-card { background: #f8f9fa; border: 1px solid var(--border-light); border-radius: 6px; padding: 12px; text-align: center; transition: all 0.2s ease; }
        .time-block-card.status-free { border-top: 4px solid var(--success); }
        .time-block-card.status-booked { border-top: 4px solid var(--danger); background: #fff5f5; opacity: 0.85; }
        .time-block-card.status-pending { border-top: 4px solid var(--accent-color); background: #fffdf3; }
        .time-block-card .time-range { font-weight: 700; font-size: 14px; color: var(--text-dark); display: block; margin-bottom: 4px; }
        .time-block-card .slot-badge { font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 4px; display: inline-block; }
        .slot-badge.badge-free { background: #e8f5e9; color: var(--success); }
        .slot-badge.badge-booked { background: #ffebee; color: var(--danger); }
        .slot-badge.badge-pending { background: #fff3cd; color: #856404; }
        .weekend-alert { padding: 20px; background: #f7fafc; border: 1px dashed #cbd5e0; border-radius: 8px; text-align: center; color: var(--text-light); grid-column: 1 / -1; }

        @media(max-width:800px){ .details-card{grid-template-columns:1fr;} .details-image{min-height:240px;} .info-grid{grid-template-columns:1fr;} }

        .availability-result.available { border-left: 5px solid var(--success); background:#e8f5e9; }
        .availability-result.unavailable { border-left: 5px solid var(--danger); background:#ffebee; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="breadcrumb">
    <a href="../../homepage.php">Home</a>
    <span> &gt; </span>
    <span><?= h($details['name']) ?></span>
</div>

<div class="container">
    <section class="details-card">
        <div class="details-image">
            <?php if (!empty($details['image'])): ?>
                <img src="<?= h($details['image']) ?>" loading="lazy" decoding="async" alt="<?= h($details['name']) ?>">
            <?php else: ?>
                <div class="image-placeholder"><?= strtoupper(substr($details['name'], 0, 1)) ?></div>
            <?php endif; ?>
        </div>
        <div class="details-content">
            <span class="type-badge"><?= h($details['type']) ?></span>
            <h1><?= h($details['name']) ?></h1>
            <p class="description"><?= h($details['description']) ?></p>

            <div class="info-grid">
                <div class="info-box">
                    <div class="info-label">Location</div>
                    <div class="info-value"><?= h($details['location']) ?></div>
                </div>
                <?php if ($type === 'room'): ?>
                <div class="info-box">
                    <div class="info-label">Capacity</div>
                    <div class="info-value"><?= $details['capacity'] ? h($details['capacity']) . ' people' : 'Not specified' ?></div>
                </div>
                <?php endif; ?>
                <div class="info-box">
                    <div class="info-label">Price</div>
                    <div class="info-value"><?= h($details['price_label']) ?></div>
                </div>
                <div class="info-box">
                    <div class="info-label">Resource Status</div>
                    <div class="info-value"><?= h(ucfirst($details['status'])) ?></div>
                </div>
            </div>

            <div class="button-row">
                <a class="btn-primary" id="continue-booking-link" href="<?= h($bookingUrl) ?>">Continue to Booking</a>
                <a class="btn-secondary" href="../../homepage.php">Back to Home</a>
            </div>
        </div>
    </section>


    <section class="selection-panel">
        <h2>Choose Booking Date &amp; Time</h2>
        <p class="selection-note">
            Pick a date and directly tap time slots to select your booking period.
        </p>

        <div class="picker-grid">
            <div class="picker-field">
                <label for="availability-date">Booking Date</label>
                <input type="date" id="availability-date">
                <div class="helper-text" id="date-range-note">Date range will be applied based on your account role.</div>
            </div>

            <div class="picker-field" style="grid-column: 1 / -1;">
                <label>Time Slots</label>
                <div id="start-slot-grid" class="time-slot-grid"></div>
                <div class="helper-text" id="selection-helper">Choose continuous slots only.</div>
            </div>
        </div>

        <div class="rule-pill"><strong>Operating hours:</strong><br>Bookings are allowed only on weekdays (Monday to Friday), between 08:00 and 17:00.</div>


        <div class="availability-result neutral" id="selected-time-result">
            <h3>No time selected yet</h3>
            <p>Choose a date and contiguous time slot(s) to preview this <?= h($type) ?>.</p>
        </div>
    </section>

    <section class="availability-panel">
        <h2>Availability Details</h2>
        <div class="availability-status <?= h($availabilityClass) ?>">
            <strong><?= h(ucfirst($availabilityClass)) ?></strong>
            <span><?= h($availabilityMessage) ?></span>
        </div>

        <h3>Upcoming Booked / Pending Slots</h3>
        <?php if (!empty($upcomingBookings)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Status</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingBookings as $booking): ?>
                        <tr>
                            <td><?= h(date('d M Y', strtotime($booking['booking_start']))) ?></td>
                            <td><?= h(date('h:i A', strtotime($booking['booking_start']))) ?></td>
                            <td><?= h(date('h:i A', strtotime($booking['booking_end']))) ?></td>
                            <td><?= h(ucfirst($booking['booking_status'])) ?></td>
                            <td><?= h($booking['purpose']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No approved or pending booking slots found for this <?= h($type) ?>. You can submit a booking request.</div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>


<script>
    const dbBookingLogs = <?php echo json_encode($upcomingBookings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const dbManualSchedules = <?php echo json_encode($manualSchedules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const dbWeeklyRules = <?php echo json_encode($weeklyScheduleRules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const resourceType = <?= json_encode($type) ?>;
    const continueBookingBaseUrl = <?= json_encode($bookingUrl, JSON_UNESCAPED_SLASHES) ?>;
    let currentUserRole = '';

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            const sessionResponse = await fetch('../../api/auth/auth_session.php', { credentials: 'same-origin' });
            if (sessionResponse.ok) {
                const sessionData = await sessionResponse.json();
                if (sessionData.authenticated) {
                    currentUserRole = String(sessionData.user?.role || '').toLowerCase();
                }
            }
        } catch (err) {
            console.warn('Session check could not be completed.');
        }

        setupAvailabilityPreview();
    });

    function formatLocalDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatTime(totalMinutes) {
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    function bookingDateLimitDays() {
        return currentUserRole === 'student' ? 2 : 30;
    }

    function bookingDateRangeMessage() {
        return currentUserRole === 'student'
            ? 'Booking date must be within 3 days including today.'
            : 'Booking date must be within 30 days including today.';
    }

    function setupAvailabilityPreview() {
        const dateInput = document.getElementById('availability-date');
        const note = document.getElementById('date-range-note');
        if (!dateInput) return;

        const today = new Date();
        dateInput.min = formatLocalDate(today);
        const maxDate = new Date(today);
        maxDate.setDate(maxDate.getDate() + bookingDateLimitDays());
        dateInput.max = formatLocalDate(maxDate);
        if (note) note.textContent = bookingDateRangeMessage();

        dateInput.value = dateInput.min;
        renderStartSlotCards();
        dateInput.addEventListener('change', () => {
            renderStartSlotCards();
            renderSelectedTimePreview();
            updateContinueBookingLink();
        });
        updateContinueBookingLink();
    }

    function renderStartSlotCards() {
        const startGrid = document.getElementById('start-slot-grid');
        if (!startGrid) return;
        const dateValue = document.getElementById('availability-date')?.value || '';

        let cards = '';
        for (let minute = 8 * 60; minute <= 16 * 60; minute += 60) {
            const start = formatTime(minute);
            const end = formatTime(minute + 59);
            const status = getSlotStatus(dateValue, start, minute + 60);
            cards += `<button type="button" class="time-slot-card status-${status}" data-minute="${minute}" data-start="${start}" data-end="${formatTime(minute+60)}"><div class="time-slot-time">${start} - ${end}</div><div class="time-slot-meta">${status.toUpperCase()}</div></button>`;
        }
        startGrid.innerHTML = cards;
        startGrid.querySelectorAll('.time-slot-card').forEach(card => card.addEventListener('click', () => handleSlotClick(card)));
    }

    function getSlotStatus(dateValue, startValue, endMinuteExclusive) {
        if (!dateValue) return 'free';
        const slotStart = new Date(`${dateValue}T${startValue}:00`);
        const slotEnd = new Date(`${dateValue}T${formatTime(endMinuteExclusive)}:00`);

        for (const schedule of (dbManualSchedules || [])) {
            const scheduleStart = new Date(schedule.start_time);
            const scheduleEnd = new Date(schedule.end_time);
            if (scheduleStart < slotEnd && scheduleEnd > slotStart) {
                return schedule.status === 'maintenance' ? 'maintenance' : 'blocked';
            }
        }

        const weekday = new Date(`${dateValue}T00:00:00`).getDay();
        const weekdayMondayFirst = weekday === 0 ? 7 : weekday;
        const slotHour = Math.floor(endMinuteExclusive / 60) - 1;
        for (const rule of (dbWeeklyRules || [])) {
            const startHour = Number(rule.start_hour);
            const endHour = Number(rule.end_hour);
            if (Number(rule.weekday) === weekdayMondayFirst && slotHour >= startHour && slotHour < endHour) {
                return rule.status === 'maintenance' ? 'maintenance' : 'blocked';
            }
        }

        for (const booking of (dbBookingLogs || [])) {
            const bookingStart = new Date(booking.booking_start);
            const bookingEnd = new Date(booking.booking_end);
            if (bookingStart < slotEnd && bookingEnd > slotStart) {
                if (booking.booking_status === 'pending') return 'pending';
                return 'booked';
            }
        }
        return 'free';
    }

    function handleSlotClick(clickedCard) {
        if (!clickedCard || !clickedCard.classList.contains('status-free')) return;
        const allCards = Array.from(document.querySelectorAll('#start-slot-grid .time-slot-card'));
        const clickedMinute = Number(clickedCard.dataset.minute);
        let selected = allCards.filter(c => c.classList.contains('is-selected')).map(c => Number(c.dataset.minute)).sort((a,b)=>a-b);

        if (selected.includes(clickedMinute)) {
            clickedCard.classList.remove('is-selected');
            selected = selected.filter(m => m !== clickedMinute);
        } else if (selected.length === 0) {
            clickedCard.classList.add('is-selected');
        } else {
            const min = Math.min(...selected), max = Math.max(...selected);
            if (clickedMinute === min - 60 || clickedMinute === max + 60) {
                if (currentUserRole === 'student' && selected.length >= 3) return;
                clickedCard.classList.add('is-selected');
            } else {
                return;
            }
        }

        renderSelectedTimePreview();
        updateContinueBookingLink();
    }

    function validatePreviewSelection() {
        const dateValue = document.getElementById('availability-date')?.value || '';
        const dateInput = document.getElementById('availability-date');
        const selectedCards = Array.from(document.querySelectorAll('#start-slot-grid .time-slot-card.is-selected'));
        if (!dateValue) return { valid:false, message:'Please choose a booking date.' };
        if (!dateInput || dateValue < dateInput.min || dateValue > dateInput.max) return { valid:false, message:bookingDateRangeMessage() };
        const day = new Date(`${dateValue}T00:00:00`).getDay();
        if (day === 0 || day === 6) return { valid:false, message:'Bookings are only available on weekdays (Monday to Friday).' };
        if (selectedCards.length === 0) return { valid:false, message:'Please choose at least one available time slot.' };
        if (currentUserRole === 'student' && selectedCards.length > 3) return { valid:false, message:'Student can select at most 3 time slots.' };

        const minutes = selectedCards.map(c => Number(c.dataset.minute)).sort((a,b)=>a-b);
        for (let i=1;i<minutes.length;i++) if (minutes[i]-minutes[i-1]!==60) return { valid:false, message:'Please select continuous time slots only.' };

        const startValue = formatTime(minutes[0]);
        const endValue = formatTime(minutes[minutes.length-1] + 60);
        return { valid:true, dateValue, startValue, endValue };
    }

    function renderSelectedTimePreview() {
        const resultBox = document.getElementById('selected-time-result');
        if (!resultBox) return;
        const selection = validatePreviewSelection();
        if (!selection.valid) {
            resultBox.className = 'availability-result unavailable';
            resultBox.innerHTML = `<h3>Selection unavailable</h3><p>${escapeHtml(selection.message)}</p>`;
            return;
        }
        resultBox.className = 'availability-result available';
        resultBox.innerHTML = `<h3>Selected slot appears available</h3><p>${escapeHtml(selection.dateValue)} · ${escapeHtml(selection.startValue)} - ${escapeHtml(selection.endValue)} may proceed to booking.</p>`;
    }

    function updateContinueBookingLink() {
        const link = document.getElementById('continue-booking-link');
        if (!link) return;
        const selection = validatePreviewSelection();
        if (!selection.valid) { link.href = continueBookingBaseUrl; return; }
        const separator = continueBookingBaseUrl.includes('?') ? '&' : '?';
        link.href = continueBookingBaseUrl + separator + 'booking_date=' + encodeURIComponent(selection.dateValue) + '&start_time=' + encodeURIComponent(selection.startValue) + '&end_time=' + encodeURIComponent(selection.endValue);
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
