// js/register.js

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('authForm');
    const errorP = document.getElementById('authError');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async e => {
        e.preventDefault();
        errorP.textContent = '';

        // Disable button to prevent double‐click
        submitBtn.disabled = true;
        submitBtn.textContent = 'Please wait…';

        const username = form.username.value.trim();
        const email = form.email.value.trim();
        const password = form.password.value;
        const confirmPassword = form.confirmPassword.value;

        // Validation
        if (username.length < 3) {
            errorP.textContent = 'Username must be at least 3 characters.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Register';
            return;
        }
        if (!email.includes('@') || email.length < 5) {
            errorP.textContent = 'Enter a valid email address.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Register';
            return;
        }
        if (password.length < 6) {
            errorP.textContent = 'Password must be at least 6 characters.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Register';
            return;
        }
        if (password !== confirmPassword) {
            errorP.textContent = 'Passwords do not match.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Register';
            return;
        }

        const fd = new FormData();
        fd.append('username', username);
        fd.append('email', email);
        fd.append('password', password);

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            });
            const json = await res.json();

            if (res.ok) {
                window.location.href = '/login.html';
            } else {
                errorP.textContent = json.message || 'Registration failed.';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Register';
            }
        } catch (err) {
            errorP.textContent = 'Network error: ' + err.message;
            submitBtn.disabled = false;
            submitBtn.textContent = 'Register';
        }
    });
});
