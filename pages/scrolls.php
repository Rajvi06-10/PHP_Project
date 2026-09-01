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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swipe Nest - Scrolls</title>
    
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
        /* ── Page header ─────────────────────────────────────────── */
        .scrolls-header {
            margin-bottom: var(--spacing-4);
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
        }

        .scrolls-header h1 {
            font-size: 24px;
            font-weight: 700;
        }

        .scrolls-header p {
            color: var(--color-text-secondary);
            font-size: var(--text-sm);
        }

        /* ── Category Pills ──────────────────────────────────────── */
        .category-pills-container {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            margin-bottom: var(--spacing-4);
            overflow-x: auto;
            padding-bottom: 8px;
            scrollbar-width: none;
        }
        .category-pills-container::-webkit-scrollbar { display: none; }

        .category-pill {
            padding: 7px 18px;
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
        .category-pill:hover { background-color: var(--color-surface-hover); }
        .category-pill.active {
            background-color: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }

        /* ── Scroll direction hint ───────────────────────────────── */
        .scroll-hint {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: var(--spacing-3);
            font-size: 12px;
            color: var(--color-text-tertiary);
        }
        .scroll-hint span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .scroll-hint i { width: 14px; height: 14px; }

        /* ── 2D Carousel ─────────────────────────────────────────── */
        /*
         * LAYOUT:
         *   Outer swiper  → HORIZONTAL (swipe left/right  = change CATEGORY)
         *   Inner swiper  → VERTICAL   (swipe up/down     = change VIDEO)
         */
        .video-carousel-container {
            position: relative;
            height: calc(100vh - 220px);
            max-height: 780px;
            width: 100%;
            max-width: 420px;
            margin-left: auto;
            margin-right: auto;
            border-radius: 20px;
            overflow: hidden;
            background: #000;
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
            touch-action: none;
        }

        /* Outer (horizontal) swiper */
        .swiper-horizontal-root {
            width: 100%;
            height: 100%;
        }

        /* Inner (vertical) swiper */
        .swiper-vertical-category {
            width: 100%;
            height: 100%;
        }

        /* ── Video card ──────────────────────────────────────────── */
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
            cursor: pointer;
        }

        /* category badge top-left */
        .card-top-left {
            position: absolute;
            top: 14px;
            left: 14px;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(4px);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            z-index: 10;
        }

        /* dots / pagination top-right area */
        .card-top-right {
            position: absolute;
            top: 14px;
            right: 14px;
            color: white;
            z-index: 10;
            cursor: pointer;
        }

        /* progress dots showing vertical position (right side) */
        .vertical-dots {
            position: absolute;
            right: 46px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            gap: 5px;
            z-index: 10;
        }
        .v-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            transition: all 0.2s;
        }
        .v-dot.active {
            background: #fff;
            height: 14px;
            border-radius: 3px;
        }

        /* right-side actions */
        .card-actions-right {
            position: absolute;
            right: 10px;
            bottom: 70px;
            display: flex;
            flex-direction: column;
            gap: 14px;
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
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .card-action i {
            width: 22px;
            height: 22px;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.8));
        }

        /* bottom info bar */
        .card-bottom-info {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 14px;
            background: linear-gradient(to top, rgba(0,0,0,0.92) 0%, transparent 100%);
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
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 1.5px solid white;
        }
        .card-user span { font-size: 13px; font-weight: 600; }

        .card-caption {
            font-size: 12px;
            line-height: 1.45;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-tags { font-size: 11px; color: rgba(255,255,255,0.8); }

        /* ── Navigation arrows overlaid on carousel ──────────────── */
        .carousel-nav {
            position: absolute;
            top: 25%; /* Moved up to avoid overlapping with action buttons */
            transform: translateY(-50%);
            z-index: 20;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .carousel-nav:hover { background: rgba(255,255,255,0.32); }
        .carousel-nav.nav-prev { left: 10px; }
        .carousel-nav.nav-next { right: 10px; }

        /* ── Primary button ──────────────────────────────────────── */
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
        .btn-primary:hover { background-color: var(--color-primary-hover); }
    </style>
</head>
<body>
    <div class="app-layout">

        <!-- ── Left Sidebar ──────────────────────────────────────── -->
        <aside class="desktop-sidebar">
            <div class="sidebar-header">
                <a href="home.php" class="sidebar-brand">
                    <img src="../assets/images/logo.svg" alt="Swipe Nest Logo" style="width: 28px; height: 28px;">
                    <span style="font-family: var(--font-family-heading); font-weight: 700; letter-spacing: -0.02em;">Swipe Nest</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <a href="home.php" class="sidebar-link">
                    <i data-lucide="home"></i>
                    <span>Home</span>
                </a>
                <a href="search.php" class="sidebar-link">
                    <i data-lucide="search"></i>
                    <span>Search</span>
                </a>
                <a href="scrolls.php" class="sidebar-link active">
                    <i data-lucide="layers"></i>
                    <span>Scrolls</span>
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
                <a href="#" class="sidebar-link" onclick="window.toggleTheme(); return false;">
                    <i data-lucide="moon" class="theme-icon"></i>
                    <span>Theme</span>
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

        <!-- ── Main Content ───────────────────────────────────────── -->
        <main class="main-content">
            <div class="main-content-inner">

                <!-- Page header -->
                <div class="scrolls-header">
                    <div>
                        <h1>Scrolls</h1>
                        <p>Swipe <strong>left/right</strong> to change category &nbsp;·&nbsp; Swipe <strong>up/down</strong> for more videos</p>
                    </div>
                </div>

                <!-- Category pills -->
                <div class="category-pills-container" id="categoryPills">
                    <!-- Populated via JS -->
                </div>

                <!-- 2D Carousel wrapper -->
                <div class="video-carousel-container" id="carouselContainer">

                    <!-- Prev category arrow (left) -->
                    <button class="carousel-nav nav-prev" id="navPrev" title="Previous category">
                        <i data-lucide="chevron-left"></i>
                    </button>

                    <!-- Outer swiper — HORIZONTAL (categories left/right) -->
                    <div class="swiper swiper-horizontal-root" id="outerSwiper">
                        <div class="swiper-wrapper" id="videoCarouselWrapper">
                            <!-- Inner vertical swipers injected by JS -->
                        </div>
                    </div>

                    <!-- Next category arrow (right) -->
                    <button class="carousel-nav nav-next" id="navNext" title="Next category">
                        <i data-lucide="chevron-right"></i>
                    </button>

                </div>

            </div><!-- /main-content-inner -->
        </main>
    </div>

    <!-- ── Comments Modal ─────────────────────────────────────────── -->
    <div class="comments-overlay" id="commentsOverlay"></div>
    <div class="comments-modal" id="commentsModal">
        <div class="comments-header">
            <h3>Comments</h3>
            <button class="close-comments" id="closeCommentsBtn">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="comments-body" id="commentsContainer"></div>
        <div class="comments-footer">
            <input type="text" class="comment-input" id="newCommentInput" placeholder="Add a comment...">
            <button class="btn-post-comment" id="postCommentBtn" disabled>Post</button>
        </div>
    </div>

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- Pass PHP session data to JS -->
    <script>
        window.currentUserId = <?= json_encode($_SESSION['user_id']) ?>;
    </script>

    <!-- Feed logic (direction-aware version) -->
    <script src="../assets/js/feed.js?v=<?= time() ?>"></script>
    <script src="../assets/js/main.js?v=<?= time() ?>"></script>

    <script>
        lucide.createIcons();

        // Arrow button controls (left/right = change category)
        document.getElementById('navPrev')?.addEventListener('click', () => {
            window.outerSwiper?.slidePrev();
        });
        document.getElementById('navNext')?.addEventListener('click', () => {
            window.outerSwiper?.slideNext();
        });
    </script>
</body>
</html>
