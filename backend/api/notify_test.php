<?php
// backend/api/notify_test.php
require_once __DIR__ . '/../shared/includes/init.php';
requireLogin('admin');

$uid = currentUser()['id'];
notifyUser($uid, "🚀 Advanced Features Live!", "Your search and notification systems are now active.", "system", APP_URL . "/frontend/admin/dashboard.php");
notifyUser($uid, "🎨 Premium UI Updated", "We've beefed up the typography and card designs.", "system");

echo "Notifications sent to admin.";
