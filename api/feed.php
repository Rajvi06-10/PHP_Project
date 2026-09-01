<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$current_user_id = $_SESSION['user_id'] ?? null;

try {
    // Fetch all categories
    $cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY id ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);


    
    $feed = [];
    
    // --- 'All' Category ---
    $vid_stmt = $pdo->prepare("
        SELECT v.id, v.file_path, v.description, v.views, v.created_at,
               u.username, u.avatar_url,
               (SELECT COUNT(*) FROM likes WHERE video_id = v.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE video_id = v.id) as comment_count,
               (SELECT COUNT(*) FROM saved_reels WHERE video_id = v.id) as save_count,
               (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = ?) as is_liked,
               (SELECT COUNT(*) FROM saved_reels WHERE video_id = v.id AND user_id = ?) as is_saved,
               (SELECT COUNT(*) FROM follows WHERE following_id = v.user_id AND follower_id = ?) as is_following
        FROM videos v
        JOIN users u ON v.user_id = u.id
        WHERE v.visibility = 'Public' OR v.user_id = ?
        ORDER BY RAND() -- Mix all videos up for a better 'All' feed
    ");
    $vid_stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id]);
    $all_videos = $vid_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_videos as &$video) {
        $tag_stmt = $pdo->prepare("SELECT tag FROM hashtags WHERE video_id = ?");
        $tag_stmt->execute([$video['id']]);
        $video['hashtags'] = $tag_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $video['is_liked'] = (bool)$video['is_liked'];
        $video['is_saved'] = (bool)$video['is_saved'];
        $video['is_following'] = (bool)$video['is_following'];
    }
    
    $feed[] = [
        'category_id' => 'all',
        'category_name' => 'All',
        'videos' => $all_videos
    ];

    foreach ($categories as $cat) {
        // Fetch videos for this category
        $vid_stmt = $pdo->prepare("
            SELECT v.id, v.file_path, v.description, v.views, v.created_at,
                   u.username, u.avatar_url,
                   (SELECT COUNT(*) FROM likes WHERE video_id = v.id) as like_count,
                   (SELECT COUNT(*) FROM comments WHERE video_id = v.id) as comment_count,
                   (SELECT COUNT(*) FROM saved_reels WHERE video_id = v.id) as save_count,
                   (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = ?) as is_liked,
                   (SELECT COUNT(*) FROM saved_reels WHERE video_id = v.id AND user_id = ?) as is_saved,
                   (SELECT COUNT(*) FROM follows WHERE following_id = v.user_id AND follower_id = ?) as is_following
            FROM videos v
            JOIN users u ON v.user_id = u.id
            WHERE v.category_id = ? AND (v.visibility = 'Public' OR v.user_id = ?)
            ORDER BY v.created_at DESC
        ");
        
        $vid_stmt->execute([$current_user_id, $current_user_id, $current_user_id, $cat['id'], $current_user_id]);
        $videos = $vid_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Only include the category if it has at least one video
        if (count($videos) > 0) {
            // Fetch hashtags for each video
            foreach ($videos as &$video) {
                $tag_stmt = $pdo->prepare("SELECT tag FROM hashtags WHERE video_id = ?");
                $tag_stmt->execute([$video['id']]);
                $video['hashtags'] = $tag_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Format booleans
                $video['is_liked'] = (bool)$video['is_liked'];
                $video['is_saved'] = (bool)$video['is_saved'];
                $video['is_following'] = (bool)$video['is_following'];
            }
            
            $feed[] = [
                'category_id' => $cat['id'],
                'category_name' => $cat['name'],
                'videos' => $videos
            ];
        }
    }
    
    echo json_encode(['success' => true, 'feed' => $feed]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
