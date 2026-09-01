// main.js

document.addEventListener('DOMContentLoaded', () => {
    // --- Theme Toggle Initialization ---
    const initTheme = () => {
        const savedTheme = localStorage.getItem('zyva_theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        } else if (!prefersDark) {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            // Default is dark (variables.css default)
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    };

    initTheme();

    // Export toggle function for buttons
    window.toggleTheme = () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('zyva_theme', newTheme);
        
        // Optional: Update toggle icon if it exists
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
            const targetId = toggle.getAttribute('data-dropdown-toggle');
            const targetMenu = document.getElementById(targetId);
            
            // Close others
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu !== targetMenu) menu.classList.remove('active');
            });
            
            if (targetMenu) {
                targetMenu.classList.toggle('active');
            }
        });
    });

    // Close dropdowns on click outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
            menu.classList.remove('active');
        });
    });

    // --- Modal Logic ---
    const modalToggles = document.querySelectorAll('[data-modal-target]');
    const modalCloses = document.querySelectorAll('[data-modal-close]');

    modalToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const targetId = toggle.getAttribute('data-modal-target');
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

    // Initialize Lucide Icons (if used)
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Add Premium Animations on Load
    const mainWrapper = document.querySelector('.main-wrapper');
    if (mainWrapper) {
        mainWrapper.classList.add('animate-fade-in');
    }
    
    const feedContent = document.querySelector('.feed-content') || document.querySelector('.grid-videos') || document.querySelector('.profile-header');
    if (feedContent) {
        feedContent.classList.add('animate-slide-up');
    }
});

// Global video delete function
window.deleteVideo = async (videoId, btnEl) => {
    if (!confirm('Are you sure you want to delete this video?')) return;
    try {
        const fd = new FormData();
        fd.append('action', 'delete_video');
        fd.append('video_id', videoId);
        
        const res = await fetch('../api/action.php', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            const card = btnEl.closest('.post-card') || btnEl.closest('.video-card');
            if (card) {
                card.style.transition = 'opacity 0.3s ease';
                card.style.opacity = '0';
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
───────────────────────────────────────────────────────────────── */
window.toggleLike = async (videoId, btnEl) => {
    try {
        const fd = new FormData();
        fd.append('action', 'toggle_like');
        fd.append('video_id', videoId);

        const data = await (await fetch('../api/action.php', { method: 'POST', body: fd })).json();
        if (data.success) {
            document.querySelectorAll(`.like-btn-${videoId}`).forEach(btn => {
                const icon = btn.tagName.toLowerCase() === 'svg' || btn.tagName.toLowerCase() === 'i' ? btn : btn.querySelector('i, svg');
                const span = btn.querySelector('span');
                if (data.status === 'liked') {
                    icon.setAttribute('fill', '#ff3040');
                    icon.style.color = '#ff3040';
                    icon.classList.add('active');
                } else {
                    icon.removeAttribute('fill');
                    icon.style.color = '';
                    icon.classList.remove('active');
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
        fd.append('action', 'toggle_save');
        fd.append('video_id', videoId);

        const data = await (await fetch('../api/action.php', { method: 'POST', body: fd })).json();
        if (data.success) {
            document.querySelectorAll(`.save-btn-${videoId}`).forEach(btn => {
                const icon = btn.tagName.toLowerCase() === 'svg' || btn.tagName.toLowerCase() === 'i' ? btn : btn.querySelector('i, svg');
                if (data.status === 'saved') {
                    icon.setAttribute('fill', 'white');
                    icon.classList.add('active');
                } else {
                    icon.removeAttribute('fill');
                    icon.classList.remove('active');
                }
            });
        }
    } catch (e) { console.error('Save failed', e); }
};

let currentCommentVideoId = null;
let currentCommentBtnSpan = null;

window.addComment = async (videoId, btnEl) => {
    currentCommentVideoId = videoId;
    currentCommentBtnSpan = btnEl.querySelector ? btnEl.querySelector('span') : null;

    document.getElementById('commentsOverlay')?.classList.add('active');
    document.getElementById('commentsModal')?.classList.add('active');

    const container = document.getElementById('commentsContainer');
    if (!container) return;
    container.innerHTML = '<div style="text-align:center;padding:20px;">Loading...</div>';

    try {
        const data = await (await fetch('../api/comments.php?video_id=' + videoId)).json();
        container.innerHTML = '';

        if (data.success && data.comments.length > 0) {
            data.comments.forEach(c => {
                const el = document.createElement('div');
                el.className = 'comment-item';
                el.innerHTML = `
                    <img src="${c.avatar_url}" class="comment-avatar">
                    <div class="comment-content">
                        <div class="comment-username">${c.username}</div>
                        <div class="comment-text">${c.comment_text}</div>
                        <div class="comment-time">${c.created_at}</div>
                    </div>`;
                container.appendChild(el);
            });
        } else {
            container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--color-text-secondary);">No comments yet. Be the first!</div>';
        }
    } catch (e) {
        container.innerHTML = '<div style="text-align:center;padding:20px;color:red;">Failed to load comments.</div>';
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

            const data = await (await fetch('../api/action.php', { method: 'POST', body: fd })).json();
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
            if (videoEl.paused) videoEl.play().catch(()=>{});
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
    heartContainer.innerHTML = '<i data-lucide="heart" fill="white"></i>';
    cardEl.appendChild(heartContainer);
    if (typeof lucide !== 'undefined') lucide.createIcons({ root: heartContainer });
    
    setTimeout(() => {
        if (heartContainer.parentNode) heartContainer.parentNode.removeChild(heartContainer);
    }, 900);

    const likeBtn = cardEl.querySelector(`.like-btn-${videoId}`);
    if (likeBtn) {
        const icon = likeBtn.tagName.toLowerCase() === 'svg' || likeBtn.tagName.toLowerCase() === 'i' ? likeBtn : likeBtn.querySelector('i, svg');
        if (icon && !icon.classList.contains('active')) {
            window.toggleLike(videoId, likeBtn);
        }
    }
};
