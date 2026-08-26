<?php
session_start();
require_once '../config/db.php';

$searchQuery = $_GET['q'] ?? '';

if (!empty($searchQuery)) {
    $searchParam = '%' . $searchQuery . '%';
    
    // Search creators
    $creatorsStmt = $pdo->prepare("SELECT id, username, bio, avatar_url FROM users WHERE username LIKE ? OR bio LIKE ? ORDER BY created_at DESC LIMIT 10");
    $creatorsStmt->execute([$searchParam, $searchParam]);
    $creators = $creatorsStmt->fetchAll();

    // Search videos
    $videosStmt = $pdo->prepare("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id WHERE v.title LIKE ? OR v.description LIKE ? OR u.username LIKE ? ORDER BY v.created_at DESC LIMIT 20");
    $videosStmt->execute([$searchParam, $searchParam, $searchParam]);
    $videos = $videosStmt->fetchAll();
} else {
    // Fetch top creators
    $creatorsStmt = $pdo->query("SELECT id, username, bio, avatar_url FROM users ORDER BY created_at DESC LIMIT 3");
    $creators = $creatorsStmt->fetchAll();

    // Fetch trending videos
    $videosStmt = $pdo->query("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id ORDER BY v.views DESC, v.created_at DESC LIMIT 6");
    $videos = $videosStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - ZYVA</title>
    
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/main.js" defer></script>
    
    <style>
        .search-header {
            padding: var(--spacing-8) 0;
            border-bottom: 1px solid var(--color-border);
        }
        
        .search-tabs {
            display: flex;
            gap: var(--spacing-6);
            margin-top: var(--spacing-6);
            border-bottom: 1px solid var(--color-border);
        }
        
        .search-tab {
            padding: var(--spacing-3) 0;
            font-weight: 500;
            color: var(--color-text-secondary);
            cursor: pointer;
            position: relative;
        }
        
        .search-tab.active {
            color: var(--color-text-primary);
        }
        
        .search-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--color-primary);
            border-radius: 2px 2px 0 0;
        }
        
        .creator-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-4);
            border-bottom: 1px solid var(--color-border);
        }
        
        .grid-videos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: var(--spacing-4);
            margin-top: var(--spacing-6);
        }
        
        .grid-video-card {
            aspect-ratio: 9/16;
            border-radius: var(--radius-md);
            background-color: var(--color-surface);
            overflow: hidden;
            position: relative;
            cursor: pointer;
        }
        
        .grid-video-card img,
        .grid-video-card video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition-normal);
        }
        
        .grid-video-card:hover img,
        .grid-video-card:hover video {
            transform: scale(1.05);
        }
        
        .grid-video-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: var(--spacing-3);
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
            font-size: var(--text-sm);
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="container">
                <a href="../index.php" class="navbar-brand">
                    <i data-lucide="zap" class="text-accent"></i>
                    <span>ZYVA</span>
                </a>
                
                <div class="navbar-actions">
                    <button class="btn btn-icon btn-ghost" onclick="toggleTheme()">
                        <i data-lucide="moon" class="theme-icon"></i>
                    </button>
                    <a href="upload.php" class="btn btn-primary">
                        <i data-lucide="plus"></i>
                        <span>Upload</span>
                    </a>
                </div>
            </div>
        </nav>

        <div class="main-wrapper">
            <!-- Left Sidebar -->
            <aside class="left-sidebar">
                <nav class="sidebar-nav">
                    <a href="home.php" class="sidebar-link">
                        <i data-lucide="home"></i>
                        <span>For You</span>
                    </a>
                    <a href="search.php" class="sidebar-link active">
                        <i data-lucide="compass"></i>
                        <span>Explore</span>
                    </a>
                    <a href="profile.php" class="sidebar-link">
                        <i data-lucide="user"></i>
                        <span>Profile</span>
                    </a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="feed-content" style="max-width: 800px;">
                
                <div class="search-header">
                    <form action="search.php" method="GET" class="input-icon-wrapper w-full">
                        <i data-lucide="search"></i>
                        <input type="text" name="q" class="input-field" placeholder="Search hashtags, creators, or videos..." style="font-size: var(--text-lg); padding: var(--spacing-4) var(--spacing-12);" value="<?= htmlspecialchars($searchQuery) ?>">
                    </form>
                    
                    <div class="search-tabs">
                        <div class="search-tab active">Top</div>
                        <div class="search-tab">Hashtags</div>
                        <div class="search-tab">Creators</div>
                        <div class="search-tab">Videos</div>
                    </div>
                </div>
                
                <div class="py-6 mt-6">
                    <h3 class="h4 mb-4">
                        <?= !empty($searchQuery) ? 'Creator Results' : 'Trending Creators' ?>
                    </h3>
                    <div class="card mb-8">
                        <?php foreach($creators as $creator): 
                            $cAvatar = $creator['avatar_url'] ? (strpos($creator['avatar_url'], 'http') === 0 ? $creator['avatar_url'] : '../' . $creator['avatar_url']) : 'https://i.pravatar.cc/150?img=11';
                        ?>
                        <div class="creator-card">
                            <div class="flex items-center gap-4">
                                <img src="<?= htmlspecialchars($cAvatar) ?>" class="avatar avatar-lg">
                                <div>
                                    <h4 class="font-semibold text-lg">@<?= htmlspecialchars($creator['username']) ?></h4>
                                    <p class="text-sm text-secondary truncate" style="max-width: 200px;"><?= htmlspecialchars($creator['bio'] ?: 'No bio available') ?></p>
                                </div>
                            </div>
                            <button class="btn btn-primary">Follow</button>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <h3 class="h4 mb-4">
                        <?= !empty($searchQuery) ? 'Video Results' : 'Trending Videos' ?>
                    </h3>
                    <div class="grid-videos">
                        <?php foreach($videos as $video): ?>
                        <div class="grid-video-card cursor-pointer">
                            <video src="../<?= htmlspecialchars($video['file_path']) ?>" style="width: 100%; height: 100%; object-fit: cover;"></video>
                            <div class="grid-video-info">
                                <div class="flex items-center gap-1 mb-1">
                                    <i data-lucide="play" style="width: 14px; height: 14px;"></i> <?= number_format($video['views']) ?>
                                </div>
                                <span class="tag" style="font-size: 10px; padding: 2px 6px;">@<?= htmlspecialchars($video['username']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($videos)): ?>
                        <p class="text-secondary">No videos available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>
</body>
</html>
