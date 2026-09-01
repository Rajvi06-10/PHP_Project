/**
 * feed.js — Scrolls page 2D carousel
 *
 * Outer swiper  → HORIZONTAL  (swipe left / right  = change CATEGORY)
 * Inner swiper  → VERTICAL    (swipe up  / down     = change VIDEO inside category)
 *
 * Category pills always reflect the currently visible category.
 */

let globalFeed         = [];
window.outerSwiper     = null;   // exposed for left/right arrow buttons
let innerSwipers       = [];

document.addEventListener('DOMContentLoaded', fetchFeedData);

/* ─────────────────────────────────────────────────────────────────
   Data fetch
───────────────────────────────────────────────────────────────── */
async function fetchFeedData() {
    try {
        const res  = await fetch('../api/feed.php');
        const data = await res.json();
        if (data.success) {
            globalFeed = data.feed;
            render2DCarousel(globalFeed);
        } else {
            console.error('Feed error:', data.message);
        }
    } catch (e) {
        console.error('Fetch failed:', e);
    }
}

/* ─────────────────────────────────────────────────────────────────
   DOM builder
───────────────────────────────────────────────────────────────── */
function render2DCarousel(feed) {
    const wrapper = document.getElementById('videoCarouselWrapper');
    wrapper.innerHTML = '';

    renderCategoryPills(feed);

    feed.forEach((category, catIndex) => {

        /* One outer (horizontal) slide per category */
        const catSlide = document.createElement('div');
        catSlide.className = 'swiper-slide';

        /* Inner VERTICAL swiper — one slide per video */
        const innerContainer = document.createElement('div');
        innerContainer.className = `swiper swiper-vertical-category swiper-inner-${catIndex}`;

        const innerWrapper = document.createElement('div');
        innerWrapper.className = 'swiper-wrapper';

        if (!category.videos || category.videos.length === 0) {
            innerWrapper.innerHTML = `
                <div class="swiper-slide"
                     style="display:flex;align-items:center;justify-content:center;
                            height:100%;color:rgba(255,255,255,0.7);font-weight:500;">
                    No videos in ${category.category_name}
                </div>`;
        } else {
            category.videos.forEach((video, vidIndex) => {
                const vidSlide = document.createElement('div');
                vidSlide.className = 'swiper-slide';

                const avatarUrl = video.avatar_url
                    ? (video.avatar_url.startsWith('http') ? video.avatar_url : '../' + video.avatar_url)
                    : 'https://i.pravatar.cc/150?img=11';

                /* Vertical progress dots (show position within category) */
                const dotsHtml = category.videos.map((_, i) =>
                    `<span class="v-dot${i === vidIndex ? ' active' : ''}"></span>`
                ).join('');

                vidSlide.innerHTML = `
                    <div class="video-card">
                        <video class="video-thumbnail"
                               src="../${video.file_path}"
                               loop playsinline preload="metadata"
                               onclick="handleVideoTap(this, ${video.id})"></video>

                        <!-- category badge -->
                        <div class="card-top-left">${category.category_name}</div>

                        <!-- vertical position dots (right edge) -->
                        <div class="vertical-dots" id="vdots-${catIndex}">${dotsHtml}</div>

                        <!-- more options -->
                        <div class="card-top-right">
                            <i data-lucide="more-vertical"></i>
                        </div>

                        <!-- action buttons -->
                        <div class="card-actions-right">
                            <div class="card-action like-btn-${video.id}" onclick="toggleLike(${video.id}, this)">
                                <i data-lucide="heart" ${video.is_liked ? 'fill="white" class="active"' : ''}></i>
                                <span>${video.like_count > 1000 ? (video.like_count / 1000).toFixed(1) + 'K' : video.like_count}</span>
                            </div>
                            <div class="card-action" onclick="addComment(${video.id}, this)">
                                <i data-lucide="message-circle"></i>
                                <span>${video.comment_count}</span>
                            </div>
                            <div class="card-action" onclick="shareVideo('${video.file_path}')">
                                <i data-lucide="send"></i>
                                <span>Share</span>
                            </div>
                            <div class="card-action" onclick="toggleSave(${video.id}, this)">
                                <i data-lucide="bookmark" ${video.is_saved ? 'fill="white" class="active"' : ''}></i>
                            </div>
                        </div>

                        <!-- bottom info -->
                        <div class="card-bottom-info">
                            <div class="card-user">
                                <img src="${avatarUrl}" alt="${video.username}">
                                <span>${video.username}</span>
                            </div>
                            <div class="card-caption">${video.description || ''}</div>
                            <div class="card-tags">${video.hashtags || '#trending'}</div>
                        </div>
                    </div>
                `;
                innerWrapper.appendChild(vidSlide);
            });
        }

        innerContainer.appendChild(innerWrapper);
        catSlide.appendChild(innerContainer);
        wrapper.appendChild(catSlide);
    });

    lucide.createIcons();
    init2DSwipers(feed);
}

