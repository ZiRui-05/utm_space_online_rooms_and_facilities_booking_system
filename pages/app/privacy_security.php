<?php
session_start();
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$currentPage = 'profile';
function h($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Privacy & Security - SPACEBOOK</title>
<style>
:root{--primary-color:#8b1538;--primary-hover:#a01d48;--accent-color:#ffc107;--text-dark:#2b0010;--text-light:#6b7280;--bg-light:#f8fafc;--border-light:#e5e7eb;--white:#fff;--danger:#dc2626}
*{box-sizing:border-box}body{margin:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f5f5;color:#333;min-height:100vh;display:flex;flex-direction:column}.page-shell{max-width:960px;width:100%;margin:32px auto;padding:0 24px;flex:1}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.06);overflow:hidden}.card-head{padding:26px 28px;border-bottom:1px solid #e5e7eb;background:linear-gradient(135deg,#fff7f8,#fff)}.card-head h1{margin:0;color:#36000f;font-size:30px}.card-head p{margin:8px 0 0;color:#6b7280}.section{padding:24px 28px;display:grid;gap:18px}.security-row{border:1px solid #e5e7eb;border-radius:14px;padding:18px;display:flex;justify-content:space-between;gap:18px;align-items:center;background:#fff}.security-row h2{font-size:18px;margin:0;color:#36000f}.security-row p{margin:6px 0 0;color:#6b7280;font-size:14px;line-height:1.5}.btn-primary{display:inline-flex;align-items:center;justify-content:center;background:#8b1538;color:#fff;text-decoration:none;border:0;border-radius:10px;padding:11px 16px;font-weight:800;white-space:nowrap}.btn-light{display:inline-flex;align-items:center;justify-content:center;background:#fff;color:#8b1538;text-decoration:none;border:1px solid #dcc0c2;border-radius:10px;padding:11px 16px;font-weight:800;white-space:nowrap}@media(max-width:700px){.security-row{flex-direction:column;align-items:flex-start}.page-shell{padding:0 14px}}
.footer{background:var(--text-dark)!important;color:var(--white)!important;width:100%}.footer a{color:var(--white)!important}
</style>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<main class="page-shell">
    <div class="card">
        <div class="card-head">
            <h1>Privacy & Security</h1>
            <p>Manage account security actions for your SPACEBOOK account.</p>
        </div>
        <div class="section">
            <div class="security-row">
                <div>
                    <h2>Reset Password</h2>
                    <p>Use email verification and OTP before setting a new password. The new password must follow the strong password rule.</p>
                </div>
                <a class="btn-primary" href="../auth/forgot-password.html">Open Reset Password Form</a>
            </div>
            <div class="security-row">
                <div>
                    <h2>Account Notifications</h2>
                    <p>Check booking status updates, profile updates, issue report updates, and other account activity.</p>
                </div>
                <a class="btn-light" href="all_notifications.php">View Notifications</a>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<style>
    footer.footer {
        background: #2b0010 !important;
        color: #ffffff !important;
    }
    footer.footer h5,
    footer.footer p,
    footer.footer a {
        color: #ffffff !important;
    }
</style>
</body>
</html>
