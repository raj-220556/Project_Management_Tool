<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sprints · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('developer');
    $activePage = 'sprints';
    $db = db();
    $uid = currentUser()['id'];

    $sprints = $db->prepare("SELECT DISTINCT s.*, p.name pname FROM tf_sprints s JOIN tf_projects p ON s.project_id=p.id JOIN tf_project_members pm ON pm.project_id=p.id WHERE pm.user_id=? ORDER BY s.created_at DESC");
    $sprints->execute([$uid]);
    $sprints = $sprints->fetchAll();
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Sprints</div>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">Project Sprints</div>
                        <div class="tf-subtitle">View active and upcoming cycles for your projects</div>
                    </div>
                </div>
                <div class="card a2">
                    <div class="tf-tbl-wrap" style="border:none">
                        <table>
                            <thead>
                                <tr>
                                    <th>Sprint</th>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th>Timeline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sprints as $s): ?>
                                    <tr>
                                        <td style="font-weight:600;color:var(--brand)">
                                            <?= e($s['name']) ?>
                                        </td>
                                        <td>
                                            <?= e($s['pname']) ?>
                                        </td>
                                        <td><span class="badge b-<?= $s['status'] ?>">
                                                <?= ucfirst($s['status']) ?>
                                            </span></td>
                                        <td style="font-size:12px;color:var(--text3)">
                                            <?= $s['start_date'] ? date('M j', strtotime($s['start_date'])) : '—' ?> →
                                            <?= $s['end_date'] ? date('M j, Y', strtotime($s['end_date'])) : '—' ?>
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