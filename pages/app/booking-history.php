<?php
session_start();
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - SPACEBOOK</title>
    <style>
        :root { --primary:#8b1538; --primary-color:#8b1538; --accent-color:#FFC107; --white:#ffffff; --text-dark:#1A202C; --border-light:#e5e7eb; --bg-light:#f8f9fa; --danger:#dc3545; --border:#e5e7eb; --bg:#f5f5f5; --text:#333; --muted:#6b7280; }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text)}
        .breadcrumb{padding:16px 30px;background:#fff;border-bottom:1px solid var(--border);font-size:13px}
        .breadcrumb a{color:var(--primary);text-decoration:none}
        .container{max-width:1200px;margin:30px auto;padding:0 24px}
        .card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
        .header{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:18px}
        .filters{display:flex;gap:12px;flex-wrap:wrap}
        select{border:1px solid var(--border);border-radius:8px;padding:10px 12px;background:#fff}
        table{width:100%;border-collapse:collapse}
        th,td{padding:12px;border-bottom:1px solid var(--border);font-size:14px;text-align:left}
        th{background:#fafafa;font-size:12px;text-transform:uppercase;letter-spacing:.03em;color:var(--muted)}
        .status{padding:4px 10px;border-radius:999px;font-weight:600;font-size:12px;display:inline-block}
        .status.pending{background:#fff7ed;color:#c2410c}
        .status.approved{background:#ecfdf5;color:#047857}
        .status.rejected,.status.cancelled{background:#fef2f2;color:#b91c1c}
        .empty{padding:22px;text-align:center;color:var(--muted)}

        @media (max-width: 768px) {
            .container { padding: 0 12px; margin: 16px auto; }
            .card { padding: 16px; overflow-x: auto; }
            .header { flex-direction: column; align-items: flex-start; }
            .filters { width: 100%; }
            .filters select { flex: 1; min-width: 0; }
            /* Force table wider than viewport so it scrolls inside .card */
            table { min-width: 520px; }
            th, td { padding: 10px 8px; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="breadcrumb"><a href="../../homepage.php">Home</a> / Booking History</div>
<div class="container">
    <div class="card">
        <div class="header">
            <div>
                <h2>Booking History</h2>
                <p style="margin-top:6px;color:var(--muted);font-size:14px;">View your past room/facility booking records.</p>
            </div>
            <div class="filters">
                <select id="sort-date">
                    <option value="desc">Date: Newest First</option>
                    <option value="asc">Date: Oldest First</option>
                </select>
                <select id="filter-status">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <table>
            <thead><tr><th>Room/Facility</th><th>Date & Time</th><th>Cost</th><th>Status</th></tr></thead>
            <tbody id="history-body"></tbody>
        </table>
        <div class="empty" id="empty" style="display:none;">No booking history found.</div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
let bookings = [];

function formatDateTime(start, end) {
    const s = new Date(start);
    const e = end ? new Date(end) : null;
    const d = s.toLocaleDateString();
    const st = s.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    if (!e) return `${d} ${st}`;
    const et = e.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    return `${d} ${st} - ${et}`;
}

function render() {
    const sortDate = document.getElementById('sort-date').value;
    const status = document.getElementById('filter-status').value;
    const body = document.getElementById('history-body');
    const empty = document.getElementById('empty');

    let list = bookings.filter(item => status === 'all' || item.booking_status === status);
    list.sort((a,b) => sortDate === 'asc'
        ? new Date(a.booking_start) - new Date(b.booking_start)
        : new Date(b.booking_start) - new Date(a.booking_start));

    if (!list.length) {
        body.innerHTML = '';
        empty.style.display = 'block';
        return;
    }

    empty.style.display = 'none';
    body.innerHTML = list.map(item => {
        const name = item.resource_name || '-';
        const statusClass = (item.booking_status || '').toLowerCase();
        const cost = Number(item.cost ?? 0) > 0 ? `RM ${Number(item.cost).toFixed(2)}` : 'Free';
        return `<tr>
            <td>${name}</td>
            <td>${formatDateTime(item.booking_start, item.booking_end)}</td>
            <td>${cost}</td>
            <td><span class="status ${statusClass}">${item.booking_status}</span></td>
        </tr>`;
    }).join('');
}

async function loadHistory() {
    const response = await fetch('../../api/booking/get_bookings.php', {credentials: 'same-origin'});
    const result = await response.json();
    if (!result.success) {
        bookings = [];
    } else {
        bookings = result.bookings || [];
    }
    render();
}

document.getElementById('sort-date').addEventListener('change', render);
document.getElementById('filter-status').addEventListener('change', render);
loadHistory();
</script>
</body>
</html>
