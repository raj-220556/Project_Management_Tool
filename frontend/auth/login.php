<?php
// REVISION: JIRA_UI_V1_0_1
// frontend/auth/login.php
require_once __DIR__ . '/../../backend/shared/includes/init.php';

// Already logged in → go to dashboard
if (isLoggedIn())
    goToDashboard(currentUser()['role']);

$err = '';
$errMap = ['access' => 'You do not have permission to access that area.'];
if (!empty($_GET['err']))
    $err = $errMap[$_GET['err']] ?? 'An error occurred.';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['pass'] ?? '';
    if ($email && $pass) {
        $s = db()->prepare('SELECT * FROM tf_users WHERE email = ? AND is_active = 1 AND oauth_provider = "local"');
        $s->execute([$email]);
        $user = $s->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            loginAs($user);
            goToDashboard($user['role'], 'login=1');
        } else {
            $err = 'Invalid email or password.';
        }
    } else {
        $err = 'Please fill in all fields.';
    }
}

$savedEmail = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars(($_COOKIE['sd_theme'] ?? 'light')) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In · SprintDesk</title>
    <link rel="stylesheet" href="<?= URL_ASSETS ?>/css/app.css?v=1.0.1">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            align-items: stretch
        }

        .auth-wrap {
            display: flex;
            width: 100%;
            min-height: 100vh
        }

        .auth-art {
            width: 44%;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 50px;
            flex-shrink: 0;
            background: var(--surface2);
        }

        .art-video {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: 0;
            object-fit: cover;
        }

        .art-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(145deg, rgba(0, 82, 204, 0.4) 0%, rgba(0, 82, 204, 0.2) 100%);
            z-index: 1;
        }

        .art-noise {
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.04'/%3E%3C/svg%3E");
            background-size: 180px;
            opacity: .6
        }

        .art-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px)
        }

        .ab1 {
            width: 380px;
            height: 380px;
            background: rgba(255, 255, 255, .1);
            top: -80px;
            right: -80px;
            animation: blobF 7s ease-in-out infinite
        }

        .ab2 {
            width: 280px;
            height: 280px;
            background: rgba(255, 87, 87, .15);
            bottom: -40px;
            left: -60px;
            animation: blobF 7s 3.5s ease-in-out infinite
        }

        @keyframes blobF {

            0%,
            100% {
                transform: translate(0, 0) scale(1)
            }

            50% {
                transform: translate(20px, -18px) scale(1.06)
            }
        }

        .art-grid {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, .05) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .05) 1px, transparent 1px);
            background-size: 46px 46px
        }

        .art-content {
            position: relative;
            z-index: 1;
            color: #fff;
            text-align: center
        }

        .art-logo {
            font-size: 28px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 11px;
            justify-content: center;
            margin-bottom: 36px
        }

        .art-logo-icon {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, .2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            backdrop-filter: blur(12px)
        }

        .art-logo em {
            color: rgba(255, 255, 255, .7);
            font-style: normal
        }

        .art-heading {
            font-size: 34px;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 14px
        }

        .art-sub {
            font-size: 15px;
            opacity: .75;
            line-height: 1.65;
            max-width: 300px;
            margin: 0 auto 36px
        }

        .art-pills {
            display: flex;
            flex-direction: column;
            gap: 10px;
            text-align: left
        }

        .art-pill {
            display: flex;
            align-items: center;
            gap: 11px;
            background: rgba(255, 255, 255, .1);
            backdrop-filter: blur(12px);
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13.5px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* FORM PANEL */
        .auth-form {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 36px
        }

        .auth-box {
            width: 100%;
            max-width: 410px
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text3);
            margin-bottom: 28px;
            transition: color .2s
        }

        .back-home:hover {
            color: var(--brand)
        }

        .auth-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 6px
        }

        .auth-sub {
            font-size: 14px;
            color: var(--text3);
            margin-bottom: 30px
        }

        /* OAuth */
        .oauth-row {
            display: flex;
            flex-direction: column;
            gap: 9px;
            margin-bottom: 22px
        }

        .oauth-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 11px;
            border-radius: var(--r2);
            border: 1.5px solid var(--border);
            background: var(--surface);
            font-size: 13.5px;
            font-weight: 500;
            color: var(--text);
            cursor: pointer;
            text-decoration: none;
            transition: all .2s
        }

        .oauth-btn:hover {
            border-color: var(--brand);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(99, 102, 241, .1);
            color: var(--text)
        }

        .oauth-github {
            background: #111;
            color: #fff;
            border-color: #111
        }

        .oauth-github:hover {
            background: #222;
            color: #fff
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 11px;
            margin: 18px 0
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: var(--border)
        }

        .divider-txt {
            font-size: 11.5px;
            color: var(--text3);
            font-weight: 500
        }

        /* Password eye */
        .pass-wrap {
            position: relative
        }

        .eye-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 15px;
            color: var(--text3);
            cursor: pointer;
            padding: 4px;
            transition: color .2s
        }

        .eye-btn:hover {
            color: var(--text)
        }

        /* Demo cards */
        .demo-label {
            font-size: 12px;
            color: var(--text3);
            text-align: center;
            margin: 18px 0 10px;
            font-weight: 500
        }

        .demo-cards {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .demo-card {
            width: 70px;
            height: 70px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            border: 1.5px solid var(--border);
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: var(--surface)
        }

        .demo-card:hover {
            border-color: var(--brand);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, .1)
        }

        .demo-card-icon {
            font-size: 22px;
            margin-bottom: 4px;
        }

        .demo-card-role {
            font-size: 11px;
            font-weight: 700;
            color: var(--brand);
            text-transform: uppercase;
            letter-spacing: .6px;
            line-height: 1.2;
            margin-top: 2px;
        }

        .demo-card-em {
            display: none;
        }

        @media(max-width:768px) {
            .auth-art {
                display: none
            }

            .auth-form {
                padding: 30px 20px
            }
        }
    </style>
