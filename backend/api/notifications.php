<?php
// backend/api/notifications.php
require_once __DIR__ . '/../shared/includes/init.php';
header('Content-Type: application/json');

if (!isLoggedIn())
    jsonResponse(['ok' => false, 'err' => 'Auth required'], 401);

$db = db();
$uid = currentUser()['id'];

// Mark as read if ID provided
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['id'])) {
        $db->prepare("UPDATE tf_notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$data['id'], $uid]);
        jsonResponse(['ok' => true]);
    }
}

// Get unread notifications
$st = $db->prepare("SELECT * FROM tf_notifications WHERE user_id=? AND is_read=0 ORDER BY created_at DESC LIMIT 10");
$st->execute([$uid]);
$notifs = $st->fetchAll();

$cnt = $db->prepare("SELECT COUNT(*) FROM tf_notifications WHERE user_id=? AND is_read=0");
$cnt->execute([$uid]);
$total = $cnt->fetchColumn();

jsonResponse(['ok' => true, 'notifs' => $notifs, 'unread' => $total]);
