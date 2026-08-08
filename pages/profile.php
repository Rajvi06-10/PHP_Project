<?php
session_start();
require_once '../config/db.php';

$profileUserId = isset($_GET['id']) ? (int)$_GET['id'] : ($_SESSION['user_id'] ?? null);

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

$videoStmt = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? ORDER BY created_at DESC");
$videoStmt->execute([$profileUserId]);
$videos = $videoStmt->fetchAll();

$isOwner = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profileUserId);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ZYVA</title>
    
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/main.js" defer></script>

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
                    <a href="search.php" class="sidebar-link">
                        <i data-lucide="compass"></i>
                        <span>Explore</span>
                    </a>
                    <a href="profile.php" class="sidebar-link active">
                        <i data-lucide="user"></i>
                        <span>Profile</span>
                    </a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="w-full pb-10">
                
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
                            <button class="btn btn-primary">Follow</button>
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
                    <div class="profile-tab active">Videos</div>
                    <div class="profile-tab">Liked</div>
                    <div class="profile-tab">Collections</div>
                    <div class="profile-tab"><i data-lucide="lock" style="width: 14px; height: 14px; display: inline; vertical-align: middle;"></i> Private</div>
                </div>

                <!-- Video Grid -->
                <div class="grid-videos">
                    <?php foreach($videos as $video): ?>
                    <div class="grid-video-card cursor-pointer">
                        <video src="../<?= htmlspecialchars($video['file_path']) ?>" style="width: 100%; height: 100%; object-fit: cover;"></video>
                        <div class="grid-video-stats">
                            <i data-lucide="play" style="width: 14px; height: 14px;"></i> <?= number_format($video['view_count']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($videos)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--color-text-secondary);">
                        <p>No videos uploaded yet.</p>
                    </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>
</body>
</html>
