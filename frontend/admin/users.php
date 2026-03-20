<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Users · SprintDesk</title>
<?php
require_once __DIR__ . '/../../backend/shared/includes/init.php';
requireLogin('admin');
$activePage = 'users';

// Handle add manager
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    $name  = trim($_POST['name']??'');
    $email = trim($_POST['email']??'');
    $pass  = $_POST['password']??'';
    $orgKeyInp = trim($_POST['org_key']??'');
    $role  = 'manager'; // STRICT HIERARCHY: Admins can only create managers
    
    // Fetch live org key
    $okQ = db()->prepare("SELECT org_key FROM tf_organizations WHERE id=?");
    $okQ->execute([currentUser()['org_id']]);
    $realKey = $okQ->fetchColumn();

    if ($name && $email && $pass && $orgKeyInp) {
        if (!$realKey) {
            $addErr = 'Organization Key not configured! Please configure it in Settings first.';
        } elseif ($orgKeyInp !== $realKey) {
            $addErr = 'Invalid Organization Key. You are not authorized to provision users.';
        } else {
            try {
                db()->prepare('INSERT INTO tf_users(name,email,password,role,org_id) VALUES(?,?,?,?,?)')->execute([
                    $name,
                    $email,
                    password_hash($pass, PASSWORD_DEFAULT),
                    $role,
                    currentUser()['org_id']
                ]);
                $newUid = db()->lastInsertId();
                logActivity(currentUser()['id'], null, null, 'created a new manager', 'user', $newUid, '', $name);
                notifyUser($newUid, 'Welcome!', 'Your account has been created.', 'dashboard.php');
                notifyUser(currentUser()['id'], 'User Added', "User '$name' created.", 'users.php');
                header('Location: users.php?ok=1&celebrate=1');
                exit;
            } catch (Exception $e) {
                $addErr = 'Email already exists.';
            }
        }
    } else { $addErr = 'Please fill in all fields including the Organization Key.'; }
}
// Handle toggle active
if (isset($_GET['toggle'])) {
    $tid = (int) $_GET['toggle'];
    $orgId = currentUser()['org_id'];
    $st = ($u['is_active'] ?? 0) ? 'deactivated' : 'activated';
    db()->prepare('UPDATE tf_users SET is_active = NOT is_active WHERE id=? AND id!=? AND org_id=?')->execute([$tid, currentUser()['id'], $orgId]);
    logActivity(currentUser()['id'], null, null, $st . ' a user account', 'user', $tid);
    header('Location: users.php');
    exit;
}

// Handle delete
if (isset($_GET['del'])) {
    $did = (int) $_GET['del'];
    if ($did != currentUser()['id']) {
        $orgId = currentUser()['org_id'];
        // Manual Cascade to prevent foreign key errors and clean DB
        db()->prepare('DELETE FROM tf_notifications WHERE user_id=?')->execute([$did]);
        db()->prepare('DELETE FROM tf_activity WHERE user_id=?')->execute([$did]);
        db()->prepare('UPDATE tf_projects SET manager_id=NULL WHERE manager_id=?')->execute([$did]);
        db()->prepare('UPDATE tf_tasks SET assigned_to=NULL WHERE assigned_to=?')->execute([$did]);
        // Final wipe
        db()->prepare('DELETE FROM tf_users WHERE id=? AND org_id=?')->execute([$did, $orgId]);
        logActivity(currentUser()['id'], null, null, 'permanently deleted', 'user', $did);
        header('Location: users.php?ok=2');
        exit;
    }
}

