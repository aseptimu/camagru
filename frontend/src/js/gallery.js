document.addEventListener('DOMContentLoaded', () => {
    const gallery = document.getElementById('gallery');
    const pagination = {
        prevBtn: document.getElementById('prevPage'),
        nextBtn: document.getElementById('nextPage'),
        info: document.getElementById('pageInfo')
    };
    let currentPage = 1, totalPages = 1;
    let authenticated = false;
    let userId = null;

    async function checkAuth() {
        try {
            const res = await fetch('/api/status', {credentials: 'same-origin'});
            if (!res.ok) return;
            const json = await res.json();
            authenticated = json.authenticated;
            if (authenticated && json.user?.id) {
                userId = json.user.id;
            }
        } catch (err) {
            console.warn('Auth check failed', err);
        }
    }

    async function loadGallery(page = 1) {
        gallery.innerHTML = 'Loading…';

        const endpoint = `/api/images/feed?page=${page}&size=5`;

        try {
            const res = await fetch(endpoint, {credentials: 'same-origin'});
            if (!res.ok) throw new Error(res.status);
            const {page: p, items, total, size} = await res.json();
            currentPage = p;
            totalPages = Math.ceil(total / size);
            renderGallery(items);
            renderPagination();
        } catch (e) {
            gallery.innerHTML = `<p class="error">Error: ${e.message}</p>`;
        }
    }

    function renderGallery(items) {
        gallery.innerHTML = '';
        items.forEach(img => {
            const card = document.createElement('div');
            card.classList.add('gallery-item');
            card.innerHTML = `
                <img src="/uploads/${img.url}" alt="${img.original_name}" />
                <div class="meta">
                  <span class="username">${img.username}</span>
                  <span class="date">${new Date(img.created_at).toLocaleString()}</span>
                </div>
                <div class="actions">
                  <button
                    class="like-btn"
                    data-id="${img.id}"
                    data-liked="${img.liked_by_me}"
                    ${!authenticated ? 'disabled' : ''}
                  >
                    ${img.liked_by_me ? '❤️' : '🤍'} ${img.like_count}
                  </button>
                  <button
                    class="comment-toggle-btn"
                    data-id="${img.id}"
                    ${!authenticated ? 'disabled' : ''}
                  >
                    💬 ${img.comment_count}
                  </button>
                </div>
                <div class="comments-list hide" id="comments-${img.id}"></div>
                <div class="comment-form hide" id="cf-${img.id}">
                  <textarea id="ta-${img.id}" placeholder="Write a comment…"></textarea>
                  <button class="comment-submit-btn" data-id="${img.id}">Send</button>
                </div>
            `;
            gallery.appendChild(card);
        });

        gallery.querySelectorAll('.like-btn').forEach(btn =>
            btn.addEventListener('click', toggleLike)
        );

        gallery.querySelectorAll('.comment-toggle-btn').forEach(btn =>
            btn.addEventListener('click', async e => {
                const id = e.currentTarget.dataset.id;
                const list = document.getElementById(`comments-${id}`);
                const form = document.getElementById(`cf-${id}`);

                if (list.classList.contains('hide')) {
                    list.innerHTML = 'Loading comments…';
                    try {
                        const res = await fetch(`/api/images/${id}/comments`, {credentials: 'same-origin'});
                        if (!res.ok) throw new Error(res.status);
                        const {comments} = await res.json();
                        list.innerHTML = comments.map(c => `
                            <div class="comment">
                              <strong>${c.username}</strong>
                              <span class="comment-date">${new Date(c.created_at).toLocaleString()}</span>
                              <p class="comment-text">${c.comment}</p>
                            </div>
                        `).join('') || '<p class="no-comments">No comments yet</p>';
                    } catch (err) {
                        console.log(err)
                        list.innerHTML = `<p class="error">Failed to load comments</p>`;
                    }
                }
                list.classList.toggle('hide');
                form.classList.toggle('hide');
            })
        );

        gallery.querySelectorAll('.comment-submit-btn').forEach(btn =>
            btn.addEventListener('click', submitComment)
        );
    }

    function renderPagination() {
        pagination.info.textContent = `Page ${currentPage} of ${totalPages}`;
        pagination.prevBtn.disabled = currentPage <= 1;
        pagination.nextBtn.disabled = currentPage >= totalPages;
    }

    pagination.prevBtn.addEventListener('click', () => loadGallery(currentPage - 1));
    pagination.nextBtn.addEventListener('click', () => loadGallery(currentPage + 1));

    async function toggleLike(e) {
        const btn = e.currentTarget;
        const id = btn.dataset.id;
        const liked = btn.dataset.liked === 'true';
        const url = liked
            ? `/api/images/${id}/unlike`
            : `/api/images/${id}/like`;

        try {
            await fetch(url, {
                method: 'POST',
                credentials: 'same-origin'
            });
            await loadGallery(currentPage);
        } catch (err) {
            console.error('Like toggle error', err);
        }
    }

    async function submitComment(e) {
        const btn = e.currentTarget;
        const id = btn.dataset.id;
        const ta = document.getElementById(`ta-${id}`);
        const text = ta.value.trim();
        if (!text) return alert('Empty comment');

        btn.disabled = true;

        try {
            const body = new URLSearchParams({comment: text});
            await fetch(`/api/images/${id}/comments`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString()
            });
            ta.value = '';
            await loadGallery(currentPage);
        } catch (err) {
            console.error('Comment error', err);
            alert('Failed to post comment');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    (async () => {
        await checkAuth();
        await loadGallery();
    })();
});
