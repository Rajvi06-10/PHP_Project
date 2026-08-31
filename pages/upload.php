<?php
session_start();
require_once '../config/db.php';

$username = $_SESSION['username'] ?? 'User';
$sessionAvatar = isset($_SESSION['avatar']) && $_SESSION['avatar'] ? (strpos($_SESSION['avatar'], 'http') === 0 ? $_SESSION['avatar'] : '../' . $_SESSION['avatar']) : 'https://i.pravatar.cc/150?img=11';

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
    
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['video/mp4', 'video/webm'];
        if (in_array($_FILES['video_file']['type'], $allowed)) {
            $ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            
            if (!is_dir('../uploads')) {
                mkdir('../uploads', 0777, true);
            }
            
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
                
                header("Location: profile.php");
                exit;
            } else {
                $error = "Failed to move uploaded file. Check directory permissions.";
            }
        } else {
            $error = "Invalid file type. Only MP4 and WebM are allowed.";
        }
    } else {
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_errors = array(
                UPLOAD_ERR_INI_SIZE => 'File is too large (exceeds upload_max_filesize in php.ini).',
                UPLOAD_ERR_FORM_SIZE => 'File is too large.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
            );
            $error = $upload_errors[$_FILES['video_file']['error']] ?? 'Unknown upload error.';
        } else {
            $error = "Please select a valid video file.";
        }
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
    <title>Upload - Swipe Nest</title>
    
    <link rel="stylesheet" href="../assets/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/globals.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= time() ?>">
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
                <a href="upload.php" class="sidebar-link active">
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
            <div class="main-content-inner" style="max-width: 900px; padding-top: var(--spacing-6);">
                
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
            </div> <!-- /main-content-inner -->
        </main>
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
