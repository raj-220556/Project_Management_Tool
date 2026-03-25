<?php
require_once __DIR__ . '/../../backend/shared/includes/init.php';

$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? '';

if (!$token || !in_array($action, ['approve', 'disapprove'])) {
    die("Invalid request parameters.");
}

$db = db();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT a.id, a.request_id, a.admin_id, a.status, r.project_id, r.status as req_status, r.requester_id, p.name as proj_name, p.org_id 
                          FROM tf_project_deletion_approvals a
                          JOIN tf_project_deletion_requests r ON a.request_id = r.id
                          JOIN tf_projects p ON r.project_id = p.id
                          WHERE a.token = ? FOR UPDATE");
    $stmt->execute([$token]);
    $approval = $stmt->fetch();

    if (!$approval) {
        throw new Exception("Invalid token or project no longer exists.");
    }

    if ($approval['req_status'] !== 'pending') {
        throw new Exception("This deletion request has already been processed.");
    }

    if ($approval['status'] !== 'pending') {
        throw new Exception("You have already {$approval['status']} this request.");
    }

    $statusEnum = $action === 'approve' ? 'approved' : 'disapproved';
    $db->prepare("UPDATE tf_project_deletion_approvals SET status=?, acted_at=NOW() WHERE id=?")->execute([$statusEnum, $approval['id']]);

    // Recalculate stats
    $statsStmt = $db->prepare("SELECT status, COUNT(*) as c FROM tf_project_deletion_approvals WHERE request_id=? GROUP BY status");
    $statsStmt->execute([$approval['request_id']]);
    $stats = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $appCount = (int)($stats['approved'] ?? 0);
    $disCount = (int)($stats['disapproved'] ?? 0);
    
    $totalStmt = $db->prepare("SELECT COUNT(*) FROM tf_project_deletion_approvals WHERE request_id=?");
    $totalStmt->execute([$approval['request_id']]);
    $totalAdmins = (int)$totalStmt->fetchColumn();

    $threshold = ceil($totalAdmins / 2);
    if ($totalAdmins == 2) $threshold = 2;

    if ($appCount >= $threshold) {
        $did = $approval['project_id'];
        $reqId = $approval['request_id'];
        $pname = $approval['proj_name'];
        $orgId = $approval['org_id'];

        $db->prepare("UPDATE tf_project_deletion_requests SET status='completed' WHERE id=?")->execute([$reqId]);

        $db->prepare('DELETE FROM tf_activity WHERE project_id=?')->execute([$did]);
        $db->prepare('DELETE FROM tf_tasks WHERE project_id=?')->execute([$did]);
        $db->prepare('DELETE FROM tf_sprints WHERE project_id=?')->execute([$did]);
        $db->prepare('DELETE FROM tf_projects WHERE id=?')->execute([$did]);
        
        logActivity($approval['admin_id'], null, null, 'permanently deleted project following approvals', 'project', $did, '', $pname);
        
        $orgUsers = $db->prepare("SELECT id FROM tf_users WHERE org_id=?");
        $orgUsers->execute([$orgId]);
        foreach($orgUsers->fetchAll() as $u) {
            notifyUser($u['id'], 'Project Deleted', "Project '$pname' was permanently purged after receiving admin approvals.", "projects.php");
        }
        
        $msg = "Project deletion approved by majority ($appCount/$totalAdmins) and project has been purged.";
    } elseif ($disCount > ($totalAdmins - $threshold)) {
        // Majority disapproval
        $db->prepare("UPDATE tf_project_deletion_requests SET status='rejected' WHERE id=?")->execute([$approval['request_id']]);
        $msg = "Project deletion request has been REJECTED by majority disapproval ($disCount/$totalAdmins).";
    } else {
        $msg = "You have successfully {$statusEnum} the request. Currently: {$appCount} Approved, {$disCount} Disapproved (out of {$totalAdmins} total admins).";
    }

    $db->commit();

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Deletion Request · SprintDesk</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #333; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 400px; text-align: center;}
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        a { display: inline-block; margin-top: 1rem; color: #6366f1; text-decoration: none; font-weight: bold;}
    </style>
</head>
<body>
    <div class="card">
        <?php if(isset($error)): ?>
            <h2 class="error">❌ Error</h2>
            <p><?= htmlspecialchars($error) ?></p>
        <?php else: ?>
            <h2 class="success">✅ Success</h2>
            <p><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>
        <a href="projects.php">Return to Dashboard</a>
    </div>
</body>
</html>
