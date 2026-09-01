<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add_category') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        exit;
    }

    if (strlen($name) > 100) {
        echo json_encode(['success' => false, 'message' => 'Category name too long (max 100 chars)']);
        exit;
    }

    // Check duplicate (case-insensitive)
    $check = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
    $check->execute([$name]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Category already exists']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$name]);

    echo json_encode([
        'success' => true,
        'category' => [
            'id'   => (int)$pdo->lastInsertId(),
            'name' => $name
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
