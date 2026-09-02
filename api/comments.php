<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$video_id = (int)($_GET['video_id'] ?? 0);

if ($video_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid video_id.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.comment_text, c.created_at, u.username, u.avatar_url
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.video_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$video_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Escape all user-generated content before sending to client
    foreach ($comments as &$comment) {
        $comment['username']     = htmlspecialchars($comment['username'],     ENT_QUOTES, 'UTF-8');
        $comment['comment_text'] = htmlspecialchars($comment['comment_text'], ENT_QUOTES, 'UTF-8');
        $comment['avatar_url']   = $comment['avatar_url']
            ? (strpos($comment['avatar_url'], 'http') === 0
                ? $comment['avatar_url']
                : '../' . $comment['avatar_url'])
            : 'https://i.pravatar.cc/150?img=11';
        $comment['created_at']   = htmlspecialchars(date('M j', strtotime($comment['created_at'])), ENT_QUOTES, 'UTF-8');
    }
    unset($comment); // clear reference

    echo json_encode(['success' => true, 'comments' => $comments]);

} catch (\PDOException $e) {
    error_log('[Swipe Nest] comments.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}
