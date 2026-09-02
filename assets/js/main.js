// main.js

/**
 * Returns the CSRF token from the <meta name="csrf-token"> tag.
 * Falls back to '' if the tag is not present (e.g. public pages).
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

document.addEventListener('DOMContentLoaded', () => {
    // --- Theme Toggle Initialization ---
    const initTheme = () => {
        const savedTheme = localStorage.getItem('swipe_nest_theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        } else if (!prefersDark) {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    };

    initTheme();

    // Export toggle function for buttons
    window.toggleTheme = () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('swipe_nest_theme', newTheme);

        const themeIcons = document.querySelectorAll('.theme-icon');
        themeIcons.forEach(icon => {
            if (newTheme === 'light') {
                icon.innerHTML = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
            } else {
                icon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
            }
        });
    };

    // --- Dropdown Logic ---
    const dropdownToggles = document.querySelectorAll('[data-dropdown-toggle]');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const targetId   = toggle.getAttribute('data-dropdown-toggle');
            const targetMenu = document.getElementById(targetId);

            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu !== targetMenu) menu.classList.remove('active');
            });

            if (targetMenu) targetMenu.classList.toggle('active');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
            menu.classList.remove('active');
        });
    });

    // --- Modal Logic ---
    const modalToggles = document.querySelectorAll('[data-modal-target]');
    const modalCloses  = document.querySelectorAll('[data-modal-close]');

    modalToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const targetId    = toggle.getAttribute('data-modal-target');
            const targetModal = document.getElementById(targetId);
            if (targetModal) {
                targetModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    modalCloses.forEach(close => {
        close.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Initialize Lucide Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Add Premium Animations on Load
    const mainWrapper = document.querySelector('.main-wrapper');
    if (mainWrapper) mainWrapper.classList.add('animate-fade-in');

    const feedContent = document.querySelector('.feed-content') || document.querySelector('.grid-videos') || document.querySelector('.profile-header');
    if (feedContent) feedContent.classList.add('animate-slide-up');
});

// ─────────────────────────────────────────────────────────────────
// Global video delete — sends CSRF token via header
// ─────────────────────────────────────────────────────────────────
window.deleteVideo = async (videoId, btnEl) => {
    if (!confirm('Are you sure you want to delete this video?')) return;
    try {
        const fd = new FormData();
        fd.append('action',   'delete_video');
        fd.append('video_id', videoId);

        const res  = await fetch('../api/action.php', {
            method:  'POST',
            body:    fd,
            headers: { 'X-CSRF-Token': getCsrfToken() }
        });
        const data = await res.json();

        if (data.success) {
            const card = btnEl ? (btnEl.closest('.post-card') || btnEl.closest('.video-card')) : null;
            if (card) {
                card.style.transition = 'opacity 0.3s ease';
                card.style.opacity    = '0';
                setTimeout(() => card.remove(), 300);
            } else {
                window.location.reload();
            }
        } else {
            alert(data.message || 'Failed to delete video.');
        }
    } catch (e) {
        console.error('Delete failed', e);
        alert('An error occurred while deleting.');
    }
};

/* ─────────────────────────────────────────────────────────────────
   Interaction handlers (Like, Save, Comment, Share)
   All send X-CSRF-Token header for CSRF protection.
───────────────────────────────────────────────────────────────── */
window.toggleLike = async (videoId, btnEl) => {
    try {
        const fd = new FormData();
        fd.append('action',   'toggle_like');
        fd.append('video_id', videoId);

        const data = await (await fetch('../api/action.php', {
            method:  'POST',
            body:    fd,
            headers: { 'X-CSRF-Token': getCsrfToken() }
        })).json();

        if (data.success) {
            document.querySelectorAll(`.like-btn-${videoId}`).forEach(btn => {
                const svg  = btn.querySelector('svg');
                const span = btn.querySelector('span');
                if (data.status === 'liked') {
                    if (svg) {
                        svg.style.fill  = '#ff3040';
                        svg.style.color = '#ff3040';
                        svg.querySelectorAll('path, polygon').forEach(el => el.style.fill = '#ff3040');
                    }
                } else {
                    if (svg) {
                        svg.style.fill  = 'none';
                        svg.style.color = 'white';
                        svg.querySelectorAll('path, polygon').forEach(el => el.style.fill = '');
                    }
                }
                if (span) {
                    span.textContent = data.new_count > 1000
                        ? (data.new_count / 1000).toFixed(1) + 'K'
                        : data.new_count;
                }
            });
        }
    } catch (e) { console.error('Like failed', e); }
};

window.toggleSave = async (videoId, btnEl) => {
    try {
        const fd = new FormData();
        fd.append('action',   'toggle_save');
        fd.append('video_id', videoId);

        const data = await (await fetch('../api/action.php', {
            method:  'POST',
            body:    fd,
            headers: { 'X-CSRF-Token': getCsrfToken() }
        })).json();

        if (data.success) {
            document.querySelectorAll(`.save-btn-${videoId}`).forEach(btn => {
                const svg = btn.querySelector('svg');
                if (data.status === 'saved') {
                    btn.classList.add('saved');
                    if (svg) {
                        svg.style.fill  = 'white';
                        svg.style.color = 'white';
                        svg.querySelectorAll('path, rect, polygon').forEach(el => el.style.fill = 'white');
                    }
                } else {
                    btn.classList.remove('saved');
                    if (svg) {
                        svg.style.fill  = 'none';
                        svg.style.color = 'white';
                        svg.querySelectorAll('path, rect, polygon').forEach(el => el.style.fill = '');
                    }
                }
            });
        }
    } catch (e) { console.error('Save failed', e); }
};

