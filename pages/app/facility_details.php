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
        $sql = "SELECT room_id AS id, room_name AS resource_name, room_code AS code, room_type AS resource_type, location, capacity, description, price_per_day, room_image_base64 AS image_base64, room_image_mime AS image_mime, resource_status FROM rooms WHERE ";
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
        $sql = "SELECT facility_id AS id, facility_name AS resource_name, facility_code AS code, facility_type AS resource_type, location, capacity, description, price_per_day, facility_image_base64 AS image_base64, facility_image_mime AS image_mime, resource_status FROM facilities WHERE ";
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
        $details['status'] = $row['resource_status'] ?: 'available';
        if (!empty($row['image_base64']) && !empty($row['image_mime'])) {
            $details['image'] = 'data:' . $row['image_mime'] . ';base64,' . $row['image_base64'];
        }

        $bookingSql = "SELECT booking_start, booking_end, booking_status, purpose
                       FROM bookings
                       WHERE resource_type = ?
                         AND " . ($type === 'room' ? 'room_id' : 'facility_id') . " = ?
                         AND booking_status IN ('pending','approved')
                         AND booking_end >= NOW()
                       ORDER BY booking_start ASC
                       LIMIT 8";
        $bookingStmt = $pdo->prepare($bookingSql);
        $bookingStmt->execute([$type, $resourceId]);
        $upcomingBookings = $bookingStmt->fetchAll();
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
        .type-badge { display:inline-block; padding:6px 12px; border-radius:999px; background:#fff3cd; color:#856404; font-size:12px; font-weight:700; margin-bottom:14px; }
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
        .picker-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; margin-bottom:16px; }
        .picker-field label { display:block; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:var(--text-light); margin-bottom:8px; }
        .picker-field input, .picker-field select { width:100%; border:1px solid var(--border-light); border-radius:8px; padding:12px 14px; font-size:14px; background:#fff; color:var(--text-dark); }
        .rules-box { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin:18px 0; }
        .rule-pill { border:1px solid var(--border-light); border-radius:8px; background:var(--bg-light); padding:12px; font-size:13px; color:var(--text-dark); line-height:1.5; }
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

        @media(max-width:800px){ .details-card{grid-template-columns:1fr;} .details-image{min-height:240px;} .info-grid{grid-template-columns:1fr;} .picker-grid,.rules-box{grid-template-columns:1fr;} .available-card{grid-template-columns:1fr;} }
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
                <img src="<?= h($details['image']) ?>" alt="<?= h($details['name']) ?>">
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
            Select a date and an hourly time slot to preview this facility's availability. 
            This preview is for page guidance only; the actual booking request is confirmed on the booking page.
        </p>

        <div class="picker-grid">
            <div class="picker-field">
                <label for="availability-date">Booking Date</label>
                <input type="date" id="availability-date">
                <div class="helper-text" id="date-range-note">Date range will be applied based on your account role.</div>
            </div>

            <div class="picker-field">
                <label for="availability-start">Start Time</label>
                <select id="availability-start">
                    <option value="">Select start time</option>
                </select>
            </div>

            <div class="picker-field">
                <label for="availability-end">End Time</label>
                <select id="availability-end">
                    <option value="">Select end time</option>
                </select>
            </div>
        </div>

        <div class="rules-box">
            <div class="rule-pill"><strong>Hourly unit:</strong><br>Time can only be selected in 1-hour steps.</div>
            <div class="rule-pill"><strong>Minimum duration:</strong><br>End time must be at least 1 hour after start time.</div>
            <div class="rule-pill"><strong>Operating hours:</strong><br>Only 08:00 to 17:00 may be selected.</div>
        </div>

        <div class="check-row">
            <button type="button" class="btn-check" id="check-selected-time">Check Selected Time</button>
            <span class="helper-text">Students may choose dates within 3 days including today.</span>
        </div>

        <div class="availability-result neutral" id="selected-time-result">
            <h3>No time selected yet</h3>
            <p>Choose a date, start time, and end time to preview this facility for the selected slot.</p>
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
    document.addEventListener('DOMContentLoaded', async function() {
        const dateInput = document.getElementById('availability-date');
        const startSelect = document.getElementById('availability-start');
        const endSelect = document.getElementById('availability-end');
        const resultBox = document.getElementById('selected-time-result');
        const checkBtn = document.getElementById('check-selected-time');
        const dateRangeNote = document.getElementById('date-range-note');
        const continueLink = document.getElementById('continue-booking-link');
        const bookingBaseUrl = <?= json_encode($bookingUrl) ?>;
        const resourceName = <?= json_encode($details['name']) ?>;
        const resourceType = <?= json_encode($type) ?>;
        const resourceLocation = <?= json_encode($details['location']) ?>;

        const pad = (number) => String(number).padStart(2, '0');
        const formatLocalDate = (date) => {
            const year = date.getFullYear();
            const month = pad(date.getMonth() + 1);
            const day = pad(date.getDate());
            return `${year}-${month}-${day}`;
        };

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        dateInput.min = formatLocalDate(today);

        let currentRole = 'guest';
        try {
            const sessionResponse = await fetch('../../api/auth/auth_session.php', { credentials: 'same-origin' });
            if (!sessionResponse.ok) { window.location.href = '../auth/login.html'; return; }
            const sessionData = await sessionResponse.json();
            if (!sessionData.authenticated) { window.location.href = '../auth/login.html'; return; }
            const userData = sessionData.user || {};
            currentRole = String(userData.role || 'guest').toLowerCase();
        } catch (error) {
            window.location.href = '../auth/login.html';
            return;
        }

        if (currentRole === 'student') {
            const maxStudentDate = new Date(today);
            maxStudentDate.setDate(maxStudentDate.getDate() + 2);
            dateInput.max = formatLocalDate(maxStudentDate);
            dateRangeNote.textContent = 'Student rule: choose from today until ' + formatLocalDate(maxStudentDate) + ' only.';
        } else {
            dateRangeNote.textContent = 'Choose an available booking date. The 3-day date limit applies to students only.';
        }

        function populateHourOptions() {
            let startOptions = '<option value="">Select start time</option>';
            let endOptions = '<option value="">Select end time</option>';

            for (let hour = 8; hour <= 16; hour++) {
                const value = `${pad(hour)}:00`;
                startOptions += `<option value="${value}">${value}</option>`;
            }

            for (let hour = 9; hour <= 17; hour++) {
                const value = `${pad(hour)}:00`;
                endOptions += `<option value="${value}">${value}</option>`;
            }

            startSelect.innerHTML = startOptions;
            endSelect.innerHTML = endOptions;
        }

        function parseHour(timeValue) {
            return Number(String(timeValue).split(':')[0]);
        }

        function updateEndTimeOptions() {
            const startHour = parseHour(startSelect.value);
            Array.from(endSelect.options).forEach((option) => {
                if (!option.value) {
                    option.disabled = false;
                    return;
                }
                const endHour = parseHour(option.value);
                option.disabled = Number.isFinite(startHour) && endHour < startHour + 1;
            });

            if (endSelect.value && Number.isFinite(startHour) && parseHour(endSelect.value) < startHour + 1) {
                endSelect.value = '';
            }
        }

        function showResult(state, title, message, includeFacilityCard = false) {
            resultBox.className = `availability-result ${state}`;
            let cardHtml = '';
            if (includeFacilityCard) {
                const selectedDate = dateInput.value;
                const selectedStart = startSelect.value;
                const selectedEnd = endSelect.value;
                cardHtml = `
                    <div class="available-card">
                        <div>
                            <strong>${escapeHtml(resourceName)}</strong>
                            <div class="meta">
                                ${escapeHtml(resourceType.charAt(0).toUpperCase() + resourceType.slice(1))} • ${escapeHtml(resourceLocation)}<br>
                                Selected slot: <span class="selected-slot">${escapeHtml(selectedDate)} | ${escapeHtml(selectedStart)} - ${escapeHtml(selectedEnd)}</span>
                            </div>
                        </div>
                        <strong>Available Preview</strong>
                    </div>
                `;
            }

            resultBox.innerHTML = `
                <h3>${escapeHtml(title)}</h3>
                <p>${escapeHtml(message)}</p>
                ${cardHtml}
            `;
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function updateBookingLink() {
            const selectedDate = dateInput.value;
            const selectedStart = startSelect.value;
            const selectedEnd = endSelect.value;

            if (!selectedDate || !selectedStart || !selectedEnd) {
                continueLink.href = bookingBaseUrl;
                return;
            }

            const params = new URLSearchParams();
            params.set('booking_date', selectedDate);
            params.set('start_time', selectedStart);
            params.set('end_time', selectedEnd);
            continueLink.href = bookingBaseUrl + '&' + params.toString();
        }

        function validateSelectedSlot() {
            const selectedDate = dateInput.value;
            const selectedStart = startSelect.value;
            const selectedEnd = endSelect.value;

            if (!selectedDate || !selectedStart || !selectedEnd) {
                showResult('error', 'Incomplete selection', 'Please choose a booking date, start time, and end time.');
                return false;
            }

            const startHour = parseHour(selectedStart);
            const endHour = parseHour(selectedEnd);

            if (startHour < 8 || startHour > 16 || endHour < 9 || endHour > 17) {
                showResult('error', 'Time outside booking hours', 'Please select a valid slot within 08:00 to 17:00.');
                return false;
            }

            if (endHour <= startHour) {
                showResult('error', 'Invalid time range', 'End time must be later than start time.');
                return false;
            }

            if ((endHour - startHour) < 1) {
                showResult('error', 'Minimum duration not met', 'The booking duration must be at least 1 hour.');
                return false;
            }

            if (currentRole === 'student') {
                const chosenDate = new Date(selectedDate + 'T00:00:00');
                const maxStudentDate = new Date(today);
                maxStudentDate.setDate(maxStudentDate.getDate() + 2);
                if (chosenDate < today || chosenDate > maxStudentDate) {
                    showResult('error', 'Student booking date is outside the allowed range', 'Students can choose only today and the next 2 days.');
                    return false;
                }
            }

            return true;
        }

        populateHourOptions();

        startSelect.addEventListener('change', function() {
            updateEndTimeOptions();
            updateBookingLink();
        });
        endSelect.addEventListener('change', updateBookingLink);
        dateInput.addEventListener('change', updateBookingLink);

        checkBtn.addEventListener('click', function() {
            if (!validateSelectedSlot()) {
                updateBookingLink();
                return;
            }

            updateBookingLink();
            showResult(
                'success',
                'Facility available for selected time preview',
                'This UI preview shows the facility as available for the selected hourly slot. Final availability should be confirmed during the booking process.',
                true
            );
        });
    });
</script>
</body>
</html>
