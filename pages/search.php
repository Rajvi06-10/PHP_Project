<?php
session_start();
require_once '../config/db.php';

$username = $_SESSION['username'] ?? 'User';
$sessionAvatar = isset($_SESSION['avatar']) && $_SESSION['avatar'] ? (strpos($_SESSION['avatar'], 'http') === 0 ? $_SESSION['avatar'] : '../' . $_SESSION['avatar']) : 'https://i.pravatar.cc/150?img=11';

$searchQuery = $_GET['q'] ?? '';

$user_id = $_SESSION['user_id'] ?? null;
$followed_ids = [];
if ($user_id) {
    $stmt = $pdo->prepare("SELECT following_id FROM follows WHERE follower_id = ?");
    $stmt->execute([$user_id]);
    $followed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$creators = [];
$videos = [];
$found_categories = [];
$found_hashtags = [];

if (!empty($searchQuery)) {
    $searchParam = '%' . $searchQuery . '%';
    
    // Search creators
    $creatorsStmt = $pdo->prepare("SELECT id, username, bio, avatar_url FROM users WHERE username LIKE ? OR bio LIKE ? ORDER BY created_at DESC LIMIT 10");
    $creatorsStmt->execute([$searchParam, $searchParam]);
    $creators = $creatorsStmt->fetchAll();

    // Search categories
    $catStmt = $pdo->prepare("SELECT id, name FROM categories WHERE name LIKE ? ORDER BY id ASC LIMIT 5");
    $catStmt->execute([$searchParam]);
    $found_categories = $catStmt->fetchAll();

    // Search hashtags
    $tagStmt = $pdo->prepare("SELECT DISTINCT tag FROM hashtags WHERE tag LIKE ? LIMIT 10");
    $tagStmt->execute([$searchParam]);
    $found_hashtags = $tagStmt->fetchAll();

    // Search videos (by description, user, or category)
    $videosStmt = $pdo->prepare("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id LEFT JOIN categories c ON v.category_id = c.id WHERE v.description LIKE ? OR u.username LIKE ? OR c.name LIKE ? ORDER BY v.created_at DESC LIMIT 20");
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
    <title>Search - Swipe Nest</title>
    
    <link rel="stylesheet" href="../assets/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/globals.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.css">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/main.js" defer></script>
    
    <style>
        .search-header {
            padding: var(--spacing-8) 0;
            border-bottom: 1px solid var(--color-border);
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
        <!-- Left Sidebar -->
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
                <a href="search.php" class="sidebar-link active">
                    <i data-lucide="search"></i>
                    <span>Search</span>
                </a>
                <a href="scrolls.php" class="sidebar-link">
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
            </nav>
            
            <div class="sidebar-footer">
                <a href="profile.php" class="sidebar-user">
                    <img src="<?= htmlspecialchars($sessionAvatar) ?>" alt="Profile">
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
            <div class="main-content-inner" style="max-width: 800px; padding: 40px 20px;">
                
                <div class="search-header" style="margin-bottom: 40px;">
                    <form action="search.php" method="GET" style="position: relative; width: 100%;">
                        <i data-lucide="search" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--color-text-tertiary); width: 20px; height: 20px;"></i>
                        <input type="text" name="q" class="input-field" placeholder="Search hashtags, creators, or videos..." 
                               style="width: 100%; font-size: 16px; padding: 16px 20px 16px 52px; border-radius: 12px; background-color: var(--color-surface); border: 1px solid var(--color-border); color: var(--color-text-primary); transition: all 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.02);" 
                               value="<?= htmlspecialchars($searchQuery) ?>">
                    </form>
                </div>
                
                <div>
                    <h3 class="h4 mb-4" style="font-weight: 700;">
                        <?= !empty($searchQuery) ? 'Creator Results' : 'Trending Creators' ?>
                    </h3>
                    <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 16px; overflow: hidden; margin-bottom: 40px;">
                        <?php foreach($creators as $creator): 
                            $cAvatar = $creator['avatar_url'] ? (strpos($creator['avatar_url'], 'http') === 0 ? $creator['avatar_url'] : '../' . $creator['avatar_url']) : 'https://i.pravatar.cc/150?img=11';
                        ?>
                        <div class="creator-card" style="display: flex; align-items: center; justify-content: space-between; padding: 20px; border-bottom: 1px solid var(--color-border);">
                            <div class="flex items-center gap-4">
                                <img src="<?= htmlspecialchars($cAvatar) ?>" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(0,0,0,0.1);">
                                <div>
                                    <h4 class="font-semibold text-base" style="margin-bottom: 2px;">@<?= htmlspecialchars($creator['username']) ?></h4>
                                    <p class="text-sm text-secondary truncate" style="max-width: 200px;"><?= htmlspecialchars($creator['bio'] ?: 'No bio available') ?></p>
                                </div>
                            </div>
                            <?php $isFollowing = in_array($creator['id'], $followed_ids); ?>
                            <?php if ($creator['id'] != $user_id): ?>
                            <button class="btn <?= $isFollowing ? 'btn-secondary' : 'btn-primary' ?>" onclick="toggleFollow(<?= $creator['id'] ?>, this)" style="border-radius: 20px; padding: 8px 16px; font-size: 13px;">
                                <?= $isFollowing ? 'Following' : 'Follow' ?>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($creators)): ?>
                            <div style="padding: 30px; text-align: center; color: var(--color-text-secondary); font-size: 14px;">No creators found.</div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($searchQuery)): ?>
                    <h3 class="h4 mb-4" style="font-weight: 700;">Categories</h3>
                    <div class="flex flex-wrap gap-2 mb-8" style="margin-bottom: 40px;">
                        <?php foreach($found_categories as $cat): ?>
                            <a href="home.php" class="btn btn-secondary" style="border-radius: 24px; padding: 8px 16px; font-weight: 500; font-size: 14px; text-decoration: none; border: 1px solid var(--color-border); background: var(--color-surface);">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if(empty($found_categories)): ?>
                            <div style="color: var(--color-text-secondary); font-size: 14px;">No categories found.</div>
                        <?php endif; ?>
                    </div>

                    <h3 class="h4 mb-4" style="font-weight: 700;">Hashtags</h3>
                    <div class="flex flex-wrap gap-2 mb-8" style="margin-bottom: 40px;">
                        <?php foreach($found_hashtags as $tag): ?>
                            <a href="search.php?q=<?= urlencode($tag['tag']) ?>" class="btn btn-secondary" style="border-radius: 24px; padding: 8px 16px; font-weight: 500; font-size: 14px; text-decoration: none; border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-primary);">
                                #<?= htmlspecialchars($tag['tag']) ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if(empty($found_hashtags)): ?>
                            <div style="color: var(--color-text-secondary); font-size: 14px;">No hashtags found.</div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <h3 class="h4 mb-4" style="font-weight: 700;">
                        <?= !empty($searchQuery) ? 'Video Results' : 'Trending Videos' ?>
                    </h3>
                    <div class="grid-videos" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
                        <?php foreach($videos as $video): ?>
                        <div class="grid-video-card cursor-pointer" style="aspect-ratio: 9/16; border-radius: 12px; background: #111; overflow: hidden; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            <video src="../<?= htmlspecialchars($video['file_path']) ?>" style="width: 100%; height: 100%; object-fit: cover;"></video>
                            <div class="grid-video-info" style="position: absolute; bottom: 0; left: 0; right: 0; padding: 16px 12px 12px; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); color: white;">
                                <div class="flex items-center gap-2 mb-1">
                                    <i data-lucide="play" style="width: 14px; height: 14px;"></i> <?= number_format($video['view_count']) ?>
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
            </div> <!-- /main-content-inner -->
        </main>
    </div>
    <script>
        async function toggleFollow(userId, btnEl) {
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_follow');
                formData.append('following_id', userId);
                
                const res = await fetch('../api/action.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    if (data.status === 'followed') {
                        btnEl.textContent = 'Following';
                        btnEl.classList.remove('btn-primary');
                        btnEl.classList.add('btn-secondary');
                    } else {
                        btnEl.textContent = 'Follow';
                        btnEl.classList.add('btn-primary');
                        btnEl.classList.remove('btn-secondary');
                    }
                }
            } catch(e) { console.error('Follow failed', e); }
        }
    </script>
</body>
</html>
