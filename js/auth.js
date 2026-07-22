const authMessage = document.getElementById('login-message');
const loginForm = document.getElementById('login-form');
const loginSubmit = document.getElementById('login-submit');
const togglePassword = document.getElementById('toggle-password');

if (loginForm) {
    togglePassword.addEventListener('click', () => {
        const passwordInput = document.getElementById('login-password');
        if (!passwordInput) return;
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        togglePassword.textContent = isPassword ? 'Hide' : 'Show';
    });

    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const username = document.getElementById('login-username').value.trim();
        const password = document.getElementById('login-password').value;
        const remember = document.getElementById('remember-me').checked;

        authMessage.textContent = '';
        loginSubmit.disabled = true;
        loginSubmit.textContent = 'Signing in...';

        try {
            const response = await fetch('api/auth_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password, remember })
            });

            const responseText = await response.text();
            let result = null;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Auth response JSON parse error:', parseError, responseText);
            }

            if (!response.ok || !result || result.status !== 'success') {
                if (result && result.message) {
                    authMessage.textContent = result.message;
                } else if (!response.ok) {
                    authMessage.textContent = `Login failed (${response.status}). ${responseText}`;
                } else {
                    authMessage.textContent = `Login failed. Server response: ${responseText}`;
                }
                loginSubmit.disabled = false;
                loginSubmit.textContent = 'Sign In';
                return;
            }

            window.location.href = result.redirect || 'admin.html';
        } catch (error) {
            console.error('Login request error:', error);
            authMessage.textContent = `Login failed. ${error.message || 'Please try again.'}`;
            loginSubmit.disabled = false;
            loginSubmit.textContent = 'Sign In';
        }
    });
}

async function fetchAdminApi(path, options = {}) {
    const headers = options.headers || {};
    if (typeof window !== 'undefined' && window.CSRF_TOKEN && options.method && options.method.toUpperCase() !== 'GET') {
        headers['X-CSRF-Token'] = window.CSRF_TOKEN;
    }
    const response = await fetch(path, { ...options, headers });
    const text = await response.text();
    let data = null;
    try { data = JSON.parse(text); } catch (e) { }
    if (!response.ok) {
        throw new Error(data?.message || `HTTP ${response.status}`);
    }
    return data;
}

async function logoutAdmin() {
    await fetch('api/logout.php', { method: 'POST' });
    window.location.href = 'login.html';
}
