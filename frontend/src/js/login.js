document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('authForm');
    const errorP = document.getElementById('authError');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async e => {
        e.preventDefault();
        errorP.textContent = '';

        submitBtn.disabled = true;
        submitBtn.textContent = 'Please wait…';

        const username = form.username.value.trim();
        const password = form.password.value;

        if (username.length < 3) {
            errorP.textContent = 'Username must be at least 3 characters.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Login';
            return;
        }
        if (password.length < 6) {
            errorP.textContent = 'Password must be at least 6 characters.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Login';
            return;
        }

        const fd = new FormData();
        fd.append('username', username);
        fd.append('password', password);

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            });
            const json = await res.json();

            if (res.ok) {
                window.location.href = '/';
            } else {
                errorP.textContent = json.message || 'Login failed.';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login';
            }
        } catch (err) {
            errorP.textContent = 'Network error: ' + err.message;
            submitBtn.disabled = false;
            submitBtn.textContent = 'Login';
        }
    });
});
