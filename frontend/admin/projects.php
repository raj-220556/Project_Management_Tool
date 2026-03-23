<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Projects · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('admin');
    $activePage = 'projects';
    $db = db();

    // Handle add project
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $desc = trim($_POST['description'] ?? '');
        $mid = (int) ($_POST['manager_id'] ?? 0);
        $clr = $_POST['color'] ?? '#6366F1';
        $github = trim($_POST['github_url'] ?? '');
        $github_pat = trim($_POST['github_pat'] ?? '');

        if ($name && $code) {
            try {
                $db->prepare('INSERT INTO tf_projects(name,code,description,manager_id,color,github_url,github_pat,created_by,org_id) VALUES(?,?,?,?,?,?,?,?,?)')
                    ->execute([$name, $code, $desc, $mid ?: null, $clr, $github ?: null, $github_pat ?: null, currentUser()['id'], currentUser()['org_id']]);
                $pid = $db->lastInsertId();
                logActivity(currentUser()['id'], $pid, null, 'created project', 'project', $pid);
                $orgUsers = $db->prepare("SELECT id FROM tf_users WHERE org_id=?");
                $orgUsers->execute([currentUser()['org_id']]);
                foreach($orgUsers->fetchAll() as $u) {
                    notifyUser($u['id'], 'New Project', "Project '$name' was created.", "projects.php");
                }
                header('Location: projects.php?ok=1&celebrate=1');
                exit;
            } catch (Exception $e) {
                $addErr = 'Project code already exists.';
            }
        } else {
            $addErr = 'Please fill in all fields.';
        }
    }

    // Handle Delete
    if (isset($_GET['del'])) {
        $did = (int) $_GET['del'];
        $orgId = currentUser()['org_id'];
        
        $chk = $db->prepare("SELECT name FROM tf_projects WHERE id=? AND org_id=?");
        $chk->execute([$did, $orgId]);
        $proj = $chk->fetch();
        if ($proj) {
            $pname = $proj['name'];
            db()->prepare('DELETE FROM tf_activity WHERE project_id=?')->execute([$did]);
            db()->prepare('DELETE FROM tf_tasks WHERE project_id=?')->execute([$did]);
            db()->prepare('DELETE FROM tf_sprints WHERE project_id=?')->execute([$did]);
            db()->prepare('DELETE FROM tf_projects WHERE id=?')->execute([$did]);
            
            logActivity(currentUser()['id'], null, null, 'permanently deleted project', 'project', $did, '', $pname);
            $orgUsers = $db->prepare("SELECT id FROM tf_users WHERE org_id=?");
            $orgUsers->execute([$orgId]);
            foreach($orgUsers->fetchAll() as $u) {
                notifyUser($u['id'], 'Project Deleted', "Project '$pname' was permanently purged.", "projects.php");
            }
            
            header('Location: projects.php?ok=2');
            exit;
        }
    }

    $orgId = currentUser()['org_id'];
    $projectsQ = $db->prepare("SELECT p.*, u.name mname, (SELECT COUNT(*) FROM tf_tasks WHERE project_id=p.id) tc FROM tf_projects p LEFT JOIN tf_users u ON p.manager_id=u.id WHERE p.org_id=? ORDER BY p.created_at DESC");
    $projectsQ->execute([$orgId]);
    $projects = $projectsQ->fetchAll();

    $managersQ = $db->prepare("SELECT id,name FROM tf_users WHERE org_id=? AND (role='manager' OR role='admin') ORDER BY name");
    $managersQ->execute([$orgId]);
    $managers = $managersQ->fetchAll();
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Project Management</div>
                <div class="tf-search"><span>🔍</span><input type="text" placeholder="Search projects..."
                        id="searchInp" class="tf-live-search" data-target=".tf-grid-projects .tf-project-card"></div>
                <button class="btn btn-primary btn-sm"
                    onclick="document.getElementById('addModal').classList.add('open')">+ New Project</button>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">All Projects</div>
                        <div class="tf-subtitle"><?= count($projects) ?> active projects</div>
                    </div>
                </div>

                <?php if (isset($_GET['ok']) && $_GET['ok']==1): ?>
                    <div class="tf-toast-inline">✅ Project created successfully.</div><?php endif; ?>
                <?php if (isset($_GET['ok']) && $_GET['ok']==2): ?>
                    <div class="tf-toast-inline" style="background:rgba(239,68,68,0.1); color:#ef4444; border-color:rgba(239,68,68,0.2);">✅ Project and all associated records permanently erased.</div><?php endif; ?>
                <?php if (!empty($addErr)): ?>
                    <div class="tf-err">⚠️ <?= e($addErr) ?></div><?php endif; ?>

                <div class="tf-grid-projects a2">
                    <?php foreach ($projects as $p): ?>
                        <div class="tf-project-card" style="border-top: 4px solid <?= e($p['color']) ?>">
                            <div class="tf-pc-body">
                                <div class="tf-pc-code"><?= e($p['code']) ?></div>
                                <h3 class="tf-pc-title"><?= e($p['name']) ?></h3>
                                <p class="tf-pc-desc"><?= e($p['description'] ?: 'No description provided.') ?></p>
                                <div class="tf-pc-meta">
                                    <div class="tf-pc-manager"><span>👤</span> <?= e($p['mname'] ?: 'No Manager') ?></div>
                                    <div class="tf-pc-tasks"><span>📋</span> <?= $p['tc'] ?> Tasks</div>
                                </div>
                            </div>
                            <div class="tf-pc-foot">
                                <a href="tasks.php?project=<?= $p['id'] ?>" class="tf-pc-link">View Tasks →</a>
                                <a href="javascript:void(0)" onclick="confirmDelete('Are you sure you want to permanently delete this project? All associated tasks and sprints will be completely erased.', () => window.location.href='projects.php?del=<?= $p['id'] ?>')" class="tf-pc-link" style="color:#ef4444; margin-left:auto;">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="tf-overlay" id="addModal">
        <div class="tf-modal">
            <div class="tf-modal-hd">
                <div class="tf-modal-title">Create New Project</div><button class="tf-modal-close">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="tf-modal-body">
                    <div class="tf-fg"><label class="tf-lbl">Project Name</label><input type="text" name="name"
                            class="tf-inp" required placeholder="E-Commerce App"></div>
                    <div class="tf-fg"><label class="tf-lbl">Project Code (Short)</label><input type="text" name="code"
                            class="tf-inp" required maxlength="10" placeholder="ECA"></div>
                    <div class="tf-fg"><label class="tf-lbl">Description</label><textarea name="description"
                            class="tf-inp" style="height:80px" placeholder="Brief overview..."></textarea></div>
                    <div class="tf-fg"><label class="tf-lbl">Assign Manager</label>
                        <select name="manager_id" class="tf-inp tf-sel">
                            <option value="">Select a manager</option>
                            <?php foreach ($managers as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="g2">
                        <div class="tf-fg"><label class="tf-lbl">Brand Color</label><input type="color" name="color"
                                class="tf-inp" value="#6366F1" style="height:44px;padding:4px"></div>
                        <div class="tf-fg"><label class="tf-lbl">GitHub Repository (Optional)</label><input type="url" name="github_url"
                                class="tf-inp" placeholder="https://github.com/org/repo"></div>
                    </div>
                    <div class="tf-fg" style="margin-top: 16px;"><label class="tf-lbl">GitHub PAT Token (Optional, fallback to .env)</label><input type="password" name="github_pat"
                            class="tf-inp" placeholder="ghp_xxx..."></div>
                </div>
                <div class="tf-modal-foot"><button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button><button
                        type="submit" class="btn btn-primary">Create Project</button></div>
            </form>
        </div>
    </div>
</body>

</html>