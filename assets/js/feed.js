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
    
    feed.forEach((category, index) => {
        const swiper = new Swiper(`.swiper-inner-${index}`, {
            direction: 'vertical',
            nested: true,
            resistanceRatio: 0,
            on: {
                slideChangeTransitionEnd: function() {
                    handlePlayback();
                }
            }
        });
        innerSwipers.push(swiper);
    });
    
    outerSwiper = new Swiper('#outerSwiper', {
        direction: 'horizontal',
        resistanceRatio: 0,
        on: {
            slideChangeTransitionEnd: function() {
                syncCategoryPills(this.activeIndex);
                handlePlayback();
            }
        }
    });
    
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

async function addComment(videoId, btnEl) {
    const comment = prompt("Add a comment:");
    if (!comment || comment.trim() === '') return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('video_id', videoId);
        formData.append('content', comment.trim());
        
        const res = await fetch('../api/action.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            const span = btnEl.querySelector('span');
            let count = parseInt(span.textContent) || 0;
            span.textContent = count + 1;
            alert("Comment added!");
        }
    } catch(e) { console.error('Comment failed', e); }
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
