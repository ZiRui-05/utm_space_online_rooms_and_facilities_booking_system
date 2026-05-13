<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Facility - UNIRESERVE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #8b1538;
            --primary-hover: #a01d48;
            --accent-color: #ffc107;
            --text-dark: #333;
            --text-light: #666;
            --border-light: #e0e0e0;
            --white: #ffffff;
            --bg-light: #f5f5f5;
            --success: #388e3c;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            color: var(--text-dark);
            background: var(--bg-light);
        }

        .breadcrumb {
            padding: 16px 30px;
            background: var(--white);
            border-bottom: 1px solid var(--border-light);
            font-size: 13px;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            cursor: pointer;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .booking-form {
            background: var(--white);
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .booking-form h2 {
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 12px;
            font-size: 16px;
            pointer-events: none;
        }

        .input-wrapper input,
        .input-wrapper select,
        .input-wrapper textarea {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid var(--border-light);
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .input-wrapper textarea {
            padding: 12px;
            resize: vertical;
            min-height: 100px;
        }

        .input-wrapper input:focus,
        .input-wrapper select:focus,
        .input-wrapper textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 21, 56, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .facility-list {
            border: 1px solid var(--border-light);
            border-radius: 6px;
            max-height: 200px;
            overflow-y: auto;
        }

        .facility-item {
            padding: 12px;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .facility-item:hover {
            background: var(--bg-light);
        }

        .facility-item input {
            cursor: pointer;
        }

        .facility-item:last-child {
            border-bottom: none;
        }

        .total-cost {
            background: var(--bg-light);
            padding: 20px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 16px;
            font-weight: 700;
        }

        .total-cost-value {
            color: var(--primary-color);
            font-size: 24px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-submit {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 21, 56, 0.3);
        }

        .btn-cancel {
            background: var(--white);
            color: var(--text-dark);
            border: 1px solid var(--border-light);
            padding: 12px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            border-color: var(--text-dark);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn-submit,
            .btn-cancel {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<?php $currentPage = 'booking'; include "includes/header.php"; ?>
    

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="homepage.php">Home</a>
        <span> > </span>
        <span>Book Facility</span>
    </div>

    <!-- Main Container -->
    <div class="container">
        <div class="booking-form">
            <h2>Book a Facility</h2>

            <form id="booking-form" onsubmit="submitBooking(event)">
                <!-- Date and Time Pickers -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Date Picker</label>
                        <div class="input-wrapper">
                            <span class="input-icon">📅</span>
                            <input type="date" id="booking-date" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Time Picker</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🕐</span>
                            <input type="time" id="booking-time" required>
                        </div>
                    </div>
                </div>

                <!-- Facility Selection -->
                <div class="form-group">
                    <label>Select Facility List</label>
                    <div class="facility-list">
                        <div class="facility-item">
                            <input type="radio" name="facility" value="lecture-hall" onchange="updateCost()">
                            <div>
                                <div style="font-weight: 600;">Lecture Hall A</div>
                                <div style="font-size: 12px; color: var(--text-light);">Capacity: 100 | Cost: $50/hour</div>
                            </div>
                        </div>
                        <div class="facility-item">
                            <input type="radio" name="facility" value="study-pod" onchange="updateCost()">
                            <div>
                                <div style="font-weight: 600;">Study Pod 04</div>
                                <div style="font-size: 12px; color: var(--text-light);">Capacity: 4 | Cost: Free (Student)</div>
                            </div>
                        </div>
                        <div class="facility-item">
                            <input type="radio" name="facility" value="lab" onchange="updateCost()">
                            <div>
                                <div style="font-weight: 600;">Advanced Lab</div>
                                <div style="font-size: 12px; color: var(--text-light);">Capacity: 20 | Cost: $85/hour</div>
                            </div>
                        </div>
                        <div class="facility-item">
                            <input type="radio" name="facility" value="meeting-room" onchange="updateCost()">
                            <div>
                                <div style="font-weight: 600;">Meeting Room B</div>
                                <div style="font-size: 12px; color: var(--text-light);">Capacity: 12 | Cost: $30/hour</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Cost -->
                <div class="total-cost">
                    <div>Total Cost:</div>
                    <div class="total-cost-value" id="total-cost">$0</div>
                </div>

                <!-- Comments -->
                <div class="form-group">
                    <label>Write Comments/Description</label>
                    <div class="input-wrapper">
                        <textarea id="comments" placeholder="Add any special requirements or comments..." style="padding-left: 12px;"></textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="button-group">
                    <button type="button" class="btn-cancel" onclick="goHome()">Cancel</button>
                    <button type="submit" class="btn-submit">Submit Booking Request</button>
                </div>
            </form>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const sessionResponse = await fetch('auth_session.php', { credentials: 'same-origin' });
            if (!sessionResponse.ok) { window.location.href = 'login.html'; return; }

            const sessionData = await sessionResponse.json();
            if (!sessionData.authenticated) { window.location.href = 'login.html'; return; }

            const userData = sessionData.user || {};
            if (userData.full_name) {
                const initials = userData.full_name
                    .split(' ')
                    .map(n => n[0])
                    .join('')
                    .toUpperCase();
                document.getElementById('user-avatar-btn').textContent = initials;
            }
        });

        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }

        document.addEventListener('click', function(event) {
            const userDropdown = document.querySelector('.user-dropdown');
            if (!userDropdown.contains(event.target)) {
                document.getElementById('user-menu').style.display = 'none';
            }
        });

        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('auth_logout.php', { method: 'POST', credentials: 'same-origin' }).finally(() => {
                    window.location.href = 'login.html';
                });
            }
        }

        function updateCost() {
            const selected = document.querySelector('input[name="facility"]:checked');
            const costMap = {
                'lecture-hall': 50,
                'study-pod': 0,
                'lab': 85,
                'meeting-room': 30
            };
            const value = selected ? costMap[selected.value] ?? 0 : 0;
            document.getElementById('total-cost').textContent = `$${value}`;
        }

        function submitBooking(event) {
            event.preventDefault();
            alert('Booking request submitted successfully!');
        }

        function goHome() {
            window.location.href = 'homepage.php';
        }
    </script>
</body>
</html>