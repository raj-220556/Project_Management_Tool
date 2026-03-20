<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Analytics · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('admin');
    $activePage = 'analytics';
    $db = db();

    $orgId = currentUser()['org_id'];

    $tasksByStatusQ = $db->prepare("SELECT t.status,COUNT(*) c FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id WHERE p.org_id=? GROUP BY t.status");
    $tasksByStatusQ->execute([$orgId]); $tasksByStatus = $tasksByStatusQ->fetchAll(PDO::FETCH_KEY_PAIR);

    $tasksByTypeQ = $db->prepare("SELECT t.type,COUNT(*) c FROM tf_tasks t JOIN tf_projects p ON t.project_id=p.id WHERE p.org_id=? GROUP BY t.type");
    $tasksByTypeQ->execute([$orgId]); $tasksByType = $tasksByTypeQ->fetchAll(PDO::FETCH_KEY_PAIR);

    $activityByDayQ = $db->prepare("SELECT DATE(a.created_at) d, COUNT(*) c FROM tf_activity a LEFT JOIN tf_users u ON a.user_id=u.id LEFT JOIN tf_projects p ON a.project_id=p.id WHERE (u.org_id=? OR p.org_id=?) GROUP BY d ORDER BY d DESC LIMIT 7");
    $activityByDayQ->execute([$orgId, $orgId]); $activityByDay = $activityByDayQ->fetchAll(PDO::FETCH_KEY_PAIR);
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Platform Analytics</div>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">Performance Insights</div>
                        <div class="tf-subtitle">Live data from all projects and teams</div>
                    </div>
                </div>

                <div class="g2 a2">
                    <div class="card">
                        <div class="card-hd">
                            <div class="card-title">Tasks by Status</div>
                        </div>
                        <div class="card-body" style="height:300px"><canvas id="statusChart"></canvas></div>
                    </div>
                    <div class="card">
                        <div class="card-hd">
                            <div class="card-title">Tasks by Type</div>
                        </div>
                        <div class="card-body" style="height:300px"><canvas id="typeChart"></canvas></div>
                    </div>
                </div>
                <div class="card a3" style="margin-top:18px">
                    <div class="card-hd">
                        <div class="card-title">System Activity (Last 7 Days)</div>
                    </div>
                    <div class="card-body" style="height:250px"><canvas id="activityChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const statusData = {
            labels: ['Done', 'Review', 'In Progress', 'Todo'],
            datasets: [{
                data: [<?= $tasksByStatus['done'] ?? 0 ?>, <?= $tasksByStatus['review'] ?? 0 ?>, <?= $tasksByStatus['inprogress'] ?? 0 ?>, <?= $tasksByStatus['todo'] ?? 0 ?>],
                backgroundColor: ['#238636', '#2F81F7', '#D29922', '#6B7280'],
                borderWidth: 0
            }]
        };
        new Chart(document.getElementById('statusChart'), { type: 'doughnut', data: statusData, options: { maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } } });

        const typeData = {
            labels: ['Story', 'Bug', 'Task', 'Epic'],
            datasets: [{
                data: [<?= $tasksByType['story'] ?? 0 ?>, <?= $tasksByType['bug'] ?? 0 ?>, <?= $tasksByType['task'] ?? 0 ?>, <?= $tasksByType['epic'] ?? 0 ?>],
                backgroundColor: ['#2F81F7', '#DA3633', '#161B22', '#8B5CF6'],
                borderWidth: 0
            }]
        };
        new Chart(document.getElementById('typeChart'), { type: 'pie', data: typeData, options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });

        const actData = {
            labels: <?= json_encode(array_reverse(array_keys($activityByDay))) ?>,
            datasets: [{
                label: 'Actions',
                data: <?= json_encode(array_reverse(array_values($activityByDay))) ?>,
                borderColor: '#2F81F7',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(47,129,247,0.1)'
            }]
        };
        new Chart(document.getElementById('activityChart'), { type: 'line', data: actData, options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } }, x: { grid: { display: false } } } } });
    </script>
</body>

</html>