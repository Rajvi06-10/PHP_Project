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
                                <i data-lucide="heart" ${video.is_liked ? 'fill="#ff3040" style="color: #ff3040;" class="active"' : ''}></i>
                                <span>${video.like_count > 1000 ? (video.like_count / 1000).toFixed(1) + 'K' : video.like_count}</span>
                            </div>
                            <div class="card-action" onclick="addComment(${video.id}, this)">
                                <i data-lucide="message-circle"></i>
                                <span>${video.comment_count}</span>
                            </div>
                            <div class="card-action" onclick="shareVideo('../${video.file_path}')">
                                <i data-lucide="send"></i>
                                <span>Share</span>
                            </div>
                            <div class="card-action save-btn-${video.id}" onclick="toggleSave(${video.id}, this)">
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
            direction:       'vertical',
            nested:          true,
            // Instagram-like physics
            initialSlide:    savedInner,
            touchRatio:      1.2,
            shortSwipes:     true,
            longSwipesRatio: 0.1,
            mousewheel: {
                forceToAxis: true,
                releaseOnEdges: true,
                thresholdTime: 250 // faster mousewheel snapping
            },
            speed: 300, // Snappy animation
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

/* Interaction handlers moved to main.js */
