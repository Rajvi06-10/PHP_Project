<?php
require_once '../config/db.php';

$username = $_SESSION['username'] ?? 'User';
$sessionAvatar = isset($_SESSION['avatar']) && $_SESSION['avatar'] ? (strpos($_SESSION['avatar'], 'http') === 0 ? $_SESSION['avatar'] : '../' . $_SESSION['avatar']) : 'https://i.pravatar.cc/150?img=11';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$message = '';
$error   = '';

// Fetch categories from DB
$cat_stmt  = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    csrf_validate('redirect');

    $caption        = trim($_POST['caption']   ?? '');
    $category_id    = $_POST['category_id']    ?? 1;
    $visibility     = $_POST['visibility']     ?? 'Public';
    $hashtags_input = trim($_POST['hashtags']  ?? '');

    // Whitelist visibility values
    if (!in_array($visibility, ['Public', 'Private'])) {
        $visibility = 'Public';
    }

    // Handle custom category creation (fallback for non-JS)
    if ($category_id === 'new') {
        $new_category = trim($_POST['new_category'] ?? '');
        if (!empty($new_category) && strlen($new_category) <= 100) {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$new_category]);
            $existing = $stmt->fetch();
            if ($existing) {
                $category_id = $existing['id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$new_category]);
                $category_id = $pdo->lastInsertId();
            }
        } else {
            $category_id = 1;
        }
    }

    $category_id = (int)$category_id;

    // ── Video upload ─────────────────────────────────────────────
    if (isset($_FILES['video_file'])) {
        $file = $_FILES['video_file'];

        // 1. Check for PHP upload errors first
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE   => 'File is too large (exceeds server limit).',
                UPLOAD_ERR_FORM_SIZE  => 'File is too large (exceeds form limit).',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'Please select a video file.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'A server extension stopped the upload.',
            ];
            $error = $upload_errors[$file['error']] ?? 'Unknown upload error (code ' . (int)$file['error'] . ').';

        } else {
            $maxBytes = 200 * 1024 * 1024; // 200 MB hard limit

            // 2. Check file size
            if ($file['size'] > $maxBytes) {
                $error = 'Video must be smaller than 200 MB.';
            } else {
                // 3. Detect MIME via finfo — never trust $_FILES['type']
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $realMime = $finfo->file($file['tmp_name']);

                $allowedMimes = [
                    'video/mp4'  => 'mp4',
                    'video/webm' => 'webm',
                ];

                if (!array_key_exists($realMime, $allowedMimes)) {
                    $error = 'Invalid video type. Only MP4 and WebM are allowed.';
                } else {
                    // 4. Generate a safe, random filename — never use user-supplied name
                    $ext        = $allowedMimes[$realMime];
                    $filename   = bin2hex(random_bytes(16)) . '.' . $ext;
                    $uploadDir  = '../uploads/videos/';
                    $uploadPath = $uploadDir . $filename;

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        try {
                            $stmt = $pdo->prepare(
                                "INSERT INTO videos (user_id, category_id, file_path, description, visibility)
                                 VALUES (?, ?, ?, ?, ?)"
                            );
                            $stmt->execute([
                                $_SESSION['user_id'],
                                $category_id,
                                'uploads/videos/' . $filename,
                                $caption,
                                $visibility,
                            ]);
                            $video_id = $pdo->lastInsertId();

                            // 5. Sanitise and store hashtags
                            if (!empty($hashtags_input)) {
                                $tags = array_slice(
                                    array_map('trim', explode(',', $hashtags_input)),
                                    0, 20 // max 20 tags
                                );
                                foreach ($tags as $tag) {
                                    $tag = ltrim($tag, '#');
                                    // Allow only alphanumeric and underscore, max 50 chars
                                    $tag = preg_replace('/[^a-zA-Z0-9_\-]/', '', $tag);
                                    $tag = substr($tag, 0, 50);
                                    if (!empty($tag)) {
                                        $pdo->prepare(
                                            "INSERT INTO hashtags (video_id, tag) VALUES (?, ?)"
                                        )->execute([$video_id, $tag]);
                                    }
                                }
                            }

                            header("Location: profile.php");
                            exit;

                        } catch (\PDOException $e) {
                            // Remove the uploaded file if DB insert failed
                            @unlink($uploadPath);
                            error_log('[Swipe Nest] Upload DB error: ' . $e->getMessage());
                            $error = 'Failed to save video. Please try again.';
                        }
                    } else {
                        $error = 'Failed to move uploaded file. Check directory permissions.';
                    }
                }
            }
        }
    } else {
        $error = 'Please select a valid video file.';
    }
}

$stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user     = $stmt->fetch();
$avatarSrc = $user['avatar_url'] ? (strpos($user['avatar_url'], 'http') === 0 ? $user['avatar_url'] : '../' . $user['avatar_url']) : 'https://i.pravatar.cc/150?img=11';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload - Swipe Nest</title>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

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
            .form-grid { grid-template-columns: 1fr 2fr; }
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
                <a href="home.php" class="sidebar-link"><i data-lucide="home"></i><span>Home</span></a>
                <a href="search.php" class="sidebar-link"><i data-lucide="search"></i><span>Search</span></a>
                <a href="scrolls.php" class="sidebar-link"><i data-lucide="layers"></i><span>Scrolls</span></a>
                <a href="upload.php" class="sidebar-link active"><i data-lucide="plus-square"></i><span>Create</span></a>
                <a href="profile.php" class="sidebar-link"><i data-lucide="user"></i><span>Profile</span></a>
                <a href="settings.php" class="sidebar-link"><i data-lucide="settings"></i><span>Settings</span></a>
            </nav>

            <div class="sidebar-footer">
                <a href="profile.php" class="sidebar-user">
                    <img src="<?= htmlspecialchars($sessionAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="Profile">
                    <span><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
                <a href="logout.php" class="sidebar-link" style="color: var(--color-danger); margin-top: 8px;">
                    <i data-lucide="log-out"></i><span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="main-content-inner" style="max-width: 900px; padding-top: var(--spacing-6);">

                <h1 class="h3 mb-2">Upload Video</h1>
                <p class="text-secondary mb-8">Post a video to your account</p>

                <?php if ($message): ?>
                    <div style="color:#10b981;background:rgba(16,185,129,0.1);padding:10px;border-radius:4px;margin-bottom:15px;">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div style="color:#ef4444;background:rgba(239,68,68,0.1);padding:10px;border-radius:4px;margin-bottom:15px;">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <div class="card p-6" style="padding: var(--spacing-6);">
                    <form method="POST" enctype="multipart/form-data" class="form-grid">
                        <?= csrf_field() ?>

                        <!-- Left: Upload Area -->
                        <div>
                            <div class="upload-area">
                                <input type="file" name="video_file" id="video_file" accept="video/mp4,video/webm" required
                                       style="position:absolute;width:100%;height:100%;opacity:0;cursor:pointer;left:0;top:0;z-index:10;">
                                <i data-lucide="upload-cloud" style="width:64px;height:64px;color:var(--color-primary);margin-bottom:15px;"></i>
                                <h3 class="font-semibold mb-2">Select video to upload</h3>
                                <p class="text-sm text-secondary mb-6">MP4 or WebM format · Max 200 MB</p>
                                <button type="button" class="btn btn-primary pointer-events-none">Select file</button>
                            </div>
                            <div id="file-name-display" class="mt-4 text-center text-sm text-primary"></div>
                        </div>

                        <!-- Right: Form Details -->
                        <div>
                            <div class="input-group mb-4">
                                <label class="input-label">Caption</label>
                                <textarea name="caption" class="input-field" rows="4" placeholder="Describe your video..." maxlength="2000"></textarea>
                            </div>

                            <div class="input-group mb-4">
                                <label class="input-label">Hashtags</label>
                                <input type="text" name="hashtags" class="input-field" placeholder="e.g. funny, tech, learning" maxlength="500">
                                <p class="text-xs text-tertiary mt-1">Separate with commas. Max 20 tags, letters/numbers only.</p>
                            </div>

                            <!-- Category -->
                            <div class="input-group mb-4">
                                <label class="input-label">Category</label>
                                <div style="position: relative;">
                                    <select name="category_id" id="categorySelect" class="input-field" style="appearance: none;">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i data-lucide="chevron-down" style="position:absolute;right:12px;top:12px;color:var(--color-text-secondary);width:18px;height:18px;pointer-events:none;"></i>
                                </div>

                                <!-- Add new category inline (JS-powered) -->
                                <button type="button" id="showAddCatBtn"
                                        style="margin-top:8px;background:none;border:none;color:var(--color-primary);
                                               font-size:13px;font-weight:600;cursor:pointer;display:flex;
                                               align-items:center;gap:5px;padding:0;">
                                    <i data-lucide="plus-circle" style="width:15px;height:15px;"></i>
                                    Add new category
                                </button>

                                <div id="addCatBox" style="display:none;margin-top:10px;">
                                    <div style="display:flex;gap:8px;align-items:center;">
                                        <input type="text" id="newCatInput" placeholder="e.g. Comedy, Fitness..."
                                               style="flex:1;padding:8px 12px;border-radius:var(--radius-md);
                                                      border:1px solid var(--color-border);
                                                      background:var(--color-bg-tertiary);
                                                      color:var(--color-text-primary);font-size:13px;" maxlength="100">
                                        <button type="button" id="saveCatBtn"
                                                style="padding:8px 16px;background:var(--color-primary);
                                                       color:white;border:none;border-radius:var(--radius-md);
                                                       font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">
                                            Save
                                        </button>
                                        <button type="button" id="cancelCatBtn"
                                                style="padding:8px;background:none;border:none;
                                                       color:var(--color-text-secondary);cursor:pointer;">
                                            <i data-lucide="x" style="width:16px;height:16px;"></i>
                                        </button>
                                    </div>
                                    <p id="catMsg" style="font-size:12px;margin-top:5px;display:none;"></p>
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
            </div><!-- /main-content-inner -->
        </main>
    </div>

    <script>
        document.getElementById('video_file').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Use textContent — never innerHTML — to avoid XSS from filename
                document.getElementById('file-name-display').textContent = 'Selected: ' + this.files[0].name;
            }
        });

        lucide.createIcons();

        // ── Add new category ──────────────────────────────────────
        const showBtn   = document.getElementById('showAddCatBtn');
        const addBox    = document.getElementById('addCatBox');
        const newInput  = document.getElementById('newCatInput');
        const saveBtn   = document.getElementById('saveCatBtn');
        const cancelBtn = document.getElementById('cancelCatBtn');
        const catMsg    = document.getElementById('catMsg');
        const catSelect = document.getElementById('categorySelect');

        showBtn.addEventListener('click', () => {
            addBox.style.display = 'block';
            newInput.focus();
        });

        cancelBtn.addEventListener('click', () => {
            addBox.style.display = 'none';
            newInput.value = '';
            catMsg.style.display = 'none';
        });

        saveBtn.addEventListener('click', async () => {
            const name = newInput.value.trim();
            if (!name) { showMsg('Please enter a category name.', 'red'); return; }

            saveBtn.disabled    = true;
            saveBtn.textContent = 'Saving...';

            try {
                const fd = new FormData();
                fd.append('action', 'add_category');
                fd.append('name', name);

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const res  = await fetch('../api/category.php', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-CSRF-Token': csrfToken }
                });
                const data = await res.json();

                if (data.success) {
                    // Use textContent to safely add the category name
                    const opt = document.createElement('option');
                    opt.value    = data.category.id;
                    opt.selected = true;
                    opt.textContent = data.category.name;
                    catSelect.add(opt);
                    newInput.value       = '';
                    addBox.style.display = 'none';
                    catMsg.style.display = 'none';
                } else {
                    showMsg(data.message, 'red');
                }
            } catch (e) {
                showMsg('Something went wrong. Try again.', 'red');
            }

            saveBtn.disabled    = false;
            saveBtn.textContent = 'Save';
        });

        newInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter')  { e.preventDefault(); saveBtn.click(); }
            if (e.key === 'Escape') cancelBtn.click();
        });

        function showMsg(text, color) {
            catMsg.textContent   = text;
            catMsg.style.color   = color;
            catMsg.style.display = text ? 'block' : 'none';
        }
    </script>
</body>
</html>
