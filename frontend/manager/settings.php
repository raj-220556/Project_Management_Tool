<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Settings · SprintDesk</title>
<?php
require_once __DIR__ . '/../../backend/shared/includes/init.php';
requireLogin('manager');
$activePage = 'settings';
$db = db();
$msg = '';
$activeTab = $_GET['tab'] ?? 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($name && $email) {
        $db->prepare("UPDATE tf_users SET name = ?, email = ? WHERE id = ?")->execute([$name, $email, currentUser()['id']]);
        currentUser(true); // refresh cache
        $msg = "Profile updated successfully.";
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
                <div class="tf-topbar-title">Settings</div>
            </div>
            <div class="tf-page">
                <div class="a1">
                    <div class="page-header-title">Settings</div>
                </div>

                <div class="set-wrap a2">
                    <div class="set-side">

                        <a href="?tab=profile" class="set-link <?= $activeTab === 'profile' ? 'active' : '' ?>">👤 Profile</a>
                        <a href="?tab=security" class="set-link <?= $activeTab === 'security' ? 'active' : '' ?>">🔒 Security</a>
                        <a href="?tab=appearance" class="set-link <?= $activeTab === 'appearance' ? 'active' : '' ?>">🎨 Appearance</a>
                        <a href="?tab=notifications" class="set-link <?= $activeTab === 'notifications' ? 'active' : '' ?>">🔔 Notification Prefs</a>

                        <a href="<?= URL_LOGOUT ?>" class="set-link logout">🚪 Logout</a>
                    </div>
                    
                    <div class="set-main">
                        <?php if(!empty($msg)): ?><div class="tf-toast-inline" style="margin-bottom:20px; border-color:var(--brand);">✅ <?= e($msg) ?></div><?php endif; ?>

                        <!-- PROFILE PANEL -->
                        <div id="panel-profile" class="set-panel <?= $activeTab === 'profile' ? 'active' : '' ?>">
                            <div class="set-title">Profile Settings</div>
                            <div class="set-av-sec">
                                <div class="tf-sb-avatar" style="width:80px;height:80px;font-size:26px" id="avatarPreview">
                                    <?php if ($u = currentUser()): if (!empty($u['avatar'])): ?>
                                        <img src="<?= e($u['avatar']) ?>" alt="">
                                    <?php else: echo e(userInitials($u['name'])); endif; endif; ?>
                                </div>
                                <div class="set-av-btns">
                                    <input type="file" id="avatarInp" style="display:none" accept="image/*">
                                    <button type="button" class="set-av-btn" onclick="document.getElementById('avatarInp').click()">
                                        ⬆ Upload Photo
                                    </button>
                                    <button type="button" class="set-av-btn remove" onclick="alert('Avatar removed (demo)')">Remove</button>
                                    <div class="set-av-help">JPG, PNG, GIF, WEBP · Max 5MB</div>
                                    <div id="uploadStatus" style="font-size:11px; font-weight:500; margin-top:2px;"></div>
                                </div>
                            </div>
                            
                            <form method="POST" action="?tab=profile">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="tf-fg">
                                    <label class="tf-lbl">Full Name</label>
                                    <input type="text" name="name" class="tf-inp" value="<?= e(currentUser()['name']) ?>" required>
                                </div>
                                <div class="tf-fg">
                                    <label class="tf-lbl">Email</label>
                                    <input type="email" name="email" class="tf-inp" value="<?= e(currentUser()['email']) ?>" required>
                                </div>
                                <div class="tf-fg">
                                    <label class="tf-lbl">Role</label>
                                    <input type="text" class="tf-inp" value="<?= ucfirst(currentUser()['role']) ?>" readonly style="opacity:0.7; cursor:not-allowed;">
                                </div>
                                <button type="submit" class="btn btn-primary" style="margin-top:14px; padding: 10px 24px; border-radius: 8px;">
                                    🔒 Save Changes
                                </button>
                            </form>
                        </div>
                        
                        <!-- SECURITY PANEL -->
                        <div id="panel-security" class="set-panel <?= $activeTab === 'security' ? 'active' : '' ?>">
                            <div class="set-title">Security Settings</div>
                            <p style="color:var(--text3); font-size:14px; margin-bottom:20px;">Update your password and secure your account.</p>
                            <form method="POST" action="?tab=security" onsubmit="event.preventDefault(); alert('Password updated! (Demo)');">
                                <div class="tf-fg"><label class="tf-lbl">Current Password</label><input type="password" class="tf-inp" required></div>
                                <div class="tf-fg"><label class="tf-lbl">New Password</label><input type="password" class="tf-inp" required></div>
                                <button class="btn btn-primary" style="margin-top:14px;">Update Password</button>
                            </form>
                        </div>
                        
                        <!-- APPEARANCE PANEL -->
                        <div id="panel-appearance" class="set-panel <?= $activeTab === 'appearance' ? 'active' : '' ?>">
                            <div class="set-title">Appearance</div>
                            <p style="color:var(--text3); font-size:14px; margin-bottom:24px;">Customize the look and feel of SprintDesk.</p>
                            <div style="display:flex; align-items:center; justify-content:space-between; background:var(--surface2); padding:24px; border-radius:12px; border:1px solid var(--border);">
                                <div>
                                    <div style="font-weight:600; font-size: 15px; color: var(--text); margin-bottom:4px;">Application Theme</div>
                                    <div style="font-size:13px; color:var(--text3);">Toggle the interface theme across your current session.</div>
                                </div>
                                <button type="button" class="btn btn-secondary tf-theme-tog-btn" style="padding:10px 20px; font-size:14px; gap:8px;">
                                    <span style="font-size:16px;">✨</span> Toggle Mode
                                </button>
                            </div>
                        </div>

                        <!-- NOTIFICATIONS PANEL -->
                        <div id="panel-notifications" class="set-panel <?= $activeTab === 'notifications' ? 'active' : '' ?>">
                            <div class="set-title">Notification Preferences</div>
                            <p style="color:var(--text3); font-size:14px; margin-bottom:20px;">Choose what you want to be notified about.</p>
                            <div style="display:flex;flex-direction:column;gap:12px;background:var(--surface2);padding:20px;border-radius:12px;">
                                <label style="display:flex; align-items:center; gap:12px; cursor:pointer;"><input type="checkbox" checked style="accent-color:var(--brand);width:16px;height:16px;"> Task Assignments</label>
                                <label style="display:flex; align-items:center; gap:12px; cursor:pointer;"><input type="checkbox" checked style="accent-color:var(--brand);width:16px;height:16px;"> Project Updates</label>
                                <label style="display:flex; align-items:center; gap:12px; cursor:pointer;"><input type="checkbox" checked style="accent-color:var(--brand);width:16px;height:16px;"> Daily Digests via Email</label>
                            </div>
                            <button class="btn btn-primary" onclick="alert('Preferences saved!')" style="margin-top:20px;">Save Preferences</button>
                        </div>

                    </div>
                </div>

                <script>
                    // Avatar upload
                    const avInp = document.getElementById('avatarInp');
                    if (avInp) {
                        avInp.addEventListener('change', function (e) {
                        const file = e.target.files[0];
                        if (!file) return;
                        const formData = new FormData();
                        formData.append('avatar', file);
                        const status = document.getElementById('uploadStatus');
                        status.innerText = 'Uploading...';
                        status.style.color = 'var(--brand)';
                        fetch('<?= URL_API ?>/upload_avatar.php', { method: 'POST', body: formData })
                            .then(r => r.json())
                            .then(d => {
                                if (d.ok) {
                                    status.innerText = '✅ Avatar updated!';
                                    status.style.color = 'var(--green)';
                                    document.getElementById('avatarPreview').innerHTML = `<img src="${d.avatar}" alt="">`;
                                    const sidebarAvatar = document.querySelector('.tf-sidebar .tf-sb-avatar');
                                    if(sidebarAvatar) sidebarAvatar.innerHTML = `<img src="${d.avatar}" alt="">`;
                                } else {
                                    status.innerText = '❌ ' + d.err;
                                    status.style.color = 'var(--red)';
                                }
                            })
                            .catch(err => {
                                status.innerText = '❌ Connection error.';
                                status.style.color = 'var(--red)';
                            });
                    });
                }
                </script>
            </div>
        </div>
    </div>
</body>
</html>
