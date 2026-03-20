<?php
  require_once __DIR__ . '/../../backend/shared/includes/init.php';
  requireLogin('developer');
  $activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= getUserTheme() ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Dashboard · SprintDesk</title>
  <?php
  $uid = currentUser()['id'];
  $db = db();

  $myTasks = $db->prepare("SELECT t.*,p.name pname FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id WHERE t.assigned_to=? ORDER BY FIELD(t.status,'inprogress','review','todo','done'), t.updated_at DESC LIMIT 10");
  $myTasks->execute([$uid]);
  $tasks = $myTasks->fetchAll();
  $doneCnt = $db->prepare("SELECT COUNT(*) FROM tf_tasks WHERE assigned_to=? AND status='done'");
  $doneCnt->execute([$uid]);
  $done = (int)$doneCnt->fetchColumn();
  $activeCnt = $db->prepare("SELECT COUNT(*) FROM tf_tasks WHERE assigned_to=? AND status='inprogress'");
  $activeCnt->execute([$uid]);
  $active = (int)$activeCnt->fetchColumn();
  $totalCnt = $db->prepare("SELECT COUNT(*) FROM tf_tasks WHERE assigned_to=?");
  $totalCnt->execute([$uid]);
  $total = (int)$totalCnt->fetchColumn();
  $mySprints = $db->prepare("SELECT DISTINCT s.*,p.name pname FROM tf_sprints s JOIN tf_projects p ON s.project_id=p.id JOIN tf_tasks t ON t.sprint_id=s.id WHERE t.assigned_to=? AND s.status='active' LIMIT 3");
  $mySprints->execute([$uid]);
  $sprints = $mySprints->fetchAll();
  
  getGitHubActivity(); // Sync commits
  $recentAct = $db->prepare("SELECT a.*,u.name uname FROM tf_activity a LEFT JOIN tf_users u ON a.user_id=u.id LEFT JOIN tf_projects p ON a.project_id=p.id LEFT JOIN tf_project_members m ON p.id=m.project_id WHERE (a.user_id=? OR (a.entity_type='github_commit' AND m.user_id=?)) ORDER BY a.created_at DESC LIMIT 8");
  $recentAct->execute([$uid, $uid]);
  $recentAct = $recentAct->fetchAll();
  ?>
  <style>
    .tf-title { font-size: 24px; font-weight: 700; }
    .card-title { font-size: 18px; font-weight: 600; }

    /* Accent Colors for Stats */
    .s-amber .tf-stat-val { color: var(--brand); }
    .s-purple .tf-stat-val { color: #8e44ad; }
    .s-green .tf-stat-val { color: var(--brand-success); }
    .s-red .tf-stat-val { color: var(--text); opacity: 0.8; }

    /* Open Kanban Button Enhancement */
    .btn-kanban {
        background: linear-gradient(135deg, var(--brand), #1f6feb) !important;
        font-weight: 600 !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
    }
    .btn-kanban:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 82, 204, 0.3);
    }
  </style>
</head>

