document.addEventListener('DOMContentLoaded', () => {
    const form         = document.getElementById('profileForm');
    const errorP       = document.getElementById('profileError');
    const submitBtn    = form.querySelector('button[type="submit"]');
    const notifyCheck  = document.getElementById('notifyCheckbox');

    (async function loadProfile() {
        try {
            const res = await fetch('api/status', {
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error();
            const { user } = await res.json();
            form.username.value = user.username;
            form.email.value    = user.email;
            if (user.notifyOnComment !== undefined) {
                notifyCheck.checked = !!user.notifyOnComment;
            }
        } catch {
            window.location.href = '/login';
        }
    })();

    form.addEventListener('submit', async e => {
        e.preventDefault();
        errorP.textContent = '';

        const username = form.username.value.trim();
        const email    = form.email.value.trim();
        const pass     = form.password.value;
        const confirm  = form.confirmPassword.value;

        const updates = {};

        if (username) {
            updates.username = username;
        }
        if (email) {
            updates.email = email;
        }

        if (pass) {
            if (pass.length < 6) {
                errorP.textContent = 'Password must be at least 6 characters.';
                return;
            }
            if (pass !== confirm) {
                errorP.textContent = 'Passwords do not match.';
                return;
            }
            updates.password = pass;
        }

        updates.notifyOnComment = notifyCheck.checked ? '1' : '0';

        if (Object.keys(updates).length === 0) {
            errorP.textContent = 'Change at least one field for update.';
            return;
        }

        const fd = new FormData();
        Object.entries(updates).forEach(([k, v]) => fd.append(k, v));

        submitBtn.disabled    = true;
        submitBtn.textContent = 'Saving…';

        try {
            const res  = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            });
            const json = await res.json();

            if (res.ok) {
                window.location.href = '/';
            } else {
                errorP.textContent = json.message || 'Update failed.';
            }
        } catch (err) {
            errorP.textContent = 'Network error: ' + err.message;
        } finally {
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Save Changes';
        }
    });
});
