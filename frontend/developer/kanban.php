<?php
require_once __DIR__ . '/../../backend/shared/includes/init.php';
requireLogin('developer');
$activePage = 'kanban';
$uid = currentUser()['id'];
$db  = db();

$sprintList = []; // dummy
$cols = ['todo'=>[],'inprogress'=>[],'review'=>[],'done'=>[]];

$myProjects = $db->prepare("SELECT DISTINCT p.id, p.name FROM tf_projects p JOIN tf_tasks t ON t.project_id=p.id WHERE t.assigned_to=?");
$myProjects->execute([$uid]);
$projectList = $myProjects->fetchAll();
$pid = (int)($_GET['project'] ?? ($projectList[0]['id'] ?? 0));

$taskQ = $db->prepare("SELECT t.*,u.name aname FROM tf_tasks t LEFT JOIN tf_users u ON t.assigned_to=u.id WHERE t.assigned_to=? AND t.project_id=? ORDER BY t.position,t.created_at");
$taskQ->execute([$uid, $pid]);
foreach ($taskQ->fetchAll() as $t) $cols[$t['status']][] = $t;
$colMeta = [
    'todo'       => ['label'=>'📋 Todo',       'cls'=>'k-todo'],
    'inprogress' => ['label'=>'⚡ In Progress', 'cls'=>'k-inprogress'],
    'review'     => ['label'=>'👁 Review',      'cls'=>'k-review'],
    'done'       => ['label'=>'✅ Done',         'cls'=>'k-done'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= getUserTheme() ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kanban · SprintDesk</title>
<link rel="stylesheet" href="<?= URL_ASSETS ?>/css/app.css">
<style>
    .tf-title { font-size: 24px; font-weight: 700; }
    .tf-kcol-name { font-weight: 600; }
</style>
</head>
<body data-theme="<?= getUserTheme() ?>" data-role="<?= currentUser()['role'] ?>">
<div id="tf-curtain"></div>
<div class="tf-wrap">
<?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
<div class="tf-main" style="padding-top: 10px;">
  <div class="tf-topbar">
    <div style="display:flex; align-items:center; gap:20px;">
        <div id="tf-mobile-toggle">☰</div>
        <div class="tf-topbar-title">Kanban Board</div>
        <form method="GET" style="margin:0;">
            <select name="project" onchange="this.form.submit()" class="tf-inp" style="padding:6px 12px; border-radius:8px; border:1px solid var(--border); background:var(--surface2);">
                <?php if (!$projectList): ?><option value="">No Active Tasks</option><?php endif; ?>
                <?php foreach ($projectList as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $pid==$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="tf-icon-btn">🔔<div class="tf-notif-dot"></div></div>
  </div>
  <div class="tf-page">

    <div class="tf-page-hd a1">
      <div>
        <div class="tf-title">Kanban Board </div>
        <div class="tf-subtitle">Drag tasks between columns · Changes saved automatically</div>
      </div>
    </div>

    <div class="tf-kanban a2" id="kanbanBoard">
      <?php foreach($colMeta as $status => $meta): ?>
      <div class="tf-kcol <?= $meta['cls'] ?>" data-status="<?= $status ?>">
        <div class="tf-kcol-hd">
          <span class="tf-kcol-name"><?= $meta['label'] ?></span>
          <span class="tf-kcol-cnt"><?= count($cols[$status]) ?></span>
        </div>
        <div class="tf-kcol-list">
          <?php foreach($cols[$status] as $t): ?>
          <div class="tf-task-card" data-id="<?= $t['id'] ?>" draggable="true">
            <div class="tf-task-type-dot t-<?= $t['type'] ?>"></div>
            <div class="tf-task-title"><?= e($t['title']) ?></div>
            <div class="tf-task-foot">
              <span class="badge b-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span>
              <span class="tf-pts"><?= $t['story_points'] ?> pts</span>
            </div>
            <?php if($t['due_date']): $over=strtotime($t['due_date'])<time()&&$t['status']!='done'; ?>
            <div class="tf-task-due <?= $over?'overdue':'' ?>">📅 <?= date('M j', strtotime($t['due_date'])) ?><?= $over?' (overdue)':'' ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <button class="tf-add-card" onclick="alert('Speak to your manager to add tasks to this sprint.')">+ Add task</button>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>
</div>
<div id="tf-toasts"></div>
</body></html>
