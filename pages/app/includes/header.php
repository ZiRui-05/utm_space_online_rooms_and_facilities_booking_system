<?php
if (!isset($page_title)) $page_title = 'SPACEBOOK';
if (!isset($active_page)) $active_page = '';
$user = $user ?? (function_exists('current_user') ? current_user() : null);
$isAdmin = $user && ($user['role'] === 'admin');
$prefix = $isAdmin ? 'admin' : 'manager';
$dashboardLink = $isAdmin ? 'admin_dashboard.php' : 'facility_manager_dashboard.php';
$dashboardLabel = $isAdmin ? 'Admin Dashboard' : 'Facility Manager Dashboard';
function nav_class($key, $active_page) {
    return $key === $active_page
        ? 'flex items-center gap-3 bg-red-900 text-white rounded-md px-4 py-3 shadow-sm text-xs uppercase tracking-wider font-bold'
        : 'flex items-center gap-3 text-slate-600 px-4 py-3 hover:bg-slate-200/50 rounded-md transition-all text-xs uppercase tracking-wider font-bold';
}
function role_url($page) { global $prefix; return $prefix . '_' . $page . '.php'; }
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script>tailwind.config = { theme: { extend: { colors: { primary:'#36000f', 'primary-container':'#5c001f', 'secondary-container':'#fdaa1d', 'outline-variant':'#dcc0c2', background:'#f8f9ff' } } } };</script>
    <style>body{font-family:Inter,sans-serif;background:#f8f9ff;overflow-x:hidden}.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;vertical-align:middle}.table-th{padding:14px 18px;font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#64748b;background:#f8fafc;text-align:left}.table-td{padding:14px 18px;border-top:1px solid #e2e8f0;font-size:14px;vertical-align:top}.input{width:100%;border:1px solid #dcc0c2;border-radius:8px;padding:10px 12px;background:#fff}.btn-primary{background:#5c001f;color:#fff;padding:10px 16px;border-radius:8px;font-weight:700;display:inline-block}.btn-warning{background:#fdaa1d;color:#291800;padding:10px 16px;border-radius:8px;font-weight:700;display:inline-block}.btn-light{background:#f8fafc;border:1px solid #dcc0c2;padding:10px 16px;border-radius:8px;font-weight:700;display:inline-block}.badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700}.badge-pending{background:#fff7ed;color:#9a3412}.badge-approved,.badge-active,.badge-available,.badge-completed,.badge-paid,.badge-verified,.badge-resolved{background:#dcfce7;color:#166534}.badge-rejected,.badge-suspended,.badge-unavailable,.badge-cancelled,.badge-expired,.badge-unpaid,.badge-unverified,.badge-payment_rejected{background:#fee2e2;color:#991b1b}.badge-maintenance,.badge-inactive,.badge-blocked,.badge-pending_verification,.badge-refunded,.badge-in_review,.badge-closed{background:#e2e8f0;color:#334155}.slot-cell{min-width:110px;height:58px;border:1px solid #e2e8f0;text-align:center;font-size:12px;cursor:pointer}.slot-free{background:#fff}.slot-selected{background:#fef3c7!important;outline:2px solid #f59e0b}.slot-booked{background:#dbeafe;color:#1e40af;cursor:not-allowed}.slot-blocked{background:#fee2e2;color:#991b1b}.slot-maintenance{background:#e2e8f0;color:#334155}@media print{.no-print,header,aside{display:none!important}main{margin-left:0!important;padding:0!important;max-width:none!important}.print-card{box-shadow:none!important;border-color:#111!important}.page-break{page-break-before:always}body{background:#fff!important;color:#111!important;overflow-x:visible}}</style>
</head>
<body class="antialiased text-slate-900">
<header class="no-print flex justify-between items-center w-full px-10 py-4 sticky top-0 z-50 bg-white border-b border-slate-200">
    <div class="flex items-center gap-8">
        <span class="text-xl font-bold tracking-tight text-red-900 uppercase">SPACEBOOK</span>
        <nav class="hidden md:flex gap-6 text-sm font-medium">
            <a class="text-slate-600 hover:text-red-900" href="../../homepage.php">Home</a>
            <a class="text-slate-600 hover:text-red-900" href="<?= h($dashboardLink) ?>"><?= h($dashboardLabel) ?></a>
            <?php if($isAdmin): ?>
                <a class="text-slate-600 hover:text-red-900" href="admin_booking_requests.php">Bookings</a>
            <?php else: ?>
                <a class="text-slate-600 hover:text-red-900" href="manager_booking_requests.php">Bookings</a>
            <?php endif; ?>
            <a class="text-slate-600 hover:text-red-900" href="<?= h(role_url('issue_reports')) ?>">Issue Reports</a>
            <a class="text-slate-600 hover:text-red-900" href="admin_reports.php">Reports</a>
        </nav>
    </div>

    <div class="flex items-center gap-4">
        <button class="relative p-2 text-slate-600 hover:text-red-900 transition-colors">
            <span class="material-symbols-outlined">notifications</span>
            <span class="absolute top-1 right-1 bg-amber-500 text-white font-bold text-[10px] w-4 h-4 flex items-center justify-center rounded-full">2</span>
        </button>

        <a href="#" class="bg-amber-500 hover:bg-amber-600 text-slate-900 text-sm font-bold px-4 py-2 rounded-md transition-colors shadow-sm">
            Book Now
        </a>

        <span class="hidden sm:block text-sm text-slate-600">
            <?= $user ? h($user['full_name']) . ' · ' . h(ucwords(str_replace('_',' ', $user['role']))) : 'Guest' ?>
        </span>

        <div class="relative inline-block text-left">
            <button id="profileDropdownBtn" class="h-9 w-9 rounded-full overflow-hidden border border-slate-200 focus:outline-none focus:ring-2 focus:ring-red-900 transition-all flex items-center justify-center">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['full_name'] ?? 'User') ?>&background=5c001f&color=fff" class="h-full w-full object-cover" alt="Avatar">
            </button>

            <div id="profileDropdownMenu" class="hidden absolute right-0 mt-2 w-56 rounded-xl bg-white shadow-xl border border-slate-100 py-2 z-50 origin-top-right transition-all">
                
                <a href="profile.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-purple-600 text-xl">person</span>
                    <span>My Profile</span>
                </a>

                <a href="<?= h($dashboardLink) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-blue-500 text-xl">shield</span>
                    <span><?= h($dashboardLabel) ?></span>
                </a>

                <a href="<?php echo $isAdmin ? 'admin_booking_requests.php' : 'manager_booking_requests.php'; ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-orange-400 text-xl">description</span>
                    <span>My Bookings</span>
                </a>

                <a href="settings.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-slate-400 text-xl">settings</span>
                    <span>Settings</span>
                </a>

                <a href="about.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-pink-500 text-xl">help</span>
                    <span>About</span>
                </a>

                <hr class="my-1 border-slate-100">

                <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-amber-700 text-xl">logout</span>
                    <span>Logout</span>
                </a>

            </div>
        </div>
    </div>
