document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('video');
    const startCam = document.getElementById('startCam');
    const captureBtn = document.getElementById('captureBtn');
    const uploadInput = document.getElementById('uploadInput');
    const uploadBtn = document.getElementById('uploadBtn');
    const overlaysList = document.getElementById('overlaysList');
    const userImages = document.getElementById('userImages');

    let stream = null;
    let selectedOverlay = null;
    let authenticated = false;
    let userId = null;

    async function checkAuth() {
        try {
            const res = await fetch('/api/status', {credentials: 'same-origin'});
            if (!res.ok) return location.href = '/login';
            const json = await res.json();
            authenticated = json.authenticated;
            if (!authenticated) location.href = '/login';
            if (authenticated && json.user?.id) {
                userId = json.user.id;
            }
        } catch (err) {
            console.warn('Auth check failed', err);
        }
    }

    async function loadOverlays() {
        const res = await fetch('/overlays/', {credentials: 'same-origin'});
        const html = await res.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const hrefs = Array.from(doc.querySelectorAll('pre a'))
            .map(a => a.getAttribute('href'))
            .filter(h => /\.(png|jpe?g|gif)$/i.test(h) && h !== '../');

        overlaysList.innerHTML = '';
        hrefs.forEach(filename => {
            const img = document.createElement('img');
            img.src = '/overlays/' + encodeURIComponent(filename);
            img.dataset.filename = filename;
            img.addEventListener('click', () => selectOverlay(img));
            overlaysList.appendChild(img);
        });
    }

    function selectOverlay(imgEl) {
        overlaysList.querySelectorAll('img').forEach(i => i.classList.remove('selected'));
        imgEl.classList.add('selected');
        selectedOverlay = imgEl.dataset.filename;
        captureBtn.disabled = false;
        uploadBtn.disabled = false;
        document.getElementById('overlayDisplay').src = imgEl.src;
    }

    async function loadUserImages() {
        const endpoint = `/api/images/user/${userId}?page=1&size=5`;
        try {
            const res = await fetch(endpoint, {credentials: 'same-origin'});
            if (!res.ok) throw new Error(res.status);
            const {items} = await res.json();
            userImages.innerHTML = '';
            items.forEach(img => {
                const div = document.createElement('div');
                div.className = 'thumb';
                const imageEl = document.createElement('img');
                imageEl.src = `/uploads/${img.filename}`;
                div.appendChild(imageEl);
                userImages.appendChild(div);
            });
        } catch (e) {
            userImages.innerHTML = `<p class="error">Error: ${e.message}</p>`;
        }
    }

    startCam.addEventListener('click', async () => {
        if (stream) return;
        stream = await navigator.mediaDevices.getUserMedia({video: true});
        video.srcObject = stream;
    });

    captureBtn.addEventListener('click', () => {
        if (!stream || !selectedOverlay) return;
        const [w, h] = [video.videoWidth, video.videoHeight];
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, w, h);
        canvas.toBlob(blob => sendEdit(blob), 'image/png');
    });

    uploadBtn.addEventListener('click', () => {
        uploadInput.click();
    });

    uploadInput.addEventListener('change', () => {
        const file = uploadInput.files[0];
        if (!file || !selectedOverlay) return;
        sendEdit(file);
    });

    async function sendEdit(blob) {
        const fd = new FormData();
        fd.append('image', blob);
        fd.append('overlay', selectedOverlay);

        captureBtn.disabled = true;
        uploadBtn.disabled = true;

        try {
            const res = await fetch('/api/images/upload', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            });
            if (!res.ok) throw new Error(res.status);
            await loadUserImages();
        } catch (e) {
            console.error('Upload failed', e);
        } finally {
            captureBtn.disabled = false;
            uploadBtn.disabled = false;
            uploadInput.value = '';
        }
    }

    (async () => {
        await checkAuth();
        await loadOverlays();
        await loadUserImages();
    })();
});
