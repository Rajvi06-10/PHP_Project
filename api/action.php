<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'toggle_like') {
        $video_id = $_POST['video_id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $video_id]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND video_id = ?")->execute([$user_id, $video_id]);
            $status = 'unliked';
        } else {
            $pdo->prepare("INSERT INTO likes (user_id, video_id) VALUES (?, ?)")->execute([$user_id, $video_id]);
            $status = 'liked';
        }
        
        $count = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE video_id = ?");
        $count->execute([$video_id]);
        
        echo json_encode(['success' => true, 'status' => $status, 'new_count' => $count->fetchColumn()]);
        
    } elseif ($action === 'toggle_save') {
        $video_id = $_POST['video_id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT id FROM saved_reels WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $video_id]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $pdo->prepare("DELETE FROM saved_reels WHERE user_id = ? AND video_id = ?")->execute([$user_id, $video_id]);
            $status = 'unsaved';
        } else {
            $pdo->prepare("INSERT INTO saved_reels (user_id, video_id) VALUES (?, ?)")->execute([$user_id, $video_id]);
            $status = 'saved';
        }
        
        echo json_encode(['success' => true, 'status' => $status]);
        
    } elseif ($action === 'toggle_follow') {
        $following_id = $_POST['following_id'] ?? 0;
        
        if ($following_id == $user_id) {
            echo json_encode(['success' => false, 'message' => 'Cannot follow yourself']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$user_id, $following_id]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?")->execute([$user_id, $following_id]);
            $status = 'unfollowed';
        } else {
            $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)")->execute([$user_id, $following_id]);
            $status = 'followed';
        }
        
        echo json_encode(['success' => true, 'status' => $status]);
        
    } elseif ($action === 'add_comment') {
        $video_id = $_POST['video_id'] ?? 0;
        $comment_text = trim($_POST['content'] ?? '');
        
        if (empty($comment_text)) {
            echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, video_id, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $video_id, $comment_text]);
        
        $count = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE video_id = ?");
        $count->execute([$video_id]);
        
        echo json_encode(['success' => true, 'count' => $count->fetchColumn()]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
