<?php
require_once __DIR__ . '/../../backend/shared/includes/init.php';
requireLogin('admin');
$activePage = 'dashboard';
$db = db();
$orgId = currentUser()['org_id'];

$totalUsers = $db->prepare("SELECT COUNT(*) FROM tf_users WHERE org_id=? AND is_active=1");
$totalUsers->execute([$orgId]); $totalUsers = $totalUsers->fetchColumn();

$totalProjects = $db->prepare("SELECT COUNT(*) FROM tf_projects WHERE org_id=?");
$totalProjects->execute([$orgId]); $totalProjects = $totalProjects->fetchColumn();

$totalTasks = $db->prepare("SELECT COUNT(*) FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id WHERE p.org_id=?");
$totalTasks->execute([$orgId]); $totalTasks = $totalTasks->fetchColumn();

$doneTasks = $db->prepare("SELECT COUNT(*) FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id WHERE p.org_id=? AND t.status='done'");
$doneTasks->execute([$orgId]); $doneTasks = $doneTasks->fetchColumn();

$activeSprints = $db->prepare("SELECT COUNT(*) FROM tf_sprints s JOIN tf_projects p ON s.project_id=p.id WHERE p.org_id=? AND s.status='active'");
$activeSprints->execute([$orgId]); $activeSprints = $activeSprints->fetchColumn();

$openBugs = $db->prepare("SELECT COUNT(*) FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id WHERE p.org_id=? AND t.type='bug' AND t.status!='done'");
$openBugs->execute([$orgId]); $openBugs = $openBugs->fetchColumn();

$tasksByStatusQ = $db->prepare("SELECT t.status,COUNT(*) c FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id WHERE p.org_id=? GROUP BY t.status");
$tasksByStatusQ->execute([$orgId]); $tasksByStatus = $tasksByStatusQ->fetchAll(PDO::FETCH_KEY_PAIR);

getGitHubActivity(); // Sync commits
$recentAct = $db->prepare("SELECT a.*, u.name uname FROM tf_activity a LEFT JOIN tf_users u ON a.user_id=u.id LEFT JOIN tf_projects p ON a.project_id=p.id WHERE (u.org_id=? OR p.org_id=?) ORDER BY a.created_at DESC LIMIT 8");
$recentAct->execute([$orgId, $orgId]); $recentAct = $recentAct->fetchAll();

$projects = $db->prepare("SELECT p.*,u.name mname,COUNT(DISTINCT t.id) tc,SUM(t.status='done') dc FROM tf_projects p LEFT JOIN tf_users u ON p.manager_id=u.id LEFT JOIN tf_tasks t ON t.project_id=p.id WHERE p.org_id=? GROUP BY p.id LIMIT 5");
$projects->execute([$orgId]); $projects = $projects->fetchAll();

