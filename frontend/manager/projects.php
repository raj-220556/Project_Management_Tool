<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Projects · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('manager');
    $activePage = 'projects';
    $db = db();
    $uid = currentUser()['id'];

    $projects = $db->prepare("SELECT p.*, (SELECT COUNT(*) FROM tf_tasks WHERE project_id=p.id) tc FROM tf_projects p WHERE p.manager_id=? ORDER BY p.created_at DESC");
    $projects->execute([$uid]);
    $projects = $projects->fetchAll();
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Managed Projects</div>
                <div class="tf-search"><span>🔍</span><input type="text" placeholder="Search my projects..." id="searchInp" class="tf-live-search" data-target=".tf-grid-projects .tf-project-card"></div>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">My Projects</div>
                        <div class="tf-subtitle">Projects where you are the primary manager</div>
                    </div>
                </div>

                <div class="tf-grid-projects a2">
                    <?php foreach ($projects as $p): ?>
                        <div class="tf-project-card" style="border-top: 4px solid <?= e($p['color']) ?>">
                            <div class="tf-pc-body">
                                <div class="tf-pc-code">
                                    <?= e($p['code']) ?>
                                </div>
                                <h3 class="tf-pc-title">
                                    <?= e($p['name']) ?>
                                </h3>
                                <p class="tf-pc-desc">
                                    <?= e($p['description'] ?: 'No description provided.') ?>
                                </p>
                                <div class="tf-pc-meta">
                                    <div class="tf-pc-tasks"><span>📋</span>
                                        <?= $p['tc'] ?> Tasks
                                    </div>
                                </div>
                            </div>
                            <div class="tf-pc-foot">
                                <a href="kanban.php?project=<?= $p['id'] ?>" class="tf-pc-link">Open Kanban Board →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$projects): ?>
                        <p style="padding:40px;text-align:center;color:var(--text3);grid-column:1/-1">You are not managing
                            any projects yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>