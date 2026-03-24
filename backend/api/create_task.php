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
    $ins = $db->prepare("INSERT INTO tf_tasks (project_id, sprint_id, title, description, type, status, priority, assigned_to, story_points, position, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
        $u['id']
    ]);
    
    $taskId = $db->lastInsertId();
    logActivity($u['id'], $project_id, $taskId, 'created task', 'task', $taskId);
    
    if ($assigned_to) {
        notifyUser($assigned_to, 'New Task Assigned', "You have been assigned to task: $title", "kanban.php?sprint=$sprint_id");
        
        $devStmt = $db->prepare("SELECT name, email FROM tf_users WHERE id = ?");
        $devStmt->execute([$assigned_to]);
        $dev = $devStmt->fetch();

        $projStmt = $db->prepare("SELECT name FROM tf_projects WHERE id = ?");
        $projStmt->execute([$project_id]);
        $projName = $projStmt->fetchColumn();

        if ($dev && $projName) {
            $mgrName = $u['name'];
            $mgrEmail = $u['email'];
            $subject = "New Task Assigned: " . $title;
            $bodyHTML = "
                <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                    <h2 style='color: #6366f1;'>New Task Assignment</h2>
                    <p>Hello <strong>" . htmlspecialchars($dev['name']) . "</strong>,</p>
                    <p>You have been assigned a new task by <strong>" . htmlspecialchars($mgrName) . "</strong> (" . htmlspecialchars($mgrEmail) . ") in the project <strong>" . htmlspecialchars($projName) . "</strong>.</p>
                    <div style='background: #f8fafc; padding: 15px; border-left: 4px solid #6366f1; margin: 15px 0;'>
                        <h3 style='margin-top: 0;'>" . htmlspecialchars($title) . "</h3>
                        <p style='margin-bottom: 0;'>" . nl2br(htmlspecialchars($desc ?: 'No description provided.')) . "</p>
                    </div>
                    <p><a href='" . APP_URL . "/frontend/auth/login.php' style='display:inline-block;padding:10px 20px;background:#6366f1;color:#fff;text-decoration:none;border-radius:5px;'>View Task in SprintDesk</a></p>
                    <hr style='border: none; border-top: 1px solid #eaeaea; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #777;'>Regards,<br>SprintDesk Team</p>
                </div>
            ";
            sendSystemEmail($dev['email'], $subject, $bodyHTML);
        }
    }

    jsonResponse(['ok' => true, 'id' => $taskId]);
} catch (Exception $e) {
    jsonResponse(['ok' => false, 'msg' => 'Database error: ' . $e->getMessage()], 500);
}
