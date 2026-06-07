<?php
$currentPage = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SPACE BOOK</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #8b1538;
            --primary-hover: #a01d48;
            --border-color: #e0e0e0;
            --text-color: #333;
            --text-light: #666;
            --bg-color: #f5f5f5;
            --white: #ffffff;
            --error-color: #d32f2f;
            --success-color: #388e3c;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #ebebeb 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-color);
        }

        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 32px;
            color: var(--primary-color);
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .header .subtitle {
            font-size: 16px;
            color: var(--text-light);
            font-weight: 500;
        }

        .auth-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .form-switcher {
            display: flex;
            width: 100%;
            max-width: 450px;
            margin-bottom: -1px;
            z-index: 2;
        }

        .tab-button {
            flex: 1;
            padding: 14px 26px;
            background: #f9f9f9;
            border: 1px solid var(--border-color);
            border-bottom: none;
            border-radius: 0;
            color: var(--text-light);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .tab-button:first-child {
            border-top-left-radius: 8px;
        }

        .tab-button:last-child {
            border-top-right-radius: 8px;
        }

        .tab-button:hover {
            color: var(--text-color);
            background: #fff;
        }

        .tab-button.active {
            background: var(--white);
            color: var(--primary-color);
            border-color: var(--border-color);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.04);
        }

        .tab-content {
            display: none;
            padding: 30px;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .form-group small {
            display: block;
            font-size: 12px;
            color: var(--text-light);
            margin-top: 4px;
        }

        .password-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .forgot-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 12px;
            font-size: 16px;
            pointer-events: none;
            color: var(--text-light);
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 21, 56, 0.1);
        }

        .input-wrapper input::placeholder {
            color: #999;
        }

        .input-wrapper.has-password input {
            padding-right: 48px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 50%;
            background: transparent;
            color: var(--text-light);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .password-toggle:hover,
        .password-toggle:focus-visible {
            background: rgba(139, 21, 56, 0.08);
            color: var(--primary-color);
            outline: none;
        }

        .password-toggle:active {
            transform: scale(0.94);
        }

        .password-toggle svg {
            width: 20px;
            height: 20px;
            fill: none;
        }

        .password-toggle .icon-eye {
            display: none;
        }

        .password-toggle .icon-eye-off {
            display: block;
        }

        .password-toggle.is-visible .icon-eye {
            display: block;
        }

        .password-toggle.is-visible .icon-eye-off {
            display: none;
        }

        .password-rules {
            font-size: 12px;
            color: var(--text-light);
            line-height: 1.5;
            margin-top: 6px;
        }

        .password-rules .met {
            color: var(--success-color);
            font-weight: 700;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        .checkbox-group label {
            cursor: pointer;
            margin: 0;
            font-weight: 400;
        }

        .checkbox-group a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .checkbox-group a:hover {
            text-decoration: underline;
        }

        .btn-primary {
            width: 100%;
            padding: 12px 16px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 4px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 21, 56, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-arrow {
            font-size: 16px;
        }

        .error-message {
            background: #ffebee;
            color: var(--error-color);
            padding: 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 16px;
            border-left: 4px solid var(--error-color);
        }

        .success-message {
            background: #e8f5e9;
            color: var(--success-color);
            padding: 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 16px;
            border-left: 4px solid var(--success-color);
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .loading::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 3px solid var(--border-color);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .terms-notice {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            font-size: 13px;
            color: var(--text-light);
            line-height: 1.6;
            max-width: 450px;
        }

        .footer {
            background: var(--white);
            border-top: 1px solid var(--border-color);
            padding: 24px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: auto;
        }

        .footer-left p {
            margin: 0;
            font-size: 13px;
            color: var(--text-light);
        }

        .footer-left strong {
            color: var(--primary-color);
        }

        .footer-right {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .footer-right a {
            color: var(--text-light);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .footer-right a:hover {
            color: var(--primary-color);
        }

        @media (max-width: 600px) {
            .content-wrapper {
                padding: 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .auth-card {
                max-width: 100%;
            }

            .tab-content {
                padding: 20px;
            }

            .footer {
                flex-direction: column;
                text-align: center;
            }

            .footer-right {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>
    <div class="container">
        <div class="content-wrapper">
            <!-- Header -->
            <div class="header">
                <h1>SPACEBOOK</h1>
                <p class="subtitle">UTMSPACE Rooms and Facilities Management System</p>
            </div>

            <div class="form-switcher">
                <button class="tab-button active" data-tab="signin">Login</button>
                <button class="tab-button" data-tab="register">Register</button>
            </div>

            <!-- Auth Card -->
            <div class="auth-card">
                <!-- Sign In Form -->
                <div id="signin-tab" class="tab-content active">
                    <form id="signin-form" onsubmit="handleSignIn(event)">
                        <!-- Email Field -->
                        <div class="form-group">
                            <label for="signin-login-id">University Email / UTM ID</label>
                            <div class="input-wrapper">
                                <span class="input-icon">✉️</span>
                                <input 
                                    type="text" 
                                    id="signin-login-id" 
                                    placeholder="student@graduate.utm.my or A22EC0000"
                                    required
                                >
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="form-group">
                            <div class="password-header">
                                <label for="signin-password">Password</label>
                                <a href="forgot-password.html" class="forgot-link">Forgot Password?</a>
                            </div>
                            <div class="input-wrapper has-password">
                                <span class="input-icon">🔒</span>
                                <input 
                                    type="password" 
                                    id="signin-password" 
                                    placeholder="••••••••"
                                    required
                                >
                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('signin-password', this)" aria-label="Show password" aria-pressed="false">
    <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
    <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 8 10 8a17.98 17.98 0 0 1-2.25 3.35"></path><path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"></path><path d="M3 3l18 18"></path><path d="M6.58 6.58C3.95 8.35 2 12 2 12s3.5 8 10 8c1.5 0 2.86-.34 4.05-.88"></path></svg>
</button>
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="checkbox-group">
                            <input 
                                type="checkbox" 
                                id="remember-me"
                                onchange="toggleRememberMe()"
                            >
                            <label for="remember-me">Keep me signed in on this device</label>
                        </div>

                        <!-- Sign In Button -->
                        <button type="submit" class="btn-primary">
                            Sign In to Dashboard
                            <span class="btn-arrow">→</span>
                        </button>

                        <!-- Error Message -->
                        <div id="signin-error" class="error-message" style="display:none;"></div>
                        <!-- Loading Spinner -->
                        <div id="signin-loading" class="loading" style="display:none;"></div>
                    </form>

                    <p style="text-align:center;margin-bottom:16px;font-size:14px;">No account? <a href="#" onclick="switchTab('register'); return false;">Create one</a></p>
                </div>
                <!-- Register Form -->
                <div id="register-tab" class="tab-content">
                    <form id="signup-form" onsubmit="handleSignUp(event)">
                        <div class="form-group">
                            <label for="signup-name">Full Name</label>
                            <div class="input-wrapper"><span class="input-icon">👤</span><input type="text" id="signup-name" placeholder="John Doe" required></div>
                        </div>
                        <div class="form-group">
                            <label for="signup-utm-id">UTM ID</label>
                            <div class="input-wrapper"><span class="input-icon">🆔</span><input type="text" id="signup-utm-id" placeholder="A22EC0000" required></div>
                        </div>
                        <div class="form-group">
                            <label for="signup-ic-no">Identification Card Number (IC No.)</label>
                            <div class="input-wrapper"><span class="input-icon">📇</span><input type="text" id="signup-ic-no" placeholder="901010-10-1010" required></div>
                        </div>
                        <div class="form-group">
                            <label for="signup-email">University Email</label>
                            <div class="input-wrapper"><span class="input-icon">✉️</span><input type="email" id="signup-email" placeholder="student@graduate.utm.my" required></div>
                        </div>
                        <div class="form-group">
                            <label for="signup-password">Password</label>
                            <div class="input-wrapper has-password"><span class="input-icon">🔒</span><input type="password" id="signup-password" placeholder="••••••••" minlength="8" oninput="updatePasswordRules()" required><button type="button" class="password-toggle" onclick="togglePasswordVisibility('signup-password', this)" aria-label="Show password" aria-pressed="false">
    <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
    <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 8 10 8a17.98 17.98 0 0 1-2.25 3.35"></path><path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"></path><path d="M3 3l18 18"></path><path d="M6.58 6.58C3.95 8.35 2 12 2 12s3.5 8 10 8c1.5 0 2.86-.34 4.05-.88"></path></svg>
</button></div><div class="password-rules" id="password-rules">Min 8 characters + uppercase + lowercase + number + symbol.</div>
                        </div>
                        <div class="form-group">
                            <label for="signup-confirm">Confirm Password</label>
                            <div class="input-wrapper has-password"><span class="input-icon">🔐</span><input type="password" id="signup-confirm" placeholder="••••••••" required><button type="button" class="password-toggle" onclick="togglePasswordVisibility('signup-confirm', this)" aria-label="Show password" aria-pressed="false">
    <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
    <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 8 10 8a17.98 17.98 0 0 1-2.25 3.35"></path><path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"></path><path d="M3 3l18 18"></path><path d="M6.58 6.58C3.95 8.35 2 12 2 12s3.5 8 10 8c1.5 0 2.86-.34 4.05-.88"></path></svg>
</button></div>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="agree-terms" required>
                            <label for="agree-terms">I agree to the Terms of Service</label>
                        </div>
                        <button type="submit" class="btn-primary">Create Account <span class="btn-arrow">→</span></button>
                        <div id="signup-error" class="error-message" style="display:none;"></div>
                        <div id="signup-success" class="success-message" style="display:none;"></div>
                        <div id="signup-loading" class="loading" style="display:none;"></div>
                    </form>
                </div>

            </div>

            <!-- Terms Notice -->
            <div class="terms-notice">
                <p>By signing in, you agree to the University Facilities Use Policy and General Terms of Service regarding campus resource reservation.</p>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-left">
                <p><strong>SPACEBOOK</strong> © 2026 UTMSPACE Rooms and Facilities Management. All rights reserved.</p>
            </div>
            <div class="footer-right">
                <a href="#">Terms of Service</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Accessibility</a>
                <a href="#">Contact Support</a>
            </div>
        </footer>
    </div>

    <script>

        async function handleSignIn(event) {
            event.preventDefault();
            const login_id = document.getElementById('signin-login-id').value.trim();
            const password = document.getElementById('signin-password').value;

            if (!login_id || !password) {
                showError('signin', 'Please fill in all fields');
                return;
            }

            if (login_id.includes('@') && !validateEmail(login_id)) {
                showError('signin', 'Please enter a valid university email or UTM ID');
                return;
            }

            showLoading('signin', true);
            try {
                const response = await fetch('../../api/auth/auth_login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ login_id, password })
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Sign in failed');
                }

                const checkbox = document.getElementById('remember-me');
                if (checkbox.checked) {
                    localStorage.setItem('rememberLoginId', login_id);
                } else {
                    localStorage.removeItem('rememberLoginId');
                }

                window.location.href = getRoleBasedRedirect(result.user?.role);
            } catch (error) {
                showError('signin', error.message || 'Unable to sign in.');
            } finally {
                showLoading('signin', false);
            }
        }


        function getRoleBasedRedirect(role) {
            const normalizedRole = (role || '').toLowerCase();

            if (normalizedRole === 'admin') {
                return '../../manager_admin/admin_dashboard.php';
            }

            if (normalizedRole === 'manager' || normalizedRole === 'facility_manager') {
                return '../../manager_admin/facility_manager_dashboard.php';
            }

            return '../../homepage.php';
        }

        function handleSSO() {
            window.location.href = '/Shibboleth.sso/Login?target=' + encodeURIComponent(window.location.href);
        }

        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function showError(tab, message) {
            const errorElement = document.getElementById(tab + '-error');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
        }

        function showLoading(tab, show) {
            const loadingElement = document.getElementById(tab + '-loading');
            const submitButton = document.querySelector('#' + tab + '-form button[type="submit"]');
            if (show) {
                if (loadingElement) loadingElement.style.display = 'block';
                if (submitButton) submitButton.disabled = true;
            } else {
                if (loadingElement) loadingElement.style.display = 'none';
                if (submitButton) submitButton.disabled = false;
            }
        }



        function isStrongPassword(password) {
            return password.length >= 8
                && /[A-Z]/.test(password)
                && /[a-z]/.test(password)
                && /\d/.test(password)
                && /[^A-Za-z0-9]/.test(password);
        }

        function updatePasswordRules() {
            const passwordInput = document.getElementById('signup-password');
            const rules = document.getElementById('password-rules');
            if (!passwordInput || !rules) return;
            const password = passwordInput.value;
            const checks = [
                { label: '8 characters', met: password.length >= 8 },
                { label: 'uppercase', met: /[A-Z]/.test(password) },
                { label: 'lowercase', met: /[a-z]/.test(password) },
                { label: 'number', met: /\d/.test(password) },
                { label: 'symbol', met: /[^A-Za-z0-9]/.test(password) }
            ];
            rules.innerHTML = checks.map(item => `<span class="${item.met ? 'met' : ''}">${item.met ? '✓' : '○'} ${item.label}</span>`).join(' · ');
        }

        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            button.classList.toggle('is-visible', shouldShow);
            button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
        }

        async function handleSignUp(event) {
            event.preventDefault();
            const full_name = document.getElementById('signup-name').value.trim();
            const utm_id = document.getElementById('signup-utm-id').value.trim();
            const ic_no = document.getElementById('signup-ic-no').value.trim();
            const email = document.getElementById('signup-email').value.trim();
            const password = document.getElementById('signup-password').value;
            const confirmPassword = document.getElementById('signup-confirm').value;

            if (password !== confirmPassword) {
                showError('signup', 'Passwords do not match');
                return;
            }

            if (!isStrongPassword(password)) {
                showError('signup', 'Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.');
                updatePasswordRules();
                return;
            }

            showLoading('signup', true);
            try {
                const response = await fetch('../../api/auth/auth_register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ full_name, utm_id, ic_no, email, password })
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Registration failed');
                }

                const success = document.getElementById('signup-success');
                success.textContent = 'Registration successful. Please sign in.';
                success.style.display = 'block';
                setTimeout(() => switchTab('signin'), 1000);
            } catch (error) {
                showError('signup', error.message || 'Unable to register.');
            } finally {
                showLoading('signup', false);
            }
        }

        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(el => el.classList.remove('active'));
            document.getElementById(tabName + '-tab').classList.add('active');
            document.querySelector(`.tab-button[data-tab="${tabName}"]`).classList.add('active');
        }

        document.addEventListener('DOMContentLoaded', async function() {
            document.querySelectorAll('.tab-button').forEach(button => {
                button.addEventListener('click', () => switchTab(button.dataset.tab));
            });

            const rememberedLoginId = localStorage.getItem('rememberLoginId');
            if (rememberedLoginId) {
                document.getElementById('signin-login-id').value = rememberedLoginId;
                document.getElementById('remember-me').checked = true;
            }

            const response = await fetch('../../api/auth/auth_session.php', { credentials: 'same-origin' });
            if (response.ok) {
                const sessionData = await response.json();
                if (sessionData.authenticated) {
                    window.location.href = getRoleBasedRedirect(sessionData.user?.role);
                }
            }
        });
    </script>
</body>
</html>
