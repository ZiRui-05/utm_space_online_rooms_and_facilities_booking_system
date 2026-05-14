<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities - UNIRESERVE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

:root {
    --primary: #8b1538;
    --secondary: #8b1538;
    --accent: #FFD700;
    --text-dark: #333;
    --text-light: #666;
    --border: #ddd;
    --bg-light: #f5f5f5;
    --white: #fff;
}

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            background: var(--bg-light);
        }

        /* Breadcrumb */
        .breadcrumb {
            background: var(--white);
            padding: 12px 30px;
            font-size: 13px;
            color: var(--text-light);
            border-bottom: 1px solid var(--border);
        }

        .breadcrumb a {
            color: var(--secondary);
            text-decoration: none;
        }

        /* Main */
        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 14px;
            max-width: 600px;
        }

        /* Controls */
        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
        }

        .filters {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .filter-btn {
    background: #8b1538;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s;
    white-space: nowrap;
}

.filter-btn:hover {
    background: #a01d48;
}

.filter-btn.active {
    background: #8b1538;
    color: white;
}
        .sort-options {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .sort-select {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            background: var(--white);
        }

        /* Featured Section */
        .featured-section {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
            margin-bottom: 50px;
        }

        .featured-main {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .featured-main img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }

        .featured-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--primary);
            color: var(--white);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .featured-available {
            position: absolute;
            top: 16px;
            right: 16px;
            background: var(--accent);
            color: var(--text-dark);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .featured-content {
            background: var(--white);
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .featured-category {
            color: var(--secondary);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .featured-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .featured-info {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--text-light);
        }

        .featured-info span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .featured-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .featured-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary,
.modal-footer .btn-primary,
.facility-actions .btn-primary {

    background: #8b1538;
    color: white;
    border: none;
}

.btn-primary:hover,
.modal-footer .btn-primary:hover,
.facility-actions .btn-primary:hover {

    background: #8b1538;
}

       .btn-secondary {
    background: #8b1538;
    color: white;
    border: none;
}

.btn-secondary:hover {
    background: #a01d48;
}

        /* Facilities Grid */
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            margin-bottom: 24px;
            margin-top: 40px;
        }

        .facilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .facility-card {
            background: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
        }

        .facility-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }

        .facility-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .facility-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .facility-card:hover .facility-image img {
            transform: scale(1.05);
        }

        .facility-status {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--primary);
            color: var(--white);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .facility-status.available {
            background: #28a745;
        }

        .facility-body {
            padding: 16px;
        }

        .facility-category {
            color: var(--secondary);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .facility-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .facility-desc {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .facility-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 12px;
            color: var(--text-light);
        }

        .facility-price {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .facility-actions {
            display: flex;
            gap: 8px;
        }

        .facility-actions button {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .facility-actions .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .facility-actions .btn-primary:hover {
            background: var(--secondary);
        }

        .facility-actions .btn-secondary {
            background: var(--white);
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .facility-actions .btn-secondary:hover {
            background: var(--primary);
            color: var(--white);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--white);
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .modal-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(92, 0, 31, 0.1);
        }

        .modal-footer {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .modal-footer button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        @media (max-width: 768px) {
            .featured-section {
                grid-template-columns: 1fr;
            }

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .facilities-grid {
                grid-template-columns: 1fr;
            }

        }
    </style>
</head>
<body>
    <!-- Header -->
<?php $currentPage = 'facilities'; include '../../includes/header.php'; ?>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="/homepage.php">Home</a> > <a href="#">Facilities Explorer</a>
    </div>

    <!-- Main -->
    <main>
        <!-- Page Header -->
        <div class="page-header">
            <h1>University Facilities</h1>
            <p>Reserve high-spec research laboratories, professional sports courts, and state-of-the-art performance auditoriums across campus.</p>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="filters">
                <button class="filter-btn active" onclick="filterFacilities('all')">All Facilities</button>
                <button class="filter-btn" onclick="filterFacilities('research')">Research Labs</button>
                <button class="filter-btn" onclick="filterFacilities('sports')">Sports Centers</button>
                <button class="filter-btn" onclick="filterFacilities('auditorium')">Auditoriums</button>
            </div>

            <div class="sort-options">
                <span style="font-size: 13px; color: var(--text-light);">Filters</span>
                <select class="sort-select">
                    <option>Sort: Capacity</option>
                    <option>Sort: Price</option>
                    <option>Sort: Rating</option>
                </select>
            </div>
        </div>

        <!-- Featured Section -->
        <div class="featured-section">

    <!-- DATARAN UTM -->
    <div class="featured-main">
        <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=600&h=400&fit=crop" alt="Dataran UTM">

        <span class="featured-badge">
            FEATURED
        </span>

        <span class="featured-available">
            ✓ AVAILABLE TODAY
        </span>
    </div>

    <div class="featured-content">

        <div class="featured-category">
            EVENTS & COMMUNITY
        </div>

        <h2 class="featured-title">
            Dataran UTM
        </h2>

        <div class="featured-info">
            <span>👥 1000+ Capacity</span>
            <span>📍 Central Campus</span>
        </div>

        <div class="featured-price">
            RM 300/hr
        </div>

        <p style="font-size: 13px; color: var(--text-light); margin-bottom: 16px;">
            Open public event space suitable for concerts, night markets, exhibitions,
            university festivals, and large-scale student activities.
        </p>

        <div class="featured-buttons">
            <button class="btn btn-primary"
            onclick="openBookingModal('Dataran UTM')">

                Instant Book

            </button>

            <button class="btn btn-secondary">
                View Details
            </button>
        </div>

    </div>
</div>

<!-- RIGHT COLUMN -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 50px;">

    <!-- UTM EQUESTRIAN -->
    <div class="facility-card">
        <div class="facility-image">
            <img src="https://images.unsplash.com/photo-1517849845537-4d257902454a?w=400&h=300&fit=crop"
            alt="UTM Equestrian">

            <span class="facility-status available">✓ AVAILABLE</span>

        </div>
        <div class="facility-body">
            <div class="facility-category">SPORTS & TRAINING</div>
            <div class="facility-name">UTM Equestrian Centre</div>
            <div class="facility-desc">Horse riding arena and equestrian training facility</div>
            <div class="facility-price">RM 150/hr</div>
            <div class="facility-actions">

                <button class="btn-primary"
                onclick="openBookingModal('UTM Equestrian Centre')">Book</button>
                <button class="btn-secondary">Info</button>
            </div>
        </div>
    </div>

    <!-- STADIUM -->
    <div class="facility-card">
        <div class="facility-image">
            <img src="https://images.unsplash.com/photo-1547347298-4074fc3086f0?w=400&h=300&fit=crop"
            alt="Stadium">
            <span class="facility-status available">✓ AVAILABLE</span>
        </div>
        <div class="facility-body">
            <div class="facility-category">SPORTS & EVENTS</div>
            <div class="facility-name">UTM Stadium</div>
            <div class="facility-desc">Professional sports stadium for tournaments and major events</div>
            <div class="facility-price">RM 250/hr</div>
            <div class="facility-actions">
                <button class="btn-primary" onclick="openBookingModal('UTM Stadium')">Book</button>
                <button class="btn-secondary">Info</button>
            </div>
        </div>
    </div>
</div>
        <!-- Section: Other Facilities -->
        <h3 class="section-title">Other Facilities</h3>

        <div class="facilities-grid" id="facilitiesGrid">
            <!-- Cards will be loaded here -->
        </div>
    </main>

    <!-- Booking Modal -->
    <div class="modal" id="bookingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📅 Quick Booking</h2>
                <button class="modal-close" onclick="closeBookingModal()">✕</button>
            </div>

            <form onsubmit="submitBooking(event)">
                <div class="form-group">
                    <label>Facility Name</label>
                    <input type="text" id="facilityName" readonly>
                </div>

                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label>Start Time *</label>
                        <input type="time" required>
                    </div>
                    <div class="form-group">
                        <label>End Time *</label>
                        <input type="time" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Purpose</label>
                    <textarea rows="3" placeholder="What will you use this facility for?"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeBookingModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>

    <script>
        // Facilities Data - 4 facilities only
          const facilities = [
              {
                  id: 1,
                  name: 'Swimming Pool',
                  category: 'sports',
                  price: 'RM 15/per entry',
                  image: 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=400&h=300&fit=crop',
                  description: 'Olympic-sized swimming pool with training lanes and spectator seating.',
                  available: true
              },
              {
                  id: 2,
                  name: 'Electrical Engineering Lab',
                  category: 'research',
                  price: 'RM 10/hr',
                  image: 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=400&h=300&fit=crop',
                  description: 'Room 1, Level 3',
                  available: true
              },
              {
                  id: 3,
                  name: 'Lecture Hall P19',
                  category: 'auditorium',
                  price: 'RM 20/hr',
                  image: 'https://images.unsplash.com/photo-1576086213369-6befa08b2e4f?w=400&h=300&fit=crop',
                  description: 'Lecture hall with projector system and tiered seating.',
                  available: true
              },
              {
                  id: 4,
                  name: 'Sport Hall',
                  category: 'sports',
                  price: 'RM 10/per entry', 
                  image: 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=400&h=300&fit=crop',
                  description: 'Indoor sports hall suitable for badminton, volleyball, and training activities.',
                  available: true
              },
          ];

        let currentFilter = 'all';

        // Display facilities
        function displayFacilities() {
            const grid = document.getElementById('facilitiesGrid');
            
            let filtered = currentFilter === 'all' 
                ? facilities 
                : facilities.filter(f => f.category === currentFilter);

            grid.innerHTML = filtered.map(f => `
                <div class="facility-card">
                    <div class="facility-image">
                        <img src="${f.image}" alt="${f.name}">
                        <span class="facility-status ${f.available ? 'available' : ''}">
                            ${f.available ? '✓ AVAILABLE' : '✗ BOOKED'}
                        </span>
                    </div>
                    <div class="facility-body">
                        <div class="facility-category">${f.category.toUpperCase()}</div>
                        <div class="facility-name">${f.name}</div>
                        <div class="facility-desc">${f.description}</div>
                        <div class="facility-price">${f.price}</div>
                        <div class="facility-actions">
                            <button class="btn-primary" onclick="openBookingModal('${f.name}')" ${!f.available ? 'disabled' : ''}>
                                ${f.available ? 'Book' : 'Booked'}
                            </button>
                            <button class="btn-secondary">Info</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Filter facilities
        function filterFacilities(category) {
            currentFilter = category;
            
            // Update button styles
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            displayFacilities();
        }

        // Booking modal
        function openBookingModal(facilityName = '') {
            const modal = document.getElementById('bookingModal');
            if (facilityName) {
                document.getElementById('facilityName').value = facilityName;
            }
            modal.classList.add('show');
        }

        function closeBookingModal() {
            const modal = document.getElementById('bookingModal');
            modal.classList.remove('show');
        }

        function submitBooking(event) {
            event.preventDefault();
            alert('✅ Booking submitted successfully!');
            closeBookingModal();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('bookingModal');
            if (event.target === modal) {
                closeBookingModal();
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', displayFacilities);
    </script>
</body>
</html>
