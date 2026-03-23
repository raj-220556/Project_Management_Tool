<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kanban · SprintDesk</title>
<?php
require_once __DIR__ . '/../../backend/shared/includes/init.php';
requireLogin('manager');
$activePage = 'kanban';
$uid = currentUser()['id'];
$db  = db();

$sprintList = []; // dummy
$cols = ['todo'=>[],'inprogress'=>[],'review'=>[],'done'=>[]];

// Fetch data for Project Selector and Modal
$myProjects = $db->prepare("SELECT id, name FROM tf_projects WHERE manager_id=?");
$myProjects->execute([$uid]);
$projectList = $myProjects->fetchAll();
$pid = (int)($_GET['project'] ?? ($projectList[0]['id'] ?? 0));

$tq = $db->prepare("SELECT t.*,u.name aname FROM tf_tasks t LEFT JOIN tf_users u ON t.assigned_to=u.id WHERE t.project_id=? ORDER BY t.position,t.created_at");
$tq->execute([$pid]);
foreach ($tq->fetchAll() as $t) $cols[$t['status']][] = $t;

$colMeta = ['todo'=>['label'=>'📋 Todo','cls'=>'k-todo'],'inprogress'=>['label'=>'⚡ In Progress','cls'=>'k-inprogress'],'review'=>['label'=>'👁 Review','cls'=>'k-review'],'done'=>['label'=>'✅ Done','cls'=>'k-done']];

$team = $db->prepare("SELECT id, name FROM tf_users WHERE org_id=? AND role='developer' AND is_active=1");
$team->execute([currentUser()['org_id']]);
$teamMembers = $team->fetchAll();
?>
</head>
<body data-theme="<?= getUserTheme() ?>">
<div id="tf-curtain"></div>
<div class="tf-wrap">
<?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
<div class="tf-main">
  <div class="tf-topbar">
    <div style="display:flex; align-items:center; gap:20px;">
        <div class="tf-topbar-title">Kanban Board</div>
        <form method="GET" style="margin:0;">
            <select name="project" onchange="this.form.submit()" class="tf-inp" style="padding:6px 12px; border-radius:8px; border:1px solid var(--border); background:var(--surface2);">
                <?php if (!$projectList): ?><option value="">No Projects Managed</option><?php endif; ?>
                <?php foreach ($projectList as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $pid==$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

  </div>
  <div class="tf-page">
    <div class="tf-page-hd a1">
      <div><div class="tf-title">Team Kanban</div><div class="tf-subtitle">All team tasks · Drag to move</div></div>
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
          <div class="tf-task-card" data-id="<?= $t['id'] ?>" draggable="true" ondblclick="window.location.href='task_detail.php?id=<?= $t['id'] ?>'" title="Double-click to view details">
            <div class="tf-task-type-dot t-<?= $t['type'] ?>"></div>
            <div class="tf-task-title"><?= e($t['title']) ?></div>
            <div style="font-size:11px;color:var(--text3);margin-bottom:6px"><?= e($t['aname']??'Unassigned') ?></div>
            <div class="tf-task-foot">
              <span class="badge b-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span>
              <span class="tf-pts"><?= $t['story_points'] ?> pts</span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <button class="tf-add-card" onclick="Modal.open('addTaskModal')">+ Add task</button>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>
</div>
<div id="tf-toasts"></div>

<!-- ADD TASK MODAL -->
<div class="tf-overlay" id="addTaskModal">
  <div class="tf-modal">
    <div class="tf-modal-hd"><div class="tf-modal-title">Create New Task</div><button class="tf-modal-close" onclick="Modal.close('addTaskModal')">✕</button></div>
    <form id="addTaskForm" onsubmit="event.preventDefault(); Kanban.createTask(this)">
      <div class="tf-modal-body">
        <div class="tf-fg"><label class="tf-lbl">Task Title</label><input type="text" name="title" class="tf-inp" required placeholder="e.g. Design Landing Page"></div>
        
        <div class="g2">
          <div class="tf-fg"><label class="tf-lbl">Project</label>
            <select name="project_id" class="tf-inp tf-sel" required onchange="Kanban.loadSprints(this.value)">
              <option value="">Select Project</option>
              <?php foreach($projectList as $p): ?>
              <option value="<?= $p['id'] ?>" <?= (isset($sprintList[0]) && $sprintList[0]['project_id']==$p['id']) ? 'selected' : '' ?>><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="tf-fg"><label class="tf-lbl">Sprint</label>
            <select name="sprint_id" id="modalSprintSel" class="tf-inp tf-sel">
              <option value="">Backlog (No Sprint)</option>
              <?php foreach($sprintList as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $s['id']==$selectedSprint?'selected':'' ?>><?= e($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="g2">
          <div class="tf-fg"><label class="tf-lbl">Type</label>
            <select name="type" class="tf-inp tf-sel">
              <option value="task">Task</option>
              <option value="story">Story</option>
              <option value="bug">Bug</option>
              <option value="epic">Epic</option>
            </select>
          </div>
          <div class="tf-fg"><label class="tf-lbl">Priority</label>
            <select name="priority" class="tf-inp tf-sel">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
            </select>
          </div>
        </div>

        <div class="g2">
          <div class="tf-fg"><label class="tf-lbl">Assign To</label>
            <select name="assigned_to" class="tf-inp tf-sel">
              <option value="">Unassigned</option>
              <?php foreach($teamMembers as $m): ?>
              <option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="tf-fg"><label class="tf-lbl">Story Points</label><input type="number" name="story_points" class="tf-inp" value="0" min="0" max="100"></div>
        </div>

        <div class="tf-fg"><label class="tf-lbl">Description</label><textarea name="description" class="tf-inp" style="height:80px" placeholder="Details about this task..."></textarea></div>
      </div>
      <div class="tf-modal-foot">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('addTaskModal')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="createTaskBtn">Create Task</button>
      </div>
    </form>
  </div>
</div>

<script>
// Kanban specialized JS for the modal
Kanban.loadSprints = function(projectId) {
    if(!projectId) return;
    const sel = document.getElementById('modalSprintSel');
    sel.innerHTML = '<option value="">Backlog (No Sprint)</option>';
    fetch(APP_BASE + '/backend/api/search.php?type=sprint&project_id=' + projectId)
        .then(r => r.json())
        .then(d => {
            if(d.ok) {
                d.results.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.title;
                    sel.appendChild(opt);
                });
            }
        });
};

Kanban.createTask = function(form) {
    const btn = document.getElementById('createTaskBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    fetch(APP_BASE + '/backend/api/create_task.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if(d.ok) {
            Toast.show('Task created successfully');
            Modal.close('addTaskModal');
            setTimeout(() => location.reload(), 800);
        } else {
            Toast.show(d.msg || 'Error creating task', 'err');
            btn.disabled = false;
            btn.textContent = 'Create Task';
        }
    })
    .catch(() => {
        Toast.show('Network error', 'err');
        btn.disabled = false;
        btn.textContent = 'Create Task';
    });
};
</script>