</header>
<div class="flex min-h-screen">
<aside class="no-print flex flex-col fixed left-0 top-0 pt-20 pb-8 px-4 z-40 bg-slate-50 h-screen w-64 border-r border-slate-200">
    <div class="mb-8 px-4 flex items-center gap-3"><div class="h-10 w-10 bg-primary-container rounded flex items-center justify-center text-white"><span class="material-symbols-outlined">domain</span></div><div><h2 class="text-lg font-black text-red-900 leading-none"><?= $isAdmin ? 'Admin Portal' : 'Facility Manager' ?></h2><p class="text-[10px] uppercase tracking-widest font-bold text-slate-500 mt-1">Institutional Mgmt</p></div></div>
    <div class="flex-1 space-y-1">
        <a class="<?= nav_class('dashboard', $active_page) ?>" href="<?= h($dashboardLink) ?>"><span class="material-symbols-outlined"><?= $isAdmin ? 'admin_panel_settings' : 'dashboard' ?></span><span><?= h($dashboardLabel) ?></span></a>
        <?php if($isAdmin): ?>
            <a class="<?= nav_class('bookings', $active_page) ?>" href="admin_booking_requests.php"><span class="material-symbols-outlined">event_note</span><span>Booking Requests</span></a>
            <a class="<?= nav_class('issues', $active_page) ?>" href="admin_issue_reports.php"><span class="material-symbols-outlined">report_problem</span><span>Issue Reports</span></a>
            <a class="<?= nav_class('reports', $active_page) ?>" href="admin_reports.php"><span class="material-symbols-outlined">bar_chart</span><span>Reports</span></a>
            <a class="<?= nav_class('users', $active_page) ?>" href="admin_manage_users.php"><span class="material-symbols-outlined">group</span><span>User Accounts</span></a>
            <a class="<?= nav_class('verify_cards', $active_page) ?>" href="admin_verify_cards.php"><span class="material-symbols-outlined">badge</span><span>Verify Student Card</span></a>
        <?php else: ?>
            <a class="<?= nav_class('bookings', $active_page) ?>" href="manager_booking_requests.php"><span class="material-symbols-outlined">event_note</span><span>Booking Requests</span></a>
            <a class="<?= nav_class('rooms', $active_page) ?>" href="manager_manage_rooms.php"><span class="material-symbols-outlined">meeting_room</span><span>Rooms</span></a>
            <a class="<?= nav_class('facilities', $active_page) ?>" href="manager_manage_facilities.php"><span class="material-symbols-outlined">inventory</span><span>Facilities</span></a>
            <a class="<?= nav_class('schedules', $active_page) ?>" href="manager_manage_schedules.php"><span class="material-symbols-outlined">calendar_month</span><span>Schedules</span></a>
            <a class="<?= nav_class('issues', $active_page) ?>" href="manager_issue_reports.php"><span class="material-symbols-outlined">report_problem</span><span>Issue Reports</span></a>
            <a class="<?= nav_class('reports', $active_page) ?>" href="admin_reports.php"><span class="material-symbols-outlined">bar_chart</span><span>Reports</span></a>
        <?php endif; ?>
    </div>
</aside>
<main class="ml-64 min-w-0 max-w-[calc(100vw-16rem)] flex-1 overflow-hidden p-6 lg:p-10">
<?php if (!empty($_GET['success'])): ?><div class="no-print mb-5 rounded-lg bg-green-50 border border-green-200 text-green-800 p-4 font-semibold"><?= h($_GET['success']) ?></div><?php endif; ?>
<?php if (!empty($_GET['error'])): ?><div class="no-print mb-5 rounded-lg bg-red-50 border border-red-200 text-red-800 p-4 font-semibold"><?= h($_GET['error']) ?></div><?php endif; ?>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    const dropdownBtn = document.getElementById('profileDropdownBtn');
    const dropdownMenu = document.getElementById('profileDropdownMenu');

    // Toggle dropdown on avatar click
    dropdownBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        dropdownMenu.classList.toggle('hidden');
    });

    // Close dropdown if user clicks outside of it
    document.addEventListener('click', function (event) {
        if (!dropdownBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
            dropdownMenu.classList.add('hidden');
        }
    });
});
</script>