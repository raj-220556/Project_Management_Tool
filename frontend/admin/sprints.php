<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sprints · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('admin');
    $activePage = 'sprints';
    $db = db();

    $sprints = $db->query("SELECT s.*, p.name pname, u.name uname FROM tf_sprints s JOIN tf_projects p ON s.project_id=p.id LEFT JOIN tf_users u ON s.created_by=u.id ORDER BY s.created_at DESC")->fetchAll();
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Sprint Management</div>
                <div class="tf-search"><span>🔍</span><input type="text" placeholder="Search sprints..." id="searchInp">
                </div>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">All Sprints</div>
                        <div class="tf-subtitle">
                            <?= count($sprints) ?> sprints across all projects
                        </div>
                    </div>
                </div>

                <div class="card a2">
                    <div class="tf-tbl-wrap" style="border:none">
                        <table id="sprintsTable">
                            <thead>
                                <tr>
                                    <th>Sprint Name</th>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th>Dates</th>
                                    <th>Created By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sprints as $s): ?>
                                    <tr>
                                        <td style="font-weight:600;color:var(--brand)">
                                            <?= e($s['name']) ?>
                                        </td>
                                        <td>
                                            <?= e($s['pname']) ?>
                                        </td>
                                        <td><span class="badge b-<?= $s['status'] ?>">
                                                <?= ucfirst($s['status']) ?>
                                            </span></td>
                                        <td style="font-size:12px;color:var(--text2)">
                                            <?= $s['start_date'] ? date('M j', strtotime($s['start_date'])) : '—' ?>
                                            →
                                            <?= $s['end_date'] ? date('M j, Y', strtotime($s['end_date'])) : '—' ?>
                                        </td>
                                        <td style="color:var(--text3)">
                                            <?= e($s['uname'] ?: 'System') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('searchInp')?.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#sprintsTable tbody tr').forEach(r => {
                r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    </script>
</body>

</html>