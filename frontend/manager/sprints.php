<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sprints · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('manager');
    $activePage = 'sprints';
    $db = db();
    $uid = currentUser()['id'];

    // Handle add sprint
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
        $name = trim($_POST['name'] ?? '');
        $pid = (int) ($_POST['project_id'] ?? 0);
        $goal = trim($_POST['goal'] ?? '');
        $start = $_POST['start_date'] ?: null;
        $end = $_POST['end_date'] ?: null;

        if ($name && $pid) {
            $db->prepare('INSERT INTO tf_sprints(project_id,name,goal,start_date,end_date,created_by) VALUES(?,?,?,?,?,?)')
                ->execute([$pid, $name, $goal, $start, $end, $uid]);
            $sid = $db->lastInsertId();
            
            logActivity($uid, $pid, null, 'created sprint', 'sprint', $sid, '', $name);
            
            // Notify active developers in the organization
            notifyUser(currentUser()['id'], 'New Sprint', "Sprint '$name' has been created.", "sprints.php");
            $devs = $db->prepare("SELECT id FROM tf_users WHERE org_id=? AND role='developer' AND is_active=1");
            $devs->execute([currentUser()['org_id']]);
            foreach($devs->fetchAll() as $d) {
                notifyUser($d['id'], 'New Sprint', "Sprint '$name' has been created.", "sprints.php");
            }
            header('Location: sprints.php?ok=1&celebrate=1');
            exit;
        }
    }

    $sprints = $db->prepare("SELECT s.*, p.name pname FROM tf_sprints s JOIN tf_projects p ON s.project_id=p.id WHERE p.manager_id=? ORDER BY s.created_at DESC");
    $sprints->execute([$uid]);
    $sprints = $sprints->fetchAll();

    $projects = $db->prepare("SELECT id,name FROM tf_projects WHERE manager_id=?");
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
                <div class="tf-topbar-title">Manage Sprints</div>
                <button class="btn btn-primary btn-sm"
                    onclick="document.getElementById('addModal').classList.add('open')">+ New Sprint</button>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">Active & Planned Sprints</div>
                        <div class="tf-subtitle">Manage your team's development cycles</div>
                    </div>
                </div>

                <?php if (isset($_GET['ok'])): ?>
                    <div class="tf-toast-inline">✅ Sprint created successfully.</div>
                <?php endif; ?>

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

    <div class="tf-overlay" id="addModal">
        <div class="tf-modal">
            <div class="tf-modal-hd">
                <div class="tf-modal-title">New Sprint</div><button class="tf-modal-close">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="tf-modal-body">
                    <div class="tf-fg"><label class="tf-lbl">Sprint Name</label><input type="text" name="name"
                            class="tf-inp" required placeholder="Sprint 1 — MVP"></div>
                    <div class="tf-fg"><label class="tf-lbl">Project</label>
                        <select name="project_id" class="tf-inp tf-sel" required>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= e($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tf-fg"><label class="tf-lbl">Goal</label><textarea name="goal" class="tf-inp"
                            style="height:60px"></textarea></div>
                    <div class="g2">
                        <div class="tf-fg"><label class="tf-lbl">Start Date</label><input type="date" name="start_date"
                                class="tf-inp"></div>
                        <div class="tf-fg"><label class="tf-lbl">End Date</label><input type="date" name="end_date"
                                class="tf-inp"></div>
                    </div>
                </div>
                <div class="tf-modal-foot"><button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button><button
                        type="submit" class="btn btn-primary">Create Sprint</button></div>
            </form>
        </div>
    </div>
</body>

</html>