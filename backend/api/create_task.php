<?php
// backend/api/create_task.php
require_once __DIR__ . '/../shared/includes/init.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !in_array(currentUser()['role'], ['admin', 'manager'], true)) {
    jsonResponse(['ok' => false, 'msg' => 'Unauthorized: Only managers/admins can create tasks'], 403);
}

$data = json_decode(file_get_contents('php://input'), true);

$title = trim($data['title'] ?? '');
$project_id = (int)($data['project_id'] ?? 0);
$sprint_id = !empty($data['sprint_id']) ? (int)$data['sprint_id'] : null;
$type = $data['type'] ?? 'task';
$status = 'todo';
$priority = $data['priority'] ?? 'medium';
$assigned_to = !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null;
$points = (int)($data['story_points'] ?? 0);
$desc = trim($data['description'] ?? '');

if (!$title || !$project_id) {
    jsonResponse(['ok' => false, 'msg' => 'Missing required fields'], 400);
}

$db = db();
$u = currentUser();

// Get max position for the new task in its column
$posQ = $db->prepare("SELECT MAX(position) as maxp FROM tf_tasks WHERE project_id=? AND sprint_id" . ($sprint_id ? "=?" : " IS NULL") . " AND status=?");
$params = [$project_id];
if($sprint_id) $params[] = $sprint_id;
$params[] = $status;
$posQ->execute($params);
$maxPos = (int)$posQ->fetch()['maxp'] + 1;

try {
    $ins = $db->prepare("INSERT INTO tf_tasks (project_id, sprint_id, title, description, type, status, priority, assigned_to, story_points, position, created_by, org_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->execute([
        $project_id,
        $sprint_id,
        $title,
        $desc,
        $type,
        $status,
        $priority,
        $assigned_to,
        $points,
        $maxPos,
        $u['id'],
        $u['org_id']
    ]);
    
    $taskId = $db->lastInsertId();
    logActivity($u['id'], $project_id, $taskId, 'created task', 'task', $taskId);
    
    if ($assigned_to) {
        notifyUser($assigned_to, 'New Task Assigned', "You have been assigned to task: $title", "kanban.php?sprint=$sprint_id");
    }

    jsonResponse(['ok' => true, 'id' => $taskId]);
} catch (Exception $e) {
    jsonResponse(['ok' => false, 'msg' => 'Database error: ' . $e->getMessage()], 500);
}
