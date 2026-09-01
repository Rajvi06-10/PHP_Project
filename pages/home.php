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

// Fetch all videos for home feed (Instagram style)
$stmt = $pdo->prepare("
    SELECT v.*, u.username, u.avatar_url,
           (SELECT COUNT(*) FROM likes WHERE video_id = v.id) as like_count,
           (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = ?) as is_liked
    FROM videos v 
    JOIN users u ON v.user_id = u.id 
    ORDER BY v.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$feedVideos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swipe Nest - Home</title>
    
    <link rel="stylesheet" href="../assets/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/reset.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/globals.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.css?v=<?= time() ?>">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        .feed-container {
            max-width: 470px;
            margin: 0 auto;
            padding-top: 20px;
            padding-bottom: 80px;
        }
        
        .post-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            margin-bottom: 24px;
            overflow: hidden;
            position: relative; /* For double tap heart */
        }
        
        .post-header {
            display: flex;
            align-items: center;
            padding: 14px;
            gap: 12px;
        }
        
        .post-header img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .post-header .username {
            font-weight: 600;
            font-size: 14px;
            color: var(--color-text-primary);
            text-decoration: none;
        }
        
        .post-media {
            width: 100%;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .post-video {
            width: 100%;
            max-height: 600px;
            object-fit: contain;
            cursor: pointer;
        }
        
        .post-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 14px;
        }
        
        .post-actions i, .post-actions svg {
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .post-actions i:hover, .post-actions svg:hover {
            color: var(--color-text-secondary);
        }
        
        .post-details {
            padding: 0 14px 14px;
        }
        
        .post-likes {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .post-caption {
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .post-caption .username {
            font-weight: 600;
            margin-right: 6px;
        }
        
        .post-time {
            font-size: 12px;
            color: var(--color-text-tertiary);
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="app-layout">
        
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

        <!-- Main Content -->
        <main class="main-content">
            <div class="feed-container">
                <?php foreach($feedVideos as $video): 
                    $vAvatar = $video['avatar_url'] ? (strpos($video['avatar_url'], 'http') === 0 ? $video['avatar_url'] : '../' . $video['avatar_url']) : 'https://i.pravatar.cc/150?img=11';
                ?>
                <div class="post-card">
                    <div class="post-header">
                        <img src="<?= htmlspecialchars($vAvatar) ?>" alt="Avatar">
                        <a href="profile.php?id=<?= $video['user_id'] ?>" class="username"><?= htmlspecialchars($video['username']) ?></a>
                        
                        <?php if($video['user_id'] == $_SESSION['user_id']): ?>
                        <div style="margin-left: auto; cursor: pointer; color: var(--color-danger);" onclick="deleteVideo(<?= $video['id'] ?>, this)">
                            <i data-lucide="trash-2"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-media">
                        <video class="post-video" src="../<?= htmlspecialchars($video['file_path']) ?>" loop playsinline preload="metadata" onclick="handleVideoTap(this, <?= $video['id'] ?>)"></video>
                    </div>
                    
                    <div class="post-actions">
                        <i data-lucide="heart" class="like-btn-<?= $video['id'] ?> <?= $video['is_liked'] ? 'active' : '' ?>" style="<?= $video['is_liked'] ? 'color:#ff3040; fill:#ff3040;' : '' ?>" onclick="toggleLike(<?= $video['id'] ?>, this)"></i>
                        <i data-lucide="message-circle" onclick="addComment(<?= $video['id'] ?>, this)"></i>
                        <i data-lucide="send" onclick="shareVideo('<?= htmlspecialchars($video['file_path']) ?>')"></i>
                        <i data-lucide="bookmark" style="margin-left: auto;" onclick="toggleSave(<?= $video['id'] ?>, this)"></i>
                    </div>
                    
                    <div class="post-details">
                        <div class="post-likes">
                            <span class="like-btn-<?= $video['id'] ?>"><span><?= $video['like_count'] > 1000 ? number_format($video['like_count']/1000, 1) . 'K' : $video['like_count'] ?></span></span> likes
                        </div>
                        <div class="post-caption">
                            <span class="username"><?= htmlspecialchars($video['username']) ?></span>
                            <?= htmlspecialchars($video['description']) ?>
                        </div>
                        <div class="post-time"><?= date('F j, Y', strtotime($video['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($feedVideos)): ?>
                    <div style="text-align:center; padding: 40px; color: var(--color-text-secondary);">No posts yet. Start following people or creating!</div>
                <?php endif; ?>
            </div>
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
    
    <script>
        window.currentUserId = <?= json_encode($_SESSION['user_id']) ?>;
    </script>
    <script src="../assets/js/home_feed.js"></script>
    <!-- Use main functions for likes/comments/saves -->
    <script src="../assets/js/main.js"></script> 
    
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
