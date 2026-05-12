<?php
$currentPage = $currentPage ?? '';
?>
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
