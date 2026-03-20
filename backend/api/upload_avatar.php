<?php
// backend/api/upload_avatar.php
require_once __DIR__ . '/../shared/includes/init.php';
header('Content-Type: application/json');

if (!isLoggedIn())
    jsonResponse(['ok' => false, 'err' => 'Auth required'], 401);

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    jsonResponse(['ok' => false, 'err' => 'Invalid request'], 400);

$uid = currentUser()['id'];

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['ok' => false, 'err' => 'No file uploaded or upload error'], 400);
}

$file = $_FILES['avatar'];
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($file['type'], $allowed)) {
    jsonResponse(['ok' => false, 'err' => 'Invalid file type. Only JPG, PNG, WEBP, GIF allowed.'], 400);
}

// Limit size to 2MB
if ($file['size'] > 2 * 1024 * 1024) {
    jsonResponse(['ok' => false, 'err' => 'File too large. Max 2MB allowed.'], 400);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
$uploadDir = __DIR__ . '/../../frontend/assets/uploads/avatars/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$uploadPath = $uploadDir . $filename;
$publicPath = URL_ASSETS . '/uploads/avatars/' . $filename;

if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    // Update DB
    $db = db();
    $db->prepare("UPDATE tf_users SET avatar=? WHERE id=?")->execute([$publicPath, $uid]);

    // Log Activity
    logActivity($uid, null, null, 'updated avatar', 'user', $uid);

    jsonResponse(['ok' => true, 'avatar' => $publicPath]);
} else {
    jsonResponse(['ok' => false, 'err' => 'Failed to save file.'], 500);
}
