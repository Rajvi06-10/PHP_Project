<?php
require_once '../config/db.php';   // starts session, loads csrf helper

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check — validate on all form posts
    csrf_validate('redirect');

    $action   = $_POST['action'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($action === 'login') {
        $login_identifier = trim($_POST['username'] ?? '');

        if (empty($login_identifier) || empty($password)) {
            $error = 'Username/email and password are required.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT id, username, password_hash, avatar_url
                 FROM users
                 WHERE username = ? OR email = ?
                 LIMIT 1"
            );
            $stmt->execute([$login_identifier, $login_identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session ID after login to prevent session fixation
                session_regenerate_id(true);

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['avatar']   = $user['avatar_url'];
                header("Location: home.php");
                exit;
            } else {
                // Generic message — do not reveal which field was wrong
                $error = 'Invalid username or password.';
            }
        }

    } elseif ($action === 'signup') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');

        if (empty($username) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif (strlen($username) > 50) {
            $error = 'Username must be 50 characters or fewer.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)"
                );
                $stmt->execute([$username, $email, $hash]);
                $newUserId = $pdo->lastInsertId();

                // Regenerate session ID after login
                session_regenerate_id(true);

                $_SESSION['user_id']  = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['avatar']   = null;

                header("Location: home.php");
                exit;

            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'Username or email already exists.';
                } else {
                    error_log('[Swipe Nest] Signup error: ' . $e->getMessage());
                    $error = 'An error occurred during registration. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth - Swipe Nest</title>
    <link rel="stylesheet" href="../assets/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/globals.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= time() ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-4);
            background: linear-gradient(135deg, var(--color-bg-primary), var(--color-bg-sidebar));
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
            padding: var(--spacing-8);
            border-radius: var(--radius-xl);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .auth-tabs {
            display: flex;
            margin-bottom: var(--spacing-6);
            position: relative;
        }
        .auth-tab {
            flex: 1;
            text-align: center;
            padding: var(--spacing-3);
            cursor: pointer;
            color: var(--color-text-secondary);
            font-weight: 600;
            transition: color var(--transition-fast);
            border-bottom: 2px solid var(--color-border);
        }
        .auth-tab:hover { color: var(--color-text-primary); }
        .auth-tab.active {
            color: var(--color-primary);
            border-bottom: 2px solid var(--color-primary);
        }
        .auth-form {
            display: none;
            animation: fadeIn var(--transition-normal) forwards;
        }
        .auth-form.active { display: block; }
    </style>
</head>
<body>
    <button onclick="window.toggleTheme();" style="position: absolute; top: 20px; right: 20px; background: none; border: none; cursor: pointer; color: var(--color-text-primary);">
        <i data-lucide="moon" class="theme-icon"></i>
    </button>
    <div class="auth-container">
        <div class="auth-card card">
            <div class="text-center mb-8" style="display:flex; flex-direction:column; align-items:center; gap:var(--spacing-3);">
                <img src="../assets/images/logo.svg" alt="Swipe Nest Logo" style="width: 48px; height: 48px;">
                <h1 style="font-family: var(--font-family-heading); font-size: 2rem; font-weight: 700; margin: 0; letter-spacing: -0.02em;">Swipe Nest</h1>
                <p style="color: var(--color-text-secondary); font-size: var(--text-sm); max-width: 280px; margin: 0;">Your personalized content home. Swipe to explore.</p>
            </div>

            <?php if ($error): ?>
                <div style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center;">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center;">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="auth-tabs">
                <div class="auth-tab active" onclick="switchTab('login')">Login</div>
                <div class="auth-tab" onclick="switchTab('signup')">Sign Up</div>
            </div>

            <!-- Login Form -->
            <form method="POST" id="login-form" class="auth-form active" autocomplete="off">
                <input type="hidden" name="action" value="login">
                <?= csrf_field() ?>
                <div class="input-group">
                    <label class="input-label">Username or Email</label>
                    <input type="text" name="username" class="input-field" required autocomplete="off">
                </div>
                <div class="input-group">
                    <label class="input-label">Password</label>
                    <input type="password" name="password" class="input-field" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary w-full justify-center">Login</button>
                <div style="text-align:center; margin-top: 12px; font-size: var(--text-sm);">
                    <a href="forgot_password.php" style="color: var(--color-primary); text-decoration: none;">Forgot your password?</a>
                </div>
            </form>

            <!-- Signup Form -->
            <form method="POST" id="signup-form" class="auth-form" autocomplete="off">
                <input type="hidden" name="action" value="signup">
                <?= csrf_field() ?>
                <div class="input-group">
                    <label class="input-label">Username</label>
                    <input type="text" name="username" class="input-field" required autocomplete="off" maxlength="50">
                </div>
                <div class="input-group">
                    <label class="input-label">Email</label>
                    <input type="email" name="email" class="input-field" required autocomplete="off">
                </div>
                <div class="input-group">
                    <label class="input-label">Password</label>
                    <input type="password" name="password" class="input-field" required minlength="6" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary w-full justify-center">Create Account</button>
            </form>
        </div>
    </div>
    <script>
        lucide.createIcons();
        function switchTab(tab) {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            if (tab === 'login') {
                document.querySelectorAll('.auth-tab')[0].classList.add('active');
                document.getElementById('login-form').classList.add('active');
            } else {
                document.querySelectorAll('.auth-tab')[1].classList.add('active');
                document.getElementById('signup-form').classList.add('active');
            }
        }
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
