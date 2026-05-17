<?php
$currentPage = $currentPage ?? '';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$prefix = strpos($scriptName, '/pages/') !== false ? '../../' : '';
$toRoot = static fn(string $path): string => $prefix . ltrim($path, '/');
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
    .icon-button { background: none; border: none; font-size: 20px; cursor: pointer; transition: transform 0.3s; color: var(--white); display: block; }
    .icon-button:hover { transform: scale(1.1); }
    .btn-book-now {
        background-color: #FFC107 !important; /* Forces your golden yellow color */
        color: #1A202C !important;            /* Ensures text is dark and legible */
        border: none;
        padding: 8px 20px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-book-now:hover {
        background-color: #E0A800 !important; /* Darker gold shift on hover */
        transform: translateY(-2px); 
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4); 
    }
    /* Notification Wrap & Badge Configurations */
    .noti-dropdown-container { position: relative; display: inline-block; }
    .noti-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background-color: var(--danger, #dc3545);
        color: var(--white, #fff);
        font-size: 10px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 10px;
        line-height: 1;
    }

    /* Professional Notification Pop-out Frame */
    .noti-menu {
        position: absolute; top: 45px; right: -50px; background: var(--white); border-radius: 8px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15); width: 340px; z-index: 1000; overflow: hidden;
        border: 1px solid var(--border-light, #edf2f7);
    }
    .noti-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 16px; border-bottom: 1px solid var(--border-light, #edf2f7);
        background: var(--bg-light, #f8f9fa);
    }
    .noti-header h3 { margin: 0; font-size: 14px; font-weight: 600; color: var(--text-dark); }
    .noti-clear-btn { background: none; border: none; color: #3182ce; font-size: 11px; cursor: pointer; padding: 0; }
    .noti-clear-btn:hover { text-decoration: underline; }
    
    .noti-body { max-height: 280px; overflow-y: auto; }
    .noti-item {
        display: flex; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--border-light, #f7fafc);
        cursor: pointer; transition: background 0.2s; text-decoration: none; align-items: flex-start;
    }
    .noti-item:hover { background: var(--bg-light, #f8f9fa); }
    .noti-item.unread { background: #f0f7ff; }
    .noti-item-icon { font-size: 16px; margin-top: 2px; }
    .noti-item-content p { margin: 0 0 4px 0; font-size: 12px; color: var(--text-dark); line-height: 1.4; text-align: left; }
    .noti-item-time { font-size: 10px; color: #a0aec0; display: block; }
    
    .noti-footer { padding: 10px; text-align: center; background: var(--bg-light, #f8f9fa); border-top: 1px solid var(--border-light, #edf2f7); }
    .noti-footer a { color: #3182ce; text-decoration: none; font-size: 12px; font-weight: 500; }
    .noti-footer a:hover { text-decoration: underline; }

    /* Existing User Dropdown Layout Rules */
    .user-dropdown { position: relative; }
    .user-avatar {
        width: 36px; height: 36px; border-radius: 50%; background: var(--white);
        color: var(--primary-color); border: none; font-weight: 700; cursor: pointer; transition: all 0.3s;
        display: inline-flex; align-items: center; justify-content: center; line-height: 1;
        overflow: hidden;
    }
    .user-avatar img {
        width: 100%;
        min-width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .user-avatar.has-image {
        padding: 0;
        background: var(--white);
        color: transparent;
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
    .dropdown-item.logout { color: var(--danger); }
    .dropdown-divider { margin: 8px 0; border: none; border-top: 1px solid var(--border-light); }
    
    @media (max-width: 768px) {
        .navbar { flex-direction: column; gap: 12px; padding: 12px 16px; }
        .navbar-left { width: 100%; flex-direction: column; gap: 12px; align-items: flex-start; }
        .navbar-right { width: 100%; justify-content: space-between; }
        .nav-links { flex-direction: column; gap: 8px; }
        .noti-menu { right: auto; left: 0; width: calc(100vw - 32px); }
    }
</style>

<nav class="navbar">
    <div class="navbar-left">
        <h1 class="navbar-logo">SPACEBOOK</h1>
        <div class="nav-links">
            <a href="<?= htmlspecialchars($toRoot('homepage.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link<?= $currentPage === 'home' ? ' active' : '' ?>">Home</a>
            <a href="<?= htmlspecialchars($toRoot('pages/app/room-availability.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link<?= $currentPage === 'room' ? ' active' : '' ?>">Rooms</a>
            <a href="<?= htmlspecialchars($toRoot('pages/app/facilities.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-link<?= $currentPage === 'facilities' ? ' active' : '' ?>">Facilities</a>
        </div>
    </div>

    <div class="navbar-right">
        <div class="noti-dropdown-container">
            <button class="icon-button" id="noti-btn" onclick="toggleNotiMenu(event)" title="Notification">
                🔔<span class="noti-badge">2</span>
            </button>
            
            <div class="noti-menu" id="noti-menu" style="display:none;">
                <div class="noti-header">
                    <h3>Notifications</h3>
                    <button class="noti-clear-btn" onclick="markAllNotificationsAsRead()">Mark all as read</button>
                </div>
                <div class="noti-body">
                    <div class="noti-item unread">
                        <span class="noti-item-icon">✨</span>
                        <div class="noti-item-content">
                            <p>Your reservation structural request for <strong>Room T05</strong> has been successfully booked.</p>
                            <span class="noti-item-time">Just now</span>
                        </div>
                    </div>
                    <div class="noti-item">
                        <span class="noti-item-icon">📅</span>
                        <div class="noti-item-content">
                            <p>System maintenance scheduled for this weekend. Some facility selectors may experience structural updates.</p>
                            <span class="noti-item-time">2 hours ago</span>
                        </div>
                    </div>
                </div>
                <div class="noti-footer">
                    <a href="#">View All System Activity</a>
                </div>
            </div>
        </div>

        <button class="btn-book-now" onclick="window.location.href='<?= htmlspecialchars($toRoot('pages/app/booking.php'), ENT_QUOTES, 'UTF-8') ?>'">Book Now</button>

        <div class="user-dropdown">
            <button class="user-avatar" id="user-avatar-btn" onclick="toggleUserMenu(event)">U</button>
            <div class="dropdown-menu" id="user-menu" style="display:none;">
                <a href="<?= htmlspecialchars($toRoot('pages/app/profile.php'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item">👤 My Profile</a>
                <a href="#" class="dropdown-item">📋 My Bookings</a>
                <a href="#" class="dropdown-item">⚙️ Settings</a>
                <a href="#" class="dropdown-item">❓ About</a>
                <hr class="dropdown-divider">
                <a href="<?= htmlspecialchars($toRoot('pages/auth/login.html'), ENT_QUOTES, 'UTF-8') ?>" class="dropdown-item logout" onclick="handleLogout()">🚪 Logout</a>
            </div>
        </div>
    </div>
</nav>

<script>
    (function(){
        function computeInitials(fullName){
            const normalized = (fullName || '').trim();
            return (normalized.charAt(0) || 'U').toUpperCase();
        }

        async function hydrateAvatar(){
            const avatar = document.getElementById('user-avatar-btn');
            if (!avatar) return;
            const setInitialAvatar = function(fullName){
                avatar.classList.remove('has-image');
                avatar.innerHTML = '';
                avatar.textContent = computeInitials(fullName);
            };
            const setImageAvatar = function(base64, mime){
                if (!base64 || !mime) return false;
                const image = document.createElement('img');
                image.src = `data:${mime};base64,${base64}`;
                image.alt = 'Profile picture';
                avatar.classList.add('has-image');
                avatar.innerHTML = '';
                avatar.appendChild(image);
                return true;
            };

            try {
                const localUser = JSON.parse(localStorage.getItem('userData') || 'null');
                const fullName = localUser?.full_name || localUser?.name;
                if (fullName) {
                    setInitialAvatar(fullName);
                }
            } catch (error) {
                console.warn('Unable to parse local user data for avatar.', error);
            }

            try {
                const sessionResponse = await fetch('<?= htmlspecialchars($toRoot('api/auth/auth_session.php'), ENT_QUOTES, 'UTF-8') ?>', {
                    credentials: 'same-origin'
                });
                if (!sessionResponse.ok) return;

                const sessionData = await sessionResponse.json();
                if (!sessionData?.authenticated) return;

                const sessionUser = sessionData.user || null;
                const fullName = sessionUser?.full_name || sessionUser?.name;
                if (!fullName) return;

                setInitialAvatar(fullName);
                localStorage.setItem('userData', JSON.stringify(sessionUser));

                const profileResponse = await fetch('<?= htmlspecialchars($toRoot('api/user/profile_data.php'), ENT_QUOTES, 'UTF-8') ?>', {
                    credentials: 'same-origin'
                });
                if (!profileResponse.ok) return;

                const profileData = await profileResponse.json();
                if (!profileData?.success || !profileData?.user) return;

                const profileUser = profileData.user;
                setImageAvatar(profileUser.profile_image_base64, profileUser.profile_image_mime);
            } catch (error) {
                console.warn('Unable to load session user data for avatar.', error);
            }
        }

        // Dropdown controls with event.stopPropagation to prevent conflicting click captures
        window.toggleUserMenu = function toggleUserMenu(e){
            if(e) e.stopPropagation();
            const userMenu = document.getElementById('user-menu');
            const notiMenu = document.getElementById('noti-menu');
            
            if (notiMenu) notiMenu.style.display = 'none';
            if (userMenu) {
                userMenu.style.display = userMenu.style.display === 'block' ? 'none' : 'block';
            }
        };

        window.toggleNotiMenu = function toggleNotiMenu(e){
            if(e) e.stopPropagation();
            const userMenu = document.getElementById('user-menu');
            const notiMenu = document.getElementById('noti-menu');
            
            if (userMenu) userMenu.style.display = 'none';
            if (notiMenu) {
                notiMenu.style.display = notiMenu.style.display === 'block' ? 'none' : 'block';
            }
        };

        window.markAllNotificationsAsRead = function markAllNotificationsAsRead() {
            document.querySelectorAll('.noti-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            const badge = document.querySelector('.noti-badge');
            if (badge) badge.style.display = 'none';
        };

        window.handleLogout = function handleLogout(){
            localStorage.removeItem('userData');
            fetch('<?= htmlspecialchars($toRoot('api/auth/auth_logout.php'), ENT_QUOTES, 'UTF-8') ?>', {
                method: 'POST',
                credentials: 'same-origin'
            }).finally(() => {
                window.location.href = '<?= htmlspecialchars($toRoot('pages/auth/login.html'), ENT_QUOTES, 'UTF-8') ?>';
            });
        };

        // Universal close catcher for clicking outside either active menu structure
        document.addEventListener('click', function(event){
            const userDropdown = document.querySelector('.user-dropdown');
            const notiDropdown = document.querySelector('.noti-dropdown-container');
            
            if (userDropdown && !userDropdown.contains(event.target)) {
                const userMenu = document.getElementById('user-menu');
                if (userMenu) userMenu.style.display = 'none';
            }
            if (notiDropdown && !notiDropdown.contains(event.target)) {
                const notiMenu = document.getElementById('noti-menu');
                if (notiMenu) notiMenu.style.display = 'none';
            }
        });

        document.addEventListener('DOMContentLoaded', hydrateAvatar);
    })();
</script>