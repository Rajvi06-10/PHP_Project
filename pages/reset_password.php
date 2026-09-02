<?php
/**
 * Reset Password — validates a single-use, expiring token and sets a new password.
 */
require_once '../config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

$rawToken = trim($_GET['token'] ?? '');
$error = '';
$success = '';
$validToken = null; // holds the DB row if valid

// ── Validate the token from the URL ─────────────────────────────
if (!empty($rawToken)) {
    $tokenHash = hash('sha256', $rawToken);

    $stmt = $pdo->prepare(
        "SELECT pr.id, pr.user_id, pr.expires_at, pr.used
         FROM password_resets pr
         WHERE pr.token_hash = ?
         LIMIT 1"
    );
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch();

    if (!$row) {
        $error = 'This reset link is invalid. Please request a new one.';
    } elseif ($row['used']) {
        $error = 'This reset link has already been used. Please request a new one.';
    } elseif (new DateTime() > new DateTime($row['expires_at'])) {
        $error = 'This reset link has expired (1 hour limit). Please request a new one.';
    } else {
        $validToken = $row; // token is good
    }
} else {
    $error = 'No reset token provided.';
}

// ── Handle new password submission ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    csrf_validate('redirect');

    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update the password
            $pdo->prepare(
                "UPDATE users SET password_hash = ? WHERE id = ?"
            )->execute([$hash, $validToken['user_id']]);

            // Mark token as used (single-use)
            $pdo->prepare(
                "UPDATE password_resets SET used = 1 WHERE id = ?"
            )->execute([$validToken['id']]);

            $success = 'Password reset successfully! You can now log in with your new password.';
            $validToken = null; // don't show the form again
        } catch (\PDOException $e) {
            error_log('[Swipe Nest] Reset password error: ' . $e->getMessage());
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Swipe Nest</title>
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
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card card">
            <div class="text-center mb-8"
                style="display:flex;flex-direction:column;align-items:center;gap:var(--spacing-3);">
                <img src="../assets/images/logo.svg" alt="Swipe Nest Logo" style="width:48px;height:48px;">
                <h1
                    style="font-family:var(--font-family-heading);font-size:2rem;font-weight:700;margin:0;letter-spacing:-0.02em;">
                    Swipe Nest</h1>
            </div>

            <?php if ($success): ?>
                <div style="text-align:center;">
                    <i data-lucide="check-circle" style="width:48px;height:48px;color:#10b981;margin-bottom:12px;"></i>
                    <h2 style="font-weight:700;margin-bottom:8px;">Password Reset!</h2>
                    <p style="color:var(--color-text-secondary);font-size:var(--text-sm);margin-bottom:20px;">
                        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <a href="auth.php" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
                        <i data-lucide="log-in" style="width:16px;height:16px;"></i>
                        Go to Login
                    </a>
                </div>

            <?php elseif ($error && !$validToken): ?>
                <div style="text-align:center;">
                    <i data-lucide="alert-circle" style="width:48px;height:48px;color:#ef4444;margin-bottom:12px;"></i>
                    <h2 style="font-weight:700;margin-bottom:8px;">Link Invalid</h2>
                    <p style="color:#ef4444;font-size:var(--text-sm);margin-bottom:20px;">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <a href="forgot_password.php" class="btn btn-primary"
                        style="display:inline-flex;align-items:center;gap:6px;">
                        Request New Link
                    </a>
                </div>

            <?php elseif ($validToken): ?>
                <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:6px;">Set New Password</h2>
                <p style="color:var(--color-text-secondary);font-size:var(--text-sm);margin-bottom:24px;">
                    Enter and confirm your new password below.
                </p>

                <?php if ($error): ?>
                    <div
                        style="color:#ef4444;background:rgba(239,68,68,0.1);padding:10px;border-radius:4px;margin-bottom:15px;text-align:center;">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="reset_password.php?token=<?= urlencode($rawToken) ?>" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="input-group">
                        <label class="input-label">New Password</label>
                        <input type="password" name="new_password" class="input-field" required minlength="6"
                            placeholder="At least 6 characters">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="input-field" required minlength="6"
                            placeholder="Repeat new password">
                    </div>
                    <button type="submit" class="btn btn-primary w-full justify-center">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script>lucide.createIcons();</script>
    <script src="../assets/js/main.js"></script>
</body>

</html>