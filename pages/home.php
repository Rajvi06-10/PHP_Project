<?php
session_start();
require_once '../config/db.php';

$stmt = $pdo->query("SELECT v.*, u.username, u.avatar_url as avatar FROM videos v JOIN users u ON v.user_id = u.id ORDER BY v.created_at DESC");
$videos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - ZYVA</title>
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Main JS -->
    <script src="../assets/js/main.js" defer></script>
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
                
                <div class="navbar-search hidden md:block" style="display: block; width: 100%;">
                    <form action="search.php" method="GET" class="input-icon-wrapper">
                        <i data-lucide="search"></i>
                        <input type="text" name="q" class="input-field" placeholder="Search hashtags, creators...">
                    </form>
                </div>

                <div class="navbar-actions">
                    <button class="btn btn-icon btn-ghost" onclick="toggleTheme()">
                        <i data-lucide="moon" class="theme-icon"></i>
                    </button>
                    <a href="upload.php" class="btn btn-primary">
                        <i data-lucide="plus"></i>
                        <span>Upload</span>
                    </a>
                    <div class="relative">
                        <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Profile" class="avatar cursor-pointer" data-dropdown-toggle="profile-menu">
                        <div class="dropdown-menu" id="profile-menu">
                            <a href="profile.php" class="dropdown-item"><i data-lucide="user"></i> Profile</a>
                            <a href="settings.php" class="dropdown-item"><i data-lucide="settings"></i> Settings</a>
                            <hr style="margin: var(--spacing-2) 0; border-color: var(--color-border);">
                            <a href="../index.php" class="dropdown-item text-danger"><i data-lucide="log-out"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="main-wrapper">
            
            <!-- Left Sidebar -->
            <aside class="left-sidebar">
                <nav class="sidebar-nav">
                    <a href="home.php" class="sidebar-link active">
                        <i data-lucide="home"></i>
                        <span>For You</span>
                    </a>
                    <a href="search.php" class="sidebar-link">
                        <i data-lucide="compass"></i>
                        <span>Explore</span>
                    </a>
                    <a href="profile.php" class="sidebar-link">
                        <i data-lucide="user"></i>
                        <span>Profile</span>
                    </a>
                </nav>
                
                <hr style="border-color: var(--color-border);">
                
                <div>
                    <p class="text-xs font-semibold text-tertiary mb-3 uppercase tracking-wider" style="padding-left: var(--spacing-4)">Following Tags</p>
                    <div class="sidebar-nav">
                        <a href="#" class="sidebar-link"><span>#UIUX</span></a>
                        <a href="#" class="sidebar-link"><span>#MotionDesign</span></a>
                        <a href="#" class="sidebar-link"><span>#TechNews</span></a>
                    </div>
                </div>
            </aside>

            <!-- Main Feed -->
            <main class="feed-content">
                
                <?php if(empty($videos)): ?>
                    <div style="text-align: center; padding: 50px; color: var(--color-text-secondary);">
                        <p>No videos found. Be the first to <a href="upload.php" style="color: var(--color-primary);">upload</a>!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($videos as $video): ?>
                    <div class="video-card">
                        <video src="../<?= htmlspecialchars($video['file_path']) ?>" class="video-player" loop controls style="object-fit: cover; width: 100%; height: 100%; background: black;"></video>
                        
                        <div class="video-overlay" style="pointer-events: none;">
                            <div class="video-info" style="pointer-events: auto;">
                                <div class="video-author">
                                    <img src="<?= htmlspecialchars($video['avatar']) ?>" alt="Creator" class="avatar avatar-sm">
                                    <div>
                                        <h4 class="text-sm font-semibold text-primary">@<?= htmlspecialchars($video['username']) ?></h4>
                                    </div>
                                    <?php if(!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $video['user_id']): ?>
                                    <button class="btn btn-primary" style="padding: 4px 12px; font-size: 12px; margin-left: 8px;">Follow</button>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-primary mb-2"><?= htmlspecialchars($video['description']) ?></p>
                                <div class="flex gap-2 flex-wrap">
                                    <?php 
                                        $tagStmt = $pdo->prepare("SELECT tag FROM hashtags WHERE video_id = ?");
                                        $tagStmt->execute([$video['id']]);
                                        while($tag = $tagStmt->fetchColumn()):
                                    ?>
                                    <span class="tag">#<?= htmlspecialchars($tag) ?></span>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>

                        <div class="video-actions">
                            <button class="action-btn">
                                <div class="icon-bg"><i data-lucide="heart"></i></div>
                                <span>0</span>
                            </button>
                            <button class="action-btn">
                                <div class="icon-bg"><i data-lucide="message-circle"></i></div>
                                <span>0</span>
                            </button>
                            <button class="action-btn">
                                <div class="icon-bg"><i data-lucide="bookmark"></i></div>
                                <span>Save</span>
                            </button>
                            <button class="action-btn">
                                <div class="icon-bg"><i data-lucide="share-2"></i></div>
                                <span>Share</span>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </main>

            <!-- Right Sidebar (Suggested/Actions) -->
            <aside class="right-sidebar">
                <div class="card card-glass mb-6">
                    <div style="padding: var(--spacing-4);">
                        <h3 class="text-sm font-semibold mb-4 text-secondary uppercase tracking-wider">Suggested Creators</h3>
                        
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <img src="https://i.pravatar.cc/150?img=47" class="avatar avatar-sm">
                                    <div>
                                        <div class="text-sm font-semibold">@dev_log</div>
                                        <div class="text-xs text-tertiary">Frontend Tips</div>
                                    </div>
                                </div>
                                <button class="text-accent text-sm font-medium">Follow</button>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <img src="https://i.pravatar.cc/150?img=68" class="avatar avatar-sm">
                                    <div>
                                        <div class="text-sm font-semibold">@motion_magic</div>
                                        <div class="text-xs text-tertiary">3D Artist</div>
                                    </div>
                                </div>
                                <button class="text-accent text-sm font-medium">Follow</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-4">
                    <div style="padding: var(--spacing-4);">
                        <h3 class="text-sm font-semibold mb-4 text-secondary uppercase tracking-wider">Trending Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            <span class="tag">#Web3</span>
                            <span class="tag">#AIArt</span>
                            <span class="tag">#CodingLife</span>
                            <span class="tag">#MinimalSetup</span>
                        </div>
                    </div>
                </div>
            </aside>
            
        </div>
        
        <!-- Mobile Bottom Nav -->
        <nav class="bottom-nav-mobile md:hidden" style="display: none;">
            <a href="home.php" class="action-btn text-primary"><i data-lucide="home"></i></a>
            <a href="search.php" class="action-btn text-secondary"><i data-lucide="search"></i></a>
            <a href="upload.php" class="action-btn text-secondary"><div class="icon-bg" style="width:40px;height:40px;background:var(--color-primary);"><i data-lucide="plus" color="white"></i></div></a>
            <a href="profile.php" class="action-btn text-secondary"><i data-lucide="user"></i></a>
        </nav>
    </div>
</body>
</html>
