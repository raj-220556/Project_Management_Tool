<?php
require_once __DIR__ . '/../../backend/shared/includes/init.php';
requireLogin('manager');
$activePage = 'tasks';
$db = db();

$taskId = (int)($_GET['id'] ?? 0);
if (!$taskId) die("Task ID required.");

// Fetch task
$stmt = $db->prepare("SELECT t.*, p.name pname, p.code pcode, p.github_url, p.org_id as porg, u.name uname FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id LEFT JOIN tf_users u ON t.assigned_to=u.id WHERE t.id=?");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task || $task['porg'] != currentUser()['org_id']) {
    die("Task not found or access denied.");
}

$taskCode = $task['pcode'] . '-' . $task['id'];
$projectId = $task['project_id'];

// Fetch Git Context
$linkedBranches = [];
$taskCommits = [];

if ($task['github_url']) {
    // 1. Linked Branches (branch name contains task code)
    $bStmt = $db->prepare("SELECT name FROM tf_github_branches WHERE project_id=? AND name LIKE ?");
    $bStmt->execute([$projectId, "%{$taskCode}%"]);
    $linkedBranches = $bStmt->fetchAll();

    // 2. Code Diff Snippets (commits where message contains task code)
    $cStmt = $db->prepare("SELECT sha, message, author_name, commit_date, additions, deletions, files_changed FROM tf_github_commits WHERE project_id=? AND message LIKE ? ORDER BY commit_date DESC");
    $cStmt->execute([$projectId, "%{$taskCode}%"]);
    $taskCommits = $cStmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= getUserTheme() ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($taskCode) ?> · SprintDesk</title>
  <style>
      .detail-card { background: var(--surface2); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 20px; }
      .git-section { border-top: 1px solid var(--border); margin-top: 24px; padding-top: 24px; }
      .git-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
      .branch-badge { background: #e0f2fe; color: #0284c7; padding: 4px 10px; border-radius: 12px; font-family: monospace; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
      .commit-card { background: var(--surface); border: 1px solid var(--border); padding: 16px; border-radius: 8px; margin-bottom: 12px; }
      .commit-header { font-family: monospace; font-size: 12px; color: var(--text3); margin-bottom: 8px; }
      .commit-msg { font-size: 14px; font-weight: 500; color: var(--text); }
      .commit-stats { margin-top: 10px; font-size: 13px; display: flex; gap: 12px; }
      .stat-add { color: #059669; font-weight: bold; }
      .stat-del { color: #dc2626; font-weight: bold; }
  </style>
</head>
<body data-theme="<?= getUserTheme() ?>">
  <div id="tf-curtain"></div>
  <div class="tf-wrap">
    <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
    <div class="tf-main">
      <div class="tf-topbar">
        <div class="tf-topbar-title">Task Details</div>
        <a href="tasks.php" class="btn btn-secondary btn-sm">← Back to Tasks</a>
      </div>
      <div class="tf-page">
        <div class="tf-page-hd">
            <div>
                <div class="tf-title"><span style="color:var(--text3); margin-right:8px;"><?= $taskCode ?></span> <?= e($task['title']) ?></div>
                <div class="tf-subtitle">Project: <?= e($task['pname']) ?></div>
            </div>
        </div>

        <div class="detail-card">
            <div style="display:flex; gap:20px; margin-bottom: 20px;">
                <div><span class="tf-lbl">Status:</span> <span class="badge b-<?= $task['status'] ?>"><?= ucfirst($task['status']) ?></span></div>
                <div><span class="tf-lbl">Priority:</span> <span class="badge b-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span></div>
                <div><span class="tf-lbl">Assignee:</span> <strong style="color:var(--text)"><?= e($task['uname'] ?: 'Unassigned') ?></strong></div>
            </div>
            
            <div class="tf-lbl" style="margin-bottom:8px;">Description</div>
            <div style="color:var(--text2); line-height:1.6; font-size:14px;">
                <?= nl2br(e($task['description'] ?: 'No description provided.')) ?>
            </div>

            <!-- GIT CONTEXT -->
            <div class="git-section">
                <div class="git-title">🐙 Developer Git Context 
                    <?php if(empty($task['github_url'])): ?>
                        <span style="font-size:12px; color:var(--text3); font-weight:normal;">(No repository linked)</span>
                    <?php endif; ?>
                </div>

                <?php if($task['github_url']): ?>
                    
                    <div style="margin-bottom: 24px;">
                        <h4 style="font-size:14px; margin-bottom:12px; color:var(--text2);">Linked Branches</h4>
                        <?php if ($linkedBranches): ?>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <?php foreach($linkedBranches as $b): ?>
                                    <span class="branch-badge">🔀 <?= e($b['name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="color:var(--text3); font-size:13px; margin-bottom:10px;">No branches found matching '<?= $taskCode ?>'.</div>
                            <button class="btn btn-secondary btn-sm" onclick="promptCreateBranch()">+ Create Linked Branch</button>
                        <?php endif; ?>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <h4 style="font-size:14px; margin-bottom:12px; color:var(--text2);">Code Diff Snippets & Commits</h4>
                        <?php if ($taskCommits): ?>
                            <?php foreach($taskCommits as $tc): ?>
                                <div class="commit-card">
                                    <div class="commit-header">
                                        <span style="background:var(--surface2); padding:2px 6px; border-radius:4px;"><?= substr($tc['sha'], 0, 7) ?></span>
                                        Committed by <strong><?= e($tc['author_name']) ?></strong> • <?= timeAgo($tc['commit_date']) ?>
                                    </div>
                                    <div class="commit-msg"><?= nl2br(e($tc['message'])) ?></div>
                                    
                                    <?php 
                                        $files = json_decode($tc['files_changed'], true) ?: []; 
                                    ?>
                                    <div class="commit-stats">
                                        <span class="stat-add">+<?= $tc['additions'] ?></span>
                                        <span class="stat-del">-<?= $tc['deletions'] ?></span>
                                        <span style="color:var(--text3)"><?= count($files) ?> files changed</span>
                                    </div>
                                    <?php if(count($files) > 0): ?>
                                        <div style="margin-top:10px; padding:10px; background:var(--surface2); border:1px dashed var(--border); border-radius:6px; font-family:monospace; font-size:11px; max-height:100px; overflow-y:auto;">
                                            <?php foreach(array_slice($files, 0, 5) as $f): ?>
                                                <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= e($f) ?>">↳ <?= e(basename($f)) ?></div>
                                            <?php endforeach; ?>
                                            <?php if(count($files) > 5): ?>
                                                <div style="color:var(--text3); margin-top:4px;">...and <?= count($files)-5 ?> more files</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="color:var(--text3); font-size:13px;">No commits found referencing '<?= $taskCode ?>' in their commit message.</div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <h4 style="font-size:14px; margin-bottom:12px; color:var(--text2);">Blame / Ownership</h4>
                        <?php 
                            // Calculate ownership based on who has most commits with this task code
                            $authors = [];
                            foreach($taskCommits as $tc) {
                                $a = $tc['author_name'];
                                if(!isset($authors[$a])) $authors[$a] = 0;
                                $authors[$a] += ($tc['additions'] + $tc['deletions']);
                            }
                            arsort($authors);
                        ?>
                        <?php if($authors): ?>
                            <div style="font-size:13px; color:var(--text);">
                                Based on task commits, module primarily owned by: <strong style="color:var(--brand);"><?= e(array_key_first($authors)) ?></strong>
                            </div>
                        <?php else: ?>
                            <div style="color:var(--text3); font-size:13px;">Cannot determine module ownership until code is committed for this task.</div>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>
            </div>
        </div>
      </div>
    </div>
  </div>

  <script>
      function promptCreateBranch() {
          const name = prompt("Enter branch name:", "feature/<?= $taskCode ?>-<?= preg_replace('/[^a-z0-9]+/', '-', strtolower($task['title'])) ?>");
          if (!name) return;
          const fd = new FormData();
          fd.append('action', 'create_branch');
          fd.append('project_id', <?= $projectId ?>);
          fd.append('branch_name', name);
          
          fetch('../../backend/api/github_service.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                alert(res.message);
                if(res.success) window.location.reload();
            });
      }
  </script>
</body>
</html>
