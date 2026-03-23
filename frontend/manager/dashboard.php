<?php
  require_once __DIR__ . '/../../backend/shared/includes/init.php';
  requireLogin('manager');
  $activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= getUserTheme() ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manager Dashboard · SprintDesk</title>
  <?php
  $uid = currentUser()['id'];
  $db = db();

  $myProjects = $db->prepare("SELECT p.*,COUNT(DISTINCT m.user_id) mc,COUNT(DISTINCT t.id) tc,SUM(t.status='done') dc FROM tf_projects p LEFT JOIN tf_project_members m ON m.project_id=p.id LEFT JOIN tf_tasks t ON t.project_id=p.id WHERE p.manager_id=? GROUP BY p.id");
  $myProjects->execute([$uid]);
  $projects = $myProjects->fetchAll();
  $activeSprints = $db->prepare("SELECT s.*,p.name pname FROM tf_sprints s JOIN tf_projects p ON s.project_id=p.id WHERE p.manager_id=? AND s.status='active' LIMIT 5");
  $activeSprints->execute([$uid]);
  $sprints = $activeSprints->fetchAll();
  $pendingReview = $db->prepare("SELECT t.*,u.name aname,p.name pname FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id LEFT JOIN tf_users u ON t.assigned_to=u.id WHERE p.manager_id=? AND t.status='review' ORDER BY t.updated_at DESC LIMIT 6");
  $pendingReview->execute([$uid]);
  $reviewTasks = $pendingReview->fetchAll();
  $teamWorkload = $db->prepare("SELECT u.name,u.id,COUNT(t.id) total,SUM(t.status='inprogress') active,SUM(t.status='done') done FROM tf_project_members pm JOIN tf_users u ON pm.user_id=u.id JOIN tf_projects p ON pm.project_id=p.id LEFT JOIN tf_tasks t ON t.assigned_to=u.id AND t.project_id=p.id WHERE p.manager_id=? AND u.role='developer' GROUP BY u.id ORDER BY active DESC LIMIT 6");
  $teamWorkload->execute([$uid]);
  $team = $teamWorkload->fetchAll();
  
  getGitHubActivity(); // Sync commits
  $orgId = currentUser()['org_id'] ?? 0;
  $recentAct = $db->prepare("SELECT a.*, u.name uname FROM tf_activity a LEFT JOIN tf_users u ON a.user_id=u.id LEFT JOIN tf_projects p ON a.project_id=p.id WHERE (p.manager_id=? OR u.org_id=? OR p.org_id=?) ORDER BY a.created_at DESC LIMIT 8");
  $recentAct->execute([$uid, $orgId, $orgId]);
  $recentAct = $recentAct->fetchAll();
  ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
  <div id="tf-curtain"></div>
  <div class="tf-wrap">
    <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
    <div class="tf-main">
      <div class="tf-topbar">
        <div id="tf-mobile-toggle">☰</div>
        <div class="tf-topbar-title"><?= e($activePage ?? 'Dashboard') ?></div>
        <div class="tf-search"><span>🔍</span><input type="text" placeholder="Search..."></div>
        <div class="tf-icon-btn">🔔<div class="tf-notif-dot"></div>
        </div>
      </div>
      <div class="tf-page">
        <div class="tf-page-hd a1">
          <div>
            <div class="tf-title">Good <?= date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') ?>,
              <?= e(explode(' ', currentUser()['name'])[0]) ?> 🗂️</div>
            <div class="tf-subtitle"><?= count($projects) ?> project<?= count($projects) != 1 ? 's' : '' ?> ·
              <?= count($sprints) ?> active sprint<?= count($sprints) != 1 ? 's' : '' ?></div>
          </div>
          <a href="sprints.php" class="btn btn-primary">+ New Sprint</a>
        </div>

        <!-- My Projects -->
        <div class="a2" style="margin-bottom:20px">
          <div style="font-size:13px;font-weight:600;margin-bottom:12px;color:var(--text2)">My Projects</div>
          <div class="g3">
            <?php foreach ($projects as $p):
              $pct = $p['tc'] > 0 ? round(($p['dc'] / $p['tc']) * 100) : 0; ?>
              <div class="card" style="padding:18px">
                <div style="display:flex;align-items:center;gap:9px;margin-bottom:12px">
                  <div style="width:10px;height:10px;border-radius:50%;background:<?= e($p['color']) ?>;flex-shrink:0">
                  </div>
                  <div style="font-family:var(--fh);font-size:14px;font-weight:700"><?= e($p['name']) ?></div>
                  <span class="badge b-<?= $p['status'] ?>" style="margin-left:auto"><?= ucfirst($p['status']) ?></span>
                </div>
                <div style="display:flex;gap:12px;font-size:12px;color:var(--text3);margin-bottom:12px">
                  <span>👥 <?= $p['mc'] ?></span><span>📋 <?= $p['tc'] ?> tasks</span><span>✅ <?= (int) $p['dc'] ?>
                    done</span>
                </div>
                <div class="tf-prog">
                  <div class="tf-prog-fill" style="width:<?= $pct ?>%"></div>
                </div>
                <div style="font-size:11px;color:var(--text3);margin-top:5px"><?= $pct ?>% complete</div>
              </div>
            <?php endforeach; ?>
            <?php if (!$projects): ?>
              <div class="card" style="padding:24px;text-align:center;color:var(--text3)">No projects assigned yet.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="g2 a3">
          <!-- Active Sprints -->
          <div class="card">
            <div class="card-hd">
              <div class="card-title">Active Sprints</div><a href="sprints.php" class="btn btn-secondary btn-sm">All
                Sprints</a>
            </div>
            <div class="card-body">
              <?php foreach ($sprints as $s):
                $total = $db->prepare("SELECT COUNT(*) FROM tf_tasks WHERE sprint_id=?");
                $total->execute([$s['id']]);
                $tc = $total->fetchColumn();
                $done = $db->prepare("SELECT COUNT(*) FROM tf_tasks WHERE sprint_id=? AND status='done'");
                $done->execute([$s['id']]);
                $dc = $done->fetchColumn();
                $pct = $tc > 0 ? round(($dc / $tc) * 100) : 0;
                $days = $s['end_date'] ? ceil((strtotime($s['end_date']) - time()) / 86400) : null;
                ?>
                <div
                  style="padding:13px;background:var(--surface2);border-radius:12px;margin-bottom:10px;border:1px solid var(--border)">
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <div style="font-size:13px;font-weight:600"><?= e($s['name']) ?></div>
                    <?php if ($days !== null): ?><span
                        style="font-size:11px;color:<?= $days < 3 ? 'var(--red)' : 'var(--text3)' ?>"><?= $days ?>d
                        left</span><?php endif; ?>
                  </div>
                  <div style="font-size:11px;color:var(--text3);margin-bottom:8px"><?= e($s['pname']) ?> · <?= $tc ?>
                    tasks</div>
                  <div class="tf-prog">
                    <div class="tf-prog-fill" style="width:<?= $pct ?>%"></div>
                  </div>
                  <div style="font-size:11px;color:var(--text3);margin-top:4px"><?= $pct ?>% · <?= $dc ?>/<?= $tc ?> done
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if (!$sprints): ?>
                <p style="color:var(--text3);font-size:13px">No active sprints.</p><?php endif; ?>
            </div>
          </div>
          <!-- In Review -->
          <div class="card">
            <div class="card-hd">
              <div class="card-title">Pending Review</div><span
                style="font-size:12px;color:var(--text3)"><?= count($reviewTasks) ?> tasks</span>
            </div>
            <div class="card-body" style="padding:0">
              <div class="tf-tbl-wrap" style="border:none">
                <table>
                  <thead>
                    <tr>
                      <th>Task</th>
                      <th>Assignee</th>
                      <th>Priority</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($reviewTasks as $t): ?>
                      <tr>
                        <td style="font-size:13px;font-weight:500"><?= e($t['title']) ?></td>
                        <td style="color:var(--text2);font-size:12px"><?= e($t['aname'] ?? '—') ?></td>
                        <td><span class="badge b-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (!$reviewTasks): ?>
                      <tr>
                        <td colspan="3" style="text-align:center;color:var(--text3);padding:16px">No tasks in review.</td>
                      </tr><?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Activity & Team Workload -->
        <div class="g2 a4" style="margin-top:18px">
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
                <div class="tf-act" style="display:flex; gap:10px; margin-bottom:12px; font-size:13px">
                  <div style="width:10px; height:10px; border-radius:50%; background:#8B5CF6; flex-shrink:0; margin-top:5px"></div>
                  <div>
                    <?php if ($isGitHub): ?>
                        <div class="tf-act-txt">
                            <strong><?= e($a['uname']) ?></strong> pushed commit 
                            <a href="<?= e($a['commit_url']) ?>" target="_blank" style="font-family:monospace; background:var(--surface2); padding:2px 6px; border-radius:4px; font-size:12px; font-weight:600; text-decoration:none; color:var(--text); border:1px solid var(--border)"><?= e($a['commit_id']) ?></a> 
                            to <strong><?= e($a['repo_name']) ?></strong>
                        </div>
                        <div style="color:var(--text2); font-size:12px; font-family:monospace; margin-top:4px; display:inline-block; border-left:2px solid var(--border); padding-left:8px;">
                            <?= e($a['message']) ?>
                        </div>
                    <?php else: ?>
                        <div class="tf-act-txt"><strong><?= e($a['uname'] ?? 'System') ?></strong> <?= e($a['action']) ?> a <strong><?= e($a['entity_type']) ?></strong></div>
                    <?php endif; ?>
                    <div style="font-size:11px; color:var(--text3)"><?= timeAgo($a['created_at']) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if (!$recentAct): ?><div class="empty-state" style="padding:20px 0"><div class="empty-msg" style="font-size:14px">No activity yet</div></div><?php endif; ?>
            </div>
          </div>
          <div class="card">
            <div class="card-hd">
              <div class="card-title">Team Workload</div>
            </div>
          <div class="card-body">
            <?php foreach ($team as $m):
              $mx = max($m['total'], 1); ?>
              <div style="display:flex;align-items:center;gap:14px;margin-bottom:10px">
                <div class="tf-sb-avatar" style="width:30px;height:30px;font-size:11px;flex-shrink:0">
                  <?= e(userInitials($m['name'])) ?></div>
                <div style="font-size:13px;font-weight:500;width:110px;flex-shrink:0"><?= e($m['name']) ?></div>
                <div style="flex:1">
                  <div class="tf-prog">
                    <div class="tf-prog-fill" style="width:<?= round(($m['active'] / $mx) * 100) ?>%"></div>
                  </div>
                </div>
                <div style="font-size:12px;color:var(--text3);white-space:nowrap"><?= $m['active'] ?> active /
                  <?= $m['done'] ?> done</div>
              </div>
            <?php endforeach; ?>
            <?php if (!$team): ?>
              <p style="color:var(--text3);font-size:13px">No team members found.</p><?php endif; ?>
          </div>
        </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>