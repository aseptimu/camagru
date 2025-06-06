document.addEventListener('DOMContentLoaded', async () => {
    const params = new URLSearchParams(window.location.search);
    const token = params.get('token');
    const messageDiv = document.getElementById('confirmMessage');

    if (!token) {
        messageDiv.innerHTML = '<p class="error">Invalid confirmation link.</p>';
        return;
    }

    try {
        const res = await fetch(`/api/confirm?token=${encodeURIComponent(token)}`, {
            method: 'GET',
            credentials: 'same-origin',
        });
        const json = await res.json();
        if (res.ok) {
            messageDiv.innerHTML = '<h2>Account Confirmed!</h2><p>You can now <a href="/login">login</a>.</p>';
        } else {
            messageDiv.innerHTML = `<p class="error">${json.message || 'Confirmation failed.'}</p>`;
        }
    } catch (err) {
        messageDiv.innerHTML = `<p class="error">Error: ${err.message}</p>`;
    }
});