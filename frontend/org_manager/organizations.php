<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manual Organizations · SprintDesk</title>
<?php
require_once __DIR__ . '/../../backend/shared/includes/init.php';
requireLogin('org_manager');
$activePage = 'orgs';
$db = db();

$msg = '';
// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        $db->prepare("INSERT INTO tf_organizations (name) VALUES (?)")->execute([$name]);
        $oid = $db->lastInsertId();
        
        // Auto-generate a root Admin for this new Organization so it is immediately accessible
        $adminEmail = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . "_admin@sprintdesk.com";
        $db->prepare("INSERT INTO tf_users (name, email, password, role, org_id) VALUES (?, ?, ?, 'admin', ?)")->execute([
            $name . ' Admin',
            $adminEmail,
            password_hash('password', PASSWORD_DEFAULT),
            $oid
        ]);
        
        logActivity(currentUser()['id'], null, null, 'registered organization', 'organization', $oid, '', $name);
        notifyUser(currentUser()['id'], 'Org Created', "Organization '$name' was created with admin ($adminEmail).", "organizations.php");
        header("Location: organizations.php?ok=1&celebrate=1"); exit;
    }
}

// Handle Delete
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    // Check if it's the only org
    $count = $db->query("SELECT COUNT(*) FROM tf_organizations")->fetchColumn();
    if ($count > 1) {
        $db->prepare("DELETE FROM tf_tasks WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?)")->execute([$id]);
        $db->prepare("DELETE FROM tf_sprints WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?)")->execute([$id]);
        $db->prepare("DELETE FROM tf_projects WHERE org_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM tf_notifications WHERE user_id IN (SELECT id FROM tf_users WHERE org_id = ?)")->execute([$id]);
        $db->prepare("DELETE FROM tf_activity WHERE user_id IN (SELECT id FROM tf_users WHERE org_id = ?)")->execute([$id]);
        $db->prepare("DELETE FROM tf_users WHERE org_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM tf_organizations WHERE id = ?")->execute([$id]);
        logActivity(currentUser()['id'], null, null, 'purged organization', 'organization', $id);
        notifyUser(currentUser()['id'], 'System Alert', "Organization #$id completely purged from system.", "dashboard.php");
        header("Location: organizations.php?ok=2"); exit;
    } else {
        $msg = "Cannot delete the only organization.";
    }
}

$orgs = $db->query("SELECT o.*, 
    (SELECT COUNT(*) FROM tf_users WHERE org_id = o.id) uc,
    (SELECT COUNT(*) FROM tf_projects WHERE org_id = o.id) pc
    FROM tf_organizations o ORDER BY o.name ASC")->fetchAll();
?>
<style>
.tf-page { max-width: 1100px; margin: 0 auto; }
.page-header-title { font-size: 34px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 8px; color: var(--text); }
.page-header-sub { color: var(--text3); font-size: 15px; margin-bottom: 24px;}
.prem-card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
.prem-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; display: flex; flex-direction: column; }
.prem-card:hover { transform: translateY(-4px); border-color: var(--brand); box-shadow: var(--sh-md); background: var(--surface2); }
.prem-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--brand); opacity: 0; transition: opacity 0.3s; }
.prem-card:hover::before { opacity: 1; }
.prem-card-title { font-size: 20px; font-weight: 700; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: flex-start; }
.prem-card-date { font-size: 12px; color: var(--text3); margin-bottom: 16px; }
.prem-card-pills { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.prem-pill { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.05); padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; color: var(--text2); border: 1px solid rgba(255,255,255,0.05); }
.prem-card-foot { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.05); margin-top: auto; }
</style>
</head>
<body data-theme="<?= getUserTheme() ?>">
<div id="tf-curtain"></div>
<div class="tf-wrap">
  <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
  <div class="tf-main">
    <div class="tf-topbar">
      <div class="tf-topbar-title">Organization Management</div>
      <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')">+ New Organization</button>
    </div>
    <div class="tf-page">
      <div class="a1">
        <div>
          <h1 class="page-header-title">All Organizations</h1>
          <p class="page-header-sub">Manage registered organizations and their resources</p>
        </div>
      </div>

      <?php if(isset($_GET['ok']) && $_GET['ok'] == 1): ?><div class="tf-toast-inline" style="margin-bottom:20px;">✅ Organization added successfully.</div><?php endif; ?>
      <?php if(isset($_GET['ok']) && $_GET['ok'] == 2): ?><div class="tf-toast-inline" style="margin-bottom:20px;">✅ Organization removed successfully.</div><?php endif; ?>
      <?php if($msg): ?><div class="tf-err" style="margin-bottom:20px;">⚠️ <?= e($msg) ?></div><?php endif; ?>

      <div class="prem-card-grid a2">
        <?php foreach($orgs as $o): ?>
        <div class="prem-card">
          <div>
            <div class="prem-card-title">
                <?= e($o['name']) ?>
                <span style="font-size:16px;background:rgba(99,102,241,0.1);color:#818cf8;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">🏢</span>
            </div>
            <div class="prem-card-date">Created on <?= date('M j, Y', strtotime($o['created_at'])) ?></div>
            <div class="prem-card-pills">
              <div class="prem-pill">👥 <?= $o['uc'] ?> Users</div>
              <div class="prem-pill">📁 <?= $o['pc'] ?> Projects</div>
            </div>
          </div>
          <div class="prem-card-foot">
            <a href="dashboard.php?org_id=<?= $o['id'] ?>" class="btn btn-secondary btn-sm" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">View Stats →</a>
            <?php if(count($orgs) > 1): ?>
            <a href="javascript:void(0)" class="tf-pc-link" style="color:#ef4444;font-size:13px;text-decoration:none;" onclick="confirmDelete('Delete this organization? All projects and users will be completely erased.', () => window.location.href='?del=<?= $o['id'] ?>')">Delete</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="tf-overlay" id="addModal">
  <div class="tf-modal">
    <div class="tf-modal-hd"><div class="tf-modal-title">Add Organization</div><button class="tf-modal-close" onclick="document.getElementById('addModal').classList.remove('open')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="tf-modal-body">
        <div class="tf-fg"><label class="tf-lbl">Organization Name</label><input type="text" name="name" class="tf-inp" required placeholder="Acme Corp"></div>
      </div>
      <div class="tf-modal-foot">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Organization</button>
      </div>
    </form>
  </div>
</div>
</body></html>
