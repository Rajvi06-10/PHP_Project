<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$avatarSrc = $_SESSION['avatar'] ?? 'https://i.pravatar.cc/150?img=11';
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zyva - Home</title>
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/reset.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/globals.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.css?v=<?= time() ?>">
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        /* Dashboard specific styles */
        .welcome-header {
            margin-bottom: var(--spacing-6);
        }
        
        .welcome-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .welcome-header p {
            color: var(--color-text-secondary);
            font-size: var(--text-base);
        }

        /* Category Pills */
        .category-pills-container {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            margin-bottom: var(--spacing-6);
            overflow-x: auto;
            padding-bottom: 8px;
            scrollbar-width: none;
        }
        
        .category-pills-container::-webkit-scrollbar { display: none; }
        
        .category-pill {
            padding: 8px 20px;
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-full);
            color: var(--color-text-secondary);
            font-size: var(--text-sm);
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: all var(--transition-fast);
        }
        
        .category-pill:hover {
            background-color: var(--color-surface-hover);
        }
        
        .category-pill.active {
            background-color: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }
        
        .nav-arrow {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }

        /* Video Carousel */
        .video-carousel-container {
            margin-bottom: var(--spacing-10);
            position: relative;
        }
        
        .swiper-videos {
            width: 100%;
            padding: 10px 0;
        }
        
        .video-card {
            width: 240px;
            height: 420px;
            border-radius: 16px;
            background-color: #000;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            transition: transform 0.2s;
            cursor: pointer;
        }
        
        .video-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        }
        
        .video-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        
        .video-card:hover .video-thumbnail {
            opacity: 1;
        }
        
        .card-top-left {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            z-index: 10;
        }
        
        .card-top-right {
            position: absolute;
            top: 12px;
            right: 12px;
            color: white;
            z-index: 10;
            cursor: pointer;
        }
        
        .card-actions-right {
            position: absolute;
            right: 8px;
            bottom: 60px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 10;
        }
        
        .card-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            color: white;
            font-size: 11px;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.8);
        }
        
        .card-action i {
            width: 20px;
            height: 20px;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.8));
        }
        
        .card-bottom-info {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 12px;
            background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
            color: white;
            z-index: 10;
        }
        
        .card-user {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        
        .card-user img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1px solid white;
        }
        
        .card-user span {
            font-size: 12px;
            font-weight: 600;
        }
        
        .card-caption {
            font-size: 12px;
            line-height: 1.4;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .card-tags {
            font-size: 11px;
            color: rgba(255,255,255,0.8);
        }

        /* Suggestions Section */
        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: var(--spacing-4);
            color: var(--color-text-primary);
        }

        .suggestions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: var(--spacing-4);
            margin-bottom: var(--spacing-8);
        }
        
        .suggestion-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            padding: var(--spacing-3) var(--spacing-4);
            border-radius: var(--radius-lg);
        }

        .suggestion-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
        }

        .suggestion-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .suggestion-details h4 {
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--color-text-primary);
        }
        
        .suggestion-details p {
            font-size: var(--text-xs);
            color: var(--color-text-tertiary);
        }

        .btn-follow {
            color: var(--color-primary);
            background: transparent;
            border: 1px solid var(--color-border);
            padding: 4px 12px;
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .btn-follow:hover {
            border-color: var(--color-primary);
            background: rgba(106, 76, 255, 0.05);
        }

        /* Hashtags Section */
        .hashtags-container {
            display: flex;
            gap: var(--spacing-4);
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .hashtag-item {
            color: var(--color-primary);
            font-size: var(--text-sm);
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background-color var(--transition-fast);
            text-decoration: none;
            justify-content: center;
        }
        .btn-primary:hover {
            background-color: var(--color-primary-hover);
        }
    </style>
</head>
<body>
    <div class="app-layout">
        
        <!-- Left Sidebar -->
        <aside class="desktop-sidebar">
            <div class="sidebar-header">
                <a href="home.php" class="sidebar-brand">
                    <i data-lucide="play-square" fill="currentColor"></i> zyva
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="home.php" class="sidebar-link active"><i data-lucide="home"></i> Home</a>
                <a href="explore.php" class="sidebar-link"><i data-lucide="search"></i> Explore</a>
                <a href="#" class="sidebar-link"><i data-lucide="layout-grid"></i> Categories</a>
                <a href="#" class="sidebar-link"><i data-lucide="bookmark"></i> Saved</a>
                <a href="#" class="sidebar-link"><i data-lucide="users"></i> Following</a>
                <a href="#" class="sidebar-link"><i data-lucide="message-square"></i> Messages</a>
                <a href="#" class="sidebar-link"><i data-lucide="bell"></i> Notifications</a>
                <a href="profile.php" class="sidebar-link"><i data-lucide="user"></i> Profile</a>
                <a href="settings.php" class="sidebar-link"><i data-lucide="settings"></i> Settings</a>
            </nav>
            
            <div class="sidebar-footer" style="display: flex; flex-direction: column; gap: 15px;">
                <a href="upload.php" class="btn-primary w-full">
                    <i data-lucide="plus"></i> Create Reel
                </a>
                <a href="../index.php" class="sidebar-link" style="padding: 0; color: var(--color-text-secondary);"><i data-lucide="log-out"></i> Logout</a>
            </div>
        </aside>

        <!-- Top Navbar -->
        <header class="desktop-navbar">
            <div class="navbar-search">
                <div class="search-input-wrapper">
                    <i data-lucide="search"></i>
                    <input type="text" class="search-input" placeholder="Search reels, users, hashtags...">
                </div>
            </div>
            
            <div class="navbar-actions">
                <i data-lucide="home" class="action-icon" style="color: var(--color-primary);"></i>
                <i data-lucide="plus-square" class="action-icon"></i>
                <i data-lucide="message-circle" class="action-icon"></i>
                <i data-lucide="bell" class="action-icon"></i>
                
                <div class="user-profile-menu">
                    <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Profile" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                    <span><?= htmlspecialchars($username) ?></span>
                    <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            
            <div class="welcome-header">
                <h1>Home</h1>
                <p>Welcome back, <?= htmlspecialchars($username) ?>! 👋</p>
            </div>

            <!-- Categories -->
            <div class="category-pills-container" id="categoryPills">
                <div class="category-pill active" data-category="all">All</div>
                <!-- Populated via JS -->
            </div>

            <!-- Video Carousel -->
            <div class="video-carousel-container">
                <div class="swiper swiper-videos">
                    <div class="swiper-wrapper" id="videoCarouselWrapper">
                        <!-- Populated via JS -->
                    </div>
                </div>
                <!-- We can add swiper navigation buttons here if needed -->
            </div>

            <!-- Suggestions -->
            <h3 class="section-title">Suggested for you</h3>
            <div class="suggestions-grid">
                <div class="suggestion-card">
                    <div class="suggestion-info">
                        <img src="https://i.pravatar.cc/150?img=1" alt="User">
                        <div class="suggestion-details">
                            <h4>wanderlust_03</h4>
                            <p>Travel Enthusiast</p>
                        </div>
                    </div>
                    <button class="btn-follow">Follow</button>
                </div>
                <div class="suggestion-card">
                    <div class="suggestion-info">
                        <img src="https://i.pravatar.cc/150?img=2" alt="User">
                        <div class="suggestion-details">
                            <h4>food_lover_22</h4>
                            <p>Food Blogger</p>
                        </div>
                    </div>
                    <button class="btn-follow">Follow</button>
                </div>
                <div class="suggestion-card">
                    <div class="suggestion-info">
                        <img src="https://i.pravatar.cc/150?img=3" alt="User">
                        <div class="suggestion-details">
                            <h4>fitlife_07</h4>
                            <p>Fitness Coach</p>
                        </div>
                    </div>
                    <button class="btn-follow">Follow</button>
                </div>
                <div class="suggestion-card">
                    <div class="suggestion-info">
                        <img src="https://i.pravatar.cc/150?img=4" alt="User">
                        <div class="suggestion-details">
                            <h4>art_world</h4>
                            <p>Artist</p>
                        </div>
                    </div>
                    <button class="btn-follow">Follow</button>
                </div>
            </div>

            <!-- Popular Hashtags -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-4);">
                <h3 class="section-title" style="margin-bottom: 0;">Popular Hashtags</h3>
                <a href="#" style="font-size: 13px; color: var(--color-primary); font-weight: 500; text-decoration: none;">View all</a>
            </div>
            <div class="hashtags-container">
                <div class="hashtag-item">#travel</div>
                <div class="hashtag-item">#foodie</div>
                <div class="hashtag-item">#music</div>
                <div class="hashtag-item">#fashion</div>
                <div class="hashtag-item">#nature</div>
                <div class="hashtag-item">#fitness</div>
                <div class="hashtag-item">#art</div>
                <div class="hashtag-item">#comedy</div>
            </div>

        </main>
    </div>

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <!-- Main JS logic for Dashboard -->
    <script src="../assets/js/feed.js"></script>
    
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
