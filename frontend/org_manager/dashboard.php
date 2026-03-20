<?php
require_once __DIR__ . '/../../backend/shared/includes/init.php';
requireLogin('org_manager');
$activePage = 'dashboard';
$db = db();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= getUserTheme() ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Global Dashboard · SprintDesk</title>
<?php
// Get list of organizations
$orgs = $db->query("SELECT * FROM tf_organizations ORDER BY name")->fetchAll();

// Select organization for filtering
$selectedOrgId = (int)($_GET['org_id'] ?? 0);

if ($selectedOrgId) {
    // Current org stats
    $stats = [
        'users'    => $db->prepare("SELECT COUNT(*) FROM tf_users WHERE org_id = ?")->execute([$selectedOrgId]) ? $db->prepare("SELECT COUNT(*) FROM tf_users WHERE org_id = ?")->execute([$selectedOrgId]) : 0, // Wait, fix PDO execution
    ];
    // Better way to get counts:
    $uCount = $db->prepare("SELECT COUNT(*) FROM tf_users WHERE org_id = ?"); $uCount->execute([$selectedOrgId]); $uCount = $uCount->fetchColumn();
    $pCount = $db->prepare("SELECT COUNT(*) FROM tf_projects WHERE org_id = ?"); $pCount->execute([$selectedOrgId]); $pCount = $pCount->fetchColumn();
    $tCount = $db->prepare("SELECT COUNT(*) FROM tf_tasks t JOIN tf_projects p ON t.project_id = p.id WHERE p.org_id = ?"); $tCount->execute([$selectedOrgId]); $tCount = $tCount->fetchColumn();
    $sCount = $db->prepare("SELECT COUNT(*) FROM tf_sprints s JOIN tf_projects p ON s.project_id = p.id WHERE p.org_id = ?"); $sCount->execute([$selectedOrgId]); $sCount = $sCount->fetchColumn();
} else {
    // Global stats
    $uCount = $db->query("SELECT COUNT(*) FROM tf_users WHERE role != 'org_manager' AND org_id IN (SELECT id FROM tf_organizations)")->fetchColumn();
    $pCount = $db->query("SELECT COUNT(*) FROM tf_projects WHERE org_id IN (SELECT id FROM tf_organizations)")->fetchColumn();
    $tCount = $db->query("SELECT COUNT(*) FROM tf_tasks WHERE project_id IN (SELECT id FROM tf_projects WHERE org_id IN (SELECT id FROM tf_organizations))")->fetchColumn();
    $oCount = count($orgs);
}
?>
<style>
.tf-page { max-width: 1200px; margin: 0 auto; }
.page-header-title { font-size: 36px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 6px; color: var(--text); }
.page-header-sub { color: var(--text3); font-size: 15px; margin-bottom: 30px; }
.prem-stat { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); backdrop-filter: blur(10px); display: flex; align-items: center; gap: 20px; transition: transform 0.3s; }
.prem-stat:hover { transform: translateY(-5px); border-color: rgba(99,102,241,0.3); background: rgba(255,255,255,0.04); }
.prem-stat-icon { width: 56px; height: 56px; border-radius: 14px; background: var(--surface2); color: var(--brand); display: flex; align-items: center; justify-content: center; font-size: 24px; }
.prem-stat-val { font-size: 32px; font-weight: 700; line-height: 1; margin-bottom: 6px; color: var(--text); }
.prem-stat-lbl { font-size: 13px; color: var(--text3); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
.prem-card-wrap { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; overflow: hidden; }

.cmd-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
.cmd-card { background: rgba(99,102,241,0.05); border: 1px solid rgba(99,102,241,0.2); border-radius: 16px; padding: 28px 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none; color: var(--text); position: relative; overflow: hidden; }
.cmd-card:hover { transform: translateY(-6px); border-color: #818cf8; background: rgba(99,102,241,0.1); box-shadow: 0 12px 30px rgba(0,0,0,0.3); }
.cmd-icon { width: 64px; height: 64px; border-radius: 20px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 16px; color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.cmd-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
.cmd-desc { font-size: 13px; color: var(--text3); line-height: 1.5; }
</style>
</head>
<body data-theme="<?= getUserTheme() ?>">
<div id="tf-curtain"></div>
<div class="tf-wrap">
  <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
  <div class="tf-main">
    <div class="tf-topbar">
      <div class="tf-topbar-title">Global View</div>
      <form method="GET" style="display:flex;align-items:center;gap:12px">
        <label style="font-size:13px;font-weight:600;color:var(--text3)">Filter Organization:</label>
        <select name="org_id" class="tf-inp tf-sel" style="width:240px" onchange="this.form.submit()">
            <option value="0">All Organizations (Combined)</option>
            <?php foreach($orgs as $o): ?>
            <option value="<?= $o['id'] ?>" <?= $o['id']==$selectedOrgId?'selected':'' ?>><?= e($o['name']) ?></option>
            <?php endforeach; ?>
        </select>
      </form>
    </div>
    <div class="tf-page">
      <div class="a1">
        <div>
          <h1 class="page-header-title"><?= $selectedOrgId ? 'Organization Stats' : 'Global Application Stats' ?></h1>
          <p class="page-header-sub"><?= $selectedOrgId ? 'Overview for selected organization' : 'Overview across all registered organizations' ?></p>
        </div>
      </div>

      <div class="tf-stats-grid a2" style="margin-bottom: 32px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px;">
        <div class="prem-stat">
          <div class="prem-stat-icon">👥</div>
          <div><div class="prem-stat-val"><?= $uCount ?></div><div class="prem-stat-lbl">Total Users</div></div>
        </div>
        <div class="prem-stat">
          <div class="prem-stat-icon">📁</div>
          <div><div class="prem-stat-val"><?= $pCount ?></div><div class="prem-stat-lbl">Total Projects</div></div>
        </div>
        <div class="prem-stat">
          <div class="prem-stat-icon">📋</div>
          <div><div class="prem-stat-val"><?= $tCount ?></div><div class="prem-stat-lbl">Total Tasks</div></div>
        </div>
        <?php if(!$selectedOrgId): ?>
        <div class="prem-stat">
          <div class="prem-stat-icon">🏢</div>
          <div><div class="prem-stat-val"><?= $oCount ?></div><div class="prem-stat-lbl">Organizations</div></div>
        </div>
        <?php else: ?>
        <div class="prem-stat">
          <div class="prem-stat-icon">🔥</div>
          <div><div class="prem-stat-val"><?= $sCount ?></div><div class="prem-stat-lbl">Active Sprints</div></div>
        </div>
        <?php endif; ?>
      </div>

      <div class="cmd-grid a3">
        <a href="organizations.php" class="cmd-card">
          <div class="cmd-icon">🏢</div>
          <div class="cmd-title">Manage Organizations</div>
          <div class="cmd-desc">Add new client organizations and configure their isolated workspaces.</div>
        </a>
        <a href="reports.php" class="cmd-card">
          <div class="cmd-icon">📊</div>
          <div class="cmd-title">System Reports</div>
          <div class="cmd-desc">Generate advanced analytics and resource usage reports across all tenants.</div>
        </a>
        <a href="settings.php" class="cmd-card">
          <div class="cmd-icon">⚙️</div>
          <div class="cmd-title">Global Settings</div>
          <div class="cmd-desc">Configure top-level system preferences, integrations, and branding.</div>
        </a>
      </div>

      <div class="prem-card-wrap a4">
        <div class="tf-card-hd" style="padding: 24px 24px 0;">
          <div class="tf-card-title" style="font-family:'Outfit',sans-serif;font-size:20px;">Recent Organizations</div>
        </div>
        <div class="tf-card-body" style="padding: 24px;">
          <table class="tf-table" style="width:100%;text-align:left;border-collapse:collapse;">
            <thead><tr style="border-bottom:1px solid rgba(255,255,255,0.1);"><th style="padding-bottom:12px;color:var(--text3);">Organization Name</th><th style="padding-bottom:12px;color:var(--text3);">Created</th><th style="padding-bottom:12px;color:var(--text3);">Projects</th></tr></thead>
            <tbody>
              <?php 
              $recentOrgs = $db->query("SELECT o.*, (SELECT COUNT(*) FROM tf_projects WHERE org_id=o.id) pc FROM tf_organizations o ORDER BY o.created_at DESC LIMIT 5")->fetchAll();
              foreach($recentOrgs as $ro): ?>
              <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                <td style="padding:16px 0;"><strong><?= e($ro['name']) ?></strong></td>
                <td style="padding:16px 0;"><?= date('M j, Y', strtotime($ro['created_at'])) ?></td>
                <td style="padding:16px 0;"><span style="background:rgba(255,255,255,0.1);padding:4px 10px;border-radius:20px;font-size:12px;"><?= $ro['pc'] ?> Projects</span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</body></html>