/* ─────────────────────────────────────────────────────────────────
   Swiper initialisation
───────────────────────────────────────────────────────────────── */
function init2DSwipers(feed) {
    innerSwipers = [];

    const uId        = window.currentUserId || 'guest';
    const outerKey   = `scrolls_user_${uId}_outer`;
    const savedOuter = parseInt(sessionStorage.getItem(outerKey) || '0');

    /* ── Inner swipers — VERTICAL (up/down for videos) ──────────── */
    feed.forEach((category, index) => {
        const catId      = category.category_id ?? index;
        const stateKey   = `scrolls_user_${uId}_cat_${catId}`;
        const savedInner = parseInt(sessionStorage.getItem(stateKey) || '0');

        const sw = new Swiper(`.swiper-inner-${index}`, {
            direction:       'vertical',    // ← videos scroll vertically
            nested:          true,
            resistanceRatio: 0,
            initialSlide:    savedInner,
            mousewheel: {
                forceToAxis: true,
                releaseOnEdges: true,
                thresholdTime: 350
            },
            speed: 400,
            keyboard:        true,
            on: {
                slideChangeTransitionEnd() {
                    sessionStorage.setItem(stateKey, this.activeIndex);
                    updateVerticalDots(index, this.activeIndex);
                    handlePlayback();
                }
            }
        });

        innerSwipers.push(sw);
    });

    /* ── Outer swiper — HORIZONTAL (left/right for categories) ──── */
    window.outerSwiper = new Swiper('#outerSwiper', {
        direction:       'horizontal',  // ← categories scroll horizontally
        resistanceRatio: 0,
        initialSlide:    Math.min(savedOuter, Math.max(feed.length - 1, 0)),
        keyboard:        true,
        on: {
            slideChangeTransitionEnd() {
                sessionStorage.setItem(outerKey, this.activeIndex);
                syncCategoryPills(this.activeIndex);
                handlePlayback();
            }
        }
    });

    syncCategoryPills(window.outerSwiper.activeIndex);
    handlePlayback();
}

/* ─────────────────────────────────────────────────────────────────
   Playback — only play the active video
───────────────────────────────────────────────────────────────── */
function handlePlayback() {
    document.querySelectorAll('.video-thumbnail').forEach(v => v.pause());

    if (!window.outerSwiper) return;

    const outerIdx = window.outerSwiper.activeIndex;
    const inner    = innerSwipers[outerIdx];
    if (!inner) return;

    const slide = inner.slides[inner.activeIndex];
    if (slide) {
        const vid = slide.querySelector('video');
        if (vid) vid.play().catch(() => {});
    }
}

/* ─────────────────────────────────────────────────────────────────
   Vertical progress dots  (show which video within the category)
───────────────────────────────────────────────────────────────── */
function updateVerticalDots(catIndex, activeVidIndex) {
    const el = document.getElementById(`vdots-${catIndex}`);
    if (!el) return;
    el.querySelectorAll('.v-dot').forEach((dot, i) => {
        dot.classList.toggle('active', i === activeVidIndex);
    });
}

/* ─────────────────────────────────────────────────────────────────
   Category pills  — always mirror the outer swiper position
───────────────────────────────────────────────────────────────── */
function renderCategoryPills(feed) {
    const container = document.getElementById('categoryPills');
    if (!container) return;
    container.innerHTML = '';

    feed.forEach((cat, index) => {
        const pill = document.createElement('div');
        pill.className = 'category-pill';
        if (index === 0) pill.classList.add('active');
        pill.textContent = cat.category_name;

        pill.addEventListener('click', () => {
            window.outerSwiper?.slideTo(index);
        });

        container.appendChild(pill);
    });
}

