<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kanban · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('admin');
    $activePage = 'kanban';
    $db = db();

    $cols = ['todo' => [], 'inprogress' => [], 'review' => [], 'done' => []];
    $projects = $db->query("SELECT id, name FROM tf_projects ORDER BY name")->fetchAll();
    $pid = (int)($_GET['project'] ?? ($projects[0]['id'] ?? 0));
    
    $tq = $db->prepare("SELECT t.*, u.name aname FROM tf_tasks t LEFT JOIN tf_users u ON t.assigned_to=u.id WHERE t.project_id=? ORDER BY t.position, t.created_at");
    $tq->execute([$pid]);
    foreach ($tq->fetchAll() as $t) {
        $cols[$t['status']][] = $t;
    }
    $colMeta = ['todo' => ['label' => '📋 Todo', 'cls' => 'k-todo'], 'inprogress' => ['label' => '⚡ In Progress', 'cls' => 'k-inprogress'], 'review' => ['label' => '👁 Review', 'cls' => 'k-review'], 'done' => ['label' => '✅ Done', 'cls' => 'k-done']];
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div style="display:flex; align-items:center; gap:20px;">
                    <div class="tf-topbar-title">Global Kanban Viewer</div>
                    <form method="GET" style="margin:0;">
                        <select name="project" onchange="this.form.submit()" class="tf-inp" style="padding:6px 12px; border-radius:8px; border:1px solid var(--border); background:var(--surface2);">
                            <?php if (!$projects): ?><option value="">No Projects Yet</option><?php endif; ?>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $pid==$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">System-wide Kanban</div>
                        <div class="tf-subtitle">View and manage tasks across any active sprint</div>
                    </div>
                </div>

                    <div class="tf-kanban a2" id="kanbanBoard">
                        <?php foreach ($colMeta as $status => $meta): ?>
                            <div class="tf-kcol <?= $meta['cls'] ?>" data-status="<?= $status ?>">
                                <div class="tf-kcol-hd">
                                    <span class="tf-kcol-name">
                                        <?= $meta['label'] ?>
                                    </span>
                                    <span class="tf-kcol-cnt">
                                        <?= count($cols[$status]) ?>
                                    </span>
                                </div>
                                <div class="tf-kcol-list">
                                    <?php foreach ($cols[$status] as $t): ?>
                                        <div class="tf-task-card" data-id="<?= $t['id'] ?>" draggable="true">
                                            <div class="tf-task-type-dot t-<?= $t['type'] ?>"></div>
                                            <div class="tf-task-title">
                                                <?= e($t['title']) ?>
                                            </div>
                                            <div style="font-size:11px;color:var(--text3);margin-bottom:6px">
                                                <?= e($t['aname'] ?? 'Unassigned') ?>
                                            </div>
                                            <div class="tf-task-foot">
                                                <span class="badge b-<?= $t['priority'] ?>">
                                                    <?= ucfirst($t['priority']) ?>
                                                </span>
                                                <span class="tf-pts">
                                                    <?= $t['story_points'] ?> pts
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

        </div>
    </div>
    <div id="tf-toasts"></div>
    <script src="<?= APP_URL ?>/frontend/assets/js/app.js"></script>
</body>

</html>