<?php
// frontend/auth/register.php
require_once __DIR__ . '/../../backend/shared/includes/init.php';

// Already logged in → go to dashboard
if (isLoggedIn())
    goToDashboard(currentUser()['role']);

$err = '';
$success = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $org_name = trim($_POST['org_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['pass'] ?? '';
    $email_2  = trim($_POST['email_2'] ?? '');
    $pass_2   = $_POST['pass_2'] ?? '';
    $email_3  = trim($_POST['email_3'] ?? '');
    $pass_3   = $_POST['pass_3'] ?? '';
    $address  = trim($_POST['address'] ?? '');
    $domain   = trim($_POST['domain'] ?? '');

    if ($org_name && $email && $pass) {
        $db = db();
        
        // Check if email already exists in users
        $checkUser = $db->prepare("SELECT id FROM tf_users WHERE email = ?");
        $checkUser->execute([$email]);
        if ($checkUser->fetch()) {
            $err = 'This email is already registered.';
        } else {
            // Check if request already exists for this email or org_name (optional but good)
            $checkReq = $db->prepare("SELECT id FROM tf_org_requests WHERE email = ? OR org_name = ?");
            $checkReq->execute([$email, $org_name]);
            if ($checkReq->fetch()) {
                $err = 'A registration request for this company or email is already pending.';
            } else {
                $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
                $hashedPass2 = $pass_2 ? password_hash($pass_2, PASSWORD_DEFAULT) : null;
                $hashedPass3 = $pass_3 ? password_hash($pass_3, PASSWORD_DEFAULT) : null;

                $stmt = $db->prepare("INSERT INTO tf_org_requests (org_name, email, password, email_2, password_2, email_3, password_3, address, domain, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                if ($stmt->execute([$org_name, $email, $hashedPass, $email_2 ?: null, $hashedPass2, $email_3 ?: null, $hashedPass3, $address, $domain])) {
                    $success = 'Your registration request has been submitted successfully! It is pending approval from the Organisation Manager.';
                    // Clear fields
                    $_POST = [];
                } else {
                    $err = 'Failed to submit request. Please try again.';
                }
            }
        }
    } else {
        $err = 'Please fill in all required fields (Company Name, Email, Password).';
    }
}

$savedOrg     = htmlspecialchars($_POST['org_name'] ?? '', ENT_QUOTES);
$savedEmail   = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES);
$savedEmail2  = htmlspecialchars($_POST['email_2'] ?? '', ENT_QUOTES);
$savedEmail3  = htmlspecialchars($_POST['email_3'] ?? '', ENT_QUOTES);
$savedAddress = htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES);
$savedDomain  = htmlspecialchars($_POST['domain'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars(($_COOKIE['sd_theme'] ?? 'light')) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Company · SprintDesk</title>
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
            background: linear-gradient(145deg, rgba(99, 102, 241, 0.4) 0%, rgba(79, 70, 229, 0.2) 100%);
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
            background: rgba(16, 185, 129, .15);
            bottom: -40px;
            left: -60px;
            animation: blobF 7s 3.5s ease-in-out infinite
        }

        @keyframes blobF {
            0%, 100% { transform: translate(0, 0) scale(1) }
            50% { transform: translate(20px, -18px) scale(1.06) }
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
            max-width: 440px
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

        .tf-toast-inline {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        @media(max-width:768px) {
            .auth-art { display: none }
            .auth-form { padding: 30px 20px }
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
                    <div class="art-logo-icon">🏢</div>
                    Sprint<em>Desk</em>
                </div>
                <h2 class="art-heading">Register Your<br>Organisation</h2>
                <p class="art-sub">Create a workspace for your team and manage projects efficiently.</p>
                <div class="art-pills">
                    <div class="art-pill">🏢 Dedicated Organization Workspace</div>
                    <div class="art-pill">👑 Admin Dashboard Control</div>
                    <div class="art-pill">📊 Custom Domain & Branding</div>
                    <div class="art-pill">👥 Manage Team Members</div>
                </div>
            </div>
        </div>

        <!-- FORM -->
        <div class="auth-form">
            <div class="auth-box">
                <a href="<?= URL_LANDING ?>" class="back-home">← Back to home</a>
                <h1 class="auth-title">Get Started 🚀</h1>
                <p class="auth-sub">Submit a request to register your company.</p>

                <?php if ($success): ?>
                    <div class="tf-toast-inline">✅ <?= $success ?></div>
                    <p style="text-align:center;"><a href="<?= URL_LOGIN ?>" class="btn btn-secondary">Go to Sign In</a></p>
                <?php else: ?>

                    <?php if ($err): ?>
                        <div class="tf-err">⚠️ <?= e($err) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="tf-fg">
                            <label class="tf-lbl">Organisation Name *</label>
                            <input type="text" name="org_name" class="tf-inp" placeholder="Acme Corp" required value="<?= $savedOrg ?>">
                        </div>
                        <div class="tf-fg">
                            <label class="tf-lbl">Domain</label>
                            <input type="text" name="domain" class="tf-inp" placeholder="acme.com" value="<?= $savedDomain ?>">
                        </div>
                        <div class="tf-fg">
                            <label class="tf-lbl">Address</label>
                            <input type="text" name="address" class="tf-inp" placeholder="123 Main St, New York" value="<?= $savedAddress ?>">
                        </div>
                        
                        <div style="margin: 24px 0; height: 1px; background: var(--border);"></div>
                        <div class="tf-fg">
                            <label class="tf-lbl">Primary Admin Email *</label>
                            <input type="email" name="email" class="tf-inp" placeholder="admin@acme.com" required value="<?= $savedEmail ?>">
                        </div>
                        <div class="tf-fg">
                            <label class="tf-lbl">Primary Admin Password *</label>
                            <div class="pass-wrap">
                                <input type="password" name="pass" id="passInp" class="tf-inp" placeholder="••••••••" required>
                                <button type="button" class="eye-btn" data-target="passInp">👁</button>
                            </div>
                        </div>

                        <div style="margin: 20px 0; padding: 15px; border: 1px dashed var(--border); border-radius: 12px; background: rgba(255,255,255,0.02);">
                            <p style="font-size:12px; color:var(--text3); margin-bottom:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Secondary Admins (Optional)</p>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="tf-fg">
                                    <label class="tf-lbl">Admin 2 Email</label>
                                    <input type="email" name="email_2" class="tf-inp" placeholder="admin2@acme.com" value="<?= $savedEmail2 ?>">
                                </div>
                                <div class="tf-fg">
                                    <label class="tf-lbl">Admin 2 Password</label>
                                    <div class="pass-wrap">
                                        <input type="password" name="pass_2" id="passInp2" class="tf-inp" placeholder="••••••••">
                                        <button type="button" class="eye-btn" data-target="passInp2">👁</button>
                                    </div>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                                <div class="tf-fg">
                                    <label class="tf-lbl">Admin 3 Email</label>
                                    <input type="email" name="email_3" class="tf-inp" placeholder="admin3@acme.com" value="<?= $savedEmail3 ?>">
                                </div>
                                <div class="tf-fg">
                                    <label class="tf-lbl">Admin 3 Password</label>
                                    <div class="pass-wrap">
                                        <input type="password" name="pass_3" id="passInp3" class="tf-inp" placeholder="••••••••">
                                        <button type="button" class="eye-btn" data-target="passInp3">👁</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;font-size:14px;margin-top:4px" id="submitBtn">
                            Submit Request
                        </button>
                    </form>
                    <p style="text-align:center;font-size:13px;color:var(--text2);margin-top:20px;">Already have an account? <a href="<?= URL_LOGIN ?>" style="color:var(--brand);font-weight:600;">Sign In</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const t = localStorage.getItem('sd_theme') || 'light';
        document.documentElement.setAttribute('data-theme', t);

        document.querySelectorAll('.eye-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const p = document.getElementById(targetId);
                if (p) {
                    p.type = p.type === 'password' ? 'text' : 'password';
                    btn.textContent = p.type === 'password' ? '👁' : '🙈';
                }
            });
        });

        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', () => {
                const b = document.getElementById('submitBtn');
                b.textContent = 'Submitting…'; b.disabled = true; b.style.opacity = '.75';
            });
        }
    </script>
</body>
</html>
