<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Profile · SprintDesk</title>
    <?php
    require_once __DIR__ . '/../../backend/shared/includes/init.php';
    requireLogin();
    $activePage = 'profile';
    $u = currentUser();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($name && $email) {
            try {
                db()->prepare('UPDATE tf_users SET name=?, email=? WHERE id=?')->execute([$name, $email, $u['id']]);
                header('Location: profile.php?ok=1');
                exit;
            } catch (Exception $e) {
                $err = 'Email already in use.';
            }
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
                <div class="tf-topbar-title">Profile Settings</div>
            </div>
            <div class="tf-page">
                <div class="tf-page-hd a1">
                    <div>
                        <div class="tf-title">Account Settings</div>
                        <div class="tf-subtitle">Manage your personal information and preferences</div>
                    </div>
                </div>

                <div class="card a2" style="max-width:600px">
                    <div class="card-body">
                        <?php if (isset($_GET['ok'])): ?>
                            <div class="tf-toast-inline">✅ Profile updated successfully.</div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="tf-fg"><label class="tf-lbl">Full Name</label><input type="text" name="name"
                                    class="tf-inp" value="<?= e($u['name']) ?>" required></div>
                            <div class="tf-fg"><label class="tf-lbl">Email Address</label><input type="email"
                                    name="email" class="tf-inp" value="<?= e($u['email']) ?>" required></div>
                            <div class="tf-fg"><label class="tf-lbl">Your Role</label><input type="text" class="tf-inp"
                                    value="<?= ucfirst($u['role']) ?>" disabled
                                    style="background:var(--surface2);opacity:.7"></div>
                            <div style="margin-top:24px"><button type="submit" class="btn btn-primary">Save
                                    Changes</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>