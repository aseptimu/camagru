document.addEventListener('DOMContentLoaded', async () => {
    const reg = document.getElementById('linkRegister');
    const logIn = document.getElementById('linkLogin');
    const upd = document.getElementById('linkUpdate');
    const logOut = document.getElementById('linkLogout');
    const usernameServer = document.getElementById('userName');

    let authenticated = false;
    let username = '';
    try {
        const res = await fetch('/api/status', {credentials: 'same-origin'});
        if (res.ok) {
            const json = await res.json();
            authenticated = json.authenticated;
            username = json.user?.username || '';
        }
    } catch (err) {
        console.error('Failed to fetch session status', err);
    }

    const p = window.location.pathname.toLowerCase();
    const isLogin = p.endsWith('/login') || p.endsWith('/login.html');
    const isRegister = p.endsWith('/register') || p.endsWith('/register.html');

    if (authenticated && (isLogin || isRegister)) {
        window.location.href = '/';
        return;
    }

    if (reg)    reg.classList.toggle('hide', authenticated);
    if (logIn)  logIn.classList.toggle('hide', authenticated);
    if (upd)    upd.classList.toggle('hide', !authenticated);
    if (logOut) logOut.classList.toggle('hide', !authenticated);

    if (usernameServer && authenticated && username) {
        usernameServer.textContent = username;
        usernameServer.classList.remove('hide');
    }

    if (logOut) {
        logOut.addEventListener('click', async e => {
            e.preventDefault();
            await fetch('/api/logout', {
                method: 'POST',
                credentials: 'same-origin'
            });
            window.location.href = '/';
        });
    }
});
