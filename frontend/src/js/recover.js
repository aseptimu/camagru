document.addEventListener('DOMContentLoaded', () => {
    const form     = document.getElementById('recoverForm');
    const emailIn  = document.getElementById('email');
    const errorP   = document.getElementById('recoverError');
    const submitBtn= form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async e => {
        e.preventDefault();
        errorP.textContent = '';

        submitBtn.disabled    = true;
        submitBtn.textContent = 'Sending…';

        const fd = new FormData();
        fd.append('email', emailIn.value);

        try {
            const res = await fetch('/api/recover', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            });

            if (res.ok) {
                errorP.style.color = 'green';
                errorP.textContent = 'Recovery link sent. Check your email.';
                form.reset();
            } else {
                let msg = `${res.status} ${res.statusText}`;
                try {
                    const json = await res.json();
                    if (json.message) msg = json.message;
                } catch {
                    const txt = await res.text();
                    if (txt) msg = txt;
                }
                errorP.style.color = 'red';
                errorP.textContent = msg;
            }
        } catch (netErr) {
            errorP.style.color = 'red';
            errorP.textContent = 'Network error: ' + netErr.message;
        } finally {
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Send link';
        }
    });
});
