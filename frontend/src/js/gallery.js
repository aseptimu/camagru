document.addEventListener('DOMContentLoaded', () => {
    const gallery     = document.getElementById('gallery');
    const uploadBtn   = document.getElementById('uploadBtn');
    const fileInput   = document.getElementById('fileInput');
    const startCamBtn = document.getElementById('startCamBtn');
    const stopCamBtn  = document.getElementById('stopCamBtn');
    const captureBtn  = document.getElementById('captureBtn');
    const cameraDiv   = document.getElementById('camera');
    const video       = document.getElementById('video');

    let stream = null;

    // 1) Загрузка галереи
    async function loadGallery() {
        gallery.innerHTML = 'Загрузка…';
        try {
            const res = await fetch('/api/images');
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
            gallery.innerHTML = `<p style="color:red">Ошибка: ${e.message}</p>`;
        }
    }
    loadGallery();

    // 2) Кнопка «Upload Image»
    uploadBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', async () => {
        if (!fileInput.files.length) return;
        const fd = new FormData();
        fd.append('image', fileInput.files[0]);
        await uploadFormData(fd);
        loadGallery();
    });

    // 3) Запуск камеры
    startCamBtn.addEventListener('click', async () => {
        cameraDiv.style.display = '';
        if (stream) return;
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
        } catch (err) {
            alert('Не удалось получить доступ к камере: ' + err.message);
        }
    });

    // 4) Остановка камеры
    stopCamBtn.addEventListener('click', () => {
        if (!stream) return;
        stream.getTracks().forEach(t => t.stop());
        stream = null;
        cameraDiv.style.display = 'none';
    });

    // 5) Съёмка и отправка изображения
    captureBtn.addEventListener('click', async () => {
        if (!stream) return;

        // создаём canvas с тем же размером
        const w = video.videoWidth;
        const h = video.videoHeight;
        const canvas = document.createElement('canvas');
        canvas.width  = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');

        // рисуем фрейм из видео
        ctx.drawImage(video, 0, 0, w, h);

        // (опционально) тут можно рисовать наклейки:
        // const sticker = new Image();
        // sticker.src = '/path/to/sticker.png';
        // await sticker.decode();
        // ctx.drawImage(sticker, x, y, sw, sh);

        // получаем Blob в формате PNG (с альфа-каналом)
        canvas.toBlob(async blob => {
            const fd = new FormData();
            fd.append('image', blob, 'webcam.png');
            await uploadFormData(fd);
            loadGallery();
        }, 'image/png');
    });

    // вспомогательная функция отправки
    async function uploadFormData(fd) {
        try {
            const res = await fetch('/api/images/upload', {
                method: 'POST',
                body: fd
            });
            if (!res.ok) throw new Error(res.status);
        } catch (e) {
            alert('Ошибка загрузки: ' + e.message);
        }
    }
});
