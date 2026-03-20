<?php
require_once __DIR__ . '/../../backend/shared/includes/init.php';
requireLogin('admin');
$activePage = 'notifs';
$db = db();
$uid = currentUser()['id'];

if (isset($_GET['read'])) {
    $db->prepare("UPDATE tf_notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$_GET['read'], $uid]);
    header("Location: notifications.php"); exit;
}
if (isset($_GET['read_all'])) {
    $db->prepare("UPDATE tf_notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
    header("Location: notifications.php"); exit;
}
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM tf_notifications WHERE id=? AND user_id=?")->execute([$_GET['delete'], $uid]);
    header("Location: notifications.php"); exit;
}

$notifs = $db->prepare("SELECT * FROM tf_notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
$notifs->execute([$uid]);
$notifs = $notifs->fetchAll();
$unreadCount = count(array_filter($notifs, fn($n) => !$n['is_read']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Notifications · SprintDesk</title>
    <style>
        .tf-page { max-width: 900px; margin: 0 auto; }
        .page-header-title { font-size: 34px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 4px; }
        .page-header-sub { color: var(--text3); font-size: 15px; margin-bottom: 30px; }
        
        .notif-wrap { background: var(--surface); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .notif-hd { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
        .notif-list { display: flex; flex-direction: column; }
        .notif-item { display: flex; gap: 16px; padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.02); transition: background 0.2s; text-decoration: none; color: inherit; }
        .notif-item:last-child { border-bottom: none; }
        .notif-item:hover { background: rgba(255,255,255,0.02); }
        .notif-item.unread { background: var(--surface2); border-left: 3px solid var(--brand); padding-left: 21px; }
        
        .notif-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .notif-item.unread .notif-icon { background: var(--surface); color: var(--brand); }
        
        .notif-content { flex: 1; }
        .notif-title { font-weight: 600; font-size: 15px; margin-bottom: 4px; color: var(--text); }
        .notif-msg { font-size: 14px; color: var(--text2); line-height: 1.5; margin-bottom: 8px; }
        .notif-time { font-size: 12px; color: var(--text3); font-weight: 500; }
        
        .notif-actions { display: flex; align-items: flex-start; gap: 8px; opacity: 0; transition: opacity 0.2s; }
        .notif-item:hover .notif-actions { opacity: 1; }
        .notif-btn { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text2); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .notif-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .notif-btn.del:hover { background: rgba(239,68,68,0.1); color: #ef4444; border-color: rgba(239,68,68,0.2); }
        
        .empty-state { padding: 60px 20px; text-align: center; color: var(--text3); }
        .empty-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
    </style>
</head>
<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Notifications</div>
            </div>
            <div class="tf-page">
                <div class="a1">
                    <h1 class="page-header-title">Inbox</h1>
                    <p class="page-header-sub">You have <?= $unreadCount ?> unread messages.</p>
                </div>

                <div class="notif-wrap a2">
                    <div class="notif-hd">
                        <div style="font-weight:600;font-size:16px;">All Notifications</div>
                        <?php if($unreadCount > 0): ?>
                            <a href="?read_all=1" class="btn btn-secondary btn-sm">Mark All as Read</a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(count($notifs) === 0): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <div style="font-size:16px;font-weight:500;color:var(--text2)">You're all caught up!</div>
                            <div style="font-size:14px;margin-top:4px">No new notifications right now.</div>
                        </div>
                    <?php else: ?>
                        <div class="notif-list">
                            <?php foreach($notifs as $n): ?>
                                <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                                    <div class="notif-icon">
                                        <?= strpos(strtolower($n['title']), 'project') !== false ? '📁' : 
                                           (strpos(strtolower($n['title']), 'sprint') !== false ? '🔥' : 
                                           (strpos(strtolower($n['title']), 'user') !== false || strpos(strtolower($n['title']), 'welcome') !== false ? '👋' : '🔔')) ?>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title"><?= e($n['title']) ?></div>
                                        <div class="notif-msg"><?= e($n['message']) ?></div>
                                        <div class="notif-time"><?= date('M j, Y • g:i A', strtotime($n['created_at'])) ?></div>
                                    </div>
                                    <div class="notif-actions">
                                        <?php if(!$n['is_read']): ?>
                                            <a href="?read=<?= $n['id'] ?>" class="notif-btn" title="Mark as Read">✓</a>
                                        <?php endif; ?>
                                        <?php if($n['link']): ?>
                                            <a href="<?= e($n['link']) ?>" class="notif-btn" title="View Link">↗</a>
                                        <?php endif; ?>
                                        <a href="?delete=<?= $n['id'] ?>" class="notif-btn del" title="Delete">✕</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
