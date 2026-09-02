<?php
/**
 * CSRF Protection Helper
 * ──────────────────────
 * Session-based CSRF tokens.
 *
 * Usage in HTML forms:
 *   <?= csrf_field() ?>
 *
 * Usage in API endpoints (reads X-CSRF-Token header OR $_POST['csrf_token']):
 *   csrf_validate();         // dies with JSON {success:false} on failure
 *   csrf_validate('redirect'); // header-redirects to auth.php on failure
 */

// ── Token generation ────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ── HTML hidden-input helper ─────────────────────────────────────
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

// ── Validation ───────────────────────────────────────────────────
/**
 * @param string $on_fail  'json'     → echo JSON error and exit  (for API)
 *                         'redirect' → redirect to auth.php      (for pages)
 */
function csrf_validate(string $on_fail = 'json'): void {
    $expected = $_SESSION['csrf_token'] ?? '';

    // Accept token from POST body OR from X-CSRF-Token request header
    $provided = $_POST['csrf_token']
        ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (!$expected || !hash_equals($expected, $provided)) {
        if ($on_fail === 'redirect') {
            header('Location: auth.php');
            exit;
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
        exit;
    }
}