</head>

<body>
    <div id="tf-curtain"></div>

    <div class="auth-wrap">
        <!-- ART -->
        <div class="auth-art">
            <video class="art-video" autoplay muted loop playsinline>
                <source src="https://assets.mixkit.co/videos/preview/mixkit-software-developer-working-on-code-38390-large.mp4" type="video/mp4">
            </video>
            <div class="art-overlay"></div>
            <div class="art-noise"></div>
            <div class="art-blob ab1"></div>
            <div class="art-blob ab2"></div>
            <div class="art-grid"></div>
            <div class="art-content">
                <div class="art-logo">
                    <div class="art-logo-icon">⚡</div>
                    Sprint<em>Desk</em>
                </div>
                <h2 class="art-heading">Your team's<br>command center</h2>
                <p class="art-sub">Plan sprints, track tasks, ship features — all in one focused workspace.</p>
                <div class="art-pills">
                    <div class="art-pill">🗂️ Kanban &amp; Sprint Boards</div>
                    <div class="art-pill">🏢 Multi-Organization Support</div>
                    <div class="art-pill">📊 Analytics &amp; Reports</div>
                    <div class="art-pill">🔗 GitHub Integration</div>
                </div>
            </div>
        </div>

        <!-- FORM -->
        <div class="auth-form">
            <div class="auth-box">
                <a href="<?= URL_LANDING ?>" class="back-home">← Back to home</a>
                <h1 class="auth-title">Welcome back 👋</h1>
                <p class="auth-sub">Sign in to your SprintDesk workspace</p>


                <?php if ($err): ?>
                    <div class="tf-err">⚠️ <?= e($err) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="tf-fg">
                        <label class="tf-lbl">Email Address</label>
                        <input type="email" name="email" class="tf-inp" placeholder="you@example.com" required
                            value="<?= $savedEmail ?>">
                    </div>
                    <div class="tf-fg">
                        <label class="tf-lbl">Password</label>
                        <div class="pass-wrap">
                            <input type="password" name="pass" id="passInp" class="tf-inp" placeholder="••••••••"
                                required>
                            <button type="button" class="eye-btn" id="eyeBtn">👁</button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"
                        style="width:100%;justify-content:center;padding:12px;font-size:14px;margin-top:4px"
                        id="submitBtn">
                        Sign In →
                    </button>
                </form>
                <p class="demo-label" style="font-size:14px; color:var(--text); margin:32px 0 14px;"><strong>Strict Access Hierarchy</strong><br><span style="color:var(--text2); font-weight:normal; font-size:12.5px; opacity: 0.85;">Global creates Admin → Admin creates Manager → Manager creates User</span></p>
                <div class="demo-cards">
                    <div class="demo-card" onclick="fill('orgmanager@sprintdesk.com')">
                        <div class="demo-card-icon">👑</div>
                        <div class="demo-card-role">Global</div>
                        <div class="demo-card-em">orgmanager@</div>
                    </div>
                    <div class="demo-card" onclick="fill('admin@sprintdesk.com')">
                        <div class="demo-card-icon">🏢</div>
                        <div class="demo-card-role">Admin</div>
                        <div class="demo-card-em">admin@</div>
                    </div>
                    <div class="demo-card" onclick="fill('manager@sprintdesk.com')">
                        <div class="demo-card-icon">🗂️</div>
                        <div class="demo-card-role">Manager</div>
                        <div class="demo-card-em">manager@</div>
                    </div>
                    <div class="demo-card" onclick="fill('dev1@sprintdesk.com')">
                        <div class="demo-card-icon">💻</div>
                        <div class="demo-card-role">User</div>
                        <div class="demo-card-em">dev1@</div>
                    </div>
                </div>
                <p style="text-align:center;font-size:12.5px;color:var(--text3);margin-top:16px; opacity: 0.85;">Demo password for all accounts: <strong style="color:var(--text); font-weight: 700;">password</strong></p>
            </div>
        </div>
    </div>

    <script>
        // theme from cookie or localStorage
        const t = localStorage.getItem('sd_theme') || 'light';
        document.documentElement.setAttribute('data-theme', t);

        document.getElementById('eyeBtn').addEventListener('click', () => {
            const p = document.getElementById('passInp');
            p.type = p.type === 'password' ? 'text' : 'password';
            document.getElementById('eyeBtn').textContent = p.type === 'password' ? '👁' : '🙈';
        });

        function fill(email) {
            document.querySelector('[name="email"]').value = email;
            const p = document.getElementById('passInp');
            p.value = 'password'; p.type = 'text';
            document.getElementById('eyeBtn').textContent = '🙈';
        }

        document.querySelector('form').addEventListener('submit', () => {
            const b = document.getElementById('submitBtn');
            b.textContent = 'Signing in…'; b.disabled = true; b.style.opacity = '.75';
        });

        // Curtain
        const curtain = document.getElementById('tf-curtain');
        if (curtain) {
            document.querySelectorAll('a[href]').forEach(a => {
                a.addEventListener('click', e => {
                    const h = a.getAttribute('href');
                    if (!h || h.startsWith('#') || h.startsWith('javascript') || a.classList.contains('oauth-btn')) return;
                    e.preventDefault();
                    curtain.classList.add('rising');
                    setTimeout(() => window.location.href = h, 380);
                });
            });
        }
    </script>
</body>

</html>