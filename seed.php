<?php
require_once 'config/db.php';

try {
    // Check if test user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['test@zyva.com']);
    $user = $stmt->fetch();

    if (!$user) {
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, email, password_hash, avatar_url) VALUES (?, ?, ?, ?)")
            ->execute(['TestUser', 'test@zyva.com', $hash, 'https://i.pravatar.cc/150?img=33']);
        $user_id = $pdo->lastInsertId();
    } else {
        $user_id = $user['id'];
    }

    // Assign videos to random existing categories (1: Entertainment, 3: Tech)
    $videos = [
        [
            'category_id' => 1,
            'file_path' => 'https://www.w3schools.com/html/mov_bbb.mp4',
            'description' => 'Big Buck Bunny sample video! 🐰 This is a classic test video.',
            'hashtags' => ['bunny', 'classic', 'animation']
        ],
        [
            'category_id' => 1,
            'file_path' => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
            'description' => 'Beautiful flower blooming. Nature is amazing! 🌸',
            'hashtags' => ['nature', 'flower', 'beautiful']
        ],
        [
            'category_id' => 3,
            'file_path' => 'https://test-videos.co.uk/vids/jellyfish/mp4/h264/720/Jellyfish_720_10s_1MB.mp4',
            'description' => 'Jellyfish moving in the ocean. Mesmerizing... 🌊',
            'hashtags' => ['ocean', 'jellyfish', 'nature']
        ]
    ];

    $count = 0;
    foreach ($videos as $v) {
        // Avoid duplicate videos if run multiple times
        $stmt = $pdo->prepare("SELECT id FROM videos WHERE file_path = ?");
        $stmt->execute([$v['file_path']]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO videos (user_id, category_id, file_path, description, visibility) VALUES (?, ?, ?, ?, 'Public')")
                ->execute([$user_id, $v['category_id'], $v['file_path'], $v['description']]);
            $video_id = $pdo->lastInsertId();

            // Insert tags
            foreach ($v['hashtags'] as $tag) {
                $pdo->prepare("INSERT INTO hashtags (video_id, tag) VALUES (?, ?)")->execute([$video_id, $tag]);
            }
            
            // Add some dummy likes and comments
            $pdo->prepare("INSERT INTO likes (user_id, video_id) VALUES (?, ?)")->execute([$user_id, $video_id]);
            $pdo->prepare("INSERT INTO comments (user_id, video_id, comment_text) VALUES (?, ?, ?)")->execute([$user_id, $video_id, 'This is an amazing video! 🔥']);
            $count++;
        }
    }

    echo "Seed completed successfully. Added $count videos.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