$recentUsers = $db->prepare("SELECT * FROM tf_users WHERE org_id=? ORDER BY created_at DESC LIMIT 5");
$recentUsers->execute([$orgId]); $recentUsers = $recentUsers->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= getUserTheme() ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard · SprintDesk</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body data-theme="<?= getUserTheme() ?>" data-role="<?= currentUser()['role'] ?>">
  <div id="tf-curtain"></div>
  <div class="tf-wrap">
    <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
    <div class="tf-main">
      <div class="tf-topbar">
        <div id="tf-mobile-toggle">☰</div>
        <div class="tf-topbar-title"><?= e($activePage ?? 'Dashboard') ?></div>
        <div class="tf-search" style="position:relative">
          <span>🔍</span>
          <input type="text" placeholder="Global search..." id="globalSearchInp">
          <div id="tf-search-results" class="glass-card"></div>
        </div>
        <div class="tf-icon-btn" id="tf-notif-btn" style="position:relative">
          🔔<div class="tf-notif-dot" id="tf-notif-badge" style="display:none"></div>
          <div id="tf-notif-dropdown" class="glass-card">
            <div class="tf-notif-hd">Notifications</div>
            <div id="tf-notif-list"></div>
          </div>
        </div>
      </div>
      <div class="tf-page">
        <div class="tf-page-hd a1">
          <div>
            <div class="tf-title">Good <?= date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') ?>,
              <?= e(explode(' ', currentUser()['name'])[0]) ?> 👑
            </div>
            <div class="tf-subtitle"><?= date('l, F j, Y') ?> &nbsp;·&nbsp; <?= $activeSprints ?> active
              sprint<?= $activeSprints != 1 ? 's' : '' ?></div>
          </div>
          <a href="projects.php" class="btn btn-primary">+ New Project</a>
        </div>

        <div class="tf-stats a2">
          <div class="tf-stat glass-card">
            <div class="tf-stat-icon">👥</div>
            <div class="tf-stat-val"><?= $totalUsers ?></div>
            <div class="tf-stat-lbl">Active Users</div>
          </div>
          <div class="tf-stat glass-card">
            <div class="tf-stat-icon">📁</div>
            <div class="tf-stat-val"><?= $totalProjects ?></div>
            <div class="tf-stat-lbl">Active Projects</div>
          </div>
          <div class="tf-stat glass-card">
            <div class="tf-stat-icon">📋</div>
            <div class="tf-stat-val"><?= $totalTasks ?></div>
            <div class="tf-stat-lbl">Total Tasks</div>
          </div>
          <div class="tf-stat glass-card">
            <div class="tf-stat-icon">🐛</div>
            <div class="tf-stat-val" style="color:var(--red)"><?= $openBugs ?></div>
            <div class="tf-stat-lbl">Incomplete Bugs</div>
          </div>
        </div>

        <div class="g2 a3" style="margin-bottom:18px">
          <div class="card">
            <div class="card-hd">
              <div class="card-title">Project Progress</div>
            </div>
            <div class="card-body">
              <div class="status-grid">
                <?php 
                $statusConfig = [
                  'done' => ['label' => 'Done', 'color' => '#00C896', 'icon' => '✅'],
                  'inprogress' => ['label' => 'Active', 'color' => '#FFB020', 'icon' => '⚡'],
                  'review' => ['label' => 'Review', 'color' => '#8B5CF6', 'icon' => '👁'],
                  'todo' => ['label' => 'Todo', 'color' => '#7AA09A', 'icon' => '📋']
                ];
                foreach ($statusConfig as $key => $conf):
                  $val = $tasksByStatus[$key] ?? 0;
                  $pct = $totalTasks > 0 ? round(($val / $totalTasks) * 100) : 0;
                ?>
                <div class="status-item">
                  <div class="status-ring" style="--p:<?= $pct ?>; --c:<?= $conf['color'] ?>">
                    <div class="status-ring-inner"><?= $conf['icon'] ?></div>
                  </div>
                  <div class="status-info">
                    <div class="status-label"><?= $conf['label'] ?></div>
                    <div class="status-val"><?= $val ?> <small>(<?= $pct ?>%)</small></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <style>
          .status-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; padding: 10px 0; }
          .status-item { display: flex; align-items: center; gap: 15px; background: var(--surface2); padding: 15px; border-radius: 16px; border: 1px solid var(--border); transition: transform 0.2s; }
          .status-item:hover { transform: translateY(-3px); border-color: var(--brand); }
          .status-ring { 
            position: relative; width: 50px; height: 50px; border-radius: 50%; 
            background: conic-gradient(var(--c) calc(var(--p) * 1%), var(--border) 0);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
          }
          .status-ring::before { content: ""; position: absolute; width: 40px; height: 40px; background: var(--surface); border-radius: 50%; }
          .status-ring-inner { position: relative; z-index: 1; font-size: 16px; }
          .status-label { font-size: 11px; font-weight: 700; color: var(--text3); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
          .status-val { font-size: 18px; font-weight: 700; color: var(--text); }
          .status-val small { font-size: 12px; font-weight: 500; color: var(--text3); margin-left: 4px; }
          @media (max-width: 640px) { .status-grid { grid-template-columns: 1fr; } }
          </style>
          <div class="card">
            <div class="card-hd">
              <div class="card-title">Recent Activity</div>
            </div>
            <div class="card-body">
              <?php foreach ($recentAct as $a): 
                $type = strtolower($a['entity_type'] ?? '');
                $isGitHub = ($type === 'github_commit');
                if ($isGitHub) {
                    $gh = @json_decode($a['old_value'], true) ?: [];
                    $a['uname'] = $a['github_author'] ?? 'GitHub User';
                    $a['commit_id'] = substr($a['commit_hash'] ?? '?', 0, 7);
                    $a['commit_url'] = $gh['commit_url'] ?? '#';
                    $a['repo_name'] = $gh['repo_name'] ?? 'Repository';
                    $a['message'] = $gh['message'] ?? '';
                }
              ?>
                <div class="tf-act">
                  <div class="tf-act-dot"></div>
                  <div>
                    <?php if ($isGitHub): ?>
                        <div class="tf-act-txt">
                            <strong><?= e($a['uname']) ?></strong> pushed commit 
                            <a href="<?= e($a['commit_url']) ?>" target="_blank" style="font-family:monospace; background:var(--surface2); padding:2px 6px; border-radius:4px; font-size:12px; font-weight:600; text-decoration:none; color:var(--text); border:1px solid var(--border)"><?= e($a['commit_id']) ?></a> 
                            to <strong><?= e($a['repo_name']) ?></strong>
                        </div>
                        <div style="color:var(--text2); font-size:12px; font-family:monospace; margin-top:4px; margin-bottom:4px; display:inline-block; border-left:2px solid var(--border); padding-left:8px;">
                            <?= e($a['message']) ?>
                        </div>
                    <?php else: ?>
                        <div class="tf-act-txt"><strong><?= e($a['uname'] ?? 'System') ?></strong> <?= e($a['action']) ?> a <strong><?= e($a['entity_type']) ?></strong></div>
                    <?php endif; ?>
                    <div class="tf-act-time" style="<?= !empty($a['is_github']) ? 'margin-top:2px;' : '' ?>"><?= timeAgo($a['created_at']) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if (!$recentAct): ?>
                <p style="color:var(--text3);font-size:13px;padding:12px 0">No activity yet.</p><?php endif; ?>
            </div>
          </div>
        </div>

        <div class="g2 a4">
          <div class="card">
            <div class="card-hd">
              <div class="card-title">Projects</div><a href="projects.php" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <div class="card-body">
              <?php foreach ($projects as $p):
                $pct = $p['tc'] > 0 ? round(($p['dc'] / $p['tc']) * 100) : 0; ?>
                <div
                  style="display:flex;align-items:center;gap:11px;padding:11px 0;border-bottom:1px solid var(--border)">
                  <div style="width:9px;height:9px;border-radius:50%;background:<?= e($p['color']) ?>;flex-shrink:0">
                  </div>
                  <div style="flex:1">
                    <div style="font-size:13px;font-weight:500"><?= e($p['name']) ?></div>
                    <div style="font-size:11px;color:var(--text3)"><?= $p['tc'] ?> tasks · <?= e($p['mname'] ?? '—') ?>
                    </div>
                    <div class="tf-prog" style="margin-top:6px">
                      <div class="tf-prog-fill" style="width:<?= $pct ?>%"></div>
                    </div>
                  </div>
                  <span style="font-size:12px;font-weight:600;color:var(--brand)"><?= $pct ?>%</span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="card">
            <div class="card-hd">
              <div class="card-title">Recent Users</div><a href="users.php" class="btn btn-secondary btn-sm">Manage</a>
            </div>
            <div class="card-body" style="padding:0">
              <div class="tf-tbl-wrap" style="border:none">
                <table>
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Role</th>
                      <th>Joined</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                      <tr>
                        <td style="font-weight:500"><?= e($u['name']) ?></td>
                        <td><span class="badge b-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td style="color:var(--text3);font-size:12px"><?= date('M j', strtotime($u['created_at'])) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
</body>

</html>