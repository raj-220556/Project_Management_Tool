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
$okMsg = '';

// Handle Add Manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        $db->prepare("INSERT INTO tf_organizations (name) VALUES (?)")->execute([$name]);
        $oid = $db->lastInsertId();
        
        $adminEmail = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . "_admin@sprintdesk.com";
        $db->prepare("INSERT INTO tf_users (name, email, password, role, org_id) VALUES (?, ?, ?, 'admin', ?)")->execute([
            $name . ' Admin',
            $adminEmail,
            password_hash('password', PASSWORD_DEFAULT),
            $oid
        ]);
        
        logActivity(currentUser()['id'], null, null, 'registered organization', 'organization', $oid, '', $name);
        notifyUser(currentUser()['id'], 'Org Created', "Organization '$name' was created with admin ($adminEmail).", "organizations.php");
        header("Location: organizations.php?ok=1"); exit;
    }
}

// Handle Approve Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve') {
    $req_id = (int)$_POST['req_id'];
    $stmt = $db->prepare("SELECT * FROM tf_org_requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$req_id]);
    $req = $stmt->fetch();
    
    if ($req) {
        $db->beginTransaction();
        try {
            // Generate org_key
            $org_key = 'ORG-' . strtoupper(bin2hex(random_bytes(4)));
            
            // Insert Organisation
            $insOrg = $db->prepare("INSERT INTO tf_organizations (name, address, domain, org_key) VALUES (?, ?, ?, ?)");
            $insOrg->execute([$req['org_name'], $req['address'], $req['domain'], $org_key]);
            $oid = $db->lastInsertId();
            
            // Insert Admin Users
            $insUser = $db->prepare("INSERT INTO tf_users (name, email, password, role, org_id) VALUES (?, ?, ?, 'admin', ?)");
            
            // Primary Admin
            $insUser->execute([$req['org_name'] . ' Admin', $req['email'], $req['password'], $oid]);

            // Secondary Admin
            if (!empty($req['email_2']) && !empty($req['password_2'])) {
                $insUser->execute([$req['org_name'] . ' Admin 2', $req['email_2'], $req['password_2'], $oid]);
            }

            // Tertiary Admin
            if (!empty($req['email_3']) && !empty($req['password_3'])) {
                $insUser->execute([$req['org_name'] . ' Admin 3', $req['email_3'], $req['password_3'], $oid]);
            }
            
            // Update Request Status
            $db->prepare("UPDATE tf_org_requests SET status = 'approved' WHERE id = ?")->execute([$req_id]);
            
            $db->commit();
            // Send Email Notification
            $loginLink = APP_URL . '/frontend/auth/login.php';
            $subject = "Your Organization has been Approved!";
            $bodyHTML = "<h3>Hello " . htmlspecialchars($req['org_name']) . " Admin,</h3>
<p>Great news! Your request to register the organization <strong>" . htmlspecialchars($req['org_name']) . "</strong> has been approved by the Global Manager.</p>
<div style='background: #f8fafc; padding: 15px; border-radius: 6px; margin: 15px 0;'>
    <p style='margin: 0 0 5px 0;'><strong>Organization ID:</strong> {$oid}</p>
    <p style='margin: 0;'><strong>Organization Key:</strong> {$org_key}</p>
</div>
<p>You can now log in and start managing your workspace.</p>
<p><a href='{$loginLink}' style='display:inline-block;padding:10px 20px;background:#6366f1;color:#fff;text-decoration:none;border-radius:5px;'>Log In to SprintDesk</a></p>";
            sendSystemEmail($req['email'], $subject, $bodyHTML);

            logActivity(currentUser()['id'], null, null, 'approved request', 'organization', $oid, '', $req['org_name']);
            header("Location: organizations.php?ok=3"); exit;
        } catch (Exception $e) {
            $db->rollBack();
            $msg = "Approval failed: " . $e->getMessage();
        }
    }
}

// Handle Reject Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reject') {
    $req_id = (int)$_POST['req_id'];
    $db->prepare("UPDATE tf_org_requests SET status = 'rejected' WHERE id = ?")->execute([$req_id]);
    header("Location: organizations.php?ok=4"); exit;
}

