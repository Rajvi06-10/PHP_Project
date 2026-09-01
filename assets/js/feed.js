let globalFeed = [];
let outerSwiper = null;
let innerSwipers = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchFeedData();
});

async function fetchFeedData() {
    try {
        const response = await fetch('../api/feed.php');
        const data = await response.json();
        
        if (data.success) {
            globalFeed = data.feed;
            render2DCarousel(globalFeed);
        } else {
            console.error('Failed to load feed:', data.message);
        }
    } catch (error) {
        console.error('Error fetching feed:', error);
    }
}

function render2DCarousel(feed) {
    const wrapper = document.getElementById('videoCarouselWrapper');
    wrapper.innerHTML = ''; 
    
    renderCategoryPills(feed);
    
    feed.forEach((category, catIndex) => {
        // Outer Horizontal Slide for Category
        const catSlide = document.createElement('div');
        catSlide.className = 'swiper-slide';
        
        // Inner Vertical Swiper for Videos
        const innerContainer = document.createElement('div');
        innerContainer.className = `swiper swiper-vertical-category swiper-inner-${catIndex}`;
        
        const innerWrapper = document.createElement('div');
        innerWrapper.className = 'swiper-wrapper';
        
        if (category.videos.length === 0) {
            innerWrapper.innerHTML = `<div class="swiper-slide" style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--color-text-secondary);font-weight:500;">No videos in ${category.category_name}</div>`;
        } else {
            category.videos.forEach(video => {
                const vidSlide = document.createElement('div');
                vidSlide.className = 'swiper-slide';
                
                const avatarUrl = video.avatar_url ? (video.avatar_url.startsWith('http') ? video.avatar_url : '../' + video.avatar_url) : 'https://i.pravatar.cc/150?img=11';
                
                vidSlide.innerHTML = `
                    <div class="video-card">
                        <video class="video-thumbnail" src="../${video.file_path}" loop playsinline preload="metadata" onclick="this.paused ? this.play() : this.pause()"></video>
                        <div class="card-top-left">${category.category_name}</div>
                        <div class="card-top-right"><i data-lucide="more-vertical"></i></div>
                        
                        <div class="card-actions-right">
                            <div class="card-action" onclick="toggleLike(${video.id}, this)">
                                <i data-lucide="heart" ${video.is_liked ? 'fill="white" class="active"' : ''}></i>
                                <span>${video.like_count > 1000 ? (video.like_count/1000).toFixed(1)+'K' : video.like_count}</span>
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
                        
                        <div class="card-bottom-info">
                            <div class="card-user">
                                <img src="${avatarUrl}" alt="${video.username}">
                                <span>${video.username}</span>
                            </div>
                            <div class="card-caption">${video.description}</div>
                            <div class="card-tags">${video.hashtags ? video.hashtags : '#trending'}</div>
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

function init2DSwipers(feed) {
    innerSwipers = [];
    
    const uId = window.currentUserId || 'guest';
    const outerKey = `swipeNest_user_${uId}_outer_category`;
    const savedOuterIndex = sessionStorage.getItem(outerKey) ? parseInt(sessionStorage.getItem(outerKey)) : 0;
    
    feed.forEach((category, index) => {
        const catId = category.category_id || index; // fallback to index if missing
        const stateKey = `swipeNest_user_${uId}_category_${catId}`;
        const savedInnerIndex = sessionStorage.getItem(stateKey) ? parseInt(sessionStorage.getItem(stateKey)) : 0;
        
        const swiper = new Swiper(`.swiper-inner-${index}`, {
            direction: 'horizontal',
            nested: true,
            resistanceRatio: 0,
            initialSlide: savedInnerIndex,
            on: {
                slideChangeTransitionEnd: function() {
                    sessionStorage.setItem(stateKey, this.activeIndex);
                    handlePlayback();
                }
            }
        });
        innerSwipers.push(swiper);
    });
    
    outerSwiper = new Swiper('#outerSwiper', {
        direction: 'horizontal',
        resistanceRatio: 0,
        initialSlide: Math.min(savedOuterIndex, feed.length - 1 < 0 ? 0 : feed.length - 1),
        on: {
            slideChangeTransitionEnd: function() {
                sessionStorage.setItem(outerKey, this.activeIndex);
                syncCategoryPills(this.activeIndex);
                handlePlayback();
            }
        }
    });
    
    if (outerSwiper) {
        syncCategoryPills(outerSwiper.activeIndex);
    }
    handlePlayback();
}

function handlePlayback() {
    document.querySelectorAll('.video-thumbnail').forEach(v => {
        v.pause();
    });
    
    if (!outerSwiper) return;
    const activeOuterIndex = outerSwiper.activeIndex;
    const activeInnerSwiper = innerSwipers[activeOuterIndex];
    
    if (activeInnerSwiper) {
        const activeInnerIndex = activeInnerSwiper.activeIndex;
        const innerSlide = activeInnerSwiper.slides[activeInnerIndex];
        if (innerSlide) {
            const video = innerSlide.querySelector('video');
            if (video) {
                video.play().catch(e => console.log('Autoplay blocked.', e));
            }
        }
    }
}

function renderCategoryPills(feed) {
    const container = document.getElementById('categoryPills');
    container.innerHTML = '';
    
    feed.forEach((cat, index) => {
        const pill = document.createElement('div');
        pill.className = 'category-pill';
        if (index === 0) pill.classList.add('active');
        pill.textContent = cat.category_name;
        
        pill.addEventListener('click', () => {
            outerSwiper.slideTo(index);
        });
        
        container.appendChild(pill);
    });
}

function syncCategoryPills(activeIndex) {
    const pills = document.querySelectorAll('.category-pill');
    pills.forEach((p, index) => {
        if (index === activeIndex) {
            p.classList.add('active');
            p.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        } else {
            p.classList.remove('active');
        }
    });
}

// Interaction Handlers

async function toggleLike(videoId, btnEl) {
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_like');
        formData.append('video_id', videoId);
        
        const res = await fetch('../api/action.php', { method: 'POST', body: formData });
        const data = await res.json();
        
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
            span.textContent = data.new_count > 1000 ? (data.new_count/1000).toFixed(1)+'K' : data.new_count;
        }
    } catch(e) { console.error('Like failed', e); }
}

async function toggleSave(videoId, btnEl) {
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_save');
        formData.append('video_id', videoId);
        
        const res = await fetch('../api/action.php', { method: 'POST', body: formData });
        const data = await res.json();
        
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
    } catch(e) { console.error('Save failed', e); }
}

let currentCommentVideoId = null;
let currentCommentBtnSpan = null;

async function addComment(videoId, btnEl) {
    currentCommentVideoId = videoId;
    // Handle case where btnEl might just be a mock object during a re-fetch
    currentCommentBtnSpan = btnEl.querySelector ? btnEl.querySelector('span') : btnEl;
    
    const overlay = document.getElementById('commentsOverlay');
    const modal = document.getElementById('commentsModal');
    if(overlay) overlay.classList.add('active');
    if(modal) modal.classList.add('active');
    
    const container = document.getElementById('commentsContainer');
    if(!container) return;
    
    container.innerHTML = '<div style="text-align:center; padding: 20px;">Loading...</div>';
    
    try {
        const res = await fetch('../api/comments.php?video_id=' + videoId);
        const data = await res.json();
        
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
                    </div>
                `;
                container.appendChild(el);
            });
        } else {
            container.innerHTML = '<div style="text-align:center; padding: 20px; color: var(--color-text-secondary);">No comments yet. Be the first!</div>';
        }
    } catch(e) {
        container.innerHTML = '<div style="text-align:center; padding: 20px; color: red;">Failed to load comments.</div>';
    }
}

function shareVideo(filePath) {
    const url = window.location.origin + window.location.pathname.replace('home.php', filePath);
    if (navigator.share) {
        navigator.share({
            title: 'Check out this video on Swipe Nest',
            url: url
        }).catch(console.error);
    } else {
        prompt("Copy this link to share:", url);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('closeCommentsBtn')?.addEventListener('click', () => {
        document.getElementById('commentsOverlay')?.classList.remove('active');
        document.getElementById('commentsModal')?.classList.remove('active');
    });
    
    document.getElementById('commentsOverlay')?.addEventListener('click', () => {
        document.getElementById('commentsOverlay')?.classList.remove('active');
        document.getElementById('commentsModal')?.classList.remove('active');
    });
    
    const commentInput = document.getElementById('newCommentInput');
    const postBtn = document.getElementById('postCommentBtn');
    
    if (commentInput && postBtn) {
        commentInput.addEventListener('input', () => {
            postBtn.disabled = commentInput.value.trim().length === 0;
        });
        
        postBtn.addEventListener('click', async () => {
            const text = commentInput.value.trim();
            if (!text || !currentCommentVideoId) return;
            
            postBtn.disabled = true;
            try {
                const formData = new FormData();
                formData.append('action', 'add_comment');
                formData.append('video_id', currentCommentVideoId);
                formData.append('content', text);
                
                const res = await fetch('../api/action.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    commentInput.value = '';
                    if (currentCommentBtnSpan) {
                        currentCommentBtnSpan.textContent = data.count;
                    }
                    // Refresh comments list
                    addComment(currentCommentVideoId, currentCommentBtnSpan);
                }
            } catch(e) { console.error('Post failed', e); }
            postBtn.disabled = false;
        });
    }
});
