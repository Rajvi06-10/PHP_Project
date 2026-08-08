<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = $_POST['caption'] ?? '';
    $category = $_POST['category'] ?? 'Entertainment';
    $visibility = $_POST['visibility'] ?? 'Public';
    $hashtags = $_POST['hashtags'] ?? '';
    
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
        $allowed = ['video/mp4', 'video/webm'];
        if (in_array($_FILES['video_file']['type'], $allowed)) {
            $ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $uploadPath = '../uploads/' . $filename;
            
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadPath)) {
                $title = substr($caption, 0, 50) ?: 'Video Upload';
                $stmt = $pdo->prepare("INSERT INTO videos (user_id, file_path, description, title, visibility) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], 'uploads/' . $filename, $caption, $title, strtolower($visibility)]);
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
        }

        .upload-area:hover, .upload-area.dragover {
            border-color: var(--color-primary);
            background-color: var(--color-surface-hover);
        }

        .upload-icon {
            width: 64px;
            height: 64px;
            color: var(--color-text-tertiary);
            margin-bottom: var(--spacing-4);
            transition: color var(--transition-fast);
        }

        .upload-area:hover .upload-icon {
            color: var(--color-primary);
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

        .thumbnail-preview {
            width: 100%;
            aspect-ratio: 9/16;
            background-color: var(--color-surface);
            border-radius: var(--radius-md);
            border: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-text-tertiary);
            overflow: hidden;
            position: relative;
        }

        .thumbnail-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: var(--color-surface);
            border-radius: var(--radius-full);
            overflow: hidden;
            margin-top: var(--spacing-2);
        }

        .progress-fill {
            height: 100%;
            background-color: var(--color-primary);
            width: 45%;
            border-radius: var(--radius-full);
            transition: width var(--transition-normal);
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
                            <!-- Initial Upload State -->
                            <div class="upload-area" style="position: relative;">
                                <input type="file" name="video_file" accept="video/mp4,video/webm" required style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; left: 0; top: 0; z-index: 10;">
                                <i data-lucide="upload-cloud" class="upload-icon"></i>
                                <h3 class="font-semibold mb-2">Select video to upload</h3>
                                <p class="text-sm text-secondary mb-6">Or drag and drop a file</p>
                                <p class="text-xs text-tertiary mb-2">MP4 or WebM</p>
                                <p class="text-xs text-tertiary mb-4">720x1280 resolution or higher</p>
                                <p class="text-xs text-tertiary mb-6">Up to 10 minutes</p>
                                <button type="button" class="btn btn-primary">Select file</button>
                            </div>

                            <!-- Uploading State (Simulated) -->
                            <div class="video-preview" style="background: var(--color-surface); display: flex; align-items: center; justify-content: center; height: 100%;">
                                <i data-lucide="video" style="width: 48px; height: 48px; color: var(--color-text-secondary);"></i>
                            </div>    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50" style="background: rgba(0,0,0,0.4); inset:0; position:absolute;">
                                    <i data-lucide="play-circle" style="width: 48px; height: 48px; color: white;"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-secondary">video_preview.mp4</span>
                                    <span class="font-semibold text-primary">45%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Form Details -->
                        <div>
                            <div class="input-group">
                                <label class="input-label">Caption</label>
                                <textarea name="caption" class="input-field" rows="4" placeholder="Describe your video... (e.g. Exploring the new Apple Vision Pro interface concepts. 🥽✨)"></textarea>
                            </div>

                            <div class="input-group">
                                <label class="input-label">Hashtags</label>
                                <input type="text" name="hashtags" class="input-field" placeholder="#VisionPro, #SpatialDesign, #UIUX">
                                <p class="text-xs text-tertiary mt-1">Separate with commas or spaces.</p>
                            </div>

                            <div class="input-group">
                                <label class="input-label">Category</label>
                                <div style="position: relative;">
                                    <select name="category" class="input-field" style="appearance: none;">
                                        <option value="Technology">Technology</option>
                                        <option value="Design">Design</option>
                                        <option value="Entertainment">Entertainment</option>
                                        <option value="Education">Education</option>
                                    </select>
                                    <i data-lucide="chevron-down" style="position: absolute; right: 12px; top: 12px; color: var(--color-text-secondary); width: 18px; height: 18px; pointer-events: none;"></i>
                                </div>
                            </div>

                            <div class="input-group">
                                <label class="input-label">Visibility</label>
                                <div class="flex gap-4 mt-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="visibility" value="Public" checked>
                                        <span class="text-sm">Public</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="visibility" value="Friends">
                                        <span class="text-sm">Friends</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="visibility" value="Private">
                                        <span class="text-sm">Private</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex gap-4 mt-8">
                                <button type="reset" class="btn btn-secondary w-full justify-center">Discard</button>
                                <button type="submit" class="btn btn-primary w-full justify-center">Post Video</button>
                            </div>
                        </div>

                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