// Handle Delete
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $count = $db->query("SELECT COUNT(*) FROM tf_organizations")->fetchColumn();
    if ($count > 1) {
        $db->beginTransaction();
        try {
            // Get org name for requests cleanup
            $stmt = $db->prepare("SELECT name FROM tf_organizations WHERE id = ?");
            $stmt->execute([$id]);
            $oname = $stmt->fetchColumn();

            // 1. Delete Task Related
            $db->prepare("DELETE FROM tf_comments WHERE task_id IN (SELECT id FROM tf_tasks WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?))")->execute([$id]);
            $db->prepare("DELETE FROM tf_tasks WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?)")->execute([$id]);
            
            // 2. Delete Project Related
            $db->prepare("DELETE FROM tf_sprints WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?)")->execute([$id]);
            $db->prepare("DELETE FROM tf_github_branches WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?)")->execute([$id]);
            $db->prepare("DELETE FROM tf_github_commits WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?)")->execute([$id]);
            $db->prepare("DELETE FROM tf_github_prs WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?)")->execute([$id]);
            $db->prepare("DELETE FROM tf_member_removal_requests WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?)")->execute([$id]);
            $db->prepare("DELETE FROM tf_project_deletion_approvals WHERE request_id IN (SELECT id FROM tf_project_deletion_requests WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?))")->execute([$id]);
            $db->prepare("DELETE FROM tf_project_deletion_requests WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?)")->execute([$id]);
            $db->prepare("DELETE FROM tf_project_members WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id = ?)")->execute([$id]);
            $db->prepare("DELETE FROM tf_projects WHERE org_id = ?")->execute([$id]);
            
            // 3. Delete User Related
            $db->prepare("DELETE FROM tf_notifications WHERE user_id IN (SELECT id FROM tf_users WHERE org_id = ?)")->execute([$id]);
            $db->prepare("DELETE FROM tf_activity WHERE user_id IN (SELECT id FROM tf_users WHERE org_id = ?)")->execute([$id]);
            $db->prepare("DELETE FROM tf_users WHERE org_id = ?")->execute([$id]);
            
            // 4. Delete Organization & Requests
            if ($oname) {
                $db->prepare("DELETE FROM tf_org_requests WHERE org_name = ?")->execute([$oname]);
            }
            $db->prepare("DELETE FROM tf_organizations WHERE id = ?")->execute([$id]);

            $db->commit();
            logActivity(currentUser()['id'], null, null, 'purged organization', 'organization', $id);
            header("Location: organizations.php?ok=2"); exit;
        } catch (Exception $e) {
            $db->rollBack();
            $msg = "Purge failed: " . $e->getMessage();
        }
    } else {
        $msg = "Cannot delete the only organization.";
    }
}