<body data-theme="<?= getUserTheme() ?>" data-role="<?= currentUser()['role'] ?>">
  <div id="tf-curtain"></div>
  <div class="tf-wrap">
    <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
    <div class="tf-main" style="padding-top: 10px;">
      <div class="tf-topbar">
        <div id="tf-mobile-toggle">☰</div>
        <div class="tf-topbar-title"><?= e($activePage ?? 'Dashboard') ?></div>
        <div class="tf-search"><span>🔍</span><input type="text" placeholder="Search tasks..."></div>
        <div class="tf-icon-btn">🔔<div class="tf-notif-dot"></div>
        </div>
      </div>
      <div class="tf-page">

        <div class="tf-page-hd a1">
          <div>
            <div class="tf-title">Hey, <?= e(explode(' ', currentUser()['name'])[0]) ?> 💻 </div>
            <div class="tf-subtitle"><?= ($active . ' active · ' . ($total - $done) . ' remaining · ' . $done . ' done') ?></div>
          </div>
          <a href="kanban.php" class="btn btn-primary btn-kanban">Open Kanban Board →</a>
        </div>

        <div class="tf-stats a2" style="gap: 20px; margin-bottom: 32px;">
          <div class="tf-stat s-amber" style="padding: 20px;">
            <div class="tf-stat-icon">⚡</div>
            <div>
              <div class="tf-stat-val"><?= $active ?></div>
              <div class="tf-stat-lbl">In Progress</div>
            </div>
          </div>
          <div class="tf-stat s-purple" style="padding: 20px;">
            <div class="tf-stat-icon">👁</div>
            <div>
              <div class="tf-stat-val">
                <?= (function () use ($db, $uid) {
                  $s = $db->prepare("SELECT COUNT(*) FROM tf_tasks WHERE assigned_to=? AND status='review'");
                  $s->execute([$uid]);
                  return $s->fetchColumn(); })() ?>
              </div>
              <div class="tf-stat-lbl">In Review</div>
            </div>
          </div>
          <div class="tf-stat s-green" style="padding: 20px;">
            <div class="tf-stat-icon">✅</div>
            <div>
              <div class="tf-stat-val"><?= $done ?></div>
              <div class="tf-stat-lbl">Completed</div>
            </div>
          </div>
          <div class="tf-stat s-red" style="padding: 20px;">
            <div class="tf-stat-icon">📋</div>
            <div>
              <div class="tf-stat-val"><?= $total ?></div>
              <div class="tf-stat-lbl">Total Tasks</div>
            </div>
          </div>
        </div>

        <div class="g2 a3" style="gap: 32px">
          <div class="card" style="border: 1px solid var(--border);">
            <div class="card-hd">
              <div class="card-title">My Tasks </div>
              <a href="kanban.php" class="btn btn-secondary btn-sm">Full View</a>
            </div>
            <div class="card-body" style="padding:0">
              <div class="tf-tbl-wrap" style="border:none">
                <table>
                  <thead>
                    <tr>
                      <th style="font-weight:700">Task</th>
                      <th style="font-weight:700">Project</th>
                      <th style="font-weight:700">Status</th>
                      <th style="font-weight:700">Priority</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($tasks as $t): ?>
                      <tr>
                        <td style="font-size:13px;font-weight:500;max-width:180px"><?= e($t['title']) ?></td>
                        <td style="font-size:12px;color:var(--text2)"><?= e($t['pname']) ?></td>
                        <td><span
                            class="badge b-<?= $t['status'] ?>"><?= ucfirst($t['status'] === 'inprogress' ? 'Active' : $t['status']) ?></span>
                        </td>
                        <td><span class="badge b-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (!$tasks): ?>
                      <tr>
                        <td colspan="4">
                          <div class="empty-state">
                            <span class="empty-icon">📭</span>
                            <div class="empty-msg">No tasks assigned</div>
                            <div class="empty-sub">Tasks assigned to you will appear here</div>
                          </div>
                        </td>
                      </tr><?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="card" style="border: 1px solid var(--border);">
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
                  <div style="width:10px; height:10px; border-radius:50%; background:var(--brand); flex-shrink:0; margin-top:5px"></div>
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
          <div class="card" style="border: 1px solid var(--border);">
            <div class="card-hd">
              <div class="card-title">Active Sprints </div>
            </div>
            <div class="card-body">
              <?php foreach ($sprints as $s):
                $stc = $db->prepare("SELECT COUNT(*) FROM tf_tasks WHERE sprint_id=? AND assigned_to=?");
                $stc->execute([$s['id'], $uid]);
                $stotal = (int)$stc->fetchColumn();
                $sdc = $db->prepare("SELECT COUNT(*) FROM tf_tasks WHERE sprint_id=? AND assigned_to=? AND status='done'");
                $sdc->execute([$s['id'], $uid]);
                $sdone = (int)$sdc->fetchColumn();
                $pct = $stotal > 0 ? round(($sdone / $stotal) * 100) : 0;
                ?>
                <div
                  style="background:var(--surface2);border-radius:12px;padding:16px;margin-bottom:12px;border:1px solid var(--border); transition: transform 0.2s;"
                  onmouseover="this.style.transform='translateX(4px)'" onmouseout="this.style.transform='translateX(0)'">
                  <div style="font-size:13px;font-weight:600;margin-bottom:4px"><?= e($s['name']) ?></div>
                  <div style="font-size:11px;color:var(--text3);margin-bottom:10px"><?= e($s['pname']) ?> · Tasks:
                    <?= $stotal ?></div>
                  <div class="tf-prog" style="height: 6px; background: var(--border); border-radius: 3px; overflow: hidden;">
                    <div class="tf-prog-fill" style="width:<?= $pct ?>%; height: 100%;"></div>
                  </div>
                  <div style="font-size:11px;color:var(--text3);margin-top:6px"><?= $pct ?>% of tasks done</div>
                </div>
              <?php endforeach; ?>
              <?php if (!$sprints): ?>
                <div class="empty-state" style="padding: 40px 0">
                   <div class="empty-msg" style="font-size: 14px">No active sprints</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
>
  </div>
</body>

</html>