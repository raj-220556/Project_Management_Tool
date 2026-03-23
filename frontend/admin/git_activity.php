<?php
require_once __DIR__ . '/../../backend/shared/includes/init.php';
requireLogin('admin');
$activePage = 'git_activity';
$db = db();
$orgId = currentUser()['org_id'];

$projectId = (int)($_GET['project_id'] ?? 0);

// Fetch projects for dropdown
$projectsStmt = $db->prepare("SELECT id, name, github_url FROM tf_projects WHERE org_id=? ORDER BY name ASC");
$projectsStmt->execute([$orgId]);
$projects = $projectsStmt->fetchAll();

$project = null;
if ($projectId) {
    foreach ($projects as $p) {
        if ($p['id'] == $projectId) {
            $project = $p;
            break;
        }
    }
}

// Fetch stats if project selected
$commits = [];
$branches = [];
$prs = [];
$hotspots = [];
$leaderboard = [];

if ($project) {
    // Recent Commits
    $stmt = $db->prepare("SELECT * FROM tf_github_commits WHERE project_id=? ORDER BY commit_date DESC LIMIT 50");
    $stmt->execute([$projectId]);
    $commits = $stmt->fetchAll();

    // Branches
    $stmt = $db->prepare("SELECT * FROM tf_github_branches WHERE project_id=? ORDER BY created_at DESC");
    $stmt->execute([$projectId]);
    $branches = $stmt->fetchAll();

    // Pull Requests
    $stmt = $db->prepare("SELECT * FROM tf_github_prs WHERE project_id=? ORDER BY pr_number DESC LIMIT 20");
    $stmt->execute([$projectId]);
    $prs = $stmt->fetchAll();

    // Leaderboard (group by author_email or name)
    $stmt = $db->prepare("SELECT author_name, author_avatar, COUNT(*) as push_count, SUM(additions) as total_add, SUM(deletions) as total_del FROM tf_github_commits WHERE project_id=? GROUP BY author_email, author_name ORDER BY push_count DESC LIMIT 5");
    $stmt->execute([$projectId]);
    $leaderboard = $stmt->fetchAll();

    // Hotspots (calculate explicitly from JSON arrays)
    $fileCounts = [];
    $stmt = $db->prepare("SELECT files_changed FROM tf_github_commits WHERE project_id=? ORDER BY commit_date DESC LIMIT 100");
    $stmt->execute([$projectId]);
    foreach ($stmt->fetchAll() as $row) {
        $files = json_decode($row['files_changed'], true) ?: [];
        foreach ($files as $f) {
            if (!isset($fileCounts[$f])) $fileCounts[$f] = 0;
            $fileCounts[$f]++;
        }
    }
    arsort($fileCounts);
    $hotspots = array_slice($fileCounts, 0, 10, true);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= getUserTheme() ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Git Activity · SprintDesk</title>
  <style>
    .git-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    @media(max-width: 900px) { .git-grid { grid-template-columns: 1fr; } }
    .gh-card { background: var(--surface2); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .gh-card-title { font-size: 16px; font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between; }
    .gh-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); }
    .gh-item:last-child { border-bottom: none; padding-bottom: 0; }
    .gh-msg { font-weight: 500; font-size: 14px; color: var(--text); }
    .gh-meta { font-size: 12px; color: var(--text3); margin-top: 4px; display: flex; align-items: center; gap: 8px; }
    .gh-badge { padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .b-open { background: var(--blue-light); color: var(--blue); }
    .b-merged { background: #d1fae5; color: #059669; }
    .b-closed { background: #fee2e2; color: #dc2626; }
    .b-draft { background: #f3f4f6; color: #6b7280; }
    
    .hs-file { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed var(--border); font-family: monospace; font-size: 13px; }
    .leader-row { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border); }
    .leader-avatar img { width: 36px; height: 36px; border-radius: 50%; }
    .leader-stats { border-left: 1px solid var(--border); padding-left: 10px; margin-left: auto; text-align: right; }
  </style>
</head>
<body data-theme="<?= getUserTheme() ?>">
  <div id="tf-curtain"></div>
  <div class="tf-wrap">
    <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
    <div class="tf-main">
      <div class="tf-topbar">
        <div class="tf-topbar-title">Git Activity & Insights</div>
        <div class="tf-search">
            <select id="projectSelect" class="tf-inp" style="max-width:300px; padding: 6px 16px;">
                <option value="">-- Select Project --</option>
                <?php foreach($projects as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $p['id'] == $projectId ? 'selected' : '' ?>><?= e($p['name']) ?> <?= empty($p['github_url']) ? '(No Repo)' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
      </div>
      <div class="tf-page">
        
        <?php if (!$project): ?>
            <div style="text-align:center; padding: 50px; color: var(--text3);">
                <h3>Please select a project to view Git Activity.</h3>
            </div>
        <?php elseif (empty($project['github_url'])): ?>
            <div style="text-align:center; padding: 50px; color: var(--text3);">
                <h3>This project is not linked to a GitHub repository.</h3>
                <p>Go to Project settings to add a GitHub URL.</p>
            </div>
        <?php else: ?>
            <div class="tf-page-hd g2">
                <div>
                    <div class="tf-title"><?= e($project['name']) ?></div>
                    <div class="tf-subtitle">Repository: <?= e($project['github_url']) ?></div>
                </div>
                <div style="display:flex; gap: 10px;">
                    <button class="btn btn-secondary" onclick="syncGithub(<?= $project['id'] ?>)" id="syncBtn">
                        <span id="syncIcon">🔄</span> Sync with Remote
                    </button>
                    <button class="btn btn-primary" onclick="window.promptCreateBranch()">+ Create Branch</button>
                    <button class="btn btn-danger" onclick="window.promptRevertCommit()" style="background:#ef4444; color:#fff; border:none; padding:8px 16px; border-radius:8px; cursor:pointer;">⚠️ Revert Commit</button>
                    <button class="btn btn-secondary" onclick="window.promptCherryPick()" style="background:#8b5cf6; color:#fff; border:none; padding:8px 16px; border-radius:8px; cursor:pointer;">🍒 Cherry Pick</button>
                </div>
            </div>

            <!-- Toast alert placeholder -->
            <div id="toast" class="tf-toast-inline" style="display:none; margin-bottom:20px;"></div>

            <div class="git-grid">
                <!-- Left Column: Activity Feed -->
                <div>
                    <div class="gh-card">
                        <div class="gh-card-title">Pull Requests</div>
                        <?php if (!$prs): ?>
                            <p style="color:var(--text3); font-size:13px;">No PRs synced.</p>
                        <?php else: ?>
                            <?php foreach ($prs as $pr): ?>
                                <div class="gh-item">
                                    <img src="<?= e($pr['author_avatar'] ?: 'https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.png') ?>" style="width:24px; height:24px; border-radius:50%;">
                                    <div style="flex:1;">
                                        <div class="gh-msg"><?= e($pr['title']) ?> <span style="color:var(--text3);font-size:12px;">#<?= $pr['pr_number'] ?></span></div>
                                        <div class="gh-meta">
                                            <span class="gh-badge b-<?= $pr['state'] ?>"><?= $pr['state'] ?></span>
                                            <span>by <?= e($pr['author_name']) ?></span>
                                            <span><?= timeAgo($pr['created_at']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="gh-card">
                        <div class="gh-card-title">Branch Tracking</div>
                        <div style="display:flex; flex-wrap:wrap; gap:10px;">
                            <?php if (!$branches): ?>
                                <p style="color:var(--text3); font-size:13px;">No branches synced.</p>
                            <?php else: ?>
                                <?php foreach ($branches as $b): ?>
                                    <div style="background:var(--surface); border:1px solid var(--border); padding:8px 12px; border-radius:8px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                                        <span style="color:var(--brand)">🔀</span> <?= e($b['name']) ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="gh-card">
                        <div class="gh-card-title">Commit Feed</div>
                        <?php if (!$commits): ?>
                            <p style="color:var(--text3); font-size:13px;">No commits synced.</p>
                        <?php else: ?>
                            <?php foreach ($commits as $c): ?>
                                <div class="gh-item">
                                    <img src="<?= e($c['author_avatar'] ?: 'https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.png') ?>" style="width:32px; height:32px; border-radius:50%;">
                                    <div style="flex:1;">
                                        <div class="gh-msg"><?= e(explode("\n", $c['message'])[0]) ?></div>
                                        <div class="gh-meta">
                                            <span style="font-family:monospace; background:var(--surface); padding:2px 4px; border-radius:4px;"><?= substr($c['sha'], 0, 7) ?></span>
                                            <span>by <?= e($c['author_name']) ?></span>
                                            <span><?= timeAgo($c['commit_date']) ?></span>
                                            <span style="color:#059669">+<?= $c['additions'] ?></span>
                                            <span style="color:#dc2626">-<?= $c['deletions'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column: Insights -->
                <div>
                    <div class="gh-card" style="border-top:4px solid var(--brand)">
                        <div class="gh-card-title">Deployment Status</div>
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                            <span style="font-size:14px; font-weight:600">Production (main)</span>
                            <span class="gh-badge b-merged">LIVE</span>
                        </div>
                        <?php 
                        $mainSha = '?';
                        foreach($branches as $b) { if($b['name'] === 'main') $mainSha = substr($b['last_commit_sha'], 0, 7); }
                        ?>
                        <div style="font-family:monospace; font-size:12px; color:var(--text3); border-bottom:1px solid var(--border); padding-bottom:15px; margin-bottom:15px;">
                            Currently deployed commit: <?= $mainSha ?>
                        </div>

                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                            <span style="font-size:14px; font-weight:600">Staging (dev)</span>
                            <span class="gh-badge b-open">BUILDING</span>
                        </div>
                        <?php 
                        $devSha = '?';
                        foreach($branches as $b) { if(in_array($b['name'], ['dev', 'develop', 'staging'])) $devSha = substr($b['last_commit_sha'], 0, 7); }
                        ?>
                        <div style="font-family:monospace; font-size:12px; color:var(--text3);">
                            Pending commit: <?= $devSha ?>
                        </div>
                    </div>

                    <div class="gh-card">
                        <div class="gh-card-title">File Hotspots <span>🔥</span></div>
                        <p style="font-size:12px; color:var(--text3); margin-bottom:12px;">Most frequently changed files (high churn).</p>
                        <?php if (!$hotspots): ?>
                            <p style="color:var(--text3); font-size:13px;">No file data available.</p>
                        <?php else: ?>
                            <?php foreach($hotspots as $file => $count): ?>
                                <div class="hs-file">
                                    <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:80%;" title="<?= e($file) ?>"><?= e(basename($file)) ?></span>
                                    <span style="color:var(--brand); font-weight:bold;"><?= $count ?> mods</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="gh-card">
                        <div class="gh-card-title">Contributor Leaderboard 🏆</div>
                        <?php if (!$leaderboard): ?>
                            <p style="color:var(--text3); font-size:13px;">No leaderboard data available.</p>
                        <?php else: ?>
                            <?php foreach($leaderboard as $l): ?>
                                <div class="leader-row">
                                    <div class="leader-avatar">
                                        <img src="<?= e($l['author_avatar'] ?: 'https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.png') ?>">
                                    </div>
                                    <div style="flex:1;">
                                        <div style="font-weight:600; font-size:14px;"><?= e($l['author_name']) ?></div>
                                        <div style="font-size:12px; color:var(--text3);"><?= $l['push_count'] ?> commits</div>
                                    </div>
                                    <div class="leader-stats">
                                        <div style="color:#059669; font-size:13px; font-weight:bold">+<?= number_format($l['total_add']) ?></div>
                                        <div style="color:#dc2626; font-size:13px; font-weight:bold">-<?= number_format($l['total_del']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <script>
      document.getElementById('projectSelect')?.addEventListener('change', function(e) {
          if (e.target.value) {
              window.location.href = 'git_activity.php?project_id=' + e.target.value;
          } else {
              window.location.href = 'git_activity.php';
          }
      });

      function showToast(msg, error = false) {
          const t = document.getElementById('toast');
          t.textContent = (error ? '❌ ' : '✅ ') + msg;
          t.style.display = 'block';
          t.style.background = error ? 'rgba(239,68,68,0.1)' : 'rgba(0,200,150,0.1)';
          t.style.color = error ? '#ef4444' : '#00C896';
          t.style.borderColor = error ? 'rgba(239,68,68,0.2)' : 'rgba(0,200,150,0.2)';
      }

      function syncGithub(projectId) {
          const btn = document.getElementById('syncBtn');
          const icon = document.getElementById('syncIcon');
          btn.disabled = true;
          icon.innerHTML = '⏳';
          
          const fd = new FormData();
          fd.append('action', 'sync');
          fd.append('project_id', projectId);

          fetch('../../backend/api/github_service.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                showToast(res.message, !res.success);
                if(res.success) {
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    btn.disabled = false; icon.innerHTML = '🔄';
                }
            })
            .catch(err => {
                showToast('Network error while syncing.', true);
                btn.disabled = false; icon.innerHTML = '🔄';
            });
      }

      window.promptCreateBranch = function() {
          const name = prompt("Enter branch name (e.g. feature/PROJ-123-ui):");
          if (!name) return;
          const fd = new FormData();
          fd.append('action', 'create_branch');
          fd.append('project_id', <?= $projectId ?>);
          fd.append('branch_name', name);
          
          fetch('../../backend/api/github_service.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                showToast(res.message, !res.success);
                if(res.success) setTimeout(() => window.location.reload(), 1500);
            });
      };

      window.promptRevertCommit = function() {
          const sha = prompt("Enter Commit SHA to revert:");
          if (!sha) return;
          const fd = new FormData();
          fd.append('action', 'revert_commit');
          fd.append('project_id', <?= $projectId ?>);
          fd.append('sha', sha);
          
          fetch('../../backend/api/github_service.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                showToast(res.message, !res.success);
            });
      };

      window.promptCherryPick = function() {
          const sha = prompt("Enter Commit SHA to cherry-pick:");
          if (!sha) return;
          const targetBranch = prompt("Enter Target Branch (e.g. main):");
          if (!targetBranch) return;
          const fd = new FormData();
          fd.append('action', 'cherry_pick');
          fd.append('project_id', <?= $projectId ?>);
          fd.append('sha', sha);
          fd.append('target_branch', targetBranch);
          
          fetch('../../backend/api/github_service.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                showToast(res.message, !res.success);
            });
      };
  </script>
</body>
</html>
