<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - UNIRESERVE</title>
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
            --warning: #ff9800;
            --danger: #d32f2f;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            color: var(--text-dark);
            background: var(--bg-light);
        }

        /* Navigation Bar */
        .navbar {
            background: var(--primary-color);
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .navbar-logo {
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
            letter-spacing: 1px;
            margin: 0;
        }

        .nav-links {
            display: flex;
            gap: 30px;
        }

        .nav-link {
            color: var(--white);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--accent-color);
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .icon-button {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--white);
            transition: transform 0.3s;
        }

        .icon-button:hover {
            transform: scale(1.1);
        }

        .btn-book-now {
            background: var(--accent-color);
            color: var(--text-dark);
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-book-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }

        .user-dropdown {
            position: relative;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--white);
            color: var(--primary-color);
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .dropdown-menu {
            position: absolute;
            top: 45px;
            right: 0;
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            padding: 8px 0;
            z-index: 1000;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: 12px 20px;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 13px;
            color: var(--text-dark);
            text-decoration: none;
            transition: background 0.3s;
        }

        .dropdown-item:hover {
            background: var(--bg-light);
        }

        .dropdown-item.logout {
            color: var(--danger);
        }

        .dropdown-divider {
            margin: 8px 0;
            border: none;
            border-top: 1px solid var(--border-light);
        }

        /* Breadcrumb */
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

        .breadcrumb span {
            color: var(--text-light);
            margin: 0 8px;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 30px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        /* Left Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Profile Card */
        .profile-card {
            background: var(--white);
            border-radius: 8px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 16px;
            border: 4px solid var(--accent-color);
            font-weight: 700;
        }

        .profile-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .profile-role {
            color: var(--primary-color);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .profile-info {
            background: var(--bg-light);
            border-radius: 8px;
            padding: 12px;
            margin-top: 16px;
            text-align: left;
        }

        .info-item {
            padding: 10px 0;
            border-bottom: 1px solid var(--border-light);
            font-size: 13px;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-light);
            font-weight: 600;
        }

        .info-value {
            color: var(--text-dark);
            margin-top: 4px;
            font-weight: 500;
        }

        /* Settings Card */
        .settings-card {
            background: var(--white);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .settings-card h3 {
            font-size: 16px;
            color: var(--text-dark);
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-light);
        }

        .settings-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background 0.3s;
        }

        .settings-item:hover {
            background: var(--bg-light);
            padding-left: 8px;
        }

        .settings-item:last-child {
            border-bottom: none;
        }

        .settings-item-label {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
        }

        .settings-item-icon {
            font-size: 18px;
        }

        .settings-item-arrow {
            color: var(--text-light);
            font-size: 16px;
        }

        .settings-item.logout-btn {
            color: var(--danger);
            font-weight: 600;
        }

        /* Right Content */
        .content {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Header Section */
        .content-header {
            background: var(--white);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-text h2 {
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .header-text p {
            font-size: 13px;
            color: var(--text-light);
        }

        .btn-edit {
            background: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 10px 24px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-edit:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        /* Details Section */
        .details-section {
            background: var(--white);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-light);
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 14px;
            color: var(--text-dark);
            font-weight: 500;
        }

        /* Booking History Section */
        .booking-section {
            background: var(--white);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-light);
        }

        .booking-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .btn-all-requests {
            background: var(--bg-light);
            color: var(--text-dark);
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-all-requests:hover {
            background: var(--border-light);
        }

        .booking-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .booking-table th {
            background: var(--bg-light);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--text-light);
            border-bottom: 2px solid var(--border-light);
        }

        .booking-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-light);
        }

        .booking-table tr:hover {
            background: var(--bg-light);
        }

        .facility-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .facility-ref {
            font-size: 12px;
            color: var(--text-light);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-confirmed {
            background: #e8f5e9;
            color: var(--success);
        }

        .status-completed {
            background: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background: #ffebee;
            color: var(--danger);
        }

        .action-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .action-link:hover {
            text-decoration: underline;
        }

        .action-cancel {
            color: var(--danger);
        }

        .view-history {
            text-align: center;
            padding: 16px;
            color: var(--text-light);
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .view-history:hover {
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Footer */
        .footer {
            background: var(--text-dark);
            color: var(--white);
            padding: 30px;
            text-align: center;
            margin-top: 50px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .footer-section {
            flex: 1;
            min-width: 200px;
        }

        .footer-section h5 {
            font-size: 14px;
            margin-bottom: 8px;
        }

        .footer-section p {
            font-size: 13px;
            opacity: 0.8;
        }

        .footer-section a {
            color: var(--white);
            text-decoration: none;
            font-size: 13px;
            opacity: 0.8;
            transition: opacity 0.3s;
            margin: 0 12px;
        }

        .footer-section a:hover {
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                padding: 0 16px;
            }

            .navbar {
                flex-direction: column;
                gap: 12px;
                padding: 12px 16px;
            }

            .navbar-left {
                width: 100%;
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .navbar-right {
                width: 100%;
                justify-content: space-between;
            }

            .nav-links {
                flex-direction: column;
                gap: 8px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .content-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            .booking-table {
                font-size: 12px;
            }

            .booking-table th,
            .booking-table td {
                padding: 8px;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin: 16px auto;
            }

            .section-title {
                font-size: 14px;
            }

            .booking-table {
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
<?php $currentPage = 'profile'; include "includes/header.php"; ?>
    

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="homepage.php">Campus</a>
        <span>></span>
        <span>User Profile</span>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Left Sidebar -->
        <div class="sidebar">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar" id="profile-avatar">J</div>
                <div class="profile-name" id="profile-name">-</div>

                <div class="profile-info">
                    <div class="info-item">
                        <div class="info-label">UTM ID</div>
                        <div class="info-value" id="profile-utm-id">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div class="info-value" id="profile-department">Waiting to edit</div>
                    </div>
                </div>
            </div>

            <!-- Settings Card -->
            <div class="settings-card">
                <h3>Profile Settings</h3>
                <div class="settings-item" onclick="navigateTo('notifications')">
                    <div class="settings-item-label">
                        <span class="settings-item-icon">📧</span>
                        <span>Notifications</span>
                    </div>
                    <span class="settings-item-arrow">›</span>
                </div>
                <div class="settings-item" onclick="navigateTo('privacy')">
                    <div class="settings-item-label">
                        <span class="settings-item-icon">🔒</span>
                        <span>Privacy & Security</span>
                    </div>
                    <span class="settings-item-arrow">›</span>
                </div>
                <div class="settings-item logout-btn" onclick="handleLogout()">
                    <div class="settings-item-label">
                        <span class="settings-item-icon">🚪</span>
                        <span>Sign Out</span>
                    </div>
                    <span class="settings-item-arrow">›</span>
                </div>
            </div>
        </div>

        <!-- Right Content -->
        <div class="content">
            <!-- Header Section -->
            <div class="content-header">
                <div class="header-text">
                    <h2>Member Profile</h2>
                    <p>Manage your personal information and track your facility reservations.</p>
                </div>
                <button class="btn-edit" onclick="editProfile()">✏️ Edit Profile</button>
            </div>

            <!-- Personal Details Section -->
            <div class="details-section">
                <div class="section-title">Personal Details</div>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value" id="detail-name">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">University Email</div>
                        <div class="detail-value" id="detail-email">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone Number</div>
                        <div class="detail-value" id="detail-phone">Waiting to edit</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Preferred Campus</div>
                        <div class="detail-value">North Campus Main</div>
                    </div>
                </div>
            </div>

            <!-- Booking History Section -->
            <div class="booking-section">
                <div class="booking-header">
                    <h3>Booking Status & History</h3>
                    <button class="btn-all-requests">All Requests</button>
                </div>
                <p style="font-size: 13px; color: var(--text-light); margin-bottom: 16px;">Manage your current and previous facility reservations.</p>

                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>Facility & ID</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
@@ -806,72 +805,114 @@
                                <div>Jan 05, 2026</div>
                                <div style="color: var(--text-light);">13:00 - 15:00</div>
                            </td>
                            <td><span class="status-badge status-cancelled">Cancelled</span></td>
                            <td><a href="#" class="action-link action-cancel">Cancel</a></td>
                        </tr>
                    </tbody>
                </table>

                <div class="view-history" onclick="viewOlderHistory()">View Older History</div>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>



    <script>
        // Load user data
        document.addEventListener('DOMContentLoaded', async function() {
            const sessionResponse = await fetch('auth_session.php', { credentials: 'same-origin' });
            if (!sessionResponse.ok) { window.location.href = 'login.html'; return; }
            const sessionData = await sessionResponse.json();
            if (!sessionData.authenticated) { window.location.href = 'login.html'; return; }
            await loadProfileData();
        });

        async function loadProfileData() {
            const response = await fetch('profile_data.php', { credentials: 'same-origin' });
            if (!response.ok) {
                alert('Failed to load profile data.');
                return;
            }

            const result = await response.json();
            if (!result.success || !result.user) {
                alert(result.message || 'Failed to load profile data.');
                return;
            }

            const userData = result.user;
            const initials = (userData.full_name || 'U').split(' ').map(n => n[0]).join('').toUpperCase();
            document.getElementById('user-avatar-btn').textContent = initials;
            document.getElementById('profile-avatar').textContent = initials;
            document.getElementById('profile-name').textContent = userData.full_name || '-';
            document.getElementById('detail-name').textContent = userData.full_name || '-';
            document.getElementById('detail-email').textContent = userData.email || '-';
            document.getElementById('profile-utm-id').textContent = userData.utm_id || '-';
            document.getElementById('detail-phone').textContent = userData.phone_number || 'Waiting to edit';
            document.getElementById('profile-department').textContent = 'Waiting to edit';
        }

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

        async function editProfile() {
            const currentName = document.getElementById('detail-name').textContent.trim();
            const currentPhone = document.getElementById('detail-phone').textContent.trim();

            const fullName = prompt('Enter Full Name:', currentName === '-' ? '' : currentName);
            if (fullName === null) return;

            const phoneDefault = currentPhone === 'Waiting to edit' ? '' : currentPhone;
            const phoneNumber = prompt('Enter Phone Number:', phoneDefault);
            if (phoneNumber === null) return;

            const response = await fetch('profile_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    full_name: fullName.trim(),
                    phone_number: phoneNumber.trim()
                })
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                alert(result.message || 'Failed to update profile.');
                return;
            }

            await loadProfileData();
            alert('Profile updated successfully.');
        }

        function navigateTo(page) {
            alert('Navigating to ' + page);
        }

        function viewOlderHistory() {
            alert('Loading older booking history...');
        }
    </script>
</body>
</html>