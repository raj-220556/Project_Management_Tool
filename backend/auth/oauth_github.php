<?php
require_once __DIR__ . '/../shared/includes/init.php';

if (GITHUB_CLIENT_ID === 'YOUR_GITHUB_CLIENT_ID') {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>OAuth Setup Required</title>
<style>body{font-family:sans-serif;background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.card{background:#1e293b;padding:40px;border-radius:20px;max-width:480px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,.3);border:1px solid #334155}
h2{color:#6366f1;margin-bottom:16px}p{line-height:1.6;color:#94a3b8;margin-bottom:24px}code{background:#0f172a;padding:4px 8px;border-radius:6px;color:#818cf8;font-family:monospace}
.btn{display:inline-block;background:#334155;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:600;transition:.2s;border:1px solid #475569}.btn:hover{background:#475569}</style></head>
<body><div class="card"><h2>🐙 GitHub OAuth Setup</h2><p>To enable GitHub login, please add your <code>GITHUB_CLIENT_ID</code> and <code>CLIENT_SECRET</code> in: <br><br><code>backend/shared/includes/init.php</code></p><a href="../../frontend/auth/login.php" class="btn">Return to Login</a></div></body></html>');
}

$url = 'https://github.com/login/oauth/authorize?' . http_build_query([
    'client_id'    => GITHUB_CLIENT_ID,
    'redirect_uri' => GITHUB_REDIRECT,
    'scope'        => 'user:email',
]);
header("Location: $url"); exit;
