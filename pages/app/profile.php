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
            min-height: 100vh;
        }

        .footer {
            width: 100%;
            margin-top: 30px;
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
            max-width: 1420px;
            margin: 30px auto;
            padding: 0 30px;
            display: grid;
            grid-template-columns: minmax(320px, 400px) minmax(0, 1fr);
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
            min-width: 0;
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

                :root {
            --utm-maroon: #800020; /* Deep Maroon Theme color */
            --utm-maroon-hover: #600018;
        }

        :root {
            --utm-maroon: #800020;
            --utm-maroon-hover: #600018;
        }
        .utm-card-upload-container {
            background: #fdfdfd;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 8px;
        }
        .utm-card-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .utm-upload-card-box {
            flex: 1;
            min-width: 240px;
            height: 150px;
            border: 2px dashed var(--utm-maroon);
            border-radius: 8px;
            background: #fff8f9;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .utm-upload-card-box:hover {
            background: #f5e6e8;
            transform: translateY(-2px);
        }
        .upload-card-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--utm-maroon);
            font-weight: 500;
            gap: 8px;
        }
        .upload-card-icon { font-size: 24px; }
        .upload-card-text { font-size: 13px; }
        .card-preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0; left: 0;
            display: none;
        }
        .btn-upload-card-maroon {
            background-color: var(--utm-maroon);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-upload-card-maroon:hover { background-color: var(--utm-maroon-hover); }
        .utm-card-status { font-size: 12px; color: #666; margin-top: 8px; }

        /* Booking History Section */
        .booking-section {
            background: var(--white);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
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
            min-width: 780px;
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

        .status-approved {
            background: #e8f5e9;
            color: var(--success);
        }

        .status-pending {
            background: #fff8e1;
            color: var(--warning);
        }

        .status-completed {
            background: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background: #ffebee;
            color: var(--danger);
        }

        .payment-unavailable {
            color: var(--text-light);
            font-weight: 600;
        }

        .payment-paid {
            background: #e8f5e9;
            color: var(--success);
        }

        .btn-payment {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 7px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.3s;
        }

        .btn-payment:hover {
            background: var(--primary-hover);
        }

        .payment-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 1000;
        }

        .payment-modal {
            width: min(520px, 100%);
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.22);
            padding: 24px;
        }

        .payment-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .payment-modal-header h3 {
            font-size: 18px;
            color: var(--text-dark);
        }

        .payment-modal-close {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 50%;
            background: var(--bg-light);
            color: var(--text-dark);
            cursor: pointer;
            font-size: 20px;
            line-height: 1;
        }

        .payment-instruction {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 18px;
        }

        .payment-reference {
            background: var(--bg-light);
            border: 1px solid var(--border-light);
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 18px;
            font-size: 13px;
            color: var(--text-dark);
        }

        .payment-modal-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .payment-upload-status {
            color: var(--text-light);
            font-size: 12px;
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
        <a href="../../homepage.php">Home</a>
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
    <div class="detail-label">UTM Card Verification</div>
    <div class="utm-card-upload-container">
        
        <input type="file" id="card-front-input" accept="image/*" style="display:none;">
        <input type="file" id="card-back-input" accept="image/*" style="display:none;">

        <div class="utm-card-row">
            <div class="utm-upload-card-box" onclick="triggerCardUpload('front')" id="card-front-box">
                <div class="upload-card-placeholder" id="front-placeholder">
                    <span class="upload-card-icon">➕</span>
                    <span class="upload-card-text">Upload Front Side</span>
                </div>
                <img id="card-front-preview" class="card-preview-img" alt="Front Preview">
            </div>

            <div class="utm-upload-card-box" onclick="triggerCardUpload('back')" id="card-back-box">
                <div class="upload-card-placeholder" id="back-placeholder">
                    <span class="upload-card-icon">➕</span>
                    <span class="upload-card-text">Upload Back Side</span>
                </div>
                <img id="card-back-preview" class="card-preview-img" alt="Back Preview">
            </div>
        </div>

        <div class="utm-action-footer">
            <button type="button" class="btn-upload-card-maroon" onclick="submitUtmCards()">🪪 Submit UTM Cards for Verification</button>
            <div class="utm-card-status" id="utm-card-status">
                Upload clear front and back images of your UTM card (Max 5MB each). Verification updates after submission.
            </div>
        </div>
    </div>
</div>

                </div>
            </div>

            <div class="booking-section">
                <div class="booking-header">
                    <h3>Booking Status & History</h3>
                    <button class="btn-all-requests" onclick="window.location.href='booking-history.php'">All Requests</button>
                </div>
                <p style="font-size: 13px; color: var(--text-light); margin-bottom: 16px;">Manage your current and previous facility reservations.</p>

                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>Facility & ID</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Payment</th>
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

    <div class="payment-modal-backdrop" id="payment-modal-backdrop">
        <div class="payment-modal" role="dialog" aria-modal="true" aria-labelledby="payment-modal-title">
            <div class="payment-modal-header">
                <h3 id="payment-modal-title">Complete Payment</h3>
                <button type="button" class="payment-modal-close" onclick="closePaymentModal()" aria-label="Close payment dialog">×</button>
            </div>
            <p class="payment-instruction">
                You may manually made a payment by transferring to official bank account number:
                <strong>202620270382</strong> with reference:
                <strong>"SPACEBOOK" + "booking_id"</strong>
            </p>
            <div class="payment-reference" id="payment-reference-text"></div>
            <input type="file" id="payment-receipt-input" accept="image/jpeg,image/png,image/webp,application/pdf" style="display:none;">
            <div class="payment-modal-actions">
                <button type="button" class="btn-payment" onclick="triggerReceiptUpload()">Upload Receipt</button>
                <span class="payment-upload-status" id="payment-upload-status">JPG, PNG, WEBP, or PDF. Max 5MB.</span>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>



    <script>
    let isEditMode = false;
    let pendingAvatarBase64 = "";
    
    // Clean individual staging containers for both card sides
    let pendingFrontCardBase64 = "";
    let pendingFrontCardMime = "";
    let pendingBackCardBase64 = "";
    let pendingBackCardMime = "";
    let currentVerificationStatus = 'unverified';
    let activePaymentBookingId = null;
    
    // Initial Load Hook
    document.addEventListener('DOMContentLoaded', async function() {
        const sessionResponse = await fetch('../../api/auth/auth_session.php', { credentials: 'same-origin' });
        if (!sessionResponse.ok) { window.location.href = '../auth/login.php'; return; }
        const sessionData = await sessionResponse.json();
        if (!sessionData.authenticated) { window.location.href = '../auth/login.php'; return; }
        await loadProfileData();
        setupCardListeners(); // Initialize modern side listeners safely
        setupReceiptUpload();
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
        document.getElementById('detail-gender').textContent = userData.gender || 'Waiting to edit';
        document.getElementById('detail-address').textContent = userData.address || 'Waiting to edit';

        const verificationStatus = userData.verification_status === 'verified' ? 'verified' : 'unverified';
        currentVerificationStatus = verificationStatus;
        const verificationBadge = document.getElementById('detail-verification-status');
        verificationBadge.textContent = verificationStatus === 'verified' ? 'Verified' : 'Unverified';
        verificationBadge.className = 'verification-badge ' + verificationStatus;
        
        document.getElementById('utm-card-status').textContent = Number(userData.has_utm_card) === 1
            ? 'UTM card uploaded. Your profile is verified.'
            : 'Upload clear front and back images of your UTM card (Max 5MB each).';
        
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

        bookings.slice(0, 5).forEach((booking) => {
            const startDate = new Date(booking.booking_start);
            const endDate = new Date(booking.booking_end);
            const facilityName = booking.resource_type === 'room'
                ? (booking.room_name || 'Unknown Room')
                : (booking.facility_name || 'Unknown Facility');
            const normalizedStatus = (booking.booking_status || '').toLowerCase();
            const statusClass = `status-${normalizedStatus}`;
            const statusLabel = normalizedStatus.charAt(0).toUpperCase() + normalizedStatus.slice(1);
            const canCancel = normalizedStatus === 'pending' || normalizedStatus === 'approved';
            const actionHTML = canCancel
                ? `<button type="button" class="action-link action-cancel" onclick="cancelBooking(${Number(booking.booking_id)})">Cancel Booking</button>`
                : '<span class="action-link">-</span>';
            const totalPrice = Number(booking.total_price || 0);
            const paymentStatus = (booking.payment_status || '').toLowerCase();
            let paymentHTML = '<span class="payment-unavailable">Unavailable</span>';
            if (totalPrice > 0 && paymentStatus === 'paid') {
                paymentHTML = '<span class="status-badge payment-paid">Paid</span>';
            } else if (totalPrice > 0 && paymentStatus === 'unpaid') {
                paymentHTML = `<button type="button" class="btn-payment" onclick="openPaymentModal(${Number(booking.booking_id)})">Complete Payment</button>`;
            } else if (totalPrice > 0 && paymentStatus) {
                const paymentLabel = paymentStatus
                    .split('_')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
                paymentHTML = `<span class="status-badge status-pending">${paymentLabel}</span>`;
            }

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
                <td>${paymentHTML}</td>
                <td>${actionHTML}</td>
            `;
            tbody.appendChild(row);
        });
    }

    function openPaymentModal(bookingId) {
        activePaymentBookingId = bookingId;
        document.getElementById('payment-reference-text').innerHTML =
            `<strong>Booking ID:</strong> ${bookingId}<br><strong>Suggested reference:</strong> SPACEBOOK${bookingId}`;
        document.getElementById('payment-upload-status').textContent = 'JPG, PNG, WEBP, or PDF. Max 5MB.';
        document.getElementById('payment-receipt-input').value = '';
        document.getElementById('payment-modal-backdrop').style.display = 'flex';
    }

    function closePaymentModal() {
        activePaymentBookingId = null;
        document.getElementById('payment-modal-backdrop').style.display = 'none';
    }

    function triggerReceiptUpload() {
        if (!activePaymentBookingId) return;
        document.getElementById('payment-receipt-input').click();
    }

    function setupReceiptUpload() {
        const receiptInput = document.getElementById('payment-receipt-input');
        receiptInput.addEventListener('change', async function(event) {
            const file = event.target.files[0];
            if (!file || !activePaymentBookingId) return;

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                alert('Receipt must be JPG, PNG, WEBP, or PDF.');
                event.target.value = '';
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                alert('Receipt file must be 5MB or smaller.');
                event.target.value = '';
                return;
            }

            const uploadStatus = document.getElementById('payment-upload-status');
            uploadStatus.textContent = 'Uploading receipt...';

            const formData = new FormData();
            formData.append('id', String(activePaymentBookingId));
            formData.append('receipt', file);

            const response = await fetch('../../api/booking/upload_receipt.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                uploadStatus.textContent = 'Upload failed.';
                alert(result.message || 'Failed to upload receipt.');
                return;
            }

            uploadStatus.textContent = 'Receipt uploaded. Pending verification.';
            await loadProfileData();
            setTimeout(closePaymentModal, 600);
        });
    }

    // Trigger Hidden Card Inputs Click Event
    function triggerCardUpload(side) {
        document.getElementById(`card-${side}-input`).click();
    }

    // Modern Isolated Setup Listeners Event Registration Engine
    function setupCardListeners() {
        ['front', 'back'].forEach(side => {
            const element = document.getElementById(`card-${side}-input`);
            if(!element) return;
            
            element.addEventListener('change', async function(event) {
                const file = event.target.files[0];
                if (!file) return;

                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('UTM card must be JPG, PNG, or WEBP.');
                    event.target.value = '';
                    return;
                }
                
                // Enforced 5MB footprint verification rule
                if (file.size > 5 * 1024 * 1024) {
                    alert('UTM card image must be 5MB or smaller.');
                    event.target.value = '';
                    return;
                }

                const dataUrl = await fileToDataUrl(file);
                const base64Str = String(dataUrl).split(',')[1] || '';

                if (side === 'front') {
                    pendingFrontCardBase64 = base64Str;
                    pendingFrontCardMime = file.type;
                } else {
                    pendingBackCardBase64 = base64Str;
                    pendingBackCardMime = file.type;
                }

                // UI Status Update & Live Preview Insertion
                const previewImg = document.getElementById(`card-${side}-preview`);
                const placeholder = document.getElementById(`${side}-placeholder`);
                previewImg.src = dataUrl;
                previewImg.style.display = 'block';
                placeholder.style.display = 'none';
            });
        });
    }

    // Profile submission engine passing both images via JSON payload
    async function submitUtmCards() {
        if (!pendingFrontCardBase64 || !pendingBackCardBase64) {
            alert('Please pick files for both Front and Back card boxes before submitting.');
            return;
        }

        document.getElementById('utm-card-status').textContent = 'Uploading card channels and updating status...';

        const phoneNumber = document.getElementById('detail-phone').textContent.trim() === 'Waiting to edit' ? '' : document.getElementById('detail-phone').textContent.trim();
        const department = document.getElementById('detail-department').textContent.trim() === 'Waiting to edit' ? '' : document.getElementById('detail-department').textContent.trim();
        const gender = document.getElementById('detail-gender').textContent.trim() === 'Waiting to edit' ? '' : document.getElementById('detail-gender').textContent.trim();
        const address = document.getElementById('detail-address').textContent.trim() === 'Waiting to edit' ? '' : document.getElementById('detail-address').textContent.trim();

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
                card_front_base64: pendingFrontCardBase64,
                card_front_mime: pendingFrontCardMime,
                card_back_base64: pendingBackCardBase64,
                card_back_mime: pendingBackCardMime
            })
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            alert(result.message || 'Failed to complete card verification update.');
            return;
        }

        // Wipe parameters trace memory space upon success
        pendingFrontCardBase64 = pendingFrontCardMime = pendingBackCardBase64 = pendingBackCardMime = '';
        alert('UTM Card files submitted successfully!');
        await loadProfileData();
    }

    // Trigger profile avatar handler context
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

    // Helper Promises Converters
    function fileToDataUrl(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
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

    function toggleProfileEdit() {
        if (!isEditMode) {
            if (pendingAvatarBase64) { saveProfileEdits(); return; }
            enterEditMode();
        } else {
            saveProfileEdits();
        }
    }

    function enterEditMode() {
        isEditMode = true;

        const phoneElement = document.getElementById('detail-phone');
        const phoneValue = phoneElement.textContent.trim() === 'Waiting to edit' ? '' : phoneElement.textContent.trim();
        phoneElement.innerHTML = `<input type="text" id="detail-phone-input" value="${phoneValue}" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">`;

        const departmentElement = document.getElementById('detail-department');
        const currentDepartment = departmentElement.textContent.trim();
        if (currentVerificationStatus !== 'verified') {
            const departments = [
                'Faculty of Artificial Intelligence (FAI)',
                'Faculty of Built Environment and Surveying',
                'Faculty of Chemical & Energy Engineering',
                'Faculty of Computing',
                'Faculty of Educational Sciences and Technology (FEST)',
                'Faculty of Electrical Engineering',
                'Faculty of Management',
                'Faculty of Mechanical Engineering',
                'Faculty of Civil Engineering',
                'Faculty of Science',
                'Faculty of Social Sciences and Humanities',
                'SPACE UTM (School of Professional and Continuing Education)'
            ];
            const departmentOptions = departments.map(department => {
                const selected = currentDepartment === department ? 'selected' : '';
                return `<option value="${department}" ${selected}>${department}</option>`;
            }).join('');
            departmentElement.innerHTML = `
                <select id="detail-department-input" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;background:#fff;">
                    <option value="" ${currentDepartment === 'Waiting to edit' || currentDepartment === '' ? 'selected' : ''}>Select Department</option>
                    ${departmentOptions}
                </select>
            `;
        }

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
                avatar_base64: pendingAvatarBase64
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
        alert('Profile updated successfully.');
    }

    function cancelBooking() {}
    function navigateTo(page) { alert('Navigating to ' + page); }
    function viewOlderHistory() { window.location.href = 'booking-history.php'; }
</script>
</body>
</html>
