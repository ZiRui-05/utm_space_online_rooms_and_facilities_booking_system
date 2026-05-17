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
        $details['status'] = $row['resource_status'] ?? $details['status'];
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
        $upcomingBookings = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);
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
                <a class="btn-primary" href="<?= h($bookingUrl) ?>">Continue to Booking</a>
                <a class="btn-secondary" href="../../homepage.php">Back to Home</a>
            </div>
        </div>
    </section>

    <section class="availability-panel">
        <h2>Availability Details</h2>
        <div class="availability-status <?= h($availabilityClass) ?>">
            <strong><?= h(ucfirst($availabilityClass)) ?></strong>
            <span><?= h($availabilityMessage) ?></span>
        </div>

        <div class="schedule-controls-wrapper">
            <h3 style="margin:0;">Daily Timetable Matrix</h3>
            <div>
                <label for="schedule-filter-date" style="font-size:13px; font-weight:600; margin-right:8px; color:var(--text-light)">Select Target Date:</label>
                <input type="date" id="schedule-filter-date" class="date-picker-input">
            </div>
        </div>

        <div class="schedule-grid-container" id="timetable-slots-view">
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
    const dbBookingLogs = <?php echo json_encode($upcomingBookings); ?>;
</script>

<script>
    document.addEventListener('DOMContentLoaded', async function() {
        // Run active authentication protection session checkpoints
        try {
            const sessionResponse = await fetch('../../api/auth/auth_session.php', { credentials: 'same-origin' });
            if (!sessionResponse.ok) { window.location.href = '../auth/login.html'; return; }
            const sessionData = await sessionResponse.json();
            if (!sessionData.authenticated) { window.location.href = '../auth/login.html'; return; }
        } catch (err) {
            console.warn("Session filter check bypassed during test phase layout evaluations.");
        }

        // Initialize Dynamic Date Picking Controller Engine
        const dateInput = document.getElementById('schedule-filter-date');
        
        // Default target picker computation loop setup (skipping weekend initializations)
        let targetDate = new Date();
        if(targetDate.getDay() === 6) { // Saturday fallback to Monday
            targetDate.setDate(targetDate.getDate() + 2);
        } else if(targetDate.getDay() === 0) { // Sunday fallback to Monday
            targetDate.setDate(targetDate.getDate() + 1);
        }
        
        // Format layout directly to local YYYY-MM-DD template values
        const year = targetDate.getFullYear();
        const month = String(targetDate.getMonth() + 1).padStart(2, '0');
        const day = String(targetDate.getDate()).padStart(2, '0');
        dateInput.value = `${year}-${month}-${day}`;

        // Initialize and listen for dynamic input tracking updates
        renderTimetableGrid(`${year}-${month}-${day}`);
        dateInput.addEventListener('change', (e) => {
            renderTimetableGrid(e.target.value);
        });
    });

    function renderTimetableGrid(selectedDateStr) {
        const gridContainer = document.getElementById('timetable-slots-view');
        gridContainer.innerHTML = '';

        // Verification validation tracking logic to capture Saturdays (6) and Sundays (0)
        const parsedDate = new Date(selectedDateStr);
        const dayOfWeek = parsedDate.getDay();

        if (dayOfWeek === 0 || dayOfWeek === 6) {
            gridContainer.innerHTML = `
                <div class="weekend-alert">
                    <strong>⚠️ Operational Weekend Notice</strong><br>
                    University room facility reservation operations operate exclusively Monday through Friday (08:00 AM - 05:00 PM).
                </div>`;
            return;
        }

        // Generating structured hours layout segments from 08:00 to 17:00
        const operationalHours = [
            { label: '08:00 AM - 09:00 AM', start: 8, end: 9 },
            { label: '09:00 AM - 10:00 AM', start: 9, end: 10 },
            { label: '10:00 AM - 11:00 AM', start: 10, end: 11 },
            { label: '11:00 AM - 12:00 PM', start: 11, end: 12 },
            { label: '12:00 PM - 01:00 PM', start: 12, end: 13 },
            { label: '01:00 PM - 02:00 PM', start: 13, end: 14 },
            { label: '02:00 PM - 03:00 PM', start: 14, end: 15 },
            { label: '03:00 PM - 04:00 PM', start: 15, end: 16 },
            { label: '04:00 PM - 05:00 PM', start: 16, end: 17 }
        ];

        operationalHours.forEach(slot => {
            let slotStatus = 'free'; // default base evaluation state flag
            
            // Intersection confirmation validation engine loops against server buffered dataset 
            if (typeof dbBookingLogs !== 'undefined' && dbBookingLogs.length > 0) {
                dbBookingLogs.forEach(booking => {
                    const bStart = new Date(booking.booking_start);
                    const bEnd = new Date(booking.booking_end);
                    
                    // Convert selected date string and layout parameters to isolated timestamp points
                    const targetStart = new Date(selectedDateStr);
                    targetStart.setHours(slot.start, 0, 0, 0);
                    
                    const targetEnd = new Date(selectedDateStr);
                    targetEnd.setHours(slot.end, 0, 0, 0);

                    // If times overlap
                    if (bStart < targetEnd && bEnd > targetStart) {
                        if (booking.booking_status.toLowerCase() === 'approved') {
                            slotStatus = 'booked';
                        } else if (booking.booking_status.toLowerCase() === 'pending' && slotStatus !== 'booked') {
                            slotStatus = 'pending';
                        }
                    }
                });
            }

            // Map corresponding display status parameters configurations
            let modifierClass = 'status-free';
            let badgeClass = 'badge-free';
            let labelText = 'Available';

            if (slotStatus === 'booked') {
                modifierClass = 'status-booked';
                badgeClass = 'badge-booked';
                labelText = 'Booked';
            } else if (slotStatus === 'pending') {
                modifierClass = 'status-pending';
                badgeClass = 'badge-pending';
                labelText = 'Pending';
            }

            const slotCardHTML = `
                <div class="time-block-card ${modifierClass}">
                    <span class="time-range">${slot.label}</span>
                    <span class="slot-badge ${badgeClass}">${labelText}</span>
                </div>
            `;
            gridContainer.insertAdjacentHTML('beforeend', slotCardHTML);
        });
    }
</script>
</body>
</html>