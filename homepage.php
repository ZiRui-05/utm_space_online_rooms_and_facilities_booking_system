<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - SPACEBOOK </title>
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

        /* Hero Section */
/* Hero Section */
.hero {
    /* Replace 'utm-gate.png' with your correct image path if it's in a different folder */
    background: linear-gradient(rgba(139, 21, 56, 0.65), rgba(139, 21, 56, 0.65)),
                url('assets/images/utm-gate.jpg') no-repeat center center;
    background-size: cover;
    padding: 100px 30px; /* Increased vertical padding to make room for the background */
    position: relative;
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: flex-start;
    min-height: 350px; /* Forces the section to be tall enough to show the image */
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3); /* Darkens the image slightly so white text stays readable */
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2; /* Ensures text stays in front of the background image and overlay */
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
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
            width: 100%;
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
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 20px;

        }

        .featured-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
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
            height: 200px;
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
            width: 100%;
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

        /* Responsive */
        @media (max-width: 768px) {

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
<?php $currentPage = 'home'; include __DIR__ . '/includes/header.php'; ?>


<!-- Header -->
<section class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h2>RESERVE YOUR EXCELLENCE</h2>
        <p>Streamlined Booking for Academic Success.</p>
        <p class="hero-description">Access world-class lecture halls, focused study pods, and specialized research facilities across all university campuses with a single click.</p>

        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section">
    <div class="section-header">
        <h3>Browse by Category</h3>
        <p>Find the perfect space for your specific academic needs.</p>
        <a href="pages/app/room-availability.php" class="view-all">View All Categories →</a>
    </div>

    <div class="categories-grid">
        <div class="category-card" onclick="navigateToCategory('lecture halls')">
            <div class="category-icon">🎤</div>
            <p>Lecture Halls</p>
        </div>
        <div class="category-card" onclick="navigateToCategory('study pods')">
            <div class="category-icon">📚</div>
            <p>Study Pods</p>
        </div>
        <div class="category-card" onclick="navigateToCategory('labs')">
            <div class="category-icon">🔬</div>
            <p>Labs</p>
        </div>
        <div class="category-card" onclick="navigateToCategory('meeting rooms')">
            <div class="category-icon">👥</div>
            <p>Meeting Rooms</p>
        </div>
        <div class="category-card" onclick="navigateToT06Rooms()">
            <div class="category-icon">🏢</div>
            <p>T06 Facility</p>
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
            <img src="T05.jpg" alt="Room T05">
            <div class="card-content">
                <h4>Room T05</h4>
                <p class="facility-description">Sound-isolated acoustic pod with whiteboards and collaborative display screens.</p>
                <div class="facility-meta">
                    <span class="price-free">Free (Student)</span>
                </div>
                <button class="btn-book-facility" onclick="navigateToT05Rooms()">View T05 Rooms</button>
            </div>
        </div>

        <div class="featured-card">
            <img src="T06.jpg" alt="T06 Facility">
            <div class="card-content">
                <h4>T06 Facility</h4>
                <p class="facility-description">T06 facility contains 6 bookable rooms for discussion, study, meetings, and academic activities.</p>
                <div class="facility-meta">
                    <span class="price-free">Free (Student)</span>
                </div>
                <button class="btn-book-facility" onclick="navigateToT06Rooms()">View T06 Rooms</button>
            </div>
        </div>
    </div>

    <div class="carousel-nav" style="margin-top: 30px;">
        <button class="nav-arrow prev" onclick="prevSlide()">◀</button>
        <button class="nav-arrow next" onclick="nextSlide()">▶</button>
    </div>
</section>

<!-- Why Book Section -->
<section class="why-book-section">
    <h3>Why Book With SPACEBOOK?</h3>
    <p class="section-subtitle">The official platform for all University of Technology Malaysia campus facilities, ensuring fairness and efficiency.</p>

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

<?php include __DIR__ . '/includes/footer.php'; ?>



<script>
    function navigateToCategory(category) {
        const normalized = String(category || '').toLowerCase();
        const facilityCategories = ['labs', 'laboratory', 'laboratories'];
        const target = facilityCategories.includes(normalized)
            ? 'pages/app/facilities.php?facility_type=' + encodeURIComponent(category)
            : 'pages/app/room-availability.php?room_type=' + encodeURIComponent(category);
        window.location.href = target;
    }

    function navigateToT05Rooms() {
        window.location.href = 'pages/app/room-availability.php?room_type=study-room&search=T05';
    }

    function navigateToT06Rooms() {
        window.location.href = 'pages/app/room-availability.php?room_type=study-room&search=T06';
    }
    function addNewFacility() { alert('Add new facility feature coming soon!'); }
    function checkAvailability(facility) { window.location.href = 'pages/app/facility_details.php?type=facility&name=' + encodeURIComponent(facility); }
    function bookFacility(facility) { window.location.href = 'pages/app/facility_details.php?type=room&name=' + encodeURIComponent(facility); }
    function reserveFacility(facility) { window.location.href = 'pages/app/facility_details.php?type=facility&name=' + encodeURIComponent(facility); }
    function prevSlide() { alert('Previous slide'); }
    function nextSlide() { alert('Next slide'); }
</script>
</body>
</html>
