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
        <button class="icon-button" title="Notification">🔔</button>
        <button class="btn-book-now" onclick="window.location.href='<?= htmlspecialchars($toRoot('pages/app/booking.php'), ENT_QUOTES, 'UTF-8') ?>'">Book Now</button>

        <div class="user-dropdown">
            <button class="user-avatar" id="user-avatar-btn" onclick="toggleUserMenu()">U</button>
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

        window.toggleUserMenu = function toggleUserMenu(){
            const menu = document.getElementById('user-menu');
            if (!menu) return;
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
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

        document.addEventListener('click', function(event){
            const dropdown = document.querySelector('.user-dropdown');
            if (!dropdown || dropdown.contains(event.target)) return;
            const menu = document.getElementById('user-menu');
            if (menu) menu.style.display = 'none';
        });

        document.addEventListener('DOMContentLoaded', hydrateAvatar);
    })();
</script>
