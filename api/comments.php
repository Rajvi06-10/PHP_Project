<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$video_id = $_GET['video_id'] ?? 0;

if (!$video_id) {
    echo json_encode(['success' => false, 'message' => 'Missing video_id']);
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
    
    // Format avatar URLs
    foreach ($comments as &$comment) {
        $comment['avatar_url'] = $comment['avatar_url'] ? (strpos($comment['avatar_url'], 'http') === 0 ? $comment['avatar_url'] : '../' . $comment['avatar_url']) : 'https://i.pravatar.cc/150?img=11';
        $comment['created_at'] = date('M j', strtotime($comment['created_at']));
    }
    
    echo json_encode(['success' => true, 'comments' => $comments]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