let currentCommentVideoId  = null;
let currentCommentBtnSpan  = null;

window.addComment = async (videoId, btnEl) => {
    currentCommentVideoId = videoId;
    currentCommentBtnSpan = btnEl?.querySelector ? btnEl.querySelector('span') : null;

    document.getElementById('commentsOverlay')?.classList.add('active');
    document.getElementById('commentsModal')?.classList.add('active');

    const container = document.getElementById('commentsContainer');
    if (!container) return;
    container.textContent = ''; // clear safely

    // Temporary loading indicator
    const loading = document.createElement('div');
    loading.style.cssText = 'text-align:center;padding:20px;';
    loading.textContent   = 'Loading...';
    container.appendChild(loading);

    try {
        const data = await (await fetch('../api/comments.php?video_id=' + videoId)).json();
        container.textContent = ''; // clear loading

        if (data.success && data.comments.length > 0) {
            data.comments.forEach(c => {
                const el = document.createElement('div');
                el.className = 'comment-item';

                const img = document.createElement('img');
                img.src       = c.avatar_url;  // avatar_url is a URL, safe to set directly
                img.className = 'comment-avatar';

                const content = document.createElement('div');
                content.className = 'comment-content';

                const usernameEl = document.createElement('div');
                usernameEl.className   = 'comment-username';
                usernameEl.textContent = c.username;       // already htmlspecialchars'd server-side

                const textEl = document.createElement('div');
                textEl.className   = 'comment-text';
                textEl.textContent = c.comment_text;       // already htmlspecialchars'd server-side

                const timeEl = document.createElement('div');
                timeEl.className   = 'comment-time';
                timeEl.textContent = c.created_at;         // already htmlspecialchars'd server-side

                content.appendChild(usernameEl);
                content.appendChild(textEl);
                content.appendChild(timeEl);

                el.appendChild(img);
                el.appendChild(content);
                container.appendChild(el);
            });
        } else {
            const empty = document.createElement('div');
            empty.style.cssText = 'text-align:center;padding:20px;color:var(--color-text-secondary);';
            empty.textContent   = 'No comments yet. Be the first!';
            container.appendChild(empty);
        }
    } catch (e) {
        container.textContent = '';
        const errEl = document.createElement('div');
        errEl.style.cssText = 'text-align:center;padding:20px;color:red;';
        errEl.textContent   = 'Failed to load comments.';
        container.appendChild(errEl);
    }
};

window.shareVideo = (filePath) => {
    const url = window.location.origin + '/' + filePath;
    if (navigator.share) {
        navigator.share({ title: 'Check out this video on Swipe Nest', url }).catch(console.error);
    } else {
        prompt('Copy this link to share:', url);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const closeComments = () => {
        document.getElementById('commentsOverlay')?.classList.remove('active');
        document.getElementById('commentsModal')?.classList.remove('active');
    };

    document.getElementById('closeCommentsBtn')?.addEventListener('click', closeComments);
    document.getElementById('commentsOverlay')?.addEventListener('click', closeComments);

    const commentInput = document.getElementById('newCommentInput');
    const postBtn      = document.getElementById('postCommentBtn');

    commentInput?.addEventListener('input', () => {
        postBtn.disabled = commentInput.value.trim().length === 0;
    });

    postBtn?.addEventListener('click', async () => {
        const text = commentInput.value.trim();
        if (!text || !currentCommentVideoId) return;

        postBtn.disabled = true;
        try {
            const fd = new FormData();
            fd.append('action',   'add_comment');
            fd.append('video_id', currentCommentVideoId);
            fd.append('content',  text);

            const data = await (await fetch('../api/action.php', {
                method:  'POST',
                body:    fd,
                headers: { 'X-CSRF-Token': getCsrfToken() }
            })).json();

            if (data.success) {
                commentInput.value = '';
                if (currentCommentBtnSpan) currentCommentBtnSpan.textContent = data.count;
                window.addComment(currentCommentVideoId, currentCommentBtnSpan || document.body);
            }
        } catch (e) { console.error('Post failed', e); }
        postBtn.disabled = false;
    });
});

let tapTimers = {};

window.handleVideoTap = (videoEl, videoId) => {
    if (!tapTimers[videoId]) {
        tapTimers[videoId] = setTimeout(() => {
            tapTimers[videoId] = null;
            if (videoEl.paused) videoEl.play().catch(() => {});
            else videoEl.pause();
        }, 250);
    } else {
        clearTimeout(tapTimers[videoId]);
        tapTimers[videoId] = null;
        window.triggerDoubleTapLike(videoEl.parentElement, videoId);
    }
};

window.triggerDoubleTapLike = (cardEl, videoId) => {
    const heartContainer = document.createElement('div');
    heartContainer.className = 'heart-animation';

    const icon = document.createElement('i');
    icon.setAttribute('data-lucide', 'heart');
    icon.setAttribute('fill', 'white');
    heartContainer.appendChild(icon);

    cardEl.appendChild(heartContainer);
    if (typeof lucide !== 'undefined') lucide.createIcons({ nodes: [icon] });

    setTimeout(() => {
        if (heartContainer.parentNode) heartContainer.parentNode.removeChild(heartContainer);
    }, 900);

    const likeBtn = cardEl.querySelector(`.like-btn-${videoId}`);
    if (likeBtn) {
        const iconEl = likeBtn.tagName.toLowerCase() === 'svg' || likeBtn.tagName.toLowerCase() === 'i'
            ? likeBtn
            : likeBtn.querySelector('i, svg');
        if (iconEl && !iconEl.classList.contains('active')) {
            window.toggleLike(videoId, likeBtn);
        }
    }
};
