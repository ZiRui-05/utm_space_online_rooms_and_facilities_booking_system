<?php
if (!isset($page_title)) $page_title = 'SPACEBOOK';
if (!isset($active_page)) $active_page = '';
$user = $user ?? current_management_user();
$isAdmin = $user && ($user['role'] === 'admin');
$prefix = $isAdmin ? 'admin' : 'manager';
$dashboardLink = $isAdmin ? 'admin_dashboard.php' : 'facility_manager_dashboard.php';
$dashboardLabel = $isAdmin ? 'Admin Dashboard' : 'Facility Manager Dashboard';
function nav_class($key, $active_page) {
    return $key === $active_page
        ? 'flex items-center gap-3 bg-red-900 text-white rounded-md px-4 py-3 shadow-sm text-xs uppercase tracking-wider font-bold'
        : 'flex items-center gap-3 text-slate-600 px-4 py-3 hover:bg-slate-200/50 rounded-md transition-all text-xs uppercase tracking-wider font-bold';
}
function role_url($page) {
    global $prefix;
    return $prefix . '_' . $page . '.php';
}
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
    <script>
        tailwind.config = { theme: { extend: { colors: { primary:'#36000f', 'primary-container':'#5c001f', 'secondary-container':'#fdaa1d', 'outline-variant':'#dcc0c2', background:'#f8f9ff' } } } };
    </script>
    <style>
        body{font-family:Inter,sans-serif;background:#f8f9ff;min-height:100vh}.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;vertical-align:middle}.table-th{padding:14px 18px;font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#64748b;background:#f8fafc;text-align:left}.table-td{padding:14px 18px;border-top:1px solid #e2e8f0;font-size:14px;vertical-align:top}.input{width:100%;border:1px solid #dcc0c2;border-radius:8px;padding:10px 12px;background:#fff}.btn-primary{background:#5c001f;color:#fff;padding:10px 16px;border-radius:8px;font-weight:700;display:inline-block}.btn-warning{background:#fdaa1d;color:#291800;padding:10px 16px;border-radius:8px;font-weight:700;display:inline-block}.btn-light{background:#f8fafc;border:1px solid #dcc0c2;padding:10px 16px;border-radius:8px;font-weight:700;display:inline-block}.badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700}.badge-pending{background:#fff7ed;color:#9a3412}.badge-approved,.badge-active,.badge-available,.badge-completed{background:#dcfce7;color:#166534}.badge-rejected,.badge-suspended,.badge-unavailable,.badge-cancelled,.badge-expired{background:#fee2e2;color:#991b1b}.badge-maintenance,.badge-inactive,.badge-blocked{background:#e2e8f0;color:#334155}@media print{.no-print,header,aside{display:none!important}main{margin-left:0!important;padding:0!important}.print-card{box-shadow:none!important;border-color:#111!important}.page-break{page-break-before:always}body{background:#fff!important;color:#111!important}}
    .admin-page-main{min-height:calc(100vh - 5rem);display:flex;flex-direction:column}.admin-page-footer{margin-top:auto;padding-top:2rem;color:#64748b;font-size:.75rem;text-align:center}
</style>
</head>
<body class="antialiased text-slate-900">
<header class="no-print flex justify-between items-center w-full px-10 py-4 sticky top-0 z-50 bg-white border-b border-slate-200">
    <div class="flex items-center gap-8">
        <span class="text-xl font-bold tracking-tight text-red-900 uppercase">SPACEBOOK</span>
        <nav class="hidden md:flex gap-6 text-sm font-medium">
            <a class="text-slate-600 hover:text-red-900" href="../../homepage.php">Home</a>
            <a class="text-slate-600 hover:text-red-900" href="<?= h($dashboardLink) ?>"><?= h($dashboardLabel) ?></a>
            <a class="text-slate-600 hover:text-red-900" href="<?= h(role_url('booking_requests')) ?>">Bookings</a>
            <a class="text-slate-600 hover:text-red-900" href="<?= h(role_url('reports')) ?>">Reports</a>
        </nav>
    </div>
    <div class="flex items-center gap-4">
        <span class="hidden sm:block text-sm text-slate-600"><?= $user ? h($user['full_name']) . ' · ' . h(ucwords(str_replace('_',' ', $user['role']))) : 'Guest' ?></span>
        <a class="btn-light py-2" href="logout.php">Logout</a>
        <div class="h-9 w-9 rounded-full overflow-hidden border border-slate-200"><img src="https://ui-avatars.com/api/?name=<?= urlencode($user['full_name'] ?? 'User') ?>&background=5c001f&color=fff" class="h-full w-full object-cover" alt="Avatar"></div>
    </div>
</header>
<div class="flex min-h-screen">
<aside class="no-print flex flex-col fixed left-0 top-0 pt-20 pb-8 px-4 z-40 bg-slate-50 h-screen w-64 border-r border-slate-200">
    <div class="mb-8 px-4 flex items-center gap-3">
        <div class="h-10 w-10 bg-primary-container rounded flex items-center justify-center text-white"><span class="material-symbols-outlined">domain</span></div>
        <div><h2 class="text-lg font-black text-red-900 leading-none"><?= $isAdmin ? 'Admin Portal' : 'Facility Manager' ?></h2><p class="text-[10px] uppercase tracking-widest font-bold text-slate-500 mt-1">Institutional Mgmt</p></div>
    </div>
    <div class="flex-1 space-y-1">
        <a class="<?= nav_class('dashboard', $active_page) ?>" href="<?= h($dashboardLink) ?>"><span class="material-symbols-outlined"><?= $isAdmin ? 'admin_panel_settings' : 'dashboard' ?></span><span><?= h($dashboardLabel) ?></span></a>
        <a class="<?= nav_class('bookings', $active_page) ?>" href="<?= h(role_url('booking_requests')) ?>"><span class="material-symbols-outlined">event_note</span><span>Booking Requests</span></a>
        <a class="<?= nav_class('rooms', $active_page) ?>" href="<?= h(role_url('manage_rooms')) ?>"><span class="material-symbols-outlined">meeting_room</span><span>Rooms</span></a>
        <a class="<?= nav_class('facilities', $active_page) ?>" href="<?= h(role_url('manage_facilities')) ?>"><span class="material-symbols-outlined">inventory</span><span>Facilities</span></a>
        <a class="<?= nav_class('schedules', $active_page) ?>" href="<?= h(role_url('manage_schedules')) ?>"><span class="material-symbols-outlined">calendar_month</span><span>Schedules</span></a>
        <a class="<?= nav_class('reports', $active_page) ?>" href="<?= h(role_url('reports')) ?>"><span class="material-symbols-outlined">bar_chart</span><span>Reports</span></a>
        <a class="<?= nav_class('users', $active_page) ?>" href="<?= h(role_url('manage_users')) ?>"><span class="material-symbols-outlined">group</span><span>User Accounts</span></a>
    </div>
</aside>
<main class="admin-page-main ml-64 flex-1 p-10">
<?php if (!empty($_GET['success'])): ?><div class="no-print mb-5 rounded-lg bg-green-50 border border-green-200 text-green-800 p-4 font-semibold"><?= h($_GET['success']) ?></div><?php endif; ?>
<?php if (!empty($_GET['error'])): ?><div class="no-print mb-5 rounded-lg bg-red-50 border border-red-200 text-red-800 p-4 font-semibold"><?= h($_GET['error']) ?></div><?php endif; ?>
