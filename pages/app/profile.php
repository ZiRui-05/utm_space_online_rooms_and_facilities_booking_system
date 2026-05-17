<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - SPACEBOOK</title>
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

        .profile-avatar-container {
            width: 206px;
            height: 296px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid var(--accent-color);
            border-radius: 8px;
            overflow: hidden;
            background: #f7f7f7;
            cursor: pointer;
        }

        .profile-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .profile-avatar-fallback {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            font-weight: 700;
        }

        .avatar-upload-btn {
            margin-top: 8px;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            background: #fff;
            border-radius: 4px;
            font-size: 12px;
            padding: 8px 10px;
            cursor: pointer;
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

        /* Added special rule to allow address field to occupy full row space */
        .full-width-field {
            grid-column: span 2;
        }

        .verification-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .verification-badge.verified {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .verification-badge.unverified {
            background: #fff3e0;
            color: #ef6c00;
        }

        .utm-card-upload-box {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            padding: 14px;
            border: 1px dashed var(--border-light);
            border-radius: 8px;
            background: var(--bg-light);
        }

        .btn-upload-card {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 4px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-upload-card:hover {
            background: var(--primary-hover);
        }

        .utm-card-status {
            font-size: 13px;
            color: var(--text-light);
            line-height: 1.5;
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

        .booking-empty {
            min-height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #b3b3b3;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                padding: 0 16px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .full-width-field {
                grid-column: span 1;
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
<?php $currentPage = 'profile'; include __DIR__ . '/../../includes/header.php'; ?>
    

    <div class="breadcrumb">
        <a href="../../homepage.php">Campus</a>
        <span>&gt;</span>
        <span>User Profile</span>
    </div>

    <div class="container">
        <div class="sidebar">
            <div class="profile-card">
                <div class="profile-avatar-container" onclick="triggerAvatarUpload()" title="Click to change avatar">
                    <img class="profile-avatar" id="profile-avatar" alt="Profile Avatar">
                    <div class="profile-avatar-fallback" id="profile-avatar-fallback">J</div>
                </div>
                <input type="file" id="avatar-input" accept="image/*" style="display:none;">
                <div class="profile-name" id="profile-name">-</div>

                <div class="profile-info">
                    <div class="info-item">
                        <div class="info-label">UTM ID</div>
                        <div class="info-value" id="profile-utm-id">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Identity Number</div>
                        <div class="info-value" id="profile-icno">-</div>
                    </div>
                </div>
            </div>

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

        <div class="content">
            <div class="content-header">
                <div class="header-text">
                    <h2>Member Profile</h2>
                    <p>Manage your personal information and track your facility reservations.</p>
                </div>
                <button class="btn-edit" id="btn-edit-profile" onclick="toggleProfileEdit()">✏️ Edit Profile</button>
            </div>

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
                        <div class="detail-value editable-field" id="detail-phone">Waiting to edit</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Department</div>
                        <div class="detail-value editable-field" id="detail-department">Waiting to edit</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Gender</div>
                        <div class="detail-value editable-field" id="detail-gender">Waiting to edit</div>
                    </div>
                    <div class="detail-item full-width-field">
                        <div class="detail-label">Address</div>
                        <div class="detail-value editable-field" id="detail-address">Waiting to edit</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Verification Status</div>
                        <div class="detail-value"><span id="detail-verification-status" class="verification-badge unverified">Unverified</span></div>
                    </div>
                    <div class="detail-item full-width-field">
                        <div class="detail-label">UTM Card</div>
                        <div class="utm-card-upload-box">
                            <input type="file" id="utm-card-input" accept="image/jpeg,image/png,image/webp" style="display:none;">
                            <button type="button" class="btn-upload-card" onclick="triggerUtmCardUpload()">🪪 Upload UTM Card</button>
                            <div class="utm-card-status" id="utm-card-status">Upload a clear JPG, PNG, or WEBP image of your UTM card. Verification becomes verified after upload.</div>
                        </div>
                    </div>
                </div>
            </div>

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
                    <tbody id="booking-table-body"></tbody>
                </table>

                <div id="booking-empty" class="booking-empty" style="display: none;">You haven't booked any rooms and facilities yet.</div>

                <div id="view-history" class="view-history" onclick="viewOlderHistory()">View Older History</div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>



    <script>
        let isEditMode = false;
        let pendingAvatarBase64 = "";
        let pendingUtmCardBase64 = "";
        let pendingUtmCardMime = "";
        
        // Load user data
        document.addEventListener('DOMContentLoaded', async function() {
            const sessionResponse = await fetch('../../api/auth/auth_session.php', { credentials: 'same-origin' });
            if (!sessionResponse.ok) { window.location.href = '../auth/login.html'; return; }
            const sessionData = await sessionResponse.json();
            if (!sessionData.authenticated) { window.location.href = '../auth/login.html'; return; }
            await loadProfileData();
        });

        async function loadProfileData() {
            const response = await fetch('../../api/user/profile_data.php', { credentials: 'same-origin' });
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
            const avatarImg = document.getElementById('profile-avatar');
            const avatarFallback = document.getElementById('profile-avatar-fallback');
            avatarFallback.textContent = initials;
            
            if (userData.profile_image_base64 && userData.profile_image_mime) {
                avatarImg.src = `data:${userData.profile_image_mime};base64,${userData.profile_image_base64}`;
                avatarImg.style.display = 'block';
                avatarFallback.style.display = 'none';
            } else {
                avatarImg.style.display = 'none';
                avatarFallback.style.display = 'flex';
            }
            
            document.getElementById('profile-name').textContent = userData.full_name || '-';
            document.getElementById('detail-name').textContent = userData.full_name || '-';
            document.getElementById('detail-email').textContent = userData.email || '-';
            document.getElementById('profile-utm-id').textContent = userData.utm_id || '-';
            document.getElementById('profile-icno').textContent = userData.ic_no || '-';
            
            document.getElementById('detail-phone').textContent = userData.phone_number || 'Waiting to edit';
            document.getElementById('detail-department').textContent = userData.department || 'Waiting to edit';
            
            // Render Gender, Address, and verification values
            document.getElementById('detail-gender').textContent = userData.gender || 'Waiting to edit';
            document.getElementById('detail-address').textContent = userData.address || 'Waiting to edit';

            const verificationStatus = userData.verification_status === 'verified' ? 'verified' : 'unverified';
            const verificationBadge = document.getElementById('detail-verification-status');
            verificationBadge.textContent = verificationStatus === 'verified' ? 'Verified' : 'Unverified';
            verificationBadge.className = 'verification-badge ' + verificationStatus;
            document.getElementById('utm-card-status').textContent = Number(userData.has_utm_card) === 1
                ? 'UTM card uploaded. Your profile is verified.'
                : 'Upload a clear JPG, PNG, or WEBP image of your UTM card. Verification becomes verified after upload.';
            
            renderBookings(result.bookings || []);
        }

        function renderBookings(bookings) {
            const tbody = document.getElementById('booking-table-body');
            const emptyMessage = document.getElementById('booking-empty');
            const bookingTable = document.querySelector('.booking-table');
            const viewHistory = document.getElementById('view-history');
            tbody.innerHTML = '';

            if (!bookings.length) {
                bookingTable.style.display = 'none';
                emptyMessage.style.display = 'flex';
                viewHistory.style.display = 'none';
                return;
            }

            bookingTable.style.display = 'table';
            emptyMessage.style.display = 'none';
            viewHistory.style.display = 'block';

            bookings.forEach((booking) => {
                const startDate = new Date(booking.booking_start);
                const endDate = new Date(booking.booking_end);
                const facilityName = booking.resource_type === 'room'
                    ? (booking.room_name || 'Unknown Room')
                    : (booking.facility_name || 'Unknown Facility');
                const statusClass = `status-${booking.booking_status}`;
                const statusLabel = booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1);

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <div class="facility-name">${facilityName}</div>
                        <div class="facility-ref">Ref: BK-${booking.booking_id}</div>
                    </td>
                    <td>
                        <div>${startDate.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })}</div>
                        <div style="color: var(--text-light);">${startDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })} - ${endDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })}</div>
                    </td>
                    <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
                    <td><span class="action-link">-</span></td>
                `;
                tbody.appendChild(row);
            });
        }

        function toggleProfileEdit() {
            if (!isEditMode) {
                if (pendingAvatarBase64) {
                    saveProfileEdits();
                    return;
                }
                enterEditMode();
            } else {
                saveProfileEdits();
            }
        }

        function enterEditMode() {
            isEditMode = true;
            
            // Standard text elements (Phone, Department)
            const textFields = ['detail-phone', 'detail-department'];
            textFields.forEach(id => {
                const element = document.getElementById(id);
                const value = element.textContent.trim() === 'Waiting to edit' ? '' : element.textContent.trim();
                element.innerHTML = `<input type="text" id="${id}-input" value="${value}" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">`;
            });

            // Gender drop-down element
            const genderElement = document.getElementById('detail-gender');
            const currentGender = genderElement.textContent.trim();
            genderElement.innerHTML = `
                <select id="detail-gender-input" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;background:#fff;">
                    <option value="" ${currentGender === 'Waiting to edit' || currentGender === '' ? 'selected' : ''}>Select Gender</option>
                    <option value="Male" ${currentGender === 'Male' ? 'selected' : ''}>Male</option>
                    <option value="Female" ${currentGender === 'Female' ? 'selected' : ''}>Female</option>
                    <option value="Other" ${currentGender === 'Other' ? 'selected' : ''}>Other</option>
                </select>
            `;

            // Address text area element
            const addressElement = document.getElementById('detail-address');
            const currentAddress = addressElement.textContent.trim() === 'Waiting to edit' ? '' : addressElement.textContent.trim();
            addressElement.innerHTML = `<textarea id="detail-address-input" rows="3" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;resize:vertical;">${currentAddress}</textarea>`;

            document.getElementById('btn-edit-profile').textContent = '✅ Save';
        }

        async function saveProfileEdits() {
            const phoneInput = document.getElementById('detail-phone-input');
            const departmentInput = document.getElementById('detail-department-input');
            const genderInput = document.getElementById('detail-gender-input');
            const addressInput = document.getElementById('detail-address-input');

            // Fallbacks gather values if inputs aren't generated yet
            const phoneNumber = phoneInput ? phoneInput.value.trim() : (document.getElementById('detail-phone').textContent.trim() === 'Waiting to edit' ? '' : document.getElementById('detail-phone').textContent.trim());
            const department = departmentInput ? departmentInput.value.trim() : (document.getElementById('detail-department').textContent.trim() === 'Waiting to edit' ? '' : document.getElementById('detail-department').textContent.trim());
            const gender = genderInput ? genderInput.value : (document.getElementById('detail-gender').textContent.trim() === 'Waiting to edit' ? '' : document.getElementById('detail-gender').textContent.trim());
            const address = addressInput ? addressInput.value.trim() : (document.getElementById('detail-address').textContent.trim() === 'Waiting to edit' ? '' : document.getElementById('detail-address').textContent.trim());

            const response = await fetch('../../api/user/profile_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    phone_number: phoneNumber,
                    department: department,
                    gender: gender,
                    address: address,
                    avatar_base64: pendingAvatarBase64,
                    utm_card_base64: pendingUtmCardBase64,
                    utm_card_mime: pendingUtmCardMime
                })
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                alert(result.message || 'Failed to update profile.');
                return;
            }

            isEditMode = false;
            document.getElementById('btn-edit-profile').textContent = '✏️ Edit Profile';
            await loadProfileData();
            pendingAvatarBase64 = '';
            pendingUtmCardBase64 = '';
            pendingUtmCardMime = '';
            alert('Profile updated successfully.');
        }

        function triggerUtmCardUpload() {
            document.getElementById('utm-card-input').click();
        }

        document.getElementById('utm-card-input').addEventListener('change', async function(event) {
            const file = event.target.files[0];
            if (!file) return;

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('UTM card must be JPG, PNG, or WEBP.');
                event.target.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('UTM card image must be 2MB or smaller.');
                event.target.value = '';
                return;
            }

            const dataUrl = await fileToDataUrl(file);
            pendingUtmCardBase64 = String(dataUrl).split(',')[1] || '';
            pendingUtmCardMime = file.type;
            document.getElementById('utm-card-status').textContent = 'Uploading UTM card and updating verification status...';
            event.target.value = '';
            await saveProfileEdits();
        });

        function triggerAvatarUpload() {
            document.getElementById('avatar-input').click();
        }

        document.getElementById('avatar-input').addEventListener('change', async function(event) {
            const file = event.target.files[0];
            if (!file) return;

            const processed = await processAvatarFile(file);
            if (!processed) {
                event.target.value = '';
                return;
            }

            pendingAvatarBase64 = processed.base64;
            const avatarImg = document.getElementById('profile-avatar');
            document.getElementById('profile-avatar-fallback').style.display = 'none';
            avatarImg.src = `data:image/jpeg;base64,${processed.base64}`;
            avatarImg.style.display = 'block';
            event.target.value = '';
        });

        async function processAvatarFile(file) {
            const dataUrl = await fileToDataUrl(file);
            const source = await loadImage(dataUrl);
            const canvas = document.createElement('canvas');
            canvas.width = 413;
            canvas.height = 591;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(source, 0, 0, 413, 591);

            for (let quality = 0.9; quality >= 0.45; quality -= 0.05) {
                const blob = await canvasToBlob(canvas, quality);
                if (blob.size < 100 * 1024) {
                    return { base64: await blobToBase64(blob) };
                }
            }

            alert('Image is too complex. Please choose another photo.');
            return null;
        }

        function fileToDataUrl(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.pointer = () => resolve(reader.result);
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        function loadImage(src) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = src;
            });
        }

        function canvasToBlob(canvas, quality) {
            return new Promise((resolve) => {
                canvas.toBlob((blob) => resolve(blob), 'image/jpeg', quality);
            });
        }

        function blobToBase64(blob) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result.split(',')[1]);
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            });
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