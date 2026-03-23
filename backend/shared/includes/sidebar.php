<?php
/**
 * backend/shared/includes/sidebar.php
 * Include this AFTER init.php and requireLogin()
 * Expects $activePage variable to be set by the calling page
 */
$__user = currentUser();
$__init = userInitials($__user['name']);
$__role = $__user['role'];

$__nav = [
    'org_manager' => [
        ['icon' => '⚡', 'label' => 'Global Dashboard', 'page' => 'dashboard', 'url' => URL_ORG_MANAGER],
        ['icon' => '🏢', 'label' => 'Organizations', 'page' => 'orgs', 'url' => APP_URL . '/frontend/org_manager/organizations.php'],
        ['icon' => '⚙️', 'label' => 'Settings', 'page' => 'settings', 'url' => APP_URL . '/frontend/org_manager/settings.php'],
    ],
    'admin' => [
        ['icon' => '⚡', 'label' => 'Dashboard', 'page' => 'dashboard', 'url' => URL_ADMIN],
        ['icon' => '👥', 'label' => 'Users', 'page' => 'users', 'url' => APP_URL . '/frontend/admin/users.php'],
        ['icon' => '📁', 'label' => 'Projects', 'page' => 'projects', 'url' => APP_URL . '/frontend/admin/projects.php'],
        ['icon' => '🐙', 'label' => 'Git Activity', 'page' => 'git_activity', 'url' => APP_URL . '/frontend/admin/git_activity.php'],
        ['icon' => '📊', 'label' => 'Analytics', 'page' => 'analytics', 'url' => APP_URL . '/frontend/admin/analytics.php'],
        ['icon' => '📜', 'label' => 'Activity', 'page' => 'activity', 'url' => APP_URL . '/frontend/admin/activity.php'],
        ['icon' => '🔔', 'label' => 'Notifications', 'page' => 'notifs', 'url' => APP_URL . '/frontend/admin/notifications.php'],
        ['icon' => '⚙️', 'label' => 'Settings', 'page' => 'settings', 'url' => APP_URL . '/frontend/admin/settings.php'],
    ],
    'manager' => [
        ['icon' => '⚡', 'label' => 'Dashboard', 'page' => 'dashboard', 'url' => URL_MANAGER],
        ['icon' => '📁', 'label' => 'Projects', 'page' => 'projects', 'url' => APP_URL . '/frontend/manager/projects.php'],
        ['icon' => '🐙', 'label' => 'Git Activity', 'page' => 'git_activity', 'url' => APP_URL . '/frontend/manager/git_activity.php'],
        ['icon' => '🔥', 'label' => 'Sprints', 'page' => 'sprints', 'url' => APP_URL . '/frontend/manager/sprints.php'],
        ['icon' => '📋', 'label' => 'Tasks', 'page' => 'tasks', 'url' => APP_URL . '/frontend/manager/tasks.php'],
        ['icon' => '🗂️', 'label' => 'Kanban', 'page' => 'kanban', 'url' => APP_URL . '/frontend/manager/kanban.php'],
        ['icon' => '👥', 'label' => 'Team Members', 'page' => 'team', 'url' => APP_URL . '/frontend/manager/team.php'],
        ['icon' => '📊', 'label' => 'Reports', 'page' => 'reports', 'url' => APP_URL . '/frontend/manager/reports.php'],
        ['icon' => '📜', 'label' => 'Activity', 'page' => 'activity', 'url' => APP_URL . '/frontend/manager/activity.php'],
        ['icon' => '🔔', 'label' => 'Notifications', 'page' => 'notifs', 'url' => APP_URL . '/frontend/manager/notifications.php'],
        ['icon' => '⚙️', 'label' => 'Settings', 'page' => 'settings', 'url' => APP_URL . '/frontend/manager/settings.php'],
    ],
    'developer' => [
        ['icon' => '⚡', 'label' => 'My Dashboard', 'page' => 'dashboard', 'url' => URL_DEVELOPER],
        ['icon' => '📋', 'label' => 'My Tasks', 'page' => 'tasks', 'url' => APP_URL . '/frontend/developer/tasks.php'],
        ['icon' => '🗂️', 'label' => 'Kanban', 'page' => 'kanban', 'url' => APP_URL . '/frontend/developer/kanban.php'],
        ['icon' => '🔔', 'label' => 'Notifications', 'page' => 'notifs', 'url' => APP_URL . '/frontend/developer/notifications.php'],
        ['icon' => '⚙️', 'label' => 'Settings', 'page' => 'settings', 'url' => APP_URL . '/frontend/developer/settings.php'],
    ],
];
$__items = $__nav[$__role] ?? $__nav['developer'];
$__theme = getUserTheme();
?>
<!-- inject CSS + JS once -->
<div id="tf-scroll-line"></div>
<link rel="stylesheet" href="<?= URL_ASSETS ?>/css/app.css?v=1.0.1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto:wght@400&display=swap" rel="stylesheet">

<aside class="tf-sidebar" id="tf-sidebar">
    <div class="tf-sb-logo" style="display:flex; align-items:center;">
        <div class="tf-sb-mark">⚡</div>
        <div class="tf-sb-name">Sprint<em>Desk</em></div>
        <button id="tf-desktop-toggle" style="margin-left:auto; background:none; border:none; color:var(--text3); font-size:18px; cursor:pointer" title="Toggle Sidebar">←</button>
    </div>

    <nav class="tf-sb-nav">
        <div class="tf-sb-section">Menu</div>
        <?php foreach ($__items as $item): ?>
            <a href="<?= $item['url'] ?>" class="tf-sb-link <?= ($activePage ?? '') === $item['page'] ? 'active' : '' ?>">
                <span class="tf-sb-icon"><?= $item['icon'] ?></span>
                <span><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>



    <div class="tf-sb-foot">
        <div class="tf-sb-theme">
            <span class="tf-sb-theme-lbl"><?= $__theme === 'dark' ? '🌙 Dark' : '☀️ Light' ?> Mode</span>
            <button class="tf-theme-tog" title="Toggle theme"></button>
        </div>
        <div class="tf-sb-user">
            <div class="tf-sb-avatar">
                <?php if (!empty($__user['avatar'])): ?>
                    <img src="<?= e($__user['avatar']) ?>" alt="">
                <?php else:
                    echo e($__init);
                endif; ?>
            </div>
            <?php 
                $roleLabels = [
                    'org_manager' => 'Global Organiser',
                    'admin'       => 'Admin',
                    'manager'     => 'Manager',
                    'developer'   => 'User',
                ];
                $displayRole = $roleLabels[$__role] ?? ucfirst($__role);
            ?>
            <div>
                <div class="tf-sb-uname"><?= e($__user['name']) ?></div>
                <div class="tf-sb-urole"><?= $displayRole ?></div>
            </div>
        </div>
        <a href="<?= URL_LOGOUT ?>" class="tf-sb-logout">
            <span>🚪</span><span>Log out</span>
        </a>
    </div>
</aside>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
<script src="<?= URL_ASSETS ?>/js/app.js"></script>