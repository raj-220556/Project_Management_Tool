<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Project Members · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('manager');
    $activePage = 'team';
    $db = db();
    $uid = currentUser()['id'];
    $orgId = currentUser()['org_id'];

    $addErr = '';
    $okMsg = '';

    // Handle Add Member
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add_member') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if ($projectId && $userId) {
            $chk = $db->prepare("SELECT id FROM tf_projects WHERE id=? AND manager_id=?");
            $chk->execute([$projectId, $uid]);
            if ($chk->fetch()) {
                try {
                    $db->prepare("INSERT INTO tf_project_members (project_id, user_id) VALUES (?, ?)")->execute([$projectId, $userId]);
                    logActivity($uid, $projectId, null, 'added member to project', 'project', $userId);

                    $devStmt = $db->prepare("SELECT name, email FROM tf_users WHERE id = ?");
                    $devStmt->execute([$userId]);
                    $dev = $devStmt->fetch();

                    $projStmt = $db->prepare("SELECT name FROM tf_projects WHERE id = ?");
                    $projStmt->execute([$projectId]);
                    $projName = $projStmt->fetchColumn();

                    if ($dev && $projName) {
                        $mgrName = currentUser()['name'];
                        $mgrEmail = currentUser()['email'];
                        $subject = "Added to Project Team: " . $projName;
                        $bodyHTML = "
                            <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                                <h2 style='color: #6366f1;'>Project Team Update</h2>
                                <p>Hello <strong>" . htmlspecialchars($dev['name']) . "</strong>,</p>
                                <p>You have been added as a team member for the project <strong>" . htmlspecialchars($projName) . "</strong>.</p>
                                <p>This action was performed by your manager: <strong>" . htmlspecialchars($mgrName) . "</strong> (" . htmlspecialchars($mgrEmail) . ").</p>
                                <p><a href='" . APP_URL . "/frontend/auth/login.php' style='display:inline-block;padding:10px 20px;background:#6366f1;color:#fff;text-decoration:none;border-radius:5px;'>Log In to SprintDesk</a></p>
                                <hr style='border: none; border-top: 1px solid #eaeaea; margin: 20px 0;'>
                                <p style='font-size: 12px; color: #777;'>Regards,<br>SprintDesk Team</p>
                            </div>
                        ";
                        sendSystemEmail($dev['email'], $subject, $bodyHTML);
                    }

                    header("Location: team.php?project_id=$projectId&ok=1"); exit;
                } catch (Exception $e) { $addErr = 'User is already a member of this project.'; }
            } else { $addErr = 'Invalid project selected.'; }
        } else { $addErr = 'Please select a user.'; }
    }

    // Handle Remove Member
    if (isset($_GET['remove_member']) && isset($_GET['project_id'])) {
        $mid = (int)$_GET['remove_member'];
        $pid = (int)$_GET['project_id'];
        
        $chk = $db->prepare("SELECT name FROM tf_projects WHERE id=? AND manager_id=?");
        $chk->execute([$pid, $uid]);
        $proj = $chk->fetch();
        if ($proj) {
            $projName = $proj['name'];
            $reqChk = $db->prepare("SELECT id FROM tf_member_removal_requests WHERE project_id=? AND user_id=? AND status='pending'");
            $reqChk->execute([$pid, $mid]);
            if (!$reqChk->fetch()) {
                $db->prepare("INSERT INTO tf_member_removal_requests (project_id, user_id, manager_id, status) VALUES (?, ?, ?, 'pending')")->execute([$pid, $mid, $uid]);
                
                $memStmt = $db->prepare("SELECT name, email FROM tf_users WHERE id = ?");
                $memStmt->execute([$mid]);
                $mem = $memStmt->fetch();

                $admins = $db->query("SELECT id, name, email FROM tf_users WHERE role='admin' AND is_active=1")->fetchAll();
                $mgrName = currentUser()['name'];
                $mgrEmail = currentUser()['email'];
                
                if ($mem) {
                    $subject = "Removal Request from Project: " . $projName;
                    $bodyHTML = "
                        <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                            <h2 style='color: #ef4444;'>Project Team Update</h2>
                            <p>Hello <strong>" . htmlspecialchars($mem['name']) . "</strong>,</p>
                            <p>Your manager <strong>" . htmlspecialchars($mgrName) . "</strong> has requested to remove you from the project <strong>" . htmlspecialchars($projName) . "</strong>. This request is currently pending admin approval.</p>
                            <p>You will be notified once the admin makes a decision.</p>
                            <hr style='border: none; border-top: 1px solid #eaeaea; margin: 20px 0;'>
                            <p style='font-size: 12px; color: #777;'>Regards,<br>SprintDesk Team</p>
                        </div>
                    ";
                    sendSystemEmail($mem['email'], $subject, $bodyHTML);
                    notifyUser($mid, "Removal Request", "Manager $mgrName requested to remove you from project $projName.", 'system');
                }

                foreach ($admins as $ad) {
                    $subject = "Action Required: Member Removal Request for " . $projName;
                    $bodyHTML = "
                        <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                            <h2 style='color: #ef4444;'>Member Removal Request</h2>
                            <p>Hello <strong>" . htmlspecialchars($ad['name']) . "</strong>,</p>
                            <p>Manager <strong>" . htmlspecialchars($mgrName) . "</strong> (" . htmlspecialchars($mgrEmail) . ") has requested to remove developer <strong>" . htmlspecialchars($mem['name'] ?? 'Unknown Member') . "</strong> from project <strong>" . htmlspecialchars($projName) . "</strong>.</p>
                            <p>Please review and approve or reject this request from your dashboard.</p>
                            <p><a href='" . APP_URL . "/frontend/admin/removal_requests.php' style='display:inline-block;padding:10px 20px;background:#ef4444;color:#fff;text-decoration:none;border-radius:5px;'>View Requests</a></p>
                            <hr style='border: none; border-top: 1px solid #eaeaea; margin: 20px 0;'>
                            <p style='font-size: 12px; color: #777;'>Regards,<br>SprintDesk Team</p>
                        </div>
                    ";
                    sendSystemEmail($ad['email'], $subject, $bodyHTML);
                    notifyUser($ad['id'], "Removal Request Pending", "Manager $mgrName wants to remove {$mem['name']} from $projName.", 'system');
                }

                logActivity($uid, $pid, null, 'requested to remove member from project', 'project', $mid);
                header("Location: team.php?project_id=$pid&ok=3"); exit;
            } else {
                header("Location: team.php?project_id=$pid&ok=4"); exit;
            }
        }
    }

    // Fetch Projects managed by this manager
    $projects = $db->prepare("SELECT id, name FROM tf_projects WHERE manager_id = ? ORDER BY name ASC");
    $projects->execute([$uid]);
    $projects = $projects->fetchAll();

    // Selected Project
    $selectedProject = (int)($_GET['project_id'] ?? ($projects[0]['id'] ?? 0));

    // Fetch Members of Selected Project
    $members = [];
    $eligibleUsers = [];
    if ($selectedProject) {
        $memQ = $db->prepare("SELECT u.* FROM tf_users u JOIN tf_project_members pm ON u.id = pm.user_id WHERE pm.project_id = ?");
        $memQ->execute([$selectedProject]);
        $members = $memQ->fetchAll();

        // Fetch developers in org NOT in this project
        $eligibleQ = $db->prepare("SELECT * FROM tf_users WHERE org_id = ? AND role = 'developer' AND is_active = 1 AND id NOT IN (SELECT user_id FROM tf_project_members WHERE project_id = ?)");
        $eligibleQ->execute([$orgId, $selectedProject]);
        $eligibleUsers = $eligibleQ->fetchAll();
    }

    if (isset($_GET['ok'])) {
        if ($_GET['ok'] == 1) $okMsg = "✅ Member added to project successfully.";
        if ($_GET['ok'] == 2) $okMsg = "✅ Member removed from project.";
        if ($_GET['ok'] == 3) $okMsg = "✅ Removal requested. Waiting for Admin approval.";
        if ($_GET['ok'] == 4) $okMsg = "⚠️ A removal request is already pending for this member.";
    }
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Project Member Management</div>
                <div>
                     <select class="tf-inp" onchange="window.location.href='team.php?project_id='+this.value" style="width: 220px; background:var(--surface);">
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $p['id'] == $selectedProject ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($selectedProject): ?>
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addModal').classList.add('open')">+ Add Member</button>
                <?php endif; ?>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">Project Members</div>
                        <div class="tf-subtitle">Manage developers assigned to your selected project</div>
                    </div>
                </div>

                <?php if($okMsg): ?><div class="tf-toast-inline" style="margin-bottom:16px"><?= $okMsg ?></div><?php endif; ?>
                <?php if(!empty($addErr)): ?><div class="tf-err" style="margin-bottom:16px">⚠️ <?= e($addErr) ?></div><?php endif; ?>

                <?php if (!$selectedProject): ?>
                    <p style="padding:40px;text-align:center;color:var(--text3);">You are not managing any projects or no project is selected.</p>
                <?php else: ?>
                    <div class="g4 a2">
                        <?php foreach ($members as $m): ?>
                            <div class="card" style="text-align:center;padding:24px">
                                <div class="tf-sb-avatar" style="width:60px;height:60px;margin:0 auto 14px;font-size:20px">
                                    <?= userInitials($m['name']) ?>
                                </div>
                                <div style="font-weight:700;font-size:15px">
                                    <?= e($m['name']) ?>
                                </div>
                                <div style="font-size:12px;color:var(--text3);margin-bottom:16px">
                                    <?= e($m['email']) ?>
                                </div>
                                <div style="display:flex; justify-content:center; gap:8px; align-items:center;">
                                    <span class="badge b-developer">Developer</span>
                                    <a href="javascript:void(0)" onclick="if(confirm('Request removal of this developer from the project?')) window.location.href='team.php?project_id=<?= $selectedProject ?>&remove_member=<?= $m['id'] ?>'" style="color:#ef4444; font-size:12px; text-decoration:none; padding:4px 8px; border-radius:6px; background:rgba(239,68,68,0.1);">Request Removal</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($members)): ?>
                            <p style="padding:20px;text-align:center;color:var(--text3);grid-column:1/-1">No members assigned to this project yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ADD MEMBER MODAL -->
    <?php if ($selectedProject): ?>
    <div class="tf-overlay" id="addModal">
      <div class="tf-modal">
        <div class="tf-modal-hd"><div class="tf-modal-title">Add Member to Project</div><button class="tf-modal-close" onclick="document.getElementById('addModal').classList.remove('open')">✕</button></div>
        <form method="POST">
          <input type="hidden" name="action" value="add_member">
          <input type="hidden" name="project_id" value="<?= $selectedProject ?>">
          <div class="tf-modal-body">
            <div class="tf-fg">
                <label class="tf-lbl">Select Existing User (Developer)</label>
                <select name="user_id" class="tf-inp" required style="background:var(--surface);">
                     <option value="">-- Select Developer --</option>
                     <?php foreach ($eligibleUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
                     <?php endforeach; ?>
                </select>
            </div>
          </div>
          <div class="tf-modal-foot">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Member</button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>
</body>

</html>