$users = db()->prepare("SELECT * FROM tf_users WHERE org_id=? ORDER BY created_at DESC");
$users->execute([currentUser()['org_id']]);
$users = $users->fetchAll();
?>
</head>
<body data-theme="<?= getUserTheme() ?>">
<div id="tf-curtain"></div>
<div class="tf-wrap">
<?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
<div class="tf-main">
  <div class="tf-topbar">
    <div class="tf-topbar-title">User Management</div>
    <div class="tf-search"><span>🔍</span><input type="text" placeholder="Search users..." id="searchInp"></div>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addModal').classList.add('open')">+ Add User</button>
  </div>
  <div class="tf-page">
    <div class="tf-page-hd a1">
      <div><div class="tf-title">Users</div><div class="tf-subtitle"><?= count($users) ?> total accounts</div></div>
    </div>
    <?php if(isset($_GET['ok']) && $_GET['ok']==1): ?><div style="background:rgba(0,200,150,.1);border:1px solid rgba(0,200,150,.2);border-radius:10px;padding:12px 16px;font-size:13px;color:var(--brand);margin-bottom:16px">✅ User created successfully.</div><?php endif; ?>
    <?php if(isset($_GET['ok']) && $_GET['ok']==2): ?><div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;font-size:13px;color:#ef4444;margin-bottom:16px">✅ User permanently deleted.</div><?php endif; ?>
    <?php if(!empty($addErr)): ?><div class="tf-err">⚠️ <?= e($addErr) ?></div><?php endif; ?>
    <div class="card a2">
      <div class="tf-tbl-wrap" style="border:none">
        <table id="usersTable">
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($users as $u): ?>
          <tr>
            <td><div style="display:flex;align-items:center;gap:10px">
              <div class="tf-sb-avatar" style="width:30px;height:30px;font-size:11px;flex-shrink:0"><?= e(userInitials($u['name'])) ?></div>
              <span style="font-weight:500"><?= e($u['name']) ?></span>
            </div></td>
            <td style="color:var(--text2)"><?= e($u['email']) ?></td>
            <td><span class="badge b-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
            <td><span class="badge <?= $u['is_active'] ? 'b-done' : 'b-todo' ?>"><?= $u['is_active'] ? '● Active' : '○ Inactive' ?></span></td>
            <td style="color:var(--text3);font-size:12px"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td>
              <?php if($u['id'] != currentUser()['id']): ?>
              <div style="display:flex; gap:6px;">
                <a href="users.php?toggle=<?= $u['id'] ?>" class="btn btn-secondary btn-sm"><?= $u['is_active']?'Deactivate':'Activate' ?></a>
                <a href="javascript:void(0)" onclick="confirmDelete('Permanently delete this user? Their email will become available and their tasks will become unassigned.', () => window.location.href='users.php?del=<?= $u['id'] ?>')" class="btn btn-secondary btn-sm" style="color:#ef4444; border-color:rgba(239,68,68,0.2); background:rgba(239,68,68,0.05)">Delete</a>
              </div>
              <?php endif; ?>
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

<!-- ADD MODAL -->
<div class="tf-overlay" id="addModal">
  <div class="tf-modal">
    <div class="tf-modal-hd"><div class="tf-modal-title">Add New User</div><button class="tf-modal-close">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="tf-modal-body">
        <div class="tf-fg"><label class="tf-lbl">Full Name</label><input type="text" name="name" class="tf-inp" required placeholder="John Doe"></div>
        <div class="tf-fg"><label class="tf-lbl">Email</label><input type="email" name="email" class="tf-inp" required placeholder="john@example.com"></div>
        <div class="tf-fg"><label class="tf-lbl">Password</label><input type="password" name="password" class="tf-inp" required placeholder="min 8 characters"></div>
        <div class="tf-fg"><label class="tf-lbl" style="color:var(--brand)">Organization Key</label><input type="text" name="org_key" class="tf-inp" required placeholder="Required Security Passcode"></div>
        <div class="tf-fg"><label class="tf-lbl">Role</label>
          <input type="text" class="tf-inp" value="Manager" disabled style="opacity:0.7; cursor:not-allowed;">
          <input type="hidden" name="role" value="manager">
        </div>
      </div>
      <div class="tf-modal-foot"><button type="button" class="btn btn-secondary" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Create Manager</button></div>
    </form>
  </div>
</div>
<script>
document.getElementById('searchInp')?.addEventListener('input', function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll('#usersTable tbody tr').forEach(r=>{
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body></html>
