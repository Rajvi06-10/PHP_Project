<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

// CSRF validation — token sent via X-CSRF-Token header by JavaScript
csrf_validate('json');

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

try {
    // ── Toggle Like ──────────────────────────────────────────────
    if ($action === 'toggle_like') {
        $video_id = (int)($_POST['video_id'] ?? 0);
        if ($video_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid video ID.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $video_id]);

        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND video_id = ?")->execute([$user_id, $video_id]);
            $status = 'unliked';
        } else {
            $pdo->prepare("INSERT INTO likes (user_id, video_id) VALUES (?, ?)")->execute([$user_id, $video_id]);
            $status = 'liked';
        }

        $count = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE video_id = ?");
        $count->execute([$video_id]);

        echo json_encode(['success' => true, 'status' => $status, 'new_count' => (int)$count->fetchColumn()]);

    // ── Toggle Save ──────────────────────────────────────────────
    } elseif ($action === 'toggle_save') {
        $video_id = (int)($_POST['video_id'] ?? 0);
        if ($video_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid video ID.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM saved_reels WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $video_id]);

        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM saved_reels WHERE user_id = ? AND video_id = ?")->execute([$user_id, $video_id]);
            $status = 'unsaved';
        } else {
            $pdo->prepare("INSERT INTO saved_reels (user_id, video_id) VALUES (?, ?)")->execute([$user_id, $video_id]);
            $status = 'saved';
        }

        echo json_encode(['success' => true, 'status' => $status]);

    // ── Toggle Follow ────────────────────────────────────────────
    } elseif ($action === 'toggle_follow') {
        $following_id = (int)($_POST['following_id'] ?? 0);

        if ($following_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
            exit;
        }
        if ($following_id === $user_id) {
            echo json_encode(['success' => false, 'message' => 'Cannot follow yourself.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$user_id, $following_id]);

        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?")->execute([$user_id, $following_id]);
            echo json_encode(['success' => true, 'status' => 'unfollowed']);
        } else {
            $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)")->execute([$user_id, $following_id]);
            echo json_encode(['success' => true, 'status' => 'followed']);
        }

    // ── Add Comment ──────────────────────────────────────────────
    } elseif ($action === 'add_comment') {
        $video_id     = (int)($_POST['video_id'] ?? 0);
        $comment_text = trim($_POST['content'] ?? '');

        if ($video_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid video ID.']);
            exit;
        }
        if (empty($comment_text)) {
            echo json_encode(['success' => false, 'message' => 'Comment cannot be empty.']);
            exit;
        }
        if (strlen($comment_text) > 1000) {
            echo json_encode(['success' => false, 'message' => 'Comment is too long (max 1000 characters).']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO comments (user_id, video_id, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $video_id, $comment_text]);

        $count = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE video_id = ?");
        $count->execute([$video_id]);

        echo json_encode(['success' => true, 'count' => (int)$count->fetchColumn()]);

    // ── Delete Video ─────────────────────────────────────────────
    } elseif ($action === 'delete_video') {
        $video_id = (int)($_POST['video_id'] ?? 0);
        if ($video_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid video ID.']);
            exit;
        }

        // Verify ownership — user can only delete their own videos
        $stmt = $pdo->prepare("SELECT file_path FROM videos WHERE id = ? AND user_id = ?");
        $stmt->execute([$video_id, $user_id]);
        $video = $stmt->fetch();

        if ($video) {
            // Delete physical file (only if it's in uploads/videos/ — never allow path traversal)
            $filePath = '../' . $video['file_path'];
            if (
                file_exists($filePath) &&
                strpos(realpath($filePath), realpath('../uploads/videos/')) === 0
            ) {
                unlink($filePath);
            }

            // Delete dependent rows (CASCADE handles them if set up, but also try manually)
            foreach (['reports', 'hashtags', 'comments', 'likes', 'saved_reels'] as $table) {
                try {
                    $pdo->prepare("DELETE FROM `$table` WHERE video_id = ?")->execute([$video_id]);
                } catch (\Exception $e) { /* table may not exist */ }
            }

            $pdo->prepare("DELETE FROM videos WHERE id = ?")->execute([$video_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not authorized or video not found.']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }

} catch (\PDOException $e) {
    error_log('[Swipe Nest] action.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again.']);
}
