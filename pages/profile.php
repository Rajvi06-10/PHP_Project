<?php
require_once '../config/db.php';

$username = $_SESSION['username'] ?? 'User';
$sessionAvatar = isset($_SESSION['avatar']) && $_SESSION['avatar'] ? (strpos($_SESSION['avatar'], 'http') === 0 ? $_SESSION['avatar'] : '../' . $_SESSION['avatar']) : 'https://i.pravatar.cc/150?img=11';

$profileUserId = $_GET['id'] ?? $_SESSION['user_id'] ?? null;

if (!$profileUserId) {
    header("Location: auth.php");
    exit;
}

$stmt = $pdo->prepare("SELECT username, bio, avatar_url FROM users WHERE id = ?");
$stmt->execute([$profileUserId]);
$profileUser = $stmt->fetch();

if (!$profileUser) {
    die("User not found.");
}

$avatarSrc = $profileUser['avatar_url'] ? (strpos($profileUser['avatar_url'], 'http') === 0 ? $profileUser['avatar_url'] : '../' . $profileUser['avatar_url']) : 'https://i.pravatar.cc/150?img=11';

$loggedInUserId = $_SESSION['user_id'] ?? null;
$followed_ids = [];
if ($loggedInUserId) {
    $stmt = $pdo->prepare("SELECT following_id FROM follows WHERE follower_id = ?");
    $stmt->execute([$loggedInUserId]);
    $followed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$activeTab = $_GET['tab'] ?? 'videos';

if ($activeTab === 'saved') {
    $videoStmt = $pdo->prepare("SELECT v.* FROM videos v JOIN saved_reels s ON v.id = s.video_id WHERE s.user_id = ? ORDER BY s.created_at DESC");
} else {
    $videoStmt = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? ORDER BY created_at DESC");
}
$videoStmt->execute([$profileUserId]);
$videos = $videoStmt->fetchAll();

$isOwner = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profileUserId);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Swipe Nest</title>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    
    <link rel="stylesheet" href="../assets/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/globals.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.css">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/main.js?v=<?= time() ?>" defer></script>

    <style>
        .profile-banner {
            width: 100%;
            height: 200px;
            background-color: var(--color-surface);
            background-image: url('https://images.unsplash.com/photo-1579546929518-9e396f3cc809?q=80&w=1200&h=400&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .profile-header {
            padding: 0 var(--spacing-8);
            position: relative;
            margin-top: -60px;
            display: flex;
            align-items: flex-end;
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: var(--radius-full);
            border: 4px solid var(--color-bg-primary);
            object-fit: cover;
            background-color: var(--color-surface);
        }

        .profile-info {
            flex: 1;
            padding-bottom: var(--spacing-2);
        }

        .profile-stats {
            display: flex;
            gap: var(--spacing-6);
            margin-top: var(--spacing-3);
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
        }

        .stat-value {
            font-weight: 700;
            font-size: var(--text-lg);
        }

        .stat-label {
            color: var(--color-text-secondary);
            font-size: var(--text-sm);
        }

        .profile-tabs {
            display: flex;
            gap: var(--spacing-8);
            border-bottom: 1px solid var(--color-border);
            margin-bottom: var(--spacing-6);
            padding: 0 var(--spacing-8);
        }

        .profile-tab {
            padding: var(--spacing-3) 0;
            font-weight: 500;
            color: var(--color-text-secondary);
            cursor: pointer;
            position: relative;
        }

        .profile-tab.active {
            color: var(--color-text-primary);
        }

        .profile-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--color-primary);
            border-radius: 2px 2px 0 0;
        }

        .grid-videos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: var(--spacing-4);
            padding: 0 var(--spacing-8);
        }

        .grid-video-card {
            aspect-ratio: 9/16;
            border-radius: var(--radius-md);
            background-color: var(--color-surface);
            overflow: hidden;
            position: relative;
            cursor: pointer;
        }

        .grid-video-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition-normal);
        }

        .grid-video-card:hover img {
            transform: scale(1.05);
        }
        
        .pin-badge {
            position: absolute;
            top: var(--spacing-2);
            right: var(--spacing-2);
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            padding: 4px;
            border-radius: var(--radius-full);
            color: white;
            z-index: 10;
        }

        .grid-video-stats {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: var(--spacing-2) var(--spacing-3);
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
            font-size: var(--text-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-1);
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
                <a href="search.php" class="sidebar-link">
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
                <a href="profile.php" class="sidebar-link active">
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
            <div class="main-content-inner">
                <!-- Banner & Profile Info -->
                <div class="profile-banner"></div>
                
                <div class="profile-header">
                    <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Profile Avatar" class="profile-avatar">
                    
                    <div class="profile-info">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="h3 mb-1">@<?= htmlspecialchars($profileUser['username']) ?></h1>
                                <p class="text-secondary font-medium">@<?= htmlspecialchars($profileUser['username']) ?></p>
                            </div>
                            <?php if($isOwner): ?>
                            <a href="settings.php" class="btn btn-secondary">
                                <i data-lucide="edit-2" style="width: 16px; height: 16px;"></i>
                                Edit Profile
                            </a>
                            <?php else: ?>
                            <?php $isFollowing = in_array($profileUserId, $followed_ids); ?>
                            <button class="btn <?= $isFollowing ? 'btn-secondary' : 'btn-primary' ?>" onclick="toggleFollow(<?= $profileUserId ?>, this)">
                                <?= $isFollowing ? 'Following' : 'Follow' ?>
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <p class="mt-3 text-sm max-w-md"><?= htmlspecialchars($profileUser['bio'] ?: 'No bio yet.') ?></p>
                        
                        <div class="profile-stats">
                            <div class="stat-item">
                                <span class="stat-value">142</span>
                                <span class="stat-label">Following</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">12.5K</span>
                                <span class="stat-label">Followers</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">840K</span>
                                <span class="stat-label">Likes</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="profile-tabs">
                    <a href="?tab=videos" class="profile-tab <?= $activeTab === 'videos' ? 'active' : '' ?>" style="text-decoration:none; color:inherit;">Videos</a>
                    <a href="?tab=saved" class="profile-tab <?= $activeTab === 'saved' ? 'active' : '' ?>" style="text-decoration:none; color:inherit;">Saved</a>
                </div>

                <!-- Video Grid -->
                <div class="grid-videos" id="video-grid">
                    <?php foreach($videos as $video): ?>
                    <div class="grid-video-card cursor-pointer" id="video-card-<?= $video['id'] ?>">
                        <video src="../<?= htmlspecialchars($video['file_path']) ?>" style="width: 100%; height: 100%; object-fit: cover;"></video>
                        <div class="grid-video-stats">
                            <i data-lucide="play" style="width: 14px; height: 14px;"></i> <?= number_format($video['views']) ?>
                        </div>
                        <?php if($isOwner && $activeTab !== 'saved'): ?>
                        <button onclick="deleteVideo(<?= $video['id'] ?>); event.stopPropagation();" title="Delete Reel" style="position: absolute; top: 8px; right: 8px; background: rgba(220, 38, 38, 0.85); color: white; border: none; padding: 6px; border-radius: 50%; cursor: pointer; backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 20; transition: all 0.2s;">
                            <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($videos)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--color-text-secondary);">
                        <p>No videos found.</p>
                    </div>
                    <?php endif; ?>
                </div>

            </div> <!-- /main-content-inner -->
        </main>
    </div>

    <script>
        async function deleteVideo(videoId) {
            if (!confirm("Are you sure you want to delete this reel?")) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            try {
                const formData = new FormData();
                formData.append('action', 'delete_video');
                formData.append('video_id', videoId);

                const res = await fetch('../api/action.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-Token': csrfToken }
                });
                const data = await res.json();

                if (data.success) {
                    const card = document.getElementById('video-card-' + videoId);
                    if (card) {
                        card.style.transform = 'scale(0.8)';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                    }
                } else {
                    alert(data.message || 'Failed to delete video');
                }
            } catch(e) { console.error('Delete failed', e); }
        }

        async function toggleFollow(userId, btnEl) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_follow');
                formData.append('following_id', userId);

                const res = await fetch('../api/action.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-Token': csrfToken }
                });
                const data = await res.json();

                if (data.success) {
                    if (data.status === 'followed') {
                        btnEl.textContent = 'Following';
                        btnEl.classList.remove('btn-primary');
                        btnEl.classList.add('btn-secondary');
                    } else {
                        btnEl.textContent = 'Follow';
                        btnEl.classList.remove('btn-secondary');
                        btnEl.classList.add('btn-primary');
                    }
                }
            } catch(e) { console.error('Follow failed', e); }
        }
    </script>
</body>
</html>
