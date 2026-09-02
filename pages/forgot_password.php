<?php
/**
 * Forgot Password — generates a secure single-use token.
 *
 * No email service is used. The reset link is shown on-screen after
 * submission, which is appropriate for a local/college-project deployment.
 * Before going to production, replace the on-screen display with an email.
 */
require_once '../config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

$error        = '';
$resetLink    = '';
$submitted    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate('redirect');

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');

    if (empty($username) || empty($email)) {
        $error = 'Both username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Look up the user — both fields must match
        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE username = ? AND email = ? LIMIT 1"
        );
        $stmt->execute([$username, $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate a cryptographically secure token
            $rawToken  = bin2hex(random_bytes(32));        // 64-char hex string
            $tokenHash = hash('sha256', $rawToken);        // store only the hash
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // expires in 1 hour

            // Invalidate any existing unused tokens for this user
            $pdo->prepare(
                "UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0"
            )->execute([$user['id']]);

            // Store the hashed token
            $pdo->prepare(
                "INSERT INTO password_resets (user_id, token_hash, expires_at, used)
                 VALUES (?, ?, ?, 0)"
            )->execute([$user['id'], $tokenHash, $expiresAt]);

            // Build the reset link (uses the RAW token — never stored raw in DB)
            $base      = rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                         . '://' . $_SERVER['HTTP_HOST']
                         . dirname(dirname($_SERVER['PHP_SELF'])), '/');
            $resetLink = $base . '/pages/reset_password.php?token=' . urlencode($rawToken);
            $submitted = true;

        } else {
            // Do NOT reveal whether the user exists — generic message
            $submitted = true; // show the same "check link" screen
            $resetLink = ''; // no link — user not found
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Swipe Nest</title>
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
        .reset-link-box {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: var(--radius-md);
            padding: var(--spacing-4);
            margin-top: var(--spacing-4);
            word-break: break-all;
            font-size: var(--text-sm);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card card">
            <div class="text-center mb-8" style="display:flex;flex-direction:column;align-items:center;gap:var(--spacing-3);">
                <img src="../assets/images/logo.svg" alt="Swipe Nest Logo" style="width:48px;height:48px;">
                <h1 style="font-family:var(--font-family-heading);font-size:2rem;font-weight:700;margin:0;letter-spacing:-0.02em;">Swipe Nest</h1>
            </div>

            <?php if ($submitted): ?>
                <div style="text-align:center;">
                    <i data-lucide="mail-check" style="width:48px;height:48px;color:var(--color-primary);margin-bottom:12px;"></i>
                    <h2 style="font-weight:700;margin-bottom:8px;">Reset Link Generated</h2>

                    <?php if ($resetLink): ?>
                        <p style="color:var(--color-text-secondary);font-size:var(--text-sm);margin-bottom:16px;">
                            Copy the link below and open it in your browser to reset your password.
                            It expires in <strong>1 hour</strong> and can only be used once.
                        </p>
                        <div class="reset-link-box">
                            <a href="<?= htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') ?>" style="color:var(--color-primary);">
                                <?= htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                        <p style="color:#ef4444;font-size:12px;margin-top:10px;">
                            ⚠️ In production, send this via email instead of displaying it here.
                        </p>
                    <?php else: ?>
                        <p style="color:var(--color-text-secondary);font-size:var(--text-sm);">
                            If an account with that username and email exists, a reset link has been generated.
                        </p>
                    <?php endif; ?>

                    <a href="auth.php" class="btn btn-secondary" style="margin-top:20px;display:inline-flex;align-items:center;gap:6px;">
                        <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                        Back to Login
                    </a>
                </div>

            <?php else: ?>
                <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:6px;">Forgot Password</h2>
                <p style="color:var(--color-text-secondary);font-size:var(--text-sm);margin-bottom:24px;">
                    Enter your username and email to generate a reset link.
                </p>

                <?php if ($error): ?>
                    <div style="color:#ef4444;background:rgba(239,68,68,0.1);padding:10px;border-radius:4px;margin-bottom:15px;text-align:center;">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="input-group">
                        <label class="input-label">Username</label>
                        <input type="text" name="username" class="input-field" required autocomplete="off">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Email Address</label>
                        <input type="email" name="email" class="input-field" required autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary w-full justify-center">Generate Reset Link</button>
                </form>

                <div style="text-align:center;margin-top:16px;font-size:var(--text-sm);">
                    <a href="auth.php" style="color:var(--color-primary);text-decoration:none;">Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>lucide.createIcons();</script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
