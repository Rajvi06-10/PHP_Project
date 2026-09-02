<?php
require_once '../config/db.php';

$sessionUsername = $_SESSION['username'] ?? 'User';
$sessionAvatar = isset($_SESSION['avatar']) && $_SESSION['avatar'] ? (strpos($_SESSION['avatar'], 'http') === 0 ? $_SESSION['avatar'] : '../' . $_SESSION['avatar']) : 'https://i.pravatar.cc/150?img=11';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$userId  = $_SESSION['user_id'];
$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check applies to ALL forms in settings
    csrf_validate('redirect');

    // ── Save Profile ─────────────────────────────────────────────
    if (isset($_POST['save_profile'])) {
        $username = trim($_POST['username'] ?? '');
        $bio      = trim($_POST['bio'] ?? '');

        if (empty($username)) {
            $error = 'Username cannot be empty.';
        } elseif (strlen($username) > 50) {
            $error = 'Username must be 50 characters or fewer.';
        } else {
            $avatarSql = '';
            $params    = [$username, $bio];

            if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
                $file      = $_FILES['avatar_file'];
                $maxBytes  = 5 * 1024 * 1024; // 5 MB

                // 1. Check upload error code
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'File upload error. Please try again.';
                }
                // 2. Check size
                elseif ($file['size'] > $maxBytes) {
                    $error = 'Avatar image must be smaller than 5 MB.';
                }
                else {
                    // 3. Detect actual MIME via finfo (cannot be spoofed via $_FILES['type'])
                    $finfo    = new finfo(FILEINFO_MIME_TYPE);
                    $realMime = $finfo->file($file['tmp_name']);

                    $allowedMimes = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/gif'  => 'gif',
                        'image/webp' => 'webp',
                    ];

                    if (!array_key_exists($realMime, $allowedMimes)) {
                        $error = 'Invalid image type. Allowed: JPG, PNG, GIF, WebP.';
                    } else {
                        // 4. Generate a safe, random filename — never trust user-supplied name
                        $ext      = $allowedMimes[$realMime];
                        $filename = 'avatar_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $uploadDir  = '../uploads/';
                        $uploadPath = $uploadDir . $filename;

                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                            $avatarSql = ', avatar_url = ?';
                            $params[]  = 'uploads/' . $filename;
                        } else {
                            $error = 'Failed to save the uploaded image.';
                        }
                    }
                }
            } elseif (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $error = 'File upload error code: ' . (int)$_FILES['avatar_file']['error'];
            }

            if (!$error) {
                $params[] = $userId;
                $stmt = $pdo->prepare("UPDATE users SET username = ?, bio = ? $avatarSql WHERE id = ?");
                try {
                    $stmt->execute($params);
                    $message = 'Profile updated successfully!';
                    $_SESSION['username'] = $username;
                    if ($avatarSql !== '') {
                        $_SESSION['avatar'] = 'uploads/' . $filename;
                    }
                } catch (\PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = 'Username already taken.';
                    } else {
                        error_log('[Swipe Nest] Profile update error: ' . $e->getMessage());
                        $error = 'Error updating profile. Please try again.';
                    }
                }
            }
        }

    // ── Change Password ──────────────────────────────────────────
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password']     ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Fetch current password_hash from DB
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();

        if (!$userData || !password_verify($current_password, $userData['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            try {
                $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
                    ->execute([$hash, $userId]);
                $message = 'Password changed successfully!';
            } catch (\PDOException $e) {
                error_log('[Swipe Nest] Password change error: ' . $e->getMessage());
                $error = 'Error changing password. Please try again.';
            }
        }

    // ── Remove Avatar ────────────────────────────────────────────
    } elseif (isset($_POST['remove_avatar'])) {
        try {
            $pdo->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?")
                ->execute([$userId]);
            $_SESSION['avatar'] = null;
            $message = 'Avatar removed successfully!';
        } catch (\PDOException $e) {
            error_log('[Swipe Nest] Remove avatar error: ' . $e->getMessage());
            $error = 'Error removing avatar.';
        }

    // ── Delete Account ───────────────────────────────────────────
    } elseif (isset($_POST['delete_account'])) {
        try {
            $pdo->prepare("DELETE FROM users WHERE id = ?")
                ->execute([$userId]);

            // Properly destroy the session
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
            session_destroy();
            header("Location: ../index.php");
            exit;
        } catch (\PDOException $e) {
            error_log('[Swipe Nest] Delete account error: ' . $e->getMessage());
            $error = 'Error deleting account. Please try again.';
        }
    }
}

