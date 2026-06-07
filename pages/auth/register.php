<?php
$currentPage = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UNIRESERVE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary-color: #8b1538;
            --primary-hover: #a01d48;
            --border-color: #e0e0e0;
            --text-color: #333;
            --text-light: #666;
            --white: #fff;
            --error-color: #d32f2f;
            --success-color: #388e3c;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #ebebeb 100%);
            min-height: 100vh; display:flex; flex-direction:column; padding:0;
        }
        .auth-card { background: var(--white); border:1px solid var(--border-color); border-radius:8px; width:100%; max-width:450px; box-shadow:0 2px 16px rgba(0,0,0,.08); padding:30px; }
        h1 { color: var(--primary-color); margin-bottom: 6px; }
        .subtitle { color: var(--text-light); margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        label { display:block; margin-bottom: 8px; font-size:14px; font-weight:600; }
        input:not([type="checkbox"]) { width:100%; padding:12px; border:1px solid var(--border-color); border-radius:4px; }
        input:not([type="checkbox"]):focus { outline:none; border-color:var(--primary-color); box-shadow:0 0 0 3px rgba(139,21,56,.1); }
        .checkbox-group { display:flex; gap:8px; align-items:center; margin:16px 0; font-size:14px; }
        .checkbox-group input[type="checkbox"] { width:16px; height:16px; accent-color: var(--primary-color); }
        .checkbox-group label { display:inline; margin:0; font-weight:500; line-height:1.4; }
        .btn-primary { width:100%; padding:12px; border:0; border-radius:4px; background:var(--primary-color); color:#fff; font-weight:600; cursor:pointer; }
        .btn-primary:hover { background:var(--primary-hover); }
        .error-message,.success-message { display:none; margin-top:14px; padding:10px; border-radius:4px; font-size:13px; }
        .error-message { background:#ffebee; color:var(--error-color); }
        .success-message { background:#e8f5e9; color:var(--success-color); }
        .redirect { margin-top:16px; text-align:center; font-size:14px; }
        .password-wrap{position:relative;}
        .password-wrap input{padding-right:44px!important;}
        
.password-wrap input{padding-right:48px;}
.password-toggle{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:0;border-radius:50%;background:transparent;color:#64748b;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:background .2s ease,color .2s ease,transform .2s ease;}
.password-toggle:hover,.password-toggle:focus-visible{background:rgba(139,21,56,.08);color:#8b1538;outline:none;}
.password-toggle:active{transform:translateY(-50%) scale(.94);}
.password-toggle svg{width:20px;height:20px;fill:currentColor;}
.password-toggle .icon-eye-off{display:none;}
.password-toggle.is-visible .icon-eye{display:none;}
.password-toggle.is-visible .icon-eye-off{display:block;}

        .password-rules{font-size:12px;color:var(--text-light);line-height:1.5;margin-top:6px;}
        .password-rules .met{color:var(--success-color);font-weight:600;}
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>
    <div style="flex:1; display:flex; align-items:center; justify-content:center; width:100%; padding:20px;">
    <div class="auth-card">
        <h1>Create Account</h1>
        <p class="subtitle">Register for UNIRESERVE</p>
        <form id="signup-form" onsubmit="handleSignUp(event)">
            <div class="form-group">
                <label for="signup-name">Full Name</label>
                <input type="text" id="signup-name" placeholder="John Doe" required>
            </div>
            <div class="form-group">
                <label for="signup-utm-id">UTM ID</label>
                <input type="text" id="signup-utm-id" placeholder="A22EC0000" required>
            </div>
            <div class="form-group">
                <label for="signup-ic-no">Identification Card Number (IC No.)</label>
                <input type="text" id="signup-ic-no" placeholder="901010-10-1010" required>
            </div>
            <div class="form-group">
                <label for="signup-email">University Email</label>
                <input type="email" id="signup-email" placeholder="student@graduate.utm.my" required>
            </div>
            <div class="form-group">
                <label for="signup-password">Password</label>
                <div class="password-wrap"><input type="password" id="signup-password" placeholder="••••••••" minlength="8" oninput="updatePasswordRules()" required><button type="button" class="password-toggle" onclick="togglePasswordVisibility('signup-password', this)" aria-label="Show password" aria-pressed="false"><svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-2.3a1.7 1.7 0 1 0 0-3.4 1.7 1.7 0 0 0 0 3.4Z"/></svg><svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M3.3 2 2 3.3l3 3C3.1 7.7 2 9.4 2 9.4S5.6 16.4 12 16.4c1.8 0 3.4-.5 4.7-1.2l4 4L22 17.9 3.3 2Zm7.2 7.2 1.8 1.8c-.1.1-.2.1-.3.1a1.7 1.7 0 0 1-1.7-1.7c0-.1.1-.2.2-.2Zm1.5-3.8c6.4 0 10 7 10 7s-1 1.9-2.8 3.5l-2.5-2.5A4 4 0 0 0 11 7.7L9.2 5.9c.9-.3 1.8-.5 2.8-.5ZM4.6 7.1 6.7 9.2a4 4 0 0 0 5.1 5.1l1.6 1.6c-.5.1-.9.1-1.4.1-4.4 0-7.3-4-8.3-5.6.4-.7.9-1.4 1.6-2.1Z"/></svg></button></div><div class="password-rules" id="password-rules">Min 8 characters + uppercase + lowercase + number + symbol.</div>
            </div>
            <div class="form-group">
                <label for="signup-confirm">Confirm Password</label>
                <div class="password-wrap"><input type="password" id="signup-confirm" placeholder="••••••••" required><button type="button" class="password-toggle" onclick="togglePasswordVisibility('signup-confirm', this)" aria-label="Show password" aria-pressed="false"><svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5C5.6 5 2 12 2 12s3.6 7 10 7 10-7 10-7-3.6-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-2.3a1.7 1.7 0 1 0 0-3.4 1.7 1.7 0 0 0 0 3.4Z"/></svg><svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M3.3 2 2 3.3l3 3C3.1 7.7 2 9.4 2 9.4S5.6 16.4 12 16.4c1.8 0 3.4-.5 4.7-1.2l4 4L22 17.9 3.3 2Zm7.2 7.2 1.8 1.8c-.1.1-.2.1-.3.1a1.7 1.7 0 0 1-1.7-1.7c0-.1.1-.2.2-.2Zm1.5-3.8c6.4 0 10 7 10 7s-1 1.9-2.8 3.5l-2.5-2.5A4 4 0 0 0 11 7.7L9.2 5.9c.9-.3 1.8-.5 2.8-.5ZM4.6 7.1 6.7 9.2a4 4 0 0 0 5.1 5.1l1.6 1.6c-.5.1-.9.1-1.4.1-4.4 0-7.3-4-8.3-5.6.4-.7.9-1.4 1.6-2.1Z"/></svg></button></div>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" id="agree-terms" required>
                <label for="agree-terms">I agree to the Terms of Service</label>
            </div>
            <button type="submit" class="btn-primary">Create Account</button>
            <div id="signup-error" class="error-message"></div>
            <div id="signup-success" class="success-message"></div>
        </form>
        <p class="redirect">Already have an account? <a href="login.php">Sign in</a></p>
    </div>
    </div>

    <script>
        async function handleSignUp(event) {
            event.preventDefault();
            const full_name = document.getElementById('signup-name').value.trim();
            const utm_id = document.getElementById('signup-utm-id').value.trim();
            const ic_no = document.getElementById('signup-ic-no').value.trim();
            const email = document.getElementById('signup-email').value.trim();
            const password = document.getElementById('signup-password').value;
            const confirmPassword = document.getElementById('signup-confirm').value;

            if (!isStrongPassword(password)) {
                return showMessage('signup-error', 'Password must be at least 8 characters and include uppercase, lowercase, number and symbol.');
            }

            if (password !== confirmPassword) {
                return showMessage('signup-error', 'Passwords do not match');
            }

            hideMessages();
            const response = await fetch('../../api/auth/auth_register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ full_name, utm_id, ic_no, email, password })
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                return showMessage('signup-error', result.message || 'Registration failed');
            }

            showMessage('signup-success', 'Registration successful. Redirecting to login...');
            setTimeout(() => window.location.href = 'login.php', 1000);
        }

        function isStrongPassword(password) {
            return password.length >= 8 && /[A-Z]/.test(password) && /[a-z]/.test(password) && /[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password);
        }

        function updatePasswordRules() {
            const password = document.getElementById('signup-password').value;
            const rules = [
                password.length >= 8 ? '8+ chars' : '8+ chars',
                /[A-Z]/.test(password) ? 'uppercase' : 'uppercase',
                /[a-z]/.test(password) ? 'lowercase' : 'lowercase',
                /[0-9]/.test(password) ? 'number' : 'number',
                /[^A-Za-z0-9]/.test(password) ? 'symbol' : 'symbol'
            ];
            const checks = [password.length >= 8, /[A-Z]/.test(password), /[a-z]/.test(password), /[0-9]/.test(password), /[^A-Za-z0-9]/.test(password)];
            document.getElementById('password-rules').innerHTML = checks.map((ok, i) => `<span class="${ok ? 'met' : ''}">${ok ? '✓' : '○'} ${rules[i]}</span>`).join(' · ');
        }

        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.classList.toggle('is-visible', show);
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
        }

        function showMessage(id, message) {
            const el = document.getElementById(id);
            el.textContent = message;
            el.style.display = 'block';
        }

        function hideMessages() {
            document.getElementById('signup-error').style.display = 'none';
            document.getElementById('signup-success').style.display = 'none';
        }
    </script>
</body>
</html>