$orgs = $db->query("SELECT o.*, 
    (SELECT COUNT(*) FROM tf_users WHERE org_id = o.id) uc,
    (SELECT COUNT(*) FROM tf_projects WHERE org_id = o.id) pc
    FROM tf_organizations o ORDER BY o.name ASC")->fetchAll();

$requests = $db->query("SELECT * FROM tf_org_requests WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();

if (isset($_GET['ok'])) {
    if ($_GET['ok'] == 1) $okMsg = "✅ Organization added successfully.";
    if ($_GET['ok'] == 2) $okMsg = "✅ Organization removed successfully.";
    if ($_GET['ok'] == 3) $okMsg = "✅ Request approved successfully. Organization and Admin created.";
    if ($_GET['ok'] == 4) $okMsg = "✅ Request rejected.";
}
?>
<style>
.tf-page { max-width: 1200px; margin: 0 auto; }
.page-header-title { font-size: 34px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 8px; color: var(--text); }
.page-header-sub { color: var(--text3); font-size: 15px; margin-bottom: 24px;}

/* HORIZONTAL GRIDS */
.prem-card-grid-horiz { 
    display: flex; 
    overflow-x: auto; 
    gap: 24px; 
    padding: 12px 4px 24px; 
    scrollbar-width: thin; 
    scroll-snap-type: x mandatory;
}
.prem-card-grid-horiz::-webkit-scrollbar { height: 6px; }
.prem-card-grid-horiz::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 3px; }

.prem-card { 
    background: var(--surface); 
    border: 1px solid var(--border); 
    border-radius: 16px; 
    padding: 24px; 
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
    position: relative; 
    overflow: hidden; 
    display: flex; 
    flex-direction: column; 
    flex: 0 0 340px; 
    scroll-snap-align: start;
}
.prem-card:hover { transform: translateY(-4px); border-color: var(--brand); box-shadow: var(--sh-md); background: var(--surface2); }
.prem-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--brand); opacity: 0; transition: opacity 0.3s; }
.prem-card:hover::before { opacity: 1; }
.prem-card-title { font-size: 20px; font-weight: 700; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: flex-start; }
.prem-card-date { font-size: 12px; color: var(--text3); margin-bottom: 16px; }
.prem-card-pills { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.prem-pill { display: inline-flex; align-items: center; gap: 6px; background: rgba(99,102,241,0.05); padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; color: var(--text2); border: 1px solid rgba(99,102,241,0.05); }

.prem-details { font-size: 13px; color: var(--text2); margin-bottom: 16px; flex: 1; }
.prem-details-item { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.prem-details-item span { color: var(--text3); width: 60px; font-weight: 500; }

.prem-card-foot { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid var(--border); margin-top: auto; }

.section-title { font-size: 20px; font-weight: 800; margin: 32px 0 16px; display: flex; align-items: center; gap: 10px; }
.section-badge { background: var(--brand); color: #fff; font-size: 12px; padding: 2px 8px; border-radius: 12px; }

/* Dashboard layout */
.tf-topbar { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border); }
.tf-topbar-title { font-size: 18px; font-weight: 700; }
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
      <div style="padding: 24px 0 0;">
        <h1 class="page-header-title">Dashboard</h1>
        <p class="page-header-sub">Manage registered organizations and new registration requests</p>
      </div>

      <?php if($okMsg): ?><div class="tf-toast-inline" style="margin-bottom:20px;"><?= $okMsg ?></div><?php endif; ?>
      <?php if($msg): ?><div class="tf-err" style="margin-bottom:20px;">⚠️ <?= e($msg) ?></div><?php endif; ?>

      <!-- NEW REQUESTS SECTION -->
      <?php if (!empty($requests)): ?>
      <div class="section-title">
        <span>🆕 Pending Company Requests</span>
        <span class="section-badge"><?= count($requests) ?></span>
      </div>
      <div class="prem-card-grid-horiz" style="background: rgba(99,102,241,0.03); border-radius: 16px; padding: 16px; border: 1px dashed var(--brand);">
        <?php foreach($requests as $r): ?>
        <div class="prem-card" style="border-color: rgba(99,102,241,0.3)">
          <div>
            <div class="prem-card-title">
                <?= e($r['org_name']) ?>
                <span style="font-size:16px;background:rgba(99,102,241,0.1);color:#818cf8;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">📋</span>
            </div>
            <div class="prem-card-date">Requested on <?= date('M j, Y H:i', strtotime($r['created_at'])) ?></div>
            
            <div class="prem-details">
                <div class="prem-details-item"><span>Email:</span> <?= e($r['email']) ?></div>
                <div class="prem-details-item"><span>Domain:</span> <?= e($r['domain'] ?: '-') ?></div>
                <div class="prem-details-item"><span>Address:</span> <?= e($r['address'] ?: '-') ?></div>
            </div>
          </div>
          <div class="prem-card-foot" style="gap:10px;">
            <form method="POST" style="flex:1;">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-primary btn-sm" style="width:100%; justify-content:center;">Approve</button>
            </form>
            <form method="POST" style="flex:1;">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-secondary btn-sm" style="width:100%; justify-content:center; border-color:#ef4444; color:#ef4444;">Reject</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- ALL ORGANIZATIONS SECTION -->
      <div class="section-title">🏢 All Organizations</div>
      <div class="prem-card-grid-horiz">
        <?php foreach($orgs as $o): ?>
        <div class="prem-card">
          <div>
            <div class="prem-card-title">
                <?= e($o['name']) ?>
                <span style="font-size:16px;background:rgba(99,102,241,0.1);color:#818cf8;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">🏢</span>
            </div>
            <div class="prem-card-date">Created on <?= date('M j, Y', strtotime($o['created_at'])) ?></div>
            
            <div class="prem-details">
                <div class="prem-details-item"><span>Domain:</span> <?= e($o['domain'] ?: '-') ?></div>
                <div class="prem-details-item"><span>Key:</span> <code style="background:var(--surface2);padding:2px 4px;border-radius:4px;font-size:11px;"><?= e($o['org_key'] ?: 'N/A') ?></code></div>
            </div>

            <div class="prem-card-pills">
              <div class="prem-pill">👥 <?= $o['uc'] ?> Users</div>
              <div class="prem-pill">📁 <?= $o['pc'] ?> Projects</div>
            </div>
          </div>
          <div class="prem-card-foot">
            <a href="dashboard.php?org_id=<?= $o['id'] ?>" class="btn btn-secondary btn-sm" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">Stats →</a>
            <?php if(count($orgs) > 1): ?>
            <a href="javascript:void(0)" style="color:#ef4444;font-size:13px;text-decoration:none;" onclick="if(confirm('Delete this organization? All projects and users will be completely erased.')) window.location.href='?del=<?= $o['id'] ?>'">Delete</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<!-- Modal for Manual Add -->
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

<script>
// Toggle modal
document.querySelectorAll('.tf-modal-close').forEach(b => {
    b.addEventListener('click', () => b.closest('.tf-overlay').classList.remove('open'));
});
</script>

</body></html>
