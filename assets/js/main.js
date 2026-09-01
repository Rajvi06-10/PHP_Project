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
