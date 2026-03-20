<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Global Reports · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('org_manager');
    $activePage = 'reports'; // Will only highlight if added to sidebar, but functionally correct.
    ?>
    <style>
        .tf-page { max-width: 1100px; margin: 0 auto; }
        .page-header-title { font-family: 'Outfit', sans-serif; font-size: 34px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 8px; }
        .page-header-sub { color: var(--text3); font-size: 15px; margin-bottom: 24px;}
        .prem-card-wrap { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; overflow: hidden; padding: 40px; text-align: center; }
    </style>
</head>
<body data-theme="dark">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div class="tf-topbar-title">Reports</div>
            </div>
            <div class="tf-page">
                <div class="a1">
                    <h1 class="page-header-title">System Reports</h1>
                    <p class="page-header-sub">Generate analytical reports across all organizations</p>
                </div>
                
                <div class="prem-card-wrap a2">
                    <div style="font-size:48px; margin-bottom: 16px;">📊</div>
                    <h2 style="font-family:'Outfit',sans-serif; font-size: 24px; margin-bottom: 8px;">Automated Reporting</h2>
                    <p style="color:var(--text3); max-width: 400px; margin: 0 auto 24px;">Global report generation is currently being integrated into the main pipeline. You will be able to export PDF and CSV reports here soon.</p>
                    <button class="btn btn-primary" onclick="alert('Export processing engine is currently offline.')">Generate Sandbox Report</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
