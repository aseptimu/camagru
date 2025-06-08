document.addEventListener('DOMContentLoaded', () => {
    const form       = document.getElementById('resetForm');
    const tokenInput = document.getElementById('token');
    const passInput  = document.getElementById('password');
    const confInput  = document.getElementById('confirmPassword');
    const errorP     = document.getElementById('resetError');
    const submitBtn  = form.querySelector('button[type="submit"]');

    const params = new URLSearchParams(window.location.search);
    const token  = params.get('token');
    if (!token) {
        errorP.textContent    = 'Invalid password reset link.';
        submitBtn.disabled    = true;
        return;
    }
    tokenInput.value = token;

    (async () => {
        try {
            const res = await fetch('/api/status', { credentials: 'same-origin' });
            const js  = await res.json();
            if (js.authenticated) {
                window.location.href = '/';
            }
        } catch {}
    })();

    form.addEventListener('submit', async e => {
        e.preventDefault();
        errorP.textContent = '';

        const pass    = passInput.value;
        const confirm = confInput.value;

        if (pass !== confirm) {
            errorP.textContent = 'Passwords do not match.';
            return;
        }

        submitBtn.disabled    = true;
        submitBtn.textContent = 'Saving…';

        const fd = new FormData();
        fd.append('token', token);
        fd.append('password', pass);
        fd.append('confirmPassword', confirm);

        try {
            const res = await fetch('/api/reset', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            });

            if (res.ok) {
                window.location.href = '/login';
                return;
            }

            let msg = `${res.status} ${res.statusText}`;
            try {
                const json = await res.json();
                if (json.message) msg = json.message;
            } catch {
                const text = await res.text();
                if (text) msg = text;
            }
            errorP.textContent = msg;

        } catch (netErr) {
            errorP.textContent = 'Network error: ' + netErr.message;
        } finally {
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Save';
        }
    });
});
