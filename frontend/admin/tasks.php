<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Tasks · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('admin');
    $activePage = 'tasks';
    $db = db();

    $tasks = $db->query("SELECT t.*, p.name pname, u.name uname FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id LEFT JOIN tf_users u ON t.assigned_to=u.id ORDER BY t.created_at DESC")->fetchAll();
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Task Management</div>
                <div class="tf-search"><span>🔍</span><input type="text" placeholder="Search tasks..." id="searchInp" class="tf-live-search" data-target="#tasksTable tbody tr">
                </div>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">All Tasks</div>
                        <div class="tf-subtitle">
                            <?= count($tasks) ?> total tasks in the system
                        </div>
                    </div>
                </div>

                <div class="card a2">
                    <div class="tf-tbl-wrap" style="border:none">
                        <table id="tasksTable">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Assigned To</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $t): ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <span class="tf-task-type-dot t-<?= $t['type'] ?>"
                                                    style="position:static;display:inline-block;width:8px;height:8px"></span>
                                                <span style="font-weight:500">
                                                    <?= e($t['title']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td style="font-size:12px">
                                            <?= e($t['pname']) ?>
                                        </td>
                                        <td><span class="badge b-<?= $t['status'] ?>">
                                                <?= ucfirst($t['status']) ?>
                                            </span></td>
                                        <td><span class="badge b-<?= $t['priority'] ?>">
                                                <?= ucfirst($t['priority']) ?>
                                            </span></td>
                                        <td style="color:var(--text3)">
                                            <?= e($t['uname'] ?: 'Unassigned') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>