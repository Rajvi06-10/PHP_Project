<?php
require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($action === 'login') {
        $stmt = $pdo->prepare("SELECT id, username, password_hash as password, avatar_url as avatar FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['avatar'] = $user['avatar'];
            header("Location: home.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } elseif ($action === 'signup') {
        $username = $_POST['username'] ?? '';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hash]);
            $success = "Account created! You can now login.";
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Username or email already exists.";
            } else {
                $error = "An error occurred during registration.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth - ZYVA</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-4);
        }
        .auth-card {
            width: 100%;
            max-width: 400px;
            padding: var(--spacing-8);
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
        }
        .auth-tabs {
            display: flex;
            margin-bottom: var(--spacing-6);
            border-bottom: 1px solid var(--color-border);
        }
        .auth-tab {
            flex: 1;
            text-align: center;
            padding: var(--spacing-3);
            cursor: pointer;
            color: var(--color-text-secondary);
            font-weight: 500;
        }
        .auth-tab.active {
            color: var(--color-primary);
            border-bottom: 2px solid var(--color-primary);
        }
        .auth-form { display: none; }
        .auth-form.active { display: block; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-6">
                <a href="../index.php" class="inline-flex items-center gap-2 mb-4">
                    <i data-lucide="zap" class="text-accent"></i>
                    <span class="font-bold text-xl">ZYVA</span>
                </a>
                <h2 class="h4">Welcome Back</h2>
            </div>
            
            <?php if ($error): ?>
                <div style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="auth-tabs">
                <div class="auth-tab active" onclick="switchTab('login')">Login</div>
                <div class="auth-tab" onclick="switchTab('signup')">Sign Up</div>
            </div>

            <form method="POST" id="login-form" class="auth-form active">
                <input type="hidden" name="action" value="login">
                <div class="input-group">
                    <label class="input-label">Email</label>
                    <input type="email" name="email" class="input-field" required>
                </div>
                <div class="input-group">
                    <label class="input-label">Password</label>
                    <input type="password" name="password" class="input-field" required>
                </div>
                <button type="submit" class="btn btn-primary w-full justify-center">Login</button>
            </form>

            <form method="POST" id="signup-form" class="auth-form">
                <input type="hidden" name="action" value="signup">
                <div class="input-group">
                    <label class="input-label">Username</label>
                    <input type="text" name="username" class="input-field" required>
                </div>
                <div class="input-group">
                    <label class="input-label">Email</label>
                    <input type="email" name="email" class="input-field" required>
                </div>
                <div class="input-group">
                    <label class="input-label">Password</label>
                    <input type="password" name="password" class="input-field" required minlength="6">
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
            if(tab === 'login') {
                document.querySelectorAll('.auth-tab')[0].classList.add('active');
                document.getElementById('login-form').classList.add('active');
            } else {
                document.querySelectorAll('.auth-tab')[1].classList.add('active');
                document.getElementById('signup-form').classList.add('active');
            }
        }
    </script>
</body>
</html>
