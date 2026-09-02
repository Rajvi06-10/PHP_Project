/**
 * feed.js — Scrolls page 2D carousel
 *
 * Outer swiper  → HORIZONTAL  (swipe left / right  = change CATEGORY)
 * Inner swiper  → VERTICAL    (swipe up  / down     = change VIDEO inside category)
 *
 * Category pills always reflect the currently visible category.
 *
 * NOTE: User-generated content (username, description, hashtags, category_name)
 * is escaped server-side in api/feed.php with htmlspecialchars().
 * Card elements are built with textContent / setAttribute to prevent XSS.
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
   DOM builder — all user content set via textContent (XSS-safe)
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
            const emptySlide = document.createElement('div');
            emptySlide.className = 'swiper-slide';
            emptySlide.style.cssText = 'display:flex;align-items:center;justify-content:center;height:100%;color:rgba(255,255,255,0.7);font-weight:500;';
            emptySlide.textContent = 'No videos in ' + category.category_name;
            innerWrapper.appendChild(emptySlide);
        } else {
            category.videos.forEach((video, vidIndex) => {
                const vidSlide = document.createElement('div');
                vidSlide.className = 'swiper-slide';

                const avatarUrl = video.avatar_url
                    ? (video.avatar_url.startsWith('http') ? video.avatar_url : '../' + video.avatar_url)
                    : 'https://i.pravatar.cc/150?img=11';

                // ── video-card container ────────────────────────
                const card = document.createElement('div');
                card.className = 'video-card';

                // video element
                const vid = document.createElement('video');
                vid.className   = 'video-thumbnail';
                vid.src         = '../' + video.file_path;  // file_path is internal, not user content
                vid.loop        = true;
                vid.playsInline = true;
                vid.preload     = 'metadata';
                vid.onclick     = () => handleVideoTap(vid, video.id);
                card.appendChild(vid);

                // category badge (top-left)
                const badge = document.createElement('div');
                badge.className   = 'card-top-left';
                badge.textContent = category.category_name; // htmlspecialchars'd by server
                card.appendChild(badge);

                // vertical progress dots (right edge)
                const dotsContainer = document.createElement('div');
                dotsContainer.className = 'vertical-dots';
                dotsContainer.id        = `vdots-${catIndex}`;
                category.videos.forEach((_, i) => {
                    const dot = document.createElement('span');
                    dot.className = 'v-dot' + (i === vidIndex ? ' active' : '');
                    dotsContainer.appendChild(dot);
                });
                card.appendChild(dotsContainer);

                // more options (top-right)
                const moreBtn = document.createElement('div');
                moreBtn.className = 'card-top-right';
                const moreIcon = document.createElement('i');
                moreIcon.setAttribute('data-lucide', 'more-vertical');
                moreBtn.appendChild(moreIcon);
                card.appendChild(moreBtn);

                // action buttons (right side)
                const actions = document.createElement('div');
                actions.className = 'card-actions-right';

                // Like button
                const likeDiv = document.createElement('div');
                likeDiv.className = `card-action like-btn-${video.id}`;
                likeDiv.onclick   = () => toggleLike(video.id, likeDiv);
                const likeIcon = document.createElement('i');
                likeIcon.setAttribute('data-lucide', 'heart');
                if (video.is_liked) {
                    likeIcon.setAttribute('fill', '#ff3040');
                    likeIcon.style.color = '#ff3040';
                    likeIcon.classList.add('active');
                }
                const likeCount = document.createElement('span');
                likeCount.textContent = video.like_count > 1000
                    ? (video.like_count / 1000).toFixed(1) + 'K'
                    : video.like_count;
                likeDiv.appendChild(likeIcon);
                likeDiv.appendChild(likeCount);
                actions.appendChild(likeDiv);

                // Comment button
                const commentDiv = document.createElement('div');
                commentDiv.className = 'card-action';
                commentDiv.onclick   = () => addComment(video.id, commentDiv);
                const commentIcon = document.createElement('i');
                commentIcon.setAttribute('data-lucide', 'message-circle');
                const commentCount = document.createElement('span');
                commentCount.textContent = video.comment_count;
                commentDiv.appendChild(commentIcon);
                commentDiv.appendChild(commentCount);
                actions.appendChild(commentDiv);

                // Share button
                const shareDiv = document.createElement('div');
                shareDiv.className = 'card-action';
                shareDiv.onclick   = () => shareVideo('../' + video.file_path);
                const shareIcon = document.createElement('i');
                shareIcon.setAttribute('data-lucide', 'send');
                const shareLabel = document.createElement('span');
                shareLabel.textContent = 'Share';
                shareDiv.appendChild(shareIcon);
                shareDiv.appendChild(shareLabel);
                actions.appendChild(shareDiv);

                // Save button
                const saveDiv = document.createElement('div');
                saveDiv.className = `card-action save-btn-${video.id}`;
                saveDiv.onclick   = () => toggleSave(video.id, saveDiv);
                const saveIcon = document.createElement('i');
                saveIcon.setAttribute('data-lucide', 'bookmark');
                if (video.is_saved) {
                    saveIcon.setAttribute('fill', 'white');
                    saveIcon.classList.add('active');
                }
                saveDiv.appendChild(saveIcon);
                actions.appendChild(saveDiv);

                card.appendChild(actions);

                // bottom info bar
                const bottomInfo = document.createElement('div');
                bottomInfo.className = 'card-bottom-info';

                const userRow = document.createElement('div');
                userRow.className = 'card-user';

                const userImg = document.createElement('img');
                userImg.src = avatarUrl;
                userImg.alt = video.username; // htmlspecialchars'd by server

                const userSpan = document.createElement('span');
                userSpan.textContent = video.username; // htmlspecialchars'd by server

                userRow.appendChild(userImg);
                userRow.appendChild(userSpan);

                const caption = document.createElement('div');
                caption.className   = 'card-caption';
                caption.textContent = video.description || ''; // htmlspecialchars'd by server

                const tags = document.createElement('div');
                tags.className   = 'card-tags';
                // hashtags is an array of htmlspecialchars'd strings from the server
                tags.textContent = Array.isArray(video.hashtags) && video.hashtags.length > 0
                    ? video.hashtags.map(t => '#' + t).join(' ')
                    : '';

                bottomInfo.appendChild(userRow);
                bottomInfo.appendChild(caption);
                bottomInfo.appendChild(tags);
                card.appendChild(bottomInfo);

                vidSlide.appendChild(card);
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
   Swiper initialisation — UNCHANGED (horizontal + vertical)
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
            direction:       'vertical',
            nested:          true,
            initialSlide:    savedInner,
            touchRatio:      1.2,
            shortSwipes:     true,
            longSwipesRatio: 0.1,
            mousewheel: {
                forceToAxis:    true,
                releaseOnEdges: true,
                thresholdTime:  250
            },
            speed:    300,
            keyboard: true,
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
        direction:       'horizontal',
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
   Vertical progress dots
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
        pill.className   = 'category-pill';
        pill.textContent = cat.category_name; // htmlspecialchars'd by server
        if (index === 0) pill.classList.add('active');

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

/* Interaction handlers are in main.js */
