<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - UNIRESERVE</title>
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
            --error: #d32f2f;
            --success: #388e3c;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            color: var(--text-dark);
            background: var(--white);
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
            position: relative;
        }

        .nav-link:hover {
            color: var(--accent-color);
        }

        .nav-link.active {
            color: var(--accent-color);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--accent-color);
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
            transition: transform 0.3s;
            color: var(--white);
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
            color: var(--error);
        }

        .dropdown-divider {
            margin: 8px 0;
            border: none;
            border-top: 1px solid var(--border-light);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(139, 21, 56, 0.5), rgba(139, 21, 56, 0.5)),
                    url('utm-gate.png');
            background-size: cover;
            background-position: center;
            padding: 60px 30px;
            position: relative;
            color: var(--white);
        }
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.2);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-content h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .hero-content > p:first-of-type {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .hero-description {
            font-size: 14px;
            max-width: 600px;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .search-bar {
            display: flex;
            gap: 12px;
            background: var(--white);
            padding: 16px;
            border-radius: 8px;
            max-width: 600px;
        }

        .search-input-group {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 12px;
            border-right: 1px solid var(--border-light);
        }

        .search-icon {
            font-size: 18px;
        }

        .search-input-group input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 14px;
            background: transparent;
        }

        .location-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px;
            border-right: 1px solid var(--border-light);
        }

        .location-icon {
            font-size: 16px;
        }

        .location-selector select {
            border: none;
            outline: none;
            background: transparent;
            font-size: 13px;
            color: var(--text-dark);
            cursor: pointer;
        }

        .date-picker {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px;
        }

        .calendar-icon {
            font-size: 16px;
        }

        .date-picker input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 13px;
        }

        .btn-find-spaces {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 10px 24px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-find-spaces:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        /* Categories Section */
        .categories-section {
            padding: 50px 30px;
            background: var(--white);
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            margin-bottom: 40px;
        }

        .section-header h3 {
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .section-header p {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 12px;
        }

        .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            position: relative;
            margin-bottom: 30px;
        }

        .category-card {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: 8px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .category-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }

        .category-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .category-card p {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .btn-add-facility {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(139, 21, 56, 0.3);
            transition: all 0.3s;
        }

        .btn-add-facility:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(139, 21, 56, 0.4);
        }

        /* Featured Section */
        .featured-section {
            padding: 50px 30px;
            background: var(--bg-light);
            max-width: 1200px;
            margin: 0 auto;
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
            position: relative;
        }

        .featured-card {
            background: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            position: relative;
        }

        .featured-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
        }

        .card-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--accent-color);
            color: var(--text-dark);
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            z-index: 10;
        }

        .featured-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .card-content {
            padding: 20px;
        }

        .card-content h4 {
            font-size: 16px;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .facility-name {
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .facility-description {
            font-size: 13px;
            color: var(--text-light);
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .facility-features {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .feature {
            font-size: 12px;
            color: var(--text-light);
            background: var(--bg-light);
            padding: 6px 12px;
            border-radius: 4px;
        }

        .facility-meta {
            margin-bottom: 16px;
        }

        .limited-availability {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .price-free {
            display: inline-block;
            background: #e8f5e9;
            color: var(--success);
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border-light);
        }

        .price {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .btn-check-availability,
        .btn-book-facility,
        .btn-reserve {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-check-availability:hover,
        .btn-book-facility:hover,
        .btn-reserve:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .carousel-nav {
            display: flex;
            justify-content: center;
            gap: 16px;
        }

        .nav-arrow {
            width: 40px;
            height: 40px;
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }

        .nav-arrow:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(139, 21, 56, 0.2);
        }

        /* Why Book Section */
        .why-book-section {
            padding: 50px 30px;
            background: var(--white);
            text-align: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .why-book-section h3 {
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .section-subtitle {
            color: var(--text-light);
            font-size: 14px;
            max-width: 600px;
            margin: 0 auto 40px;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .benefit-card {
            background: var(--bg-light);
            padding: 30px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .benefit-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .benefit-icon {
            font-size: 40px;
            margin-bottom: 16px;
        }

        .benefit-card h4 {
            font-size: 16px;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .benefit-card p {
            font-size: 13px;
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            background: var(--text-dark);
            color: var(--white);
            padding: 30px;
            text-align: center;
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

            .search-bar {
                flex-direction: column;
            }

            .search-input-group,
            .location-selector,
            .date-picker {
                border: none;
                padding: 0;
                width: 100%;
            }

            .hero {
                padding: 30px 20px;
            }

            .hero-content h2 {
                font-size: 24px;
            }

            .categories-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .featured-grid {
                grid-template-columns: 1fr;
            }

            .btn-add-facility {
                bottom: 20px;
                right: 20px;
                width: 44px;
                height: 44px;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero-content h2 {
                font-size: 18px;
            }

            .search-bar {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
<?php $currentPage = 'home'; include "includes/header.php"; ?>
    

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h2>RESERVE YOUR EXCELLENCE</h2>
            <p>Streamlined Booking for Academic Success.</p>
            <p class="hero-description">Access world-class lecture halls, focused study pods, and specialized research facilities across all university campuses with a single click.</p>

            <div class="search-bar">
                <div class="search-input-group">
                    <span class="search-icon">🔍</span>
                    <input type="text" placeholder="Search facilities...">
                </div>
                <div class="location-selector">
                    <span class="location-icon">📍</span>
                    <select>
                        <option>All Locations</option>
                        <option>Campus A</option>
                        <option>Campus B</option>
                        <option>Campus C</option>
                    </select>
                </div>
                <div class="date-picker">
                    <span class="calendar-icon">📅</span>
                    <input type="date">
                </div>
                <button class="btn-find-spaces">Find Available Spaces</button>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section">
        <div class="section-header">
            <h3>Browse by Category</h3>
            <p>Find the perfect space for your specific academic needs.</p>
            <a href="#" class="view-all">View All Categories →</a>
        </div>

        <div class="categories-grid">
            <div class="category-card" onclick="navigateTo('lecture-halls')">
                <div class="category-icon">🎤</div>
                <p>Lecture Halls</p>
            </div>
            <div class="category-card" onclick="navigateTo('study-pods')">
                <div class="category-icon">📚</div>
                <p>Study Pods</p>
            </div>
            <div class="category-card" onclick="navigateTo('labs')">
                <div class="category-icon">🔬</div>
                <p>Labs</p>
            </div>
            <div class="category-card" onclick="navigateTo('meeting-rooms')">
                <div class="category-icon">👥</div>
                <p>Meeting Rooms</p>
            </div>
            <div class="category-card" onclick="navigateTo('sports')">
                <div class="category-icon">⚽</div>
                <p>Sports</p>
            </div>
            <div class="category-card" onclick="navigateTo('theatres')">
                <div class="category-icon">🎭</div>
                <p>Theatres</p>
            </div>
        </div>
    </section>

    <!-- Featured Spaces Section -->
    <section class="featured-section">
        <div class="section-header">
            <h3>Featured Spaces</h3>
        </div>

        <div class="featured-grid">
            <div class="featured-card">
                <div class="card-badge">Priority Booking</div>
                <img src="stadium.png" alt="Control Hall">
                <div class="card-content">
                    <h4>Stadium</h4>
                    <p class="facility-name">Stadium Azman Hashim(UTM)</p>
                    <p class="facility-description">A multi-purpose stadium equipped with a quality sound system, spectator seating, and sports facilities. Suitable for sporting events, university ceremonies, large gatherings, and outdoor activities.</p>
                    <div class="facility-features">
                        <span class="feature">👥 500 Seats</span>   
                        <span class="feature">📶 Free Internet</span>
                        <span class="feature">🔌 Power Outlets</span>
                    </div>
                    <div class="card-footer">
                        <span class="price">RM 150 / hour</span>
                        <button class="btn-check-availability" onclick="checkAvailability('Stadium')">Check Availability</button>
                    </div>
                </div>
            </div>

            <div class="featured-card">
                <img src="roomt05.png" alt="Study Pod">
                <div class="card-content">
                    <h4>Room T05</h4>
                    <p class="facility-description">Sound-isolated acoustic pod with whiteboards and collaborative display screens.</p>
                    <div class="facility-meta">
                        <span class="price-free">Free (Student)</span>
                    </div>
                    <button class="btn-book-facility" onclick="bookFacility('Room T05')">Book Pod</button>
                </div>
            </div>

            <div class="featured-card">
                <img src="astanahall.png" alt="Biotech Lab">
                <div class="card-content">
                    <h4>Astana Hall KTC</h4>
                    <p class="facility-description">A functional hall equipped with a sound system, stage area, and seating arrangements. Suitable for student activities, meetings, small events, and college functions.</p>
                    <div class="facility-meta">
                        <span class="limited-availability">Limited Availability</span>
                    </div>
                    <div class="card-footer">
                        <span class="price">RM 25 / hour</span>
                        <button class="btn-reserve" onclick="reserveFacility('Astana Hall KTC')">Reserve Hall</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="carousel-nav">
            <button class="nav-arrow prev" onclick="prevSlide()">◀</button>
            <button class="nav-arrow next" onclick="nextSlide()">▶</button>
        </div>
    </section>

    <!-- Why Book Section -->
    <section class="why-book-section">
        <h3>Why Book With UniReserve?</h3>
        <p class="section-subtitle">The official platform for all University of Toronto Mississauga campus facilities, ensuring fairness and efficiency.</p>

        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">⚡</div>
                <h4>Instant Confirmation</h4>
                <p>Get immediate booking confirmation for most study spaces and meeting rooms without manual approval wait times.</p>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon">💳</div>
                <h4>Seamless Billing</h4>
                <p>Faculty and students can use university IDs for internal charging, while external visitors can use secure credit card processing.</p>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon">🔐</div>
                <h4>24/7 Facility Access</h4>
                <p>Manage your bookings on the go and gain smart-lock access to designated areas during approved reservation windows.</p>
            </div>
        </div>
    </section>

    <?php include "includes/footer.php"; ?>



    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const sessionResponse = await fetch('auth_session.php', { credentials: 'same-origin' });
            if (!sessionResponse.ok) { window.location.href = 'login.html'; return; }
            const sessionData = await sessionResponse.json();
            if (!sessionData.authenticated) { window.location.href = 'login.html'; return; }
            const userData = sessionData.user || {};
            
            if (userData.full_name) {
                const initials = userData.full_name.split(' ').map(n => n[0]).join('').toUpperCase();
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

        function navigateTo(category) { alert('Navigating to ' + category); }
        function addNewFacility() { alert('Add new facility feature coming soon!'); }
        function checkAvailability(facility) { alert('Checking availability for ' + facility); }
        function bookFacility(facility) { alert('Booking ' + facility); }
        function reserveFacility(facility) { alert('Reserving ' + facility); }
        function prevSlide() { alert('Previous slide'); }
        function nextSlide() { alert('Next slide'); }
    </script>
</body>
</html>
