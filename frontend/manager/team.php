<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Team · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('manager');
    $activePage = 'team';
    $db = db();
    $uid = currentUser()['id'];
    $orgId = currentUser()['org_id'];

    // STRICT HIERARCHY: Managers can only create users (developers)
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
        $name  = trim($_POST['name']??'');
        $email = trim($_POST['email']??'');
        $pass  = $_POST['password']??'';
        $orgKeyInp = trim($_POST['org_key']??'');
        $role  = 'developer'; // Database role for Users
        
        $okQ = db()->prepare("SELECT org_key FROM tf_organizations WHERE id=?");
        $okQ->execute([$orgId]);
        $realKey = $okQ->fetchColumn();

        if ($name && $email && $pass && $orgKeyInp) {
            if (!$realKey) {
                $addErr = 'Organization Key not configured! Please ask your Admin to set it in Settings.';
            } elseif ($orgKeyInp !== $realKey) {
                $addErr = 'Invalid Organization Key. You are not authorized to provision users.';
            } else {
                try {
                    $db->prepare('INSERT INTO tf_users(name,email,password,role,org_id) VALUES(?,?,?,?,?)')->execute([
                        $name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $orgId
                    ]);
                    $newUid = $db->lastInsertId();
                    logActivity($uid, null, null, 'added developer', 'user', $newUid, '', $name);
                    notifyUser($newUid, 'Welcome!', 'Your account has been created.', 'dashboard.php');
                    notifyUser($uid, 'User Added', "User '$name' successfully joined your team.", 'team.php');
                    header('Location: team.php?ok=1&celebrate=1');
                    exit;
                } catch (Exception $e) {
                    $addErr = 'Email already exists.';
                }
            }
        } else { $addErr = 'Please fill in all fields including the Organization Key.'; }
    }

    // Handle Delete
    if (isset($_GET['del'])) {
        $did = (int) $_GET['del'];
        if ($did != currentUser()['id']) {
            $chk = $db->prepare("SELECT id FROM tf_users WHERE id=? AND org_id=? AND role='developer'");
            $chk->execute([$did, $orgId]);
            if ($chk->fetch()) {
                db()->prepare('DELETE FROM tf_notifications WHERE user_id=?')->execute([$did]);
                db()->prepare('DELETE FROM tf_activity WHERE user_id=?')->execute([$did]);
                db()->prepare('UPDATE tf_tasks SET assigned_to=NULL WHERE assigned_to=?')->execute([$did]);
                db()->prepare('DELETE FROM tf_users WHERE id=?')->execute([$did]);
                logActivity($uid, null, null, 'removed developer', 'user', $did);
                
                notifyUser($uid, 'User Deleted', "A developer was permanently removed from your team.", 'team.php');
                header('Location: team.php?ok=2');
                exit;
            }
        }
    }

    // Get all developers in the current organization
    $orgId = currentUser()['org_id'];
    $teamQ = $db->prepare("SELECT * FROM tf_users WHERE org_id=? AND role='developer' AND is_active=1");
    $teamQ->execute([$orgId]);
    $team = $teamQ->fetchAll();
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Team Management</div>
                <button class="btn btn-primary btn-sm" onclick="document.getElementById('addModal').classList.add('open')">+ Add User</button>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">Development Team</div>
                        <div class="tf-subtitle">View and manage users available for your projects</div>
                    </div>
                </div>

                <?php if(isset($_GET['ok']) && $_GET['ok']==1): ?><div class="tf-toast-inline" style="margin-bottom:16px">✅ User created successfully.</div><?php endif; ?>
                <?php if(isset($_GET['ok']) && $_GET['ok']==2): ?><div class="tf-toast-inline" style="margin-bottom:16px; background:rgba(239,68,68,0.1); color:#ef4444; border-color:rgba(239,68,68,0.2);">✅ User permanently deleted.</div><?php endif; ?>
                <?php if(!empty($addErr)): ?><div class="tf-err" style="margin-bottom:16px">⚠️ <?= e($addErr) ?></div><?php endif; ?>

                <div class="g4 a2">
                    <?php foreach ($team as $m): ?>
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
                                <span class="badge b-developer">User</span>
                                <a href="javascript:void(0)" onclick="confirmDelete('Are you sure you want to permanently delete this user? Their assigned tasks will become unassigned.', () => window.location.href='team.php?del=<?= $m['id'] ?>')" style="color:#ef4444; font-size:12px; text-decoration:none; padding:4px 8px; border-radius:6px; background:rgba(239,68,68,0.1);">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div class="tf-overlay" id="addModal">
      <div class="tf-modal">
        <div class="tf-modal-hd"><div class="tf-modal-title">Add New User</div><button class="tf-modal-close" onclick="document.getElementById('addModal').classList.remove('open')">✕</button></div>
        <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="tf-modal-body">
            <div class="tf-fg"><label class="tf-lbl">Full Name</label><input type="text" name="name" class="tf-inp" required placeholder="John Doe"></div>
            <div class="tf-fg"><label class="tf-lbl">Email</label><input type="email" name="email" class="tf-inp" required placeholder="john@example.com"></div>
            <div class="tf-fg"><label class="tf-lbl">Password</label><input type="password" name="password" class="tf-inp" required placeholder="min 8 characters"></div>
            <div class="tf-fg"><label class="tf-lbl" style="color:var(--brand)">Organization Key</label><input type="text" name="org_key" class="tf-inp" required placeholder="Required Security Passcode"></div>
            <div class="tf-fg"><label class="tf-lbl">Role</label>
              <input type="text" class="tf-inp" value="User" disabled style="opacity:0.7; cursor:not-allowed;">
              <input type="hidden" name="role" value="developer">
            </div>
          </div>
          <div class="tf-modal-foot"><button type="button" class="btn btn-secondary" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Create User</button></div>
        </form>
      </div>
    </div>
</body>

</html>