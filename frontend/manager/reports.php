<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reports · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('manager');
    $activePage = 'reports';
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Project Reports</div>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">Generated Reports</div>
                        <div class="tf-subtitle">Download and analyze project performance data</div>
                    </div>
                </div>
                <div class="card a2" style="text-align:center;padding:60px 20px;color:var(--text3)">
                    <div style="font-size:48px;margin-bottom:16px">📊</div>
                    <div style="font-size:16px">No reports generated yet. Schedule your first report in settings.</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>