$stmt = $pdo->prepare("SELECT username, bio, avatar_url FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$avatarSrc = $user['avatar_url'] ? (strpos($user['avatar_url'], 'http') === 0 ? $user['avatar_url'] : '../' . $user['avatar_url']) : 'https://i.pravatar.cc/150?img=11';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Swipe Nest</title>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

    <link rel="stylesheet" href="../assets/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/globals.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.css">

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/main.js" defer></script>

    <style>
        .settings-layout {
            display: flex;
            gap: var(--spacing-8);
            max-width: 1000px;
            margin: 0 auto;
            padding: var(--spacing-8) var(--spacing-6);
        }

        .settings-nav {
            width: 250px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: var(--spacing-2);
        }

        .settings-nav-item {
            padding: var(--spacing-3) var(--spacing-4);
            border-radius: var(--radius-md);
            color: var(--color-text-secondary);
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
        }

        .settings-nav-item:hover {
            background-color: var(--color-surface);
            color: var(--color-text-primary);
        }

        .settings-nav-item.active {
            background-color: var(--color-surface-hover);
            color: var(--color-text-primary);
            font-weight: 600;
        }

        .settings-content { flex: 1; }

        .settings-section {
            background-color: var(--color-bg-secondary);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            padding: var(--spacing-6);
            margin-bottom: var(--spacing-6);
        }

        .settings-section-title {
            font-size: var(--text-lg);
            font-weight: 600;
            margin-bottom: var(--spacing-1);
        }

        .settings-section-desc {
            color: var(--color-text-secondary);
            font-size: var(--text-sm);
            margin-bottom: var(--spacing-6);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .toggle-switch input { opacity: 0; width: 0; height: 0; }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: var(--color-surface-hover);
            transition: .4s;
            border-radius: 34px;
            border: 1px solid var(--color-border);
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .2s;
            border-radius: 50%;
        }

        input:checked + .slider { background-color: var(--color-primary); border-color: var(--color-primary); }
        input:checked + .slider:before { transform: translateX(20px); }

        .setting-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-4) 0;
            border-bottom: 1px solid var(--color-border);
        }

        .setting-row:last-child { border-bottom: none; padding-bottom: 0; }

        @media (max-width: 768px) {
            .settings-layout { flex-direction: column; }
            .settings-nav { width: 100%; flex-direction: row; overflow-x: auto; padding-bottom: var(--spacing-2); }
            .settings-nav-item { white-space: nowrap; }
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
                <a href="upload.php" class="sidebar-link"><i data-lucide="plus-square"></i><span>Create</span></a>
                <a href="profile.php" class="sidebar-link"><i data-lucide="user"></i><span>Profile</span></a>
                <a href="settings.php" class="sidebar-link active"><i data-lucide="settings"></i><span>Settings</span></a>
            </nav>

            <div class="sidebar-footer">
                <a href="profile.php" class="sidebar-user">
                    <img src="<?= htmlspecialchars($sessionAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="Profile">
                    <span><?= htmlspecialchars($sessionUsername, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
                <a href="logout.php" class="sidebar-link" style="color: var(--color-danger); margin-top: 8px;">
                    <i data-lucide="log-out"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="main-content-inner" style="max-width: 1000px;">

            <div class="settings-layout">
                <!-- Settings Navigation -->
                <aside class="settings-nav">
                    <h1 class="h3 mb-6" style="padding-left: 16px;">Settings</h1>
                    <div class="settings-nav-item active" data-target="profile"><i data-lucide="user"></i>Profile</div>
                    <div class="settings-nav-item" data-target="password"><i data-lucide="key"></i>Password</div>
                    <div class="settings-nav-item" data-target="appearance"><i data-lucide="palette"></i>Appearance</div>
                    <div class="settings-nav-item" data-target="privacy"><i data-lucide="lock"></i>Privacy &amp; Safety</div>
                    <div class="settings-nav-item" data-target="notifications"><i data-lucide="bell"></i>Notifications</div>
                    <div class="settings-nav-item" data-target="language"><i data-lucide="globe"></i>Language</div>
                </aside>

                <!-- Settings Content -->
                <main class="settings-content">

                    <!-- Profile Section -->
                    <div id="section-profile" class="settings-panel">
                    <section class="settings-section">
                        <h2 class="settings-section-title">Edit Profile</h2>
                        <p class="settings-section-desc">Manage your public profile information.</p>

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

                        <form method="POST" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="flex items-center gap-6 mb-8">
                                <img src="<?= htmlspecialchars($avatarSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="avatar avatar-xl">
                                <div class="flex flex-col gap-2">
                                    <label class="btn btn-secondary cursor-pointer" style="text-align: center;">
                                        Change Avatar
                                        <input type="file" name="avatar_file" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                                    </label>
                                    <button type="submit" name="remove_avatar" value="1" class="btn btn-ghost text-danger">Remove</button>
                                </div>
                            </div>

                            <div class="input-group">
                                <label class="input-label">Username</label>
                                <input type="text" name="username" class="input-field" value="<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>" required maxlength="50">
                                <p class="text-xs text-tertiary mt-1">www.swipenest.com/@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>

                            <div class="input-group">
                                <label class="input-label">Bio</label>
                                <textarea name="bio" class="input-field" rows="3"><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="submit" name="save_profile" value="1" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </section>

                    <!-- Danger Zone -->
                    <section class="settings-section" style="border-color: rgba(239, 68, 68, 0.3);">
                        <h2 class="settings-section-title text-danger">Danger Zone</h2>
                        <p class="settings-section-desc">Irreversible account actions.</p>

                        <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete your account? This action cannot be undone.');">
                            <?= csrf_field() ?>
                            <div class="setting-row">
                                <div>
                                    <div class="font-medium text-danger">Delete Account</div>
                                    <div class="text-sm text-secondary">Permanently delete your account and all data.</div>
                                </div>
                                <button type="submit" name="delete_account" value="1" class="btn" style="background-color: var(--color-surface); color: var(--color-danger); border: 1px solid var(--color-danger);">Delete Account</button>
                            </div>
                        </form>
                    </section>
                    </div>

                    <!-- Password Section -->
                    <div id="section-password" class="settings-panel" style="display: none;">
                        <section class="settings-section">
                            <h2 class="settings-section-title">Change Password</h2>
                            <p class="settings-section-desc">Update your password to keep your account secure.</p>

                            <?php if ($message && isset($_POST['change_password'])): ?>
                                <div style="color:#10b981;background:rgba(16,185,129,0.1);padding:10px;border-radius:4px;margin-bottom:15px;">
                                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($error && isset($_POST['change_password'])): ?>
                                <div style="color:#ef4444;background:rgba(239,68,68,0.1);padding:10px;border-radius:4px;margin-bottom:15px;">
                                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <?= csrf_field() ?>
                                <div class="input-group">
                                    <label class="input-label">Current Password</label>
                                    <div style="position:relative;">
                                        <input type="password" name="current_password" class="input-field" required placeholder="Enter your current password" style="padding-right:42px;">
                                        <button type="button" onclick="togglePwd(this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);display:flex;align-items:center;padding:0;"><i data-lucide="eye"></i></button>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label class="input-label">New Password</label>
                                    <div style="position:relative;">
                                        <input type="password" name="new_password" class="input-field" required placeholder="At least 6 characters" minlength="6" style="padding-right:42px;">
                                        <button type="button" onclick="togglePwd(this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);display:flex;align-items:center;padding:0;"><i data-lucide="eye"></i></button>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Confirm New Password</label>
                                    <div style="position:relative;">
                                        <input type="password" name="confirm_password" class="input-field" required placeholder="Repeat new password" style="padding-right:42px;">
                                        <button type="button" onclick="togglePwd(this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);display:flex;align-items:center;padding:0;"><i data-lucide="eye"></i></button>
                                    </div>
                                </div>
                                <div class="flex justify-end mt-6">
                                    <button type="submit" name="change_password" value="1" class="btn btn-primary">Update Password</button>
                                </div>
                            </form>
                        </section>
                    </div>

                    <!-- Appearance Section -->
                    <div id="section-appearance" class="settings-panel" style="display: none;">
                        <section class="settings-section">
                            <h2 class="settings-section-title">Appearance</h2>
                            <p class="settings-section-desc">Customize how Swipe Nest looks on your device.</p>

                            <div class="setting-row">
                                <div>
                                    <div class="font-medium">Dark Mode</div>
                                    <div class="text-sm text-secondary">Switch between light and dark themes.</div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked onchange="toggleTheme()">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="setting-row">
                                <div>
                                    <div class="font-medium">Reduce Motion</div>
                                    <div class="text-sm text-secondary">Disable most animations across the app.</div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" onchange="document.body.classList.toggle('reduce-motion')">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </section>
                    </div>

                    <!-- Privacy Section -->
                    <div id="section-privacy" class="settings-panel" style="display: none;">
                        <section class="settings-section">
                            <h2 class="settings-section-title">Privacy &amp; Safety</h2>
                            <p class="settings-section-desc">Manage who can see your content.</p>
                            <div class="setting-row">
                                <div>
                                    <div class="font-medium">Private Account</div>
                                    <div class="text-sm text-secondary">Only approved followers can see your videos.</div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </section>
                    </div>

                    <!-- Notifications Section -->
                    <div id="section-notifications" class="settings-panel" style="display: none;">
                        <section class="settings-section">
                            <h2 class="settings-section-title">Notifications</h2>
                            <p class="settings-section-desc">Choose what we notify you about.</p>
                            <div class="setting-row">
                                <div>
                                    <div class="font-medium">Push Notifications</div>
                                    <div class="text-sm text-secondary">Receive alerts for likes and comments.</div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </section>
                    </div>

                    <!-- Language Section -->
                    <div id="section-language" class="settings-panel" style="display: none;">
                        <section class="settings-section">
                            <h2 class="settings-section-title">Language</h2>
                            <p class="settings-section-desc">Select your preferred language.</p>
                            <div class="input-group">
                                <select class="input-field" style="background-color: var(--color-surface); padding: 10px; border-radius: 8px;">
                                    <option value="en">English (US)</option>
                                    <option value="es">Español</option>
                                    <option value="fr">Français</option>
                                    <option value="hi">हिन्दी (Hindi)</option>
                                </select>
                            </div>
                        </section>
                    </div>

                    <script>
                        // Auto-open password panel if submitted
                        <?php if (isset($_POST['change_password'])): ?>
                        document.addEventListener('DOMContentLoaded', () => {
                            document.querySelectorAll('.settings-nav-item').forEach(n => n.classList.remove('active'));
                            document.querySelectorAll('.settings-panel').forEach(p => p.style.display = 'none');
                            document.querySelector('[data-target="password"]').classList.add('active');
                            document.getElementById('section-password').style.display = 'block';
                        });
                        <?php endif; ?>

                        document.querySelectorAll('.settings-nav-item').forEach(item => {
                            item.addEventListener('click', () => {
                                document.querySelectorAll('.settings-nav-item').forEach(n => n.classList.remove('active'));
                                item.classList.add('active');
                                document.querySelectorAll('.settings-panel').forEach(p => p.style.display = 'none');
                                const target = item.getAttribute('data-target');
                                const targetPanel = document.getElementById('section-' + target);
                                if (targetPanel) targetPanel.style.display = 'block';
                            });
                        });

                        function togglePwd(btn) {
                            const input = btn.previousElementSibling;
                            const icon  = btn.querySelector('i');
                            if (input.type === 'password') {
                                input.type = 'text';
                                icon.setAttribute('data-lucide', 'eye-off');
                            } else {
                                input.type = 'password';
                                icon.setAttribute('data-lucide', 'eye');
                            }
                            lucide.createIcons({ nodes: [icon] });
                        }
                    </script>
                </main>
            </div> <!-- /settings-layout -->
            </div> <!-- /main-content-inner -->
        </main>
    </div>
</body>
</html>
