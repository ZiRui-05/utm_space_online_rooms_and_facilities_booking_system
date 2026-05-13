<?php
$currentPage = $currentPage ?? '';
?>
<style>
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
    .navbar-left { display: flex; align-items: center; gap: 40px; }
    .navbar-logo { font-size: 18px; font-weight: 700; color: var(--white); letter-spacing: 1px; margin: 0; }
    .nav-links { display: flex; gap: 30px; }
    .nav-link {
        color: var(--white);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: color 0.3s;
        position: relative;
    }
    .nav-link:hover, .nav-link.active { color: var(--accent-color); }
    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 100%;
        height: 2px;
        background: var(--accent-color);
    }
    .navbar-right { display: flex; align-items: center; gap: 20px; }
    .icon-button { background: none; border: none; font-size: 20px; cursor: pointer; transition: transform 0.3s; color: var(--white); }
    .icon-button:hover { transform: scale(1.1); }
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
    .btn-book-now:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3); }
    .user-dropdown { position: relative; }
    .user-avatar {
        width: 36px; height: 36px; border-radius: 50%; background: var(--white);
        color: var(--primary-color); border: none; font-weight: 700; cursor: pointer; transition: all 0.3s;
    }
    .user-avatar:hover { transform: scale(1.1); }
    .dropdown-menu {
        position: absolute; top: 45px; right: 0; background: var(--white); border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); min-width: 200px; padding: 8px 0; z-index: 1000;
    }
    .dropdown-item {
        display: block; width: 100%; padding: 12px 20px; text-align: left; border: none;
        background: none; cursor: pointer; font-size: 13px; color: var(--text-dark); text-decoration: none; transition: background 0.3s;
    }
    .dropdown-item:hover { background: var(--bg-light); }
    .dropdown-item.logout { color: var(--error); }
    .dropdown-divider { margin: 8px 0; border: none; border-top: 1px solid var(--border-light); }
    @media (max-width: 768px) {
        .navbar { flex-direction: column; gap: 12px; padding: 12px 16px; }
        .navbar-left { width: 100%; flex-direction: column; gap: 12px; align-items: flex-start; }
        .navbar-right { width: 100%; justify-content: space-between; }
        .nav-links { flex-direction: column; gap: 8px; }
    }
</style>
<nav class="navbar">
    <div class="navbar-left">
        <h1 class="navbar-logo">UNIRESERVE</h1>
        <div class="nav-links">
            <a href="homepage.php" class="nav-link<?= $currentPage === 'home' ? ' active' : '' ?>">Home</a>
            <a href="booking.php" class="nav-link<?= $currentPage === 'booking' ? ' active' : '' ?>">Rooms</a>
            <a href="#" class="nav-link">Facilities</a>
        </div>
    </div>

    <div class="navbar-right">
        <button class="icon-button" title="Notifications">🔔</button>
        <button class="icon-button" title="Settings">⚙️</button>
        <button class="btn-book-now" onclick="window.location.href='booking.php'">Book Now</button>

        <div class="user-dropdown">
            <button class="user-avatar" id="user-avatar-btn" onclick="toggleUserMenu()">S</button>
            <div class="dropdown-menu" id="user-menu" style="display:none;">
                <a href="profile.php" class="dropdown-item">👤 My Profile</a>
                <a href="#" class="dropdown-item">📋 My Bookings</a>
                <a href="#" class="dropdown-item">⚙️ Settings</a>
                <a href="#" class="dropdown-item">❓ Help</a>
                <hr class="dropdown-divider">
                <a href="login.html" class="dropdown-item logout" onclick="handleLogout()">🚪 Logout</a>
            </div>
        </div>
    </div>
</nav>