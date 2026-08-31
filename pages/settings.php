<?php
session_start();
require_once '../config/db.php';

$sessionUsername = $_SESSION['username'] ?? 'User';
$sessionAvatar = isset($_SESSION['avatar']) && $_SESSION['avatar'] ? (strpos($_SESSION['avatar'], 'http') === 0 ? $_SESSION['avatar'] : '../' . $_SESSION['avatar']) : 'https://i.pravatar.cc/150?img=11';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_profile'])) {
        $username = $_POST['username'] ?? '';
        $bio = $_POST['bio'] ?? '';
        
        $avatarSql = "";
        $params = [$username, $bio];
        
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === 0) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($_FILES['avatar_file']['type'], $allowed)) {
                $ext = pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $userId . '_' . uniqid() . '.' . $ext;
                $uploadPath = '../uploads/' . $filename;
                
                if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $uploadPath)) {
                    $avatarSql = ", avatar_url = ?";
                    $params[] = 'uploads/' . $filename;
                }
            } else {
                $error = "Invalid image type for avatar.";
            }
        }
        
        if (!$error) {
            $params[] = $userId;
            $stmt = $pdo->prepare("UPDATE users SET username = ?, bio = ? $avatarSql WHERE id = ?");
            try {
                $stmt->execute($params);
                $message = "Profile updated successfully!";
                $_SESSION['username'] = $username;
                if ($avatarSql !== "") {
                    $_SESSION['avatar'] = 'uploads/' . $filename;
                }
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Username already taken.";
                } else {
                    $error = "Error updating profile.";
                }
            }
        }
    } elseif (isset($_POST['remove_avatar'])) {
        $stmt = $pdo->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['avatar'] = null;
        $message = "Avatar removed successfully!";
    } elseif (isset($_POST['delete_account'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        session_destroy();
        header("Location: ../index.php");
        exit;
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

        .settings-content {
            flex: 1;
        }

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

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
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

        input:checked + .slider {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }

        input:checked + .slider:before {
            transform: translateX(20px);
        }

        .setting-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-4) 0;
            border-bottom: 1px solid var(--color-border);
        }

        .setting-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        @media (max-width: 768px) {
            .settings-layout {
                flex-direction: column;
            }
            .settings-nav {
                width: 100%;
                flex-direction: row;
                overflow-x: auto;
                padding-bottom: var(--spacing-2);
            }
            .settings-nav-item {
                white-space: nowrap;
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
                <a href="upload.php" class="sidebar-link">
                    <i data-lucide="plus-square"></i>
                    <span>Create</span>
                </a>
                <a href="profile.php" class="sidebar-link">
                    <i data-lucide="user"></i>
                    <span>Profile</span>
                </a>
                <a href="settings.php" class="sidebar-link active">
                    <i data-lucide="settings"></i>
                    <span>Settings</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="profile.php" class="sidebar-user">
                    <img src="<?= htmlspecialchars($sessionAvatar) ?>" alt="Profile">
                    <span><?= htmlspecialchars($sessionUsername) ?></span>
                </a>
                <a href="logout.php" class="sidebar-link" style="color: var(--color-danger); margin-top: 8px;">
                    <i data-lucide="log-out"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="main-content-inner" style="max-width: 1000px; display: flex; flex-direction: column;">
            
            <!-- Settings Navigation -->
            <aside class="settings-nav">
                <div class="settings-nav-item active" data-target="profile">
                    <i data-lucide="user"></i>
                    Profile
                </div>
                <div class="settings-nav-item" data-target="appearance">
                    <i data-lucide="palette"></i>
                    Appearance
                </div>
                <div class="settings-nav-item" data-target="privacy">
                    <i data-lucide="lock"></i>
                    Privacy & Safety
                </div>
                <div class="settings-nav-item" data-target="notifications">
                    <i data-lucide="bell"></i>
                    Notifications
                </div>
                <div class="settings-nav-item" data-target="language">
                    <i data-lucide="globe"></i>
                    Language
                </div>
            </aside>

            <!-- Settings Content -->
            <main class="settings-content">
                <h1 class="h3 mb-6">Settings</h1>

                <!-- Profile Section -->
                <div id="section-profile" class="settings-panel">
                    <section class="settings-section">
                        <h2 class="settings-section-title">Edit Profile</h2>
                        <p class="settings-section-desc">Manage your public profile information.</p>
                        
                        <?php if($message): ?><div style="color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?= $message ?></div><?php endif; ?>
                        <?php if($error): ?><div style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?= $error ?></div><?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="flex items-center gap-6 mb-8">
                                <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Avatar" class="avatar avatar-xl">
                                <div class="flex flex-col gap-2">
                                    <label class="btn btn-secondary cursor-pointer" style="text-align: center;">
                                        Change Avatar
                                        <input type="file" name="avatar_file" accept="image/*" style="display: none;">
                                    </label>
                                    <button type="submit" name="remove_avatar" value="1" class="btn btn-ghost text-danger">Remove</button>
                                </div>
                            </div>

                            <div class="input-group">
                                <label class="input-label">Username</label>
                                <input type="text" name="username" class="input-field" value="<?= htmlspecialchars($user['username']) ?>" required>
                                <p class="text-xs text-tertiary mt-1">www.swipenest.com/@<?= htmlspecialchars($user['username']) ?></p>
                            </div>

                            <div class="input-group">
                                <label class="input-label">Bio</label>
                                <textarea name="bio" class="input-field" rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="submit" name="save_profile" value="1" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </section>

                    <!-- Account Management -->
                    <section class="settings-section" style="border-color: rgba(239, 68, 68, 0.3);">
                        <h2 class="settings-section-title text-danger">Danger Zone</h2>
                        <p class="settings-section-desc">Irreversible account actions.</p>
                        
                        <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete your account? This action cannot be undone.');">
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
                        <h2 class="settings-section-title">Privacy & Safety</h2>
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
                    document.querySelectorAll('.settings-nav-item').forEach(item => {
                        item.addEventListener('click', () => {
                            // Update active nav
                            document.querySelectorAll('.settings-nav-item').forEach(n => n.classList.remove('active'));
                            item.classList.add('active');
                            
                            // Hide all panels
                            document.querySelectorAll('.settings-panel').forEach(p => p.style.display = 'none');
                            
                            // Show selected panel
                            const target = item.getAttribute('data-target');
                            const targetPanel = document.getElementById('section-' + target);
                            if(targetPanel) {
                                targetPanel.style.display = 'block';
                            }
                        });
                    });
                </script>

            </div> <!-- /main-content-inner -->
        </main>
    </div>
</body>
</html>
