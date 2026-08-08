<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$message = '';
$error = '';

// Fetch categories from DB
$cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = $_POST['caption'] ?? '';
    $category_id = $_POST['category_id'] ?? 1;
    $visibility = $_POST['visibility'] ?? 'Public';
    $hashtags_input = $_POST['hashtags'] ?? '';
    
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
        $allowed = ['video/mp4', 'video/webm'];
        if (in_array($_FILES['video_file']['type'], $allowed)) {
            $ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $uploadPath = '../uploads/' . $filename;
            
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadPath)) {
                $stmt = $pdo->prepare("INSERT INTO videos (user_id, category_id, file_path, description, visibility) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $category_id, 'uploads/' . $filename, $caption, $visibility]);
                
                $video_id = $pdo->lastInsertId();
                
                // Process hashtags
                if (!empty($hashtags_input)) {
                    $tags = array_map('trim', explode(',', $hashtags_input));
                    foreach ($tags as $tag) {
                        $tag = ltrim($tag, '#');
                        if (!empty($tag)) {
                            $tagStmt = $pdo->prepare("INSERT INTO hashtags (video_id, tag) VALUES (?, ?)");
                            $tagStmt->execute([$video_id, $tag]);
                        }
                    }
                }
                
                $message = "Video uploaded successfully!";
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file type. Only MP4 and WebM are allowed.";
        }
    } else {
        $error = "Please select a valid video file.";
    }
}

$stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$avatarSrc = $user['avatar_url'] ? (strpos($user['avatar_url'], 'http') === 0 ? $user['avatar_url'] : '../' . $user['avatar_url']) : 'https://i.pravatar.cc/150?img=11';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload - ZYVA</title>
    
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/main.js" defer></script>

    <style>
        .upload-area {
            border: 2px dashed var(--color-border);
            border-radius: var(--radius-xl);
            padding: var(--spacing-16) var(--spacing-6);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            background-color: var(--color-surface);
            transition: all var(--transition-normal);
            cursor: pointer;
            position: relative;
        }
        .upload-area:hover {
            border-color: var(--color-primary);
            background-color: var(--color-surface-hover);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--spacing-6);
        }
        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr 2fr;
            }
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
                    <a href="profile.php">
                        <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Profile" class="avatar">
                    </a>
                </div>
            </div>
        </nav>

        <div class="main-wrapper justify-center" style="padding: var(--spacing-10) 0;">
            <main class="w-full" style="max-width: 900px; padding: 0 var(--spacing-6);">
                
                <h1 class="h3 mb-2">Upload Video</h1>
                <p class="text-secondary mb-8">Post a video to your account</p>
                <?php if($message): ?><div style="color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?= $message ?></div><?php endif; ?>
                <?php if($error): ?><div style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?= $error ?></div><?php endif; ?>

                <div class="card p-6" style="padding: var(--spacing-6);">
                    <form method="POST" enctype="multipart/form-data" class="form-grid">
                        
                        <!-- Left: Upload Area & Preview -->
                        <div>
                            <div class="upload-area">
                                <input type="file" name="video_file" id="video_file" accept="video/mp4,video/webm" required style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; left: 0; top: 0; z-index: 10;">
                                <i data-lucide="upload-cloud" class="upload-icon" style="width:64px;height:64px;color:var(--color-primary);margin-bottom:15px;"></i>
                                <h3 class="font-semibold mb-2">Select video to upload</h3>
                                <p class="text-sm text-secondary mb-6">MP4 or WebM format</p>
                                <button type="button" class="btn btn-primary pointer-events-none">Select file</button>
                            </div>
                            <div id="file-name-display" class="mt-4 text-center text-sm text-primary"></div>
                        </div>

                        <!-- Right: Form Details -->
                        <div>
                            <div class="input-group mb-4">
                                <label class="input-label">Caption</label>
                                <textarea name="caption" class="input-field" rows="4" placeholder="Describe your video..."></textarea>
                            </div>

                            <div class="input-group mb-4">
                                <label class="input-label">Hashtags</label>
                                <input type="text" name="hashtags" class="input-field" placeholder="e.g. funny, tech, learning">
                                <p class="text-xs text-tertiary mt-1">Separate with commas.</p>
                            </div>

                            <div class="input-group mb-4">
                                <label class="input-label">Category</label>
                                <div style="position: relative;">
                                    <select name="category_id" class="input-field" style="appearance: none;">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i data-lucide="chevron-down" style="position: absolute; right: 12px; top: 12px; color: var(--color-text-secondary); width: 18px; height: 18px; pointer-events: none;"></i>
                                </div>
                            </div>

                            <div class="input-group mb-8">
                                <label class="input-label">Visibility</label>
                                <div class="flex gap-4 mt-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="visibility" value="Public" checked>
                                        <span class="text-sm">Public</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="visibility" value="Private">
                                        <span class="text-sm">Private</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <button type="submit" class="btn btn-primary w-full justify-center">Post Video</button>
                            </div>
                        </div>

                    </form>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        document.getElementById('video_file').addEventListener('change', function(e) {
            if(this.files && this.files[0]) {
                document.getElementById('file-name-display').textContent = 'Selected: ' + this.files[0].name;
            }
        });
        lucide.createIcons();
    </script>
</body>
</html>
