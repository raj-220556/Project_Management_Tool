<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Activity Log · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin('admin');
    $activePage = 'activity';
    $db = db();

    $orgId = currentUser()['org_id'];
    $projects = $db->query("SELECT id, name FROM tf_projects ORDER BY name")->fetchAll();
    $pid = (int)($_GET['project'] ?? 0);
    
    // Sync commits first
    getGitHubActivity($pid);

    $sql = "SELECT a.*, u.name uname FROM tf_activity a LEFT JOIN tf_users u ON a.user_id=u.id LEFT JOIN tf_projects p ON a.project_id=p.id WHERE (u.org_id=? OR p.org_id=?)";
    $params = [$orgId, $orgId];
    if ($pid > 0) {
        $sql .= " AND a.project_id=?";
        $params[] = $pid;
    }
    $sql .= " ORDER BY a.created_at DESC LIMIT 50";
    
    $activitiesQ = $db->prepare($sql);
    $activitiesQ->execute($params);
    $activities = $activitiesQ->fetchAll();
    ?>
</head>

<body data-theme="<?= getUserTheme() ?>">
    <div id="tf-curtain"></div>
    <div class="tf-wrap">
        <?php include __DIR__ . '/../../backend/shared/includes/sidebar.php'; ?>
        <div class="tf-main">
            <div class="tf-topbar">
                <div style="display:flex; align-items:center; gap:20px;">
                    <div class="tf-topbar-title">System Activity Log</div>
                    <form method="GET" style="margin:0;">
                        <select name="project" onchange="this.form.submit()" class="tf-inp" style="padding:6px 12px; border-radius:8px; border:1px solid var(--border); background:var(--surface2);">
                            <option value="0">All Projects Combined</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $pid==$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">Audit Trail</div>
                        <div class="tf-subtitle">History of all important actions across the platform</div>
                    </div>
                </div>

                <style>
                .tf-act { display: flex; gap: 20px; position: relative; padding: 20px 0 !important; align-items: flex-start; }
                .tf-act:not(:last-child)::before { 
                    content: ''; position: absolute; left: 17px; top: 46px; bottom: -20px; width: 2px; 
                    background: linear-gradient(to bottom, var(--border) 80%, transparent); 
                }
                .act-icon-box {
                    width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
                    font-size: 16px; flex-shrink: 0; position: relative; z-index: 2; border: 1px solid var(--border); background: var(--surface2);
                }
                .tf-act-content {
                    flex: 1; background: var(--surface); border: 1px solid var(--border); padding: 18px 20px; border-radius: 12px;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: all 0.2s; margin-top:-6px;
                }
                .tf-act-content:hover {
                    border-color: var(--brand); box-shadow: 0 6px 16px rgba(0,0,0,0.06); transform: translateY(-2px);
                }
                </style>
                <div class="card a2" style="background:transparent; border:none; box-shadow:none;">
                    <div class="card-body" style="padding:0">
                        <?php foreach ($activities as $a): 
                            $type = strtolower($a['entity_type'] ?? '');
                            $isGitHub = ($type === 'github_commit');
                            $icon = '📌'; $c = 'var(--text2)';
                            if ($isGitHub) { $icon = '🐙'; $c = '#24292e'; }
                            elseif (str_contains($type, 'task')) { $icon = '✓'; $c = '#10B981'; }
                            elseif (str_contains($type, 'project')) { $icon = '📁'; $c = '#3B82F6'; }
                            elseif (str_contains($type, 'sprint')) { $icon = '⚡'; $c = '#8B5CF6'; }
                            elseif (str_contains($type, 'user')) { $icon = '👤'; $c = '#F59E0B'; }

                            if ($isGitHub) {
                                $gh = @json_decode($a['old_value'], true) ?: [];
                                $a['uname'] = $a['github_author'] ?? 'GitHub User';
                                $a['commit_id'] = substr($a['commit_hash'] ?? '?', 0, 7);
                                $a['commit_url'] = $gh['commit_url'] ?? '#';
                                $a['repo_name'] = $gh['repo_name'] ?? 'Repository';
                                $a['message'] = $gh['message'] ?? '';
                            }
                        ?>
                            <div class="tf-act">
                                <div class="act-icon-box" style="color:<?= $c ?>; border-color:<?= $c ?>40; background:<?= $c ?>10;"><?= $icon ?></div>
                                <div class="tf-act-content">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                                        <?php if ($isGitHub): ?>
                                            <div class="tf-act-txt" style="font-size:14px; line-height:1.5;">
                                                <strong><?= e($a['uname']) ?></strong> pushed commit 
                                                <a href="<?= e($a['commit_url']) ?>" target="_blank" style="font-family:monospace; background:var(--surface2); padding:2px 8px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none; color:var(--text); border:1px solid var(--border)"><?= e($a['commit_id']) ?></a> 
                                                to <strong><?= e($a['repo_name']) ?></strong>
                                            </div>
                                        <?php else: ?>
                                            <div class="tf-act-txt" style="font-size:14px; line-height:1.5;">
                                                <strong><?= e($a['uname'] ?? 'System') ?></strong> <?= e($a['action']) ?> a <strong><?= e($a['entity_type']) ?></strong>
                                            </div>
                                        <?php endif; ?>
                                        <div class="tf-act-time" style="font-size:12px; font-weight:500; color:var(--text3); white-space:nowrap; margin-left:16px;">
                                            <?= timeAgo($a['created_at']) ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($isGitHub): ?>
                                        <div style="color:var(--text2); font-size:13px; font-family:monospace; display:inline-block; border-left:3px solid var(--border); padding-left:12px; margin-top:4px; background:var(--surface2); padding:8px 12px; border-radius:4px; width:100%; white-space:pre-wrap; word-break:break-all;">
                                            <?= e($a['message']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!$isGitHub && (!empty($a['old_value']) || !empty($a['new_value']))): ?>
                                        <div style="font-size:12px; background:var(--surface2); padding:8px 12px; border-radius:6px; margin-top:12px; color:var(--text2); display:inline-block; border:1px solid var(--border);">
                                            <span style="text-decoration:line-through; opacity:0.7; margin-right:8px;"><?= e($a['old_value'] ?? '') ?></span> 
                                            <span style="color:var(--brand); font-weight:600;">→</span>
                                            <span style="font-weight:500; margin-left:8px;"><?= e($a['new_value'] ?? '') ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!$isGitHub): ?>
                                        <div style="font-size:11px; color:var(--text3); margin-top:12px; border-top:1px dashed var(--border); padding-top:8px; display:inline-block;">
                                            Recorded IP: <?= e($a['ip'] ?? '0.0.0.0') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$activities): ?>
                            <p style="text-align:center;padding:40px;color:var(--text3)">No activity recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>