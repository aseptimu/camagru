document.addEventListener('DOMContentLoaded', () => {
    (async function checkSession() {
        try {
            const res = await fetch('/api/status', {
                credentials: 'same-origin'
            });
            const { authenticated } = await res.json();
            document.getElementById('logout').classList.toggle('hide', !authenticated);
            document.getElementById('login').classList.toggle('hide', authenticated);
            document.getElementById('register').classList.toggle('hide', authenticated);
        } catch (e) {
            console.error('Status check failed:', e);
        }
    })();

    const logoutLink = document.getElementById('logout');
    logoutLink.addEventListener('click', async e => {
        e.preventDefault();
        try {
            await fetch('/api/logout', {
                method: 'POST',
                credentials: 'same-origin'
            });
        } catch (err) {
            console.error('Logout failed:', err);
        }
        window.location.href = '/';
    });

    const gallery     = document.getElementById('gallery');
    const uploadBtn   = document.getElementById('uploadBtn');
    const fileInput   = document.getElementById('fileInput');
    const startCamBtn = document.getElementById('startCamBtn');
    const stopCamBtn  = document.getElementById('stopCamBtn');
    const captureBtn  = document.getElementById('captureBtn');
    const cameraDiv   = document.getElementById('camera');
    const video       = document.getElementById('video');

    let stream = null;

    async function loadGallery() {
        gallery.innerHTML = 'Loading…';
        try {
            const res = await fetch('/api/images', {
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error(res.status);
            const images = await res.json();
            gallery.innerHTML = '';
            images.forEach(meta => {
                const img = document.createElement('img');
                img.src = `/uploads/${meta.filename}`;
                img.alt = meta.original_name;
                gallery.appendChild(img);
            });
        } catch (e) {
            gallery.innerHTML = `<p class="error">Ошибка: ${e.message}</p>`;
        }
    }
    loadGallery();

    uploadBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', async () => {
        if (!fileInput.files.length) return;
        const fd = new FormData();
        fd.append('image', fileInput.files[0]);
        await uploadFormData(fd);
        await loadGallery();
    });

    startCamBtn.addEventListener('click', async () => {
        cameraDiv.style.display = 'block'
        if (stream) return;
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
        } catch (err) {
            alert('Unable to access camera: ' + err.message);
        }
    });

    stopCamBtn.addEventListener('click', () => {
        if (!stream) return;
        stream.getTracks().forEach(t => t.stop());
        stream = null;
        cameraDiv.style.display = 'none';
    });

    captureBtn.addEventListener('click', async () => {
        if (!stream) return;

        const w = video.videoWidth;
        const h = video.videoHeight;
        const canvas = document.createElement('canvas');
        canvas.width  = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');

        ctx.drawImage(video, 0, 0, w, h);

        canvas.toBlob(async blob => {
            const fd = new FormData();
            fd.append('image', blob, 'webcam.png');
            await uploadFormData(fd);
            await loadGallery();
        }, 'image/png');
    });

    async function uploadFormData(fd) {
        try {
            const res = await fetch('/api/images/upload', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            });
            if (!res.ok) throw new Error(res.status);
        } catch (e) {
            alert('Loading error: ' + e.message);
        }
    }
});
