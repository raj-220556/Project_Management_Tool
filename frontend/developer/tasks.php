<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Tasks · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('developer');
    $activePage = 'tasks';
    $db = db();
    $uid = currentUser()['id'];

    $tasks = $db->prepare("SELECT t.*, p.name pname FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id WHERE t.assigned_to=? ORDER BY t.priority='critical' DESC, t.due_date ASC");
    $tasks->execute([$uid]);
    $tasks = $tasks->fetchAll();
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>" data-role="<?= currentUser()['role'] ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">My Assignments</div>
                <div class="tf-search"><span>🔍</span><input type="text" placeholder="Search my tasks..." id="searchInp" class="tf-live-search" data-target="#tasksTable tbody tr"></div>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">My Task List</div>
                        <div class="tf-subtitle">Focus on your assigned work and keep moving forward</div>
                    </div>
                </div>

                <div class="card a2">
                    <div class="tf-tbl-wrap" style="border:none">
                        <table id="tasksTable">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $t): ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <span class="tf-task-type-dot t-<?= $t['type'] ?>"
                                                    style="position:static;display:inline-block;width:8px;height:8px"></span>
                                                <span style="font-weight:600">
                                                    <?= e($t['title']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td style="font-size:12px;color:var(--text2)">
                                            <?= e($t['pname']) ?>
                                        </td>
                                        <td><span class="badge b-<?= $t['status'] ?>">
                                                <?= ucfirst($t['status']) ?>
                                            </span></td>
                                        <td><span class="badge b-<?= $t['priority'] ?>">
                                                <?= ucfirst($t['priority']) ?>
                                            </span></td>
                                        <td style="font-size:12px;color:var(--text3)">
                                            <?= $t['due_date'] ? date('M j', strtotime($t['due_date'])) : '—' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$tasks): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center;padding:30px;color:var(--text3)">No tasks
                                            assigned to you. Enjoy your day! ☕</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>