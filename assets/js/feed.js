let globalFeed = [];
let videoCarouselSwiper = null;
let currentCategoryFilter = 'all';

document.addEventListener('DOMContentLoaded', () => {
    fetchFeedData();
});

async function fetchFeedData() {
    try {
        const response = await fetch('../api/feed.php');
        const data = await response.json();
        
        if (data.success) {
            globalFeed = data.feed;
            renderCategoryPills(globalFeed);
            renderVideos(globalFeed);
        } else {
            console.error('Failed to load feed:', data.message);
        }
    } catch (error) {
        console.error('Error fetching feed:', error);
    }
}

function renderCategoryPills(feed) {
    const container = document.getElementById('categoryPills');
    // Keep 'All'
    
    feed.forEach(cat => {
        const pill = document.createElement('div');
        pill.className = 'category-pill';
        pill.textContent = cat.category_name;
        pill.dataset.categoryId = cat.category_id;
        pill.addEventListener('click', () => {
            document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            filterVideos(cat.category_id);
        });
        container.appendChild(pill);
    });

    // Handle 'All' click
    const allPill = container.querySelector('[data-category="all"]');
    allPill.addEventListener('click', () => {
        document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
        allPill.classList.add('active');
        filterVideos('all');
    });
}

function filterVideos(categoryId) {
    renderVideos(globalFeed, categoryId);
}

function renderVideos(feed, filterCategoryId = 'all') {
    const wrapper = document.getElementById('videoCarouselWrapper');
    wrapper.innerHTML = ''; // Clear existing
    
    let allVideos = [];
    
    feed.forEach(category => {
        if (filterCategoryId === 'all' || category.category_id == filterCategoryId) {
            category.videos.forEach(video => {
                video.category_name = category.category_name;
                allVideos.push(video);
            });
        }
    });

    allVideos.forEach(video => {
        const slide = document.createElement('div');
        slide.className = 'swiper-slide';
        slide.style.width = '240px';
        slide.style.marginRight = '20px';
        
        const avatarUrl = video.avatar_url ? (video.avatar_url.startsWith('http') ? video.avatar_url : '../' + video.avatar_url) : 'https://i.pravatar.cc/150?img=11';

        // Assuming thumbnails exist, or we can use video tag. Since the screenshot shows images/thumbnails, let's use the video tag but paused, or an image. 
        // We will use video tag and set it to loop and muted on hover.
        slide.innerHTML = `
            <div class="video-card">
                <video class="video-thumbnail" src="../${video.file_path}" loop muted playsinline poster=""></video>
                <div class="card-top-left">${video.category_name}</div>
                <div class="card-top-right"><i data-lucide="more-vertical"></i></div>
                
                <div class="card-actions-right">
                    <div class="card-action">
                        <i data-lucide="heart" ${video.is_liked ? 'fill="white"' : ''}></i>
                        <span>${video.likes_count > 1000 ? (video.likes_count/1000).toFixed(1)+'K' : video.likes_count}</span>
                    </div>
                    <div class="card-action">
                        <i data-lucide="message-circle"></i>
                        <span>${video.comments_count}</span>
                    </div>
                    <div class="card-action">
                        <i data-lucide="send"></i>
                        <span>Share</span>
                    </div>
                    <div class="card-action">
                        <i data-lucide="bookmark" ${video.is_saved ? 'fill="white"' : ''}></i>
                    </div>
                </div>
                
                <div class="card-bottom-info">
                    <div class="card-user">
                        <img src="${avatarUrl}" alt="${video.username}">
                        <span>${video.username}</span>
                    </div>
                    <div class="card-caption">${video.caption}</div>
                    <div class="card-tags">${video.hashtags ? video.hashtags : '#trending'}</div>
                </div>
            </div>
        `;

        // Hover to play
        const vidEl = slide.querySelector('video');
        slide.addEventListener('mouseenter', () => {
            vidEl.play().catch(e => console.log('Autoplay blocked'));
        });
        slide.addEventListener('mouseleave', () => {
            vidEl.pause();
        });

        wrapper.appendChild(slide);
    });

    lucide.createIcons();
    initCarousel();
}

function initCarousel() {
    if (videoCarouselSwiper) {
        videoCarouselSwiper.destroy(true, true);
    }
    videoCarouselSwiper = new Swiper('.swiper-videos', {
        slidesPerView: 'auto',
        spaceBetween: 20,
        freeMode: true,
        mousewheel: {
            forceToAxis: true,
        },
    });
}
