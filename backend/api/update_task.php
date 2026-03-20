<?php
require_once __DIR__ . '/../shared/includes/init.php';
requireLogin();
$data = json_decode(file_get_contents('php://input'), true);
$id     = (int)($data['id'] ?? 0);
$status = $data['status'] ?? '';
$allowed = ['todo','inprogress','review','done'];
$db = db();
$u = currentUser();

if ($id && in_array($status, $allowed, true)) {
    // If developer, check assignment
    if ($u['role'] === 'developer') {
        $chk = $db->prepare('SELECT id FROM tf_tasks WHERE id=? AND assigned_to=?');
        $chk->execute([$id, $u['id']]);
        if (!$chk->fetch()) {
            jsonResponse(['ok' => false, 'msg' => 'Forbidden: Can only move assigned tasks'], 403);
        }
    }

    $db->beginTransaction();
    try {
        // Update status of the moved task
        $s = $db->prepare('UPDATE tf_tasks SET status=? WHERE id=?');
        $s->execute([$status, $id]);

        // Update positions if provided
        if (isset($data['positions']) && is_array($data['positions'])) {
            $upPos = $db->prepare("UPDATE tf_tasks SET position=? WHERE id=?");
            foreach ($data['positions'] as $index => $taskId) {
                $upPos->execute([$index, $taskId]);
            }
        }
        
        $db->commit();
        logActivity($u['id'], null, $id, 'updated task on kanban', 'task', $id);
        jsonResponse(['ok'=>true]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['ok'=>false, 'msg'=>$e->getMessage()], 500);
    }
} else {
    jsonResponse(['ok' => false, 'msg' => 'Invalid input'], 400);
}
