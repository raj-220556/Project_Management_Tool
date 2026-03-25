<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Team Approvals · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('admin');
    $activePage = 'removal_requests';
    $db = db();
    $orgId = currentUser()['org_id'];

    // Handle Approve
    if (isset($_GET['approve']) && isset($_GET['req_id'])) {
        $reqId = (int)$_GET['req_id'];
        $req = $db->prepare("SELECT * FROM tf_member_removal_requests WHERE id = ?");
        $req->execute([$reqId]);
        $data = $req->fetch();

        if ($data && $data['status'] === 'pending') {
            $db->prepare("UPDATE tf_member_removal_requests SET status = 'approved' WHERE id = ?")->execute([$reqId]);
            $db->prepare("DELETE FROM tf_project_members WHERE project_id = ? AND user_id = ?")->execute([$data['project_id'], $data['user_id']]);
            
            logActivity(currentUser()['id'], $data['project_id'], null, 'approved member removal', 'project', $data['user_id']);
            
            // Send notifying emails
            $mem = $db->prepare("SELECT name, email FROM tf_users WHERE id = ?")->execute([$data['user_id']]);
            $mem = $db->query("SELECT name, email FROM tf_users WHERE id = {$data['user_id']}")->fetch();
            $mgr = $db->query("SELECT name, email FROM tf_users WHERE id = {$data['manager_id']}")->fetch();
            $proj = $db->query("SELECT name FROM tf_projects WHERE id = {$data['project_id']}")->fetch();

            if ($mem && $mgr && $proj) {
                // Email to member
                $subjMem = "Removal Approved: " . $proj['name'];
                $bodyMem = "
                    <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                        <h2 style='color: #ef4444;'>Project Update</h2>
                        <p>Hello <strong>" . htmlspecialchars($mem['name']) . "</strong>,</p>
                        <p>The admin has <strong>approved</strong> your manager's request to remove you from the project <strong>" . htmlspecialchars($proj['name']) . "</strong>. You are no longer part of this project's team.</p>
                        <hr style='border: none; border-top: 1px solid #eaeaea; margin: 20px 0;'>
                        <p style='font-size: 12px; color: #777;'>Regards,<br>SprintDesk Team</p>
                    </div>";
                sendSystemEmail($mem['email'], $subjMem, $bodyMem);
                notifyUser($data['user_id'], "Removal Approved", "You have been removed from project " . $proj['name'] . ".", 'system');

                // Email to manager
                $subjMgr = "Removal Request Approved: " . $proj['name'];
                $bodyMgr = "
                    <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                        <h2 style='color: #10b981;'>Request Approved</h2>
                        <p>Hello <strong>" . htmlspecialchars($mgr['name']) . "</strong>,</p>
                        <p>The admin has <strong>approved</strong> your request to remove <strong>" . htmlspecialchars($mem['name']) . "</strong> from project <strong>" . htmlspecialchars($proj['name']) . "</strong>.</p>
                        <hr style='border: none; border-top: 1px solid #eaeaea; margin: 20px 0;'>
                        <p style='font-size: 12px; color: #777;'>Regards,<br>SprintDesk Team</p>
                    </div>";
                sendSystemEmail($mgr['email'], $subjMgr, $bodyMgr);
                notifyUser($data['manager_id'], "Request Approved", "Your request to remove " . $mem['name'] . " from " . $proj['name'] . " was approved.", 'system');
            }
            
            header("Location: removal_requests.php?ok=1");
            exit;
        }
    }

    // Handle Reject
    if (isset($_GET['reject']) && isset($_GET['req_id'])) {
        $reqId = (int)$_GET['req_id'];
        $req = $db->prepare("SELECT * FROM tf_member_removal_requests WHERE id = ?");
        $req->execute([$reqId]);
        $data = $req->fetch();

        if ($data && $data['status'] === 'pending') {
            $db->prepare("UPDATE tf_member_removal_requests SET status = 'rejected' WHERE id = ?")->execute([$reqId]);
            
            logActivity(currentUser()['id'], $data['project_id'], null, 'rejected member removal', 'project', $data['user_id']);
            
            $mem = $db->query("SELECT name, email FROM tf_users WHERE id = {$data['user_id']}")->fetch();
            $mgr = $db->query("SELECT name, email FROM tf_users WHERE id = {$data['manager_id']}")->fetch();
            $proj = $db->query("SELECT name FROM tf_projects WHERE id = {$data['project_id']}")->fetch();

            if ($mgr && $proj && $mem) {
                // Email to manager
                $subjMgr = "Removal Request Rejected: " . $proj['name'];
                $bodyMgr = "
                    <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                        <h2 style='color: #ef4444;'>Request Rejected</h2>
                        <p>Hello <strong>" . htmlspecialchars($mgr['name']) . "</strong>,</p>
                        <p>The admin has <strong>rejected</strong> your request to remove <strong>" . htmlspecialchars($mem['name']) . "</strong> from project <strong>" . htmlspecialchars($proj['name']) . "</strong>. Contact the admin if you have questions.</p>
                        <hr style='border: none; border-top: 1px solid #eaeaea; margin: 20px 0;'>
                        <p style='font-size: 12px; color: #777;'>Regards,<br>SprintDesk Team</p>
                    </div>";
                sendSystemEmail($mgr['email'], $subjMgr, $bodyMgr);
                notifyUser($data['manager_id'], "Request Rejected", "Your request to remove " . $mem['name'] . " from " . $proj['name'] . " was rejected.", 'system');
            }

            header("Location: removal_requests.php?ok=2");
            exit;
        }
    }

    $requestsQuery = $db->prepare("
        SELECT r.*, p.name as project_name, u.name as member_name, u.email as member_email, m.name as manager_name, m.email as manager_email
        FROM tf_member_removal_requests r
        JOIN tf_projects p ON r.project_id = p.id
        JOIN tf_users u ON r.user_id = u.id
        JOIN tf_users m ON r.manager_id = m.id
        WHERE u.org_id = ?
        ORDER BY r.created_at DESC
    ");
    $requestsQuery->execute([$orgId]);
    $requests = $requestsQuery->fetchAll();
    ?>
</head>
<body data-theme="<?= getUserTheme() ?>">
<div id="tf-curtain"></div>
<div class="tf-wrap">
    <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
    <div class="tf-main">
        <div class="tf-topbar">
            <div class="tf-topbar-title">Team Approvals</div>
            <div class="tf-search"><span>🔍</span><input type="text" placeholder="Search requests..." id="searchInp"></div>
        </div>
        <div class="tf-page">
            <div class="tf-page-hd a1">
                <div>
                    <div class="tf-title">Removal Requests</div>
                    <div class="tf-subtitle">Approve or reject team member removal requests from project managers.</div>
                </div>
            </div>

            <?php if(isset($_GET['ok']) && $_GET['ok']==1): ?><div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);border-radius:10px;padding:12px 16px;font-size:13px;color:#10b981;margin-bottom:16px">✅ Request approved and member removed.</div><?php endif; ?>
            <?php if(isset($_GET['ok']) && $_GET['ok']==2): ?><div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;font-size:13px;color:#ef4444;margin-bottom:16px">✅ Request rejected successfully.</div><?php endif; ?>

            <div class="card a2">
                <div class="tf-tbl-wrap" style="border:none">
                    <table id="reqsTable">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Manager</th>
                                <th>Member (to remove)</th>
                                <th>Status</th>
                                <th>Requested On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($requests as $r): ?>
                                <tr>
                                    <td><span style="font-weight:600"><?= e($r['project_name']) ?></span></td>
                                    <td>
                                        <div style="font-size:14px;color:var(--text);"><?= e($r['manager_name']) ?></div>
                                        <div style="font-size:12px;color:var(--text3);"><?= e($r['manager_email']) ?></div>
                                    </td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px">
                                            <div class="tf-sb-avatar" style="width:28px;height:28px;font-size:10px;flex-shrink:0"><?= e(userInitials($r['member_name'])) ?></div>
                                            <div>
                                                <div style="font-size:14px;color:var(--text);"><?= e($r['member_name']) ?></div>
                                                <div style="font-size:12px;color:var(--text3);"><?= e($r['member_email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <span class="badge b-todo">Pending</span>
                                        <?php elseif ($r['status'] === 'approved'): ?>
                                            <span class="badge b-done">Approved</span>
                                        <?php else: ?>
                                            <span class="badge" style="background:rgba(239,68,68,0.1);color:#ef4444;">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--text3);font-size:13px"><?= date('M j, Y H:i', strtotime($r['created_at'])) ?></td>
                                    <td>
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <div style="display:flex; gap:6px;">
                                                <a href="javascript:void(0)" onclick="if(confirm('Approve removal? Member will be immediately removed from project.')) window.location.href='removal_requests.php?approve=1&req_id=<?= $r['id'] ?>'" class="btn btn-primary btn-sm" style="background:#10b981;border:none;">Approve</a>
                                                <a href="javascript:void(0)" onclick="if(confirm('Reject this request?')) window.location.href='removal_requests.php?reject=1&req_id=<?= $r['id'] ?>'" class="btn btn-secondary btn-sm" style="color:#ef4444; border-color:rgba(239,68,68,0.2); background:rgba(239,68,68,0.05)">Reject</a>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:var(--text3);font-size:12px;font-style:italic;">No actions</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($requests)): ?>
                                <tr><td colspan="6" style="text-align:center;color:var(--text3);padding:30px">No removal requests found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('searchInp')?.addEventListener('input', function(){
        const q = this.value.toLowerCase();
        document.querySelectorAll('#reqsTable tbody tr').forEach(r=>{
            r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
</script>
</body>
</html>
