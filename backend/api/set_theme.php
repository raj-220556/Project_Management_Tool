<?php
require_once __DIR__ . '/../../shared/includes/init.php';
if (!isLoggedIn()) jsonResponse(['ok'=>false], 401);
$data  = json_decode(file_get_contents('php://input'), true);
$theme = ($data['theme'] ?? '') === 'dark' ? 'dark' : 'light';
db()->prepare('UPDATE tf_users SET theme=? WHERE id=?')->execute([$theme, $_SESSION['sd_uid']]);
jsonResponse(['ok'=>true]);
