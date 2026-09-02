<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$current_user_id = $_SESSION['user_id'] ?? null;

try {
    // Fetch all categories
    $cat_stmt   = $pdo->query("SELECT id, name FROM categories ORDER BY id ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sync videos from the video folder dynamically to the database
    $videoDir = '../video/';
    if (is_dir($videoDir)) {
        $items = array_diff(scandir($videoDir), ['.', '..']);

        $userStmt = $pdo->query("SELECT id FROM users LIMIT 1");
        $user     = $userStmt->fetch();
        $uId      = $user ? $user['id'] : 1;

        foreach ($items as $item) {
            $itemPath = $videoDir . $item;

            if (is_dir($itemPath)) {
                $catName = trim($item);

                $checkCat = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
                $checkCat->execute([$catName]);
                $catData = $checkCat->fetch();

                if ($catData) {
                    $catId = $catData['id'];
                } else {
                    $insCat = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                    $insCat->execute([$catName]);
                    $catId = $pdo->lastInsertId();
                }

                $subFiles = array_diff(scandir($itemPath), ['.', '..']);
                foreach ($subFiles as $f) {
                    if (is_file($itemPath . '/' . $f) && preg_match('/\.(mp4|webm|ogg)$/i', $f)) {
                        $filePath = 'video/' . $item . '/' . $f;
                        $stmt     = $pdo->prepare("SELECT id FROM videos WHERE file_path = ?");
                        $stmt->execute([$filePath]);
                        if (!$stmt->fetch()) {
                            $insertStmt = $pdo->prepare(
                                "INSERT INTO videos (user_id, category_id, file_path, description, visibility, views)
                                 VALUES (?, ?, ?, ?, 'Public', 0)"
                            );
                            $insertStmt->execute([$uId, $catId, $filePath, $catName . ' Reel']);
                        }
                    }
                }
            } elseif (is_file($itemPath) && preg_match('/\.(mp4|webm|ogg)$/i', $item)) {
                $filePath = 'video/' . $item;
                $stmt     = $pdo->prepare("SELECT id FROM videos WHERE file_path = ?");
                $stmt->execute([$filePath]);
                if (!$stmt->fetch()) {
                    $defaultCat = !empty($categories) ? $categories[0]['id'] : 1;
                    $insertStmt = $pdo->prepare(
                        "INSERT INTO videos (user_id, category_id, file_path, description, visibility, views)
                         VALUES (?, ?, ?, ?, 'Public', 0)"
                    );
                    $insertStmt->execute([$uId, $defaultCat, $filePath, 'New Reel']);
                }
            }
        }

        // Re-fetch categories in case new ones were added
        $cat_stmt   = $pdo->query("SELECT id, name FROM categories ORDER BY id ASC");
        $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Helper: fetch + enrich videos ────────────────────────────
    function fetchVideos(PDO $pdo, $current_user_id, string $whereClause, array $params, bool $randomOrder = false): array {
        $order = $randomOrder ? 'ORDER BY RAND()' : 'ORDER BY v.created_at DESC';
        $stmt  = $pdo->prepare("
            SELECT v.id, v.file_path, v.description, v.views, v.created_at,
                   u.username, u.avatar_url,
                   (SELECT COUNT(*) FROM likes       WHERE video_id = v.id)                         AS like_count,
                   (SELECT COUNT(*) FROM comments    WHERE video_id = v.id)                         AS comment_count,
                   (SELECT COUNT(*) FROM saved_reels WHERE video_id = v.id)                         AS save_count,
                   (SELECT COUNT(*) FROM likes       WHERE video_id = v.id AND user_id = ?)         AS is_liked,
                   (SELECT COUNT(*) FROM saved_reels WHERE video_id = v.id AND user_id = ?)         AS is_saved,
                   (SELECT COUNT(*) FROM follows     WHERE following_id = v.user_id AND follower_id = ?) AS is_following
            FROM videos v
            JOIN users u ON v.user_id = u.id
            $whereClause
            $order
        ");
        $stmt->execute(array_merge([$current_user_id, $current_user_id, $current_user_id], $params));
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($videos as &$video) {
            $tag_stmt = $pdo->prepare("SELECT tag FROM hashtags WHERE video_id = ?");
            $tag_stmt->execute([$video['id']]);
            $tags = $tag_stmt->fetchAll(PDO::FETCH_COLUMN);

            // Escape all user-generated fields before sending to client
            $video['username']     = htmlspecialchars($video['username']     ?? '', ENT_QUOTES, 'UTF-8');
            $video['description']  = htmlspecialchars($video['description']  ?? '', ENT_QUOTES, 'UTF-8');
            $video['hashtags']     = array_map(fn($t) => htmlspecialchars($t, ENT_QUOTES, 'UTF-8'), $tags);
            $video['is_liked']     = (bool)$video['is_liked'];
            $video['is_saved']     = (bool)$video['is_saved'];
            $video['is_following'] = (bool)$video['is_following'];
        }
        unset($video); // clear reference

        return $videos;
    }

    $feed = [];

    // "All" category — every public video, mixed order
    $allVideos = fetchVideos(
        $pdo, $current_user_id,
        "WHERE (v.visibility = 'Public' OR v.user_id = ?)",
        [$current_user_id],
        true
    );
    $feed[] = [
        'category_id'   => 'all',
        'category_name' => 'All',
        'videos'        => $allVideos,
    ];

    // Per-category videos (only categories that have videos)
    foreach ($categories as $cat) {
        $videos = fetchVideos(
            $pdo, $current_user_id,
            "WHERE v.category_id = ? AND (v.visibility = 'Public' OR v.user_id = ?)",
            [$cat['id'], $current_user_id]
        );

        if (count($videos) === 0) continue;

        $feed[] = [
            'category_id'   => $cat['id'],
            'category_name' => htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'),
            'videos'        => $videos,
        ];
    }

    echo json_encode(['success' => true, 'feed' => $feed]);

} catch (\PDOException $e) {
    error_log('[Swipe Nest] feed.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}
