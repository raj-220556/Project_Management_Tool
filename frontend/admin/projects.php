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
        $orgKeyInp = trim($_POST['org_key'] ?? '');

        // Fetch live org key
        $okQ = $db->prepare("SELECT org_key FROM tf_organizations WHERE id=?");
        $okQ->execute([currentUser()['org_id']]);
        $realKey = $okQ->fetchColumn();

        if ($name && $code && $orgKeyInp) {
            if (!$realKey) {
                $addErr = 'Organization Key not configured! Please configure it in Settings first.';
            } elseif ($orgKeyInp !== $realKey) {
                $addErr = 'Invalid Organization Key. You are not authorized to create projects.';
            } else {
                try {
                    $db->prepare('INSERT INTO tf_projects(name,code,description,manager_id,color,github_url,github_pat,created_by,org_id) VALUES(?,?,?,?,?,?,?,?,?)')
                        ->execute([$name, $code, $desc, $mid ?: null, $clr, $github ?: null, $github_pat ?: null, currentUser()['id'], currentUser()['org_id']]);
                    $pid = $db->lastInsertId();
                    logActivity(currentUser()['id'], $pid, null, 'created project', 'project', $pid);
                    
                    if ($mid) {
                        $mgrStmt = $db->prepare("SELECT name, email FROM tf_users WHERE id = ?");
                        $mgrStmt->execute([$mid]);
                        $mgr = $mgrStmt->fetch();
                        if ($mgr) {
                            $adminName = currentUser()['name'];
                            $adminEmail = currentUser()['email'];
                            $subject = "New Project Assigned: " . $name;
                            $bodyHTML = "
                                <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                                    <h2 style='color: #6366f1;'>New Project Assignment</h2>
                                    <p>Hello <strong>" . htmlspecialchars($mgr['name']) . "</strong>,</p>
                                    <p>Admin <strong>" . htmlspecialchars($adminName) . "</strong> (" . htmlspecialchars($adminEmail) . ") has assigned you as the manager for a new project.</p>
                                    <div style='background: #f8fafc; padding: 15px; border-left: 4px solid #6366f1; margin: 15px 0;'>
                                        <h3 style='margin-top: 0;'>" . htmlspecialchars($name) . "</h3>
                                        <p style='margin-bottom: 0;'>" . nl2br(htmlspecialchars($desc ?: 'No description provided.')) . "</p>
                                    </div>
                                    <p>Log in to SprintDesk to view the details and start managing your project.</p>
                                    <p><a href='" . APP_URL . "/frontend/auth/login.php' style='display:inline-block;padding:10px 20px;background:#6366f1;color:#fff;text-decoration:none;border-radius:5px;'>Log In to SprintDesk</a></p>
                                    <hr style='border: none; border-top: 1px solid #eaeaea; margin: 20px 0;'>
                                    <p style='font-size: 12px; color: #777;'>Regards,<br>SprintDesk Team</p>
                                </div>
                            ";
                            sendSystemEmail($mgr['email'], $subject, $bodyHTML);
                        }
                    }

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
            }
        } else {
            $addErr = 'Please fill in all fields including the Organization Key.';
        }
    }

    // Handle Deletion Request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_delete') {
        $did = (int) $_POST['project_id'];
        $reason = trim($_POST['reason'] ?? '');
        $orgId = currentUser()['org_id'];
        $requesterId = currentUser()['id'];
        
        if (empty($reason)) {
            $addErr = 'Reason for deletion is required.';
        } else {
            $chk = $db->prepare("SELECT name, code FROM tf_projects WHERE id=? AND org_id=?");
            $chk->execute([$did, $orgId]);
            $proj = $chk->fetch();
            if ($proj) {
                // Check if a pending request already exists for this project
                $pendingChk = $db->prepare("SELECT id FROM tf_project_deletion_requests WHERE project_id=? AND status='pending'");
                $pendingChk->execute([$did]);
                if ($pendingChk->fetch()) {
                    $addErr = 'A deletion request is already pending for this project.';
                } else {
                    $db->prepare("INSERT INTO tf_project_deletion_requests (project_id, requester_id, reason, status) VALUES (?, ?, ?, 'pending')")->execute([$did, $requesterId, $reason]);
                    $reqId = $db->lastInsertId();

                    $adminsStmt = $db->prepare("SELECT id, name, email FROM tf_users WHERE org_id=? AND role='admin'");
                    $adminsStmt->execute([$orgId]);
                    $admins = $adminsStmt->fetchAll();
                    $adminCount = count($admins);

                    $threshold = ceil($adminCount / 2);
                    if ($adminCount == 2) $threshold = 2; // If 2 admins, both must approve to avoid auto-deletion by the requester.

                    if ($adminCount == 1 && $admins[0]['id'] == $requesterId) {
                        // Only 1 admin in org, and it's the requester. Delete immediately.
                        db()->prepare('DELETE FROM tf_activity WHERE project_id=?')->execute([$did]);
                        db()->prepare('DELETE FROM tf_tasks WHERE project_id=?')->execute([$did]);
                        db()->prepare('DELETE FROM tf_sprints WHERE project_id=?')->execute([$did]);
                        db()->prepare('DELETE FROM tf_projects WHERE id=?')->execute([$did]);
                        logActivity($requesterId, null, null, 'permanently deleted project', 'project', $did, '', $proj['name']);
                        $db->prepare("UPDATE tf_project_deletion_requests SET status='completed' WHERE id=?")->execute([$reqId]);
                        header('Location: projects.php?ok=2');
                        exit;
                    } else {
                        // More than 1 admin. Proceed with approval flow.
                        $requesterToken = bin2hex(random_bytes(32));
                        $db->prepare("INSERT INTO tf_project_deletion_approvals (request_id, admin_id, token, status, acted_at) VALUES (?, ?, ?, 'approved', NOW())")->execute([$reqId, $requesterId, $requesterToken]);
                        $subject = "Project Deletion Request: " . $proj['name'] . " (" . $proj['code'] . ")";
                        
                        foreach ($admins as $admin) {
                            if ($admin['id'] == $requesterId) continue; // Skip requester

                            $token = bin2hex(random_bytes(32));
                            $db->prepare("INSERT INTO tf_project_deletion_approvals (request_id, admin_id, token, status) VALUES (?, ?, ?, 'pending')")->execute([$reqId, $admin['id'], $token]);
                               
                            $approveLink = APP_URL . "/frontend/admin/process_deletion.php?token=" . urlencode($token) . "&action=approve";
                            $disapproveLink = APP_URL . "/frontend/admin/process_deletion.php?token=" . urlencode($token) . "&action=disapprove";

                            $bodyHTML = "
                                <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                                    <h2 style='color: #ef4444;'>Project Deletion Request</h2>
                                    <p>Hello <strong>" . htmlspecialchars($admin['name']) . "</strong>,</p>
                                    <p>Admin <strong>" . htmlspecialchars(currentUser()['name']) . "</strong> has requested to permanently delete the following project:</p>
                                    <div style='background: #f8fafc; padding: 15px; border-left: 4px solid #ef4444; margin: 15px 0;'>
                                        <h3 style='margin-top: 0;'>" . htmlspecialchars($proj['name']) . " (" . htmlspecialchars($proj['code']) . ")</h3>
                                        <p style='margin-bottom: 0;'><strong>Reason given:</strong><br>" . nl2br(htmlspecialchars($reason)) . "</p>
                                    </div>
                                    <p>Please review this request and choose to approve or disapprove below:</p>
                                    <div style='margin: 25px 0;'>
                                        <a href='{$approveLink}' style='display:inline-block;padding:12px 24px;background:#22c55e;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;margin-right:15px;'>Approve Deletion</a>
                                        <a href='{$disapproveLink}' style='display:inline-block;padding:12px 24px;background:#ef4444;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;'>Disapprove</a>
                                    </div>
                                    <p style='font-size: 13px; color: #666;'>The project will only be permanently deleted once {$threshold} or more admins approve the request.</p>
                                </div>
                            ";
                            sendSystemEmail($admin['email'], $subject, $bodyHTML);
                        }

                        header('Location: projects.php?ok=3');
                        exit;
                    }
                }
            }
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
                <?php if (isset($_GET['ok']) && $_GET['ok']==3): ?>
                    <div class="tf-toast-inline">✅ Deletion request submitted and sent to admins for approval.</div><?php endif; ?>
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
                                <a href="javascript:void(0)" onclick="openDeleteModal('<?= $p['id'] ?>', '<?= addslashes(e($p['name'])) ?>')" class="tf-pc-link" style="color:#ef4444; margin-left:auto;">Delete</a>
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
                    <div class="g2" style="margin-top:16px;">
                        <input type="password" name="github_pat" class="tf-inp" placeholder="GitHub PAT Token (Optional)">
                        <input type="text" name="org_key" class="tf-inp" required placeholder="Organization Key *" style="border-color:var(--brand)">
                    </div>
                </div>
                <div class="tf-modal-foot"><button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button><button
                        type="submit" class="btn btn-primary">Create Project</button></div>
            </form>
        </div>
    </div>

    <div class="tf-overlay" id="deleteModal">
        <div class="tf-modal">
            <div class="tf-modal-hd">
                <div class="tf-modal-title">Delete Project Request</div><button class="tf-modal-close" onclick="document.getElementById('deleteModal').classList.remove('open')">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="request_delete">
                <input type="hidden" name="project_id" id="del_project_id" value="">
                <div class="tf-modal-body">
                    <p>You are requesting to permanently delete <strong id="del_project_name"></strong>.</p>
                    <div class="tf-fg">
                        <label class="tf-lbl">Reason for Deletion</label>
                        <textarea name="reason" class="tf-inp" style="height:80px" required placeholder="Ex: Project was completed and superseded by ECA-v2..."></textarea>
                    </div>
                </div>
                <div class="tf-modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('deleteModal').classList.remove('open')">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:#ef4444; border-color:#ef4444;">Send Request</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function openDeleteModal(id, name) {
        document.getElementById('del_project_id').value = id;
        document.getElementById('del_project_name').innerText = name;
        document.getElementById('deleteModal').classList.add('open');
    }
    </script>
</body>

</html>