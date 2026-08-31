<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$sessionAvatar = isset($_SESSION['avatar']) && $_SESSION['avatar'] ? (strpos($_SESSION['avatar'], 'http') === 0 ? $_SESSION['avatar'] : '../' . $_SESSION['avatar']) : 'https://i.pravatar.cc/150?img=11';
$avatarSrc = $sessionAvatar;
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swipe Nest - Home</title>
    
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

        /* Video Carousel - 2D Navigation */
        .video-carousel-container {
            margin-bottom: var(--spacing-10);
            position: relative;
            height: calc(100vh - 200px);
            max-height: 750px;
            width: 100%;
            max-width: 420px;
            margin-left: auto;
            margin-right: auto;
            border-radius: 16px;
            overflow: hidden;
            background: #000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .swiper-horizontal-root {
            width: 100%;
            height: 100%;
        }

        .swiper-vertical-category {
            width: 100%;
            height: 100%;
        }
        
        .video-card {
            width: 100%;
            height: 100%;
            background-color: #000;
            position: relative;
            overflow: hidden;
        }
        
        .video-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
                    <img src="../assets/images/logo.svg" alt="Swipe Nest Logo" style="width: 28px; height: 28px;">
                    <span style="font-family: var(--font-family-heading); font-weight: 700; letter-spacing: -0.02em;">Swipe Nest</span>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="home.php" class="sidebar-link active">
                    <i data-lucide="home"></i>
                    <span>Home</span>
                </a>
                <a href="search.php" class="sidebar-link">
                    <i data-lucide="search"></i>
                    <span>Search</span>
                </a>
                <a href="upload.php" class="sidebar-link">
                    <i data-lucide="plus-square"></i>
                    <span>Create</span>
                </a>
                <a href="profile.php" class="sidebar-link">
                    <i data-lucide="user"></i>
                    <span>Profile</span>
                </a>
                <a href="settings.php" class="sidebar-link">
                    <i data-lucide="settings"></i>
                    <span>Settings</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="profile.php" class="sidebar-user">
                    <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Profile">
                    <span><?= htmlspecialchars($username) ?></span>
                </a>
                <a href="logout.php" class="sidebar-link" style="color: var(--color-danger); margin-top: 8px;">
                    <i data-lucide="log-out"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="main-content-inner">

            <!-- Categories -->
            <div class="category-pills-container" id="categoryPills">
                <div class="category-pill active" data-category="all">All</div>
                <!-- Populated via JS -->
            </div>

            <!-- Video Carousel 2D -->
            <div class="video-carousel-container">
                <div class="swiper swiper-horizontal-root" id="outerSwiper">
                    <div class="swiper-wrapper" id="videoCarouselWrapper">
                        <!-- Categories and inner vertical swipers populated via JS -->
                    </div>
                </div>
            </div>

            </div> <!-- /main-content-inner -->
        </main>
    </div>

    <!-- Comments Modal -->
    <div class="comments-overlay" id="commentsOverlay"></div>
    <div class="comments-modal" id="commentsModal">
        <div class="comments-header">
            <h3>Comments</h3>
            <button class="close-comments" id="closeCommentsBtn">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="comments-body" id="commentsContainer">
            <!-- Comments injected via JS -->
        </div>
        <div class="comments-footer">
            <input type="text" class="comment-input" id="newCommentInput" placeholder="Add a comment...">
            <button class="btn-post-comment" id="postCommentBtn" disabled>Post</button>
        </div>
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
