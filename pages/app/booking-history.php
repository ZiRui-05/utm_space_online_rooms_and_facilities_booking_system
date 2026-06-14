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
        :root { --primary:#8b1538; --primary-color:#8b1538; --primary-hover:#6f102d; --accent-color:#FFC107; --white:#ffffff; --text-dark:#1A202C; --text-light:#6b7280; --border-light:#e5e7eb; --bg-light:#f8f9fa; --danger:#dc3545; --border:#e5e7eb; --bg:#f5f5f5; --text:#333; --muted:#6b7280; }
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
        .status{padding:4px 10px;border-radius:999px;font-weight:600;font-size:12px;display:inline-block;white-space:nowrap}
        .status.pending{background:#fff8e1;color:#b26a00}
        .status.approved{background:#e8f5e9;color:#047857}
        .status.rejected,.status.cancelled,.status.expired{background:#ffebee;color:#b91c1c}
        .status.completed{background:#e8f5e9;color:#047857}
        .status.return_overdue{background:#fff3cd;color:#856404}
        .facility-name{font-weight:600;color:var(--text-dark)}
        .facility-ref{font-size:12px;color:var(--text-light);margin-top:3px}
        .payment-unavailable{color:var(--text-light);font-weight:600}
        .payment-paid{background:#e8f5e9;color:#047857}
        .payment-pending_verification{background:#fff8e1;color:#b26a00}
        .payment-payment_rejected{background:#ffebee;color:#b91c1c}
        .payment-refunded{background:#eef2f7;color:#475569}
        .cancel-button{border:1px solid var(--danger);border-radius:7px;padding:7px 10px;background:#fff;color:var(--danger);font-weight:600;cursor:pointer;white-space:nowrap}
        .cancel-button:hover{background:#fef2f2}
        .return-button{border:1px solid #047857;border-radius:7px;padding:7px 10px;background:#fff;color:#047857;font-weight:600;cursor:pointer;white-space:nowrap}
        .return-button:hover{background:#ecfdf5}
        .cancel-button:disabled,.return-button:disabled{cursor:not-allowed;opacity:.6}
        .empty{padding:22px;text-align:center;color:var(--muted)}

        @media (max-width: 768px) {
            .container { padding: 0 12px; margin: 16px auto; }
            .card { padding: 16px; overflow-x: auto; }
            .header { flex-direction: column; align-items: flex-start; }
            .filters { width: 100%; }
            .filters select { flex: 1; min-width: 0; }
            /* Force table wider than viewport so it scrolls inside .card */
            table { min-width: 860px; }
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
                    <option value="completed">Completed</option>
                    <option value="expired">Expired</option>
                    <option value="return_overdue">Return Overdue</option>
                </select>
            </div>
        </div>
        <table>
            <thead><tr><th>Facility & ID</th><th>Date & Time</th><th>Cost</th><th>Status</th><th>Payment</th><th>Action</th></tr></thead>
            <tbody id="history-body"></tbody>
        </table>
        <div class="empty" id="empty" style="display:none;">No booking history found.</div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/payment_receipt_modal.php'; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
let bookings = [];

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[character]);
}

function formatDateTime(start, end) {
    const s = new Date(start);
    const e = end ? new Date(end) : null;
    const d = s.toLocaleDateString();
    const st = s.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    if (!e) return `${d} ${st}`;
    const et = e.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    return `${d} ${st} - ${et}`;
}

function formatLabel(value) {
    return String(value || '')
        .toLowerCase()
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
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
        const statusLabel = formatLabel(statusClass);
        const cost = Number(item.cost ?? 0) > 0 ? `RM ${Number(item.cost).toFixed(2)}` : 'Free';
        const paymentStatus = (item.payment_status || '').toLowerCase();
        const canUploadPayment = ['pending', 'approved'].includes(statusClass);
        let payment = '<span class="payment-unavailable">Unavailable</span>';
        if (Number(item.cost ?? 0) > 0 && paymentStatus === 'paid') {
            payment = '<span class="status payment-paid">Paid</span>';
        } else if (Number(item.cost ?? 0) > 0 && paymentStatus === 'unpaid' && canUploadPayment) {
            payment = `<button type="button" class="btn-payment" onclick="openPaymentModal(${Number(item.booking_id)})">Complete Payment</button>`;
        } else if (Number(item.cost ?? 0) > 0 && paymentStatus) {
            payment = `<span class="status payment-${escapeHtml(paymentStatus)}">${escapeHtml(formatLabel(paymentStatus))}</span>`;
        }
        let action = '-';
        if (item.resource_type === 'room' && Number(item.can_return) === 1) {
            action = `<button type="button" class="return-button" onclick="returnBooking(${Number(item.booking_id)}, this)">Return</button>`;
        } else if (Number(item.can_cancel) === 1) {
            action = `<button type="button" class="cancel-button" onclick="cancelBooking(${Number(item.booking_id)}, this)">Cancel Booking</button>`;
        }
        return `<tr>
            <td>
                <div class="facility-name">${escapeHtml(name)}</div>
                <div class="facility-ref">Ref: BK-${Number(item.booking_id)}</div>
            </td>
            <td>${formatDateTime(item.booking_start, item.booking_end)}</td>
            <td>${cost}</td>
            <td><span class="status ${escapeHtml(statusClass)}">${escapeHtml(statusLabel)}</span></td>
            <td>${payment}</td>
            <td>${action}</td>
        </tr>`;
    }).join('');
}

async function loadHistory() {
    try {
        const response = await fetch('../../api/booking/get_bookings.php', {credentials: 'same-origin'});
        const result = await response.json();
        if (!response.ok || !result.success) {
            bookings = [];
            alert(result.message || 'Failed to load booking history.');
        } else {
            bookings = result.bookings || [];
        }
    } catch (error) {
        bookings = [];
        alert('Failed to load booking history. Please try again.');
    }
    render();
}

function openPaymentModal(bookingId) {
    PaymentReceiptModal.open(bookingId, {
        onUploadSuccess: async () => {
            await loadHistory();
            setTimeout(() => PaymentReceiptModal.close({ silent: true }), 600);
        }
    });
}

async function cancelBooking(bookingId, button) {
    if (!Number.isInteger(Number(bookingId)) || Number(bookingId) <= 0) {
        alert('Invalid booking ID.');
        return;
    }

    if (!confirm(`Cancel booking #${bookingId}? This action cannot be undone.`)) {
        return;
    }

    const originalText = button ? button.textContent : 'Cancel Booking';
    if (button) {
        button.disabled = true;
        button.textContent = 'Cancelling...';
    }

    try {
        const body = new URLSearchParams();
        body.set('id', String(bookingId));

        const response = await fetch('../../api/booking/cancel_booking.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: body.toString()
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            alert(result.message || 'Failed to cancel booking.');
            return;
        }

        await loadHistory();
        alert(result.message || 'Booking has been cancelled successfully.');
    } catch (error) {
        alert('Failed to cancel booking. Please try again.');
    } finally {
        if (button && button.isConnected) {
            button.disabled = false;
            button.textContent = originalText;
        }
    }
}

async function returnBooking(bookingId, button) {
    if (!Number.isInteger(Number(bookingId)) || Number(bookingId) <= 0) {
        alert('Invalid booking ID.');
        return;
    }

    if (!confirm(`Confirm that booking #${bookingId} has been returned?`)) {
        return;
    }

    const originalText = button ? button.textContent : 'Return';
    if (button) {
        button.disabled = true;
        button.textContent = 'Returning...';
    }

    try {
        const body = new URLSearchParams();
        body.set('id', String(bookingId));

        const response = await fetch('../../api/booking/return_booking.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: body.toString()
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            alert(result.message || 'Failed to return booking.');
            return;
        }

        await loadHistory();
        alert(result.message || 'Booking has been returned successfully.');
    } catch (error) {
        alert('Failed to return booking. Please try again.');
    } finally {
        if (button && button.isConnected) {
            button.disabled = false;
            button.textContent = originalText;
        }
    }
}

document.getElementById('sort-date').addEventListener('change', render);
document.getElementById('filter-status').addEventListener('change', render);
loadHistory();
</script>
</body>
</html>