function syncCategoryPills(activeIndex) {
    document.querySelectorAll('.category-pill').forEach((p, i) => {
        const isActive = i === activeIndex;
        p.classList.toggle('active', isActive);
        if (isActive) {
            p.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    });
}

/* ─────────────────────────────────────────────────────────────────
   Interaction handlers
───────────────────────────────────────────────────────────────── */
async function toggleLike(videoId, btnEl) {
    try {
        const fd = new FormData();
        fd.append('action', 'toggle_like');
        fd.append('video_id', videoId);

        const data = await (await fetch('../api/action.php', { method: 'POST', body: fd })).json();
        if (data.success) {
            const icon = btnEl.querySelector('i');
            const span = btnEl.querySelector('span');
            if (data.status === 'liked') {
                icon.setAttribute('fill', 'white');
                icon.classList.add('active');
            } else {
                icon.removeAttribute('fill');
                icon.classList.remove('active');
            }
            span.textContent = data.new_count > 1000
                ? (data.new_count / 1000).toFixed(1) + 'K'
                : data.new_count;
        }
    } catch (e) { console.error('Like failed', e); }
}

async function toggleSave(videoId, btnEl) {
    try {
        const fd = new FormData();
        fd.append('action', 'toggle_save');
        fd.append('video_id', videoId);

        const data = await (await fetch('../api/action.php', { method: 'POST', body: fd })).json();
        if (data.success) {
            const icon = btnEl.querySelector('i');
            if (data.status === 'saved') {
                icon.setAttribute('fill', 'white');
                icon.classList.add('active');
            } else {
                icon.removeAttribute('fill');
                icon.classList.remove('active');
            }
        }
    } catch (e) { console.error('Save failed', e); }
}

let currentCommentVideoId = null;
let currentCommentBtnSpan = null;

async function addComment(videoId, btnEl) {
    currentCommentVideoId = videoId;
    currentCommentBtnSpan = btnEl.querySelector ? btnEl.querySelector('span') : btnEl;

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
}

function shareVideo(filePath) {
    const url = window.location.origin + '/' + filePath;
    if (navigator.share) {
        navigator.share({ title: 'Check out this scroll on Swipe Nest', url }).catch(console.error);
    } else {
        prompt('Copy this link to share:', url);
    }
}

/* ─────────────────────────────────────────────────────────────────
   Comments modal wiring
───────────────────────────────────────────────────────────────── */
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
                addComment(currentCommentVideoId, currentCommentBtnSpan);
            }
        } catch (e) { console.error('Post failed', e); }
        postBtn.disabled = false;
    });
});

let tapTimers = {};

function handleVideoTap(videoEl, videoId) {
    if (!tapTimers[videoId]) {
        // First tap
        tapTimers[videoId] = setTimeout(() => {
            tapTimers[videoId] = null;
            // Single tap action: toggle play/pause
            if (videoEl.paused) videoEl.play().catch(()=>{});
            else videoEl.pause();
        }, 250);
    } else {
        // Second tap (Double Tap)
        clearTimeout(tapTimers[videoId]);
        tapTimers[videoId] = null;
        triggerDoubleTapLike(videoEl.parentElement, videoId);
    }
}

function triggerDoubleTapLike(cardEl, videoId) {
    // 1. Show heart animation
    const heart = document.createElement('i');
    heart.setAttribute('data-lucide', 'heart');
    heart.setAttribute('fill', 'white');
    heart.className = 'heart-animation';
    cardEl.appendChild(heart);
    lucide.createIcons({ root: cardEl });
    
    setTimeout(() => {
        if (heart.parentNode) heart.parentNode.removeChild(heart);
    }, 900);

    // 2. Trigger like logic if not liked
    const likeBtn = cardEl.querySelector(`.like-btn-${videoId}`);
    if (likeBtn) {
        const icon = likeBtn.querySelector('i');
        if (!icon.classList.contains('active')) {
            toggleLike(videoId, likeBtn);
        }
    }
}
