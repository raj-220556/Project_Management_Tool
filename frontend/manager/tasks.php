<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Tasks · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('manager');
    $activePage = 'tasks';
    $db = db();
    $uid = currentUser()['id'];

    // Handle Delete Task
    if (isset($_GET['del'])) {
        $did = (int) $_GET['del'];
        
        $chk = $db->prepare("SELECT t.id, t.title, p.name pname FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id WHERE t.id=? AND p.manager_id=?");
        $chk->execute([$did, $uid]);
        $task = $chk->fetch();
        if ($task) {
            $tname = $task['title'];
            db()->prepare('DELETE FROM tf_activity WHERE task_id=?')->execute([$did]);
            db()->prepare('DELETE FROM tf_tasks WHERE id=?')->execute([$did]);
            
            logActivity($uid, null, null, 'deleted a task', 'task', $did, '', $tname);
            notifyUser($uid, 'Task Deleted', "Task '$tname' was removed.", "tasks.php");
            
            header('Location: tasks.php?ok=1');
            exit;
        }
    }

    // Get tasks for projects managed by this user
    $tasks = $db->prepare("SELECT t.*, p.name pname, u.name uname FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id LEFT JOIN tf_users u ON t.assigned_to=u.id WHERE p.manager_id=? ORDER BY t.created_at DESC");
    $tasks->execute([$uid]);
    $tasks = $tasks->fetchAll();
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Project Tasks</div>
                <div class="tf-search"><span>🔍</span><input type="text" placeholder="Search tasks..." id="searchInp" class="tf-live-search" data-target="#tasksTable tbody tr">
                </div>
                <a href="kanban.php" class="btn btn-primary btn-sm">Kanban Board</a>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">All Tasks</div>
                        <div class="tf-subtitle">Tasks from your managed projects</div>
                    </div>
                </div>

                <?php if (isset($_GET['ok']) && $_GET['ok']==1): ?>
                    <div class="tf-toast-inline" style="background:rgba(239,68,68,0.1); color:#ef4444; border-color:rgba(239,68,68,0.2); margin-bottom:16px;">✅ Task permanently deleted.</div>
                <?php endif; ?>

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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $t): ?>
                                    <tr onclick="window.location.href='task_detail.php?id=<?= $t['id'] ?>'" style="cursor:pointer;">
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
                                        <td>
                                            <a href="javascript:void(0)" onclick="confirmDelete('Are you sure you want to delete this task?', () => window.location.href='tasks.php?del=<?= $t['id'] ?>')" class="btn btn-secondary btn-sm" style="color:#ef4444; border-color:rgba(239,68,68,0.2); background:rgba(239,68,68,0.05)">Delete</a>
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