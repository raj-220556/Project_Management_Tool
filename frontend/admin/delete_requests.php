<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Delete Requests · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('admin');
    $activePage = 'delete_requests';
    $db = db();
    $currentUser = currentUser();
    $orgId = $currentUser['org_id'];
    $adminId = $currentUser['id'];

    // Handle direct approval/rejection from this UI
    if (isset($_GET['action']) && isset($_GET['token'])) {
        $token = $_GET['token'];
        $action = $_GET['action']; // 'approve' or 'disapprove'
        header("Location: process_deletion.php?token=$token&action=$action");
        exit;
    }

    // Fetch deletion requests for this organization
    $query = $db->prepare("
        SELECT r.id as request_id, r.project_id, r.requester_id, r.status as request_status, r.created_at, r.reason,
               p.name as project_name, u.name as requester_name,
               a.token, a.status as my_status
        FROM tf_project_deletion_requests r
        JOIN tf_projects p ON r.project_id = p.id
        JOIN tf_users u ON r.requester_id = u.id
        JOIN tf_project_deletion_approvals a ON a.request_id = r.id AND a.admin_id = ?
        WHERE p.org_id = ? AND r.status = 'pending'
        ORDER BY r.created_at DESC
    ");
    $query->execute([$adminId, $orgId]);
    $requests = $query->fetchAll();

    // Get all approvals for each request to show status of other admins
    $allApprovals = [];
    if (!empty($requests)) {
        $reqIds = array_column($requests, 'request_id');
        $placeholders = implode(',', array_fill(0, count($reqIds), '?'));
        $appQuery = $db->prepare("
            SELECT a.*, u.name as admin_name 
            FROM tf_project_deletion_approvals a
            JOIN tf_users u ON a.admin_id = u.id
            WHERE a.request_id IN ($placeholders)
            ORDER BY a.admin_id ASC
        ");
        $appQuery->execute($reqIds);
        foreach ($appQuery->fetchAll() as $row) {
            $allApprovals[$row['request_id']][] = $row;
        }
    }
    ?>
</head>
<body data-theme="<?= getUserTheme() ?>">
<div id="tf-curtain"></div>
<div class="tf-wrap">
    <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
    <div class="tf-main">
        <div class="tf-topbar">
            <div class="tf-topbar-title">Project Deletion Approvals</div>
            <div class="tf-search"><span>🔍</span><input type="text" placeholder="Search requests..." id="searchInp"></div>
        </div>
        <div class="tf-page">
            <div class="tf-page-hd a1">
                <div>
                    <div class="tf-title">Pending Deletions</div>
                    <div class="tf-subtitle">Manage project deletion requests that require admin consensus.</div>
                </div>
            </div>

            <div class="card a2">
                <div class="tf-tbl-wrap" style="border:none">
                    <table id="reqsTable">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th style="width:25%">Reason</th>
                                <th>Requested By</th>
                                <th>Admin Consensus</th>
                                <th>Your Action</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($requests as $r): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--text); font-size:15px;"><?= e($r['project_name']) ?></div>
                                        <div style="font-size:11px; color:#ef4444; text-transform:uppercase; font-weight:600; margin-top:2px;">⚠️ Pending Deletion</div>
                                    </td>
                                    <td>
                                        <div style="font-size:13px; color:var(--text2); line-height:1.4; max-height:60px; overflow-y:auto;">
                                            <?= nl2br(e($r['reason'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px">
                                            <div class="tf-sb-avatar" style="width:28px;height:28px;font-size:10px;flex-shrink:0"><?= e(userInitials($r['requester_name'])) ?></div>
                                            <div style="font-size:14px;color:var(--text);"><?= e($r['requester_name']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; flex-direction:column; gap:6px;">
                                            <?php foreach(($allApprovals[$r['request_id']] ?? []) as $app): ?>
                                                <?php 
                                                    $bClass = 'b-todo';
                                                    $icon = '⏳';
                                                    if($app['status'] === 'approved') { $bClass = 'b-done'; $icon = '✅'; }
                                                    if($app['status'] === 'disapproved') { $bClass = 'badge-danger'; $icon = '❌'; }
                                                ?>
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <span class="badge <?= $bClass ?>" style="font-size:9px; width:75px; text-align:center; padding:2px 0;">
                                                        <?= ucfirst($app['status']) ?>
                                                    </span>
                                                    <span style="font-size:11px; color:var(--text2);"><?= e($app['admin_name']) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($r['my_status'] === 'pending'): ?>
                                            <div style="display:flex; gap:6px;">
                                                <a href="?action=approve&token=<?= $r['token'] ?>" class="btn btn-primary btn-sm" style="background:#10b981; border:none; padding:4px 12px; font-size:11px;">Approve</a>
                                                <a href="?action=disapprove&token=<?= $r['token'] ?>" class="btn btn-secondary btn-sm" style="color:#ef4444; border-color:rgba(239,68,68,0.2); background:rgba(239,68,68,0.05); padding:4px 12px; font-size:11px;">Reject</a>
                                            </div>
                                        <?php else: ?>
                                            <?php 
                                                $color = $r['my_status'] === 'approved' ? '#10b981' : '#ef4444';
                                            ?>
                                            <span style="color:<?= $color ?>; font-size:12px; font-weight:600; font-style:italic;">
                                                <?= $r['my_status'] === 'approved' ? 'Approved ✓' : 'Disapproved ✗' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--text3);font-size:12px"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($requests)): ?>
                                <tr><td colspan="5" style="text-align:center;color:var(--text3);padding:40px">
                                    <div style="font-size:30px; margin-bottom:10px;">🍃</div>
                                    No pending project deletion requests.
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .badge-danger { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.1); border-radius: 12px; font-weight: 500; }
</style>
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
