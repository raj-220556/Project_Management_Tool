<?php
// ============================================================
// backend/shared/includes/init.php
// THE SINGLE FILE every PHP page includes first.
// Uses __DIR__ so paths always work no matter which subfolder.
// ============================================================

// ---- APP URL (change only this one line for your setup) ----
define('APP_URL', 'http://localhost/sprintdesk');
// ------------------------------------------------------------

define('APP_NAME', 'SprintDesk');
define('APP_VERSION', '1.0.0');

// ---- DATABASE ----
define('DB_HOST', '127.0.0.1:3307');
define('DB_USER', 'root');
define('DB_PASS', '');            // blank = XAMPP default
define('DB_NAME', 'sprintdesk_db');
define('DB_CHARSET', 'utf8mb4');

// ---- NAMED URLS (built from APP_URL — never type paths manually) ----
define('URL_ASSETS', APP_URL . '/frontend/assets');
define('URL_LANDING', APP_URL . '/frontend/landing/index.php');
define('URL_LOGIN', APP_URL . '/frontend/auth/login.php');
define('URL_LOGOUT', APP_URL . '/backend/auth/logout.php');
define('URL_ADMIN', APP_URL . '/frontend/admin/dashboard.php');
define('URL_MANAGER', APP_URL . '/frontend/manager/dashboard.php');
define('URL_DEVELOPER', APP_URL . '/frontend/developer/dashboard.php');
define('URL_ORG_MANAGER', APP_URL . '/frontend/org_manager/dashboard.php');
define('URL_API', APP_URL . '/backend/api');


// ============================================================
// DATABASE CONNECTION
// ============================================================
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null)
        return $pdo;

    $ports = ['3306', '3307']; // Common MySQL ports
    $lastError = '';

    foreach ($ports as $port) {
        try {
            $pdo = new PDO(
                'mysql:host=127.0.0.1;port=' . $port . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            $lastError = $e->getMessage();
            continue;
        }
    }

    // If both failed
    http_response_code(500);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>DB Error</title>
<style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f0f4f3}
.box{background:#fff;border-left:5px solid #e74c3c;padding:36px 44px;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.1);max-width:540px}
h2{color:#e74c3c;margin-bottom:12px;font-size:20px}p{color:#555;font-size:14px;margin:6px 0}code{background:#f5f5f5;padding:2px 6px;border-radius:4px;font-size:13px}</style></head>
<body><div class="box"><h2>⚠️ Database Connection Failed</h2>
<p><strong>Error:</strong> ' . htmlspecialchars($lastError) . '</p>
<p>Fix: open <code>backend/shared/includes/init.php</code></p>
<p>Set <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code> correctly</p>
<p>Also make sure you imported <code>database/schema.sql</code> in phpMyAdmin</p>
</div></body></html>');
}

// ============================================================
// SESSION
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    session_name('SD_SESSION');
    session_start();
}

// ============================================================
// AUTH
// ============================================================
function isLoggedIn(): bool
{
    return !empty($_SESSION['sd_uid']);
}

function currentUser(bool $refresh = false): ?array
{
    static $u = null;
    if ($refresh) $u = null;
    if (!isLoggedIn())
        return null;
    if ($u === null) {
        $s = db()->prepare('SELECT u.*, o.name org_name FROM tf_users u LEFT JOIN tf_organizations o ON u.org_id = o.id WHERE u.id = ? AND u.is_active = 1');
        $s->execute([$_SESSION['sd_uid']]);
        $u = $s->fetch() ?: null;
    }
    return $u;
}

function requireLogin(string $role = ''): void
{
    if (!isLoggedIn()) {
        go(URL_LOGIN);
    }
    $u = currentUser();
    if (!$u) go(URL_LOGIN);

    // Global Organiser and Admin always have access
    if ($u['role'] === 'org_manager' || $u['role'] === 'admin') return;

    if ($role) {
        
        if ($u['role'] !== $role) {
            go(URL_LOGIN . '?err=access');
        }
    }
}

function loginAs(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['sd_uid'] = $user['id'];
    $_SESSION['sd_role'] = $user['role'];
}

function doLogout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    go(URL_LOGIN);
}

function goToDashboard(string $role, string $qs = ''): void
{
    $map = [
        'org_manager' => URL_ORG_MANAGER,
        'admin'       => URL_ADMIN,
        'manager'     => URL_MANAGER,
        'developer'   => URL_DEVELOPER,
    ];
    $url = $map[$role] ?? URL_DEVELOPER;
    if ($qs) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . ltrim($qs, '?&');
    }
    go($url);
}

function go(string $url): never
{
    header("Location: $url");
    exit;
}

// ============================================================
// GITHUB INTEGRATION & ENV
// ============================================================
function loadEnv(): void
{
    $path = dirname(__DIR__, 3) . '/.env';
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            putenv(trim($parts[0]) . '=' . trim($parts[1]));
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
}
loadEnv();

function getGitHubActivity(int $projectId = 0): void
{
    $token = $_ENV['GITHUB_TOKEN'] ?? '';
    if (!$token) return;

    $cacheFile = sys_get_temp_dir() . '/sd_gh_sync_' . $projectId . '.json';
    if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 300) {
        return; // limit hitting API rate limits (5-min cooldown)
    }

    $db = db();
    $orgId = currentUser()['org_id'] ?? 0;

    $sql = "SELECT id, name, github_url FROM tf_projects WHERE github_url IS NOT NULL AND github_url != ''";
    $params = [];
    if (!empty($orgId)) {
        $sql .= " AND org_id=?";
        $params[] = $orgId;
    }
    if ($projectId > 0) {
        $sql .= " AND id=?";
        $params[] = $projectId;
    }

    $projectsStmt = $db->prepare($sql);
    $projectsStmt->execute($params);
    $repos = $projectsStmt->fetchAll();

    foreach ($repos as $r) {
        $url = preg_replace('/\.git$/', '', $r['github_url']);
        $parts = parse_url($url, PHP_URL_PATH);
        if (!$parts) continue;
        $path = trim($parts, '/');

        $ch = curl_init('https://api.github.com/repos/' . $path . '/commits?per_page=15');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: SprintDesk',
                'Authorization: Bearer ' . $token,
                'Accept: application/vnd.github.v3+json'
            ],
            CURLOPT_TIMEOUT => 3
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        if ($res) {
            $data = json_decode($res, true);
            if (is_array($data)) {
                $ins = $db->prepare("INSERT IGNORE INTO tf_activity (user_id, project_id, action, entity_type, old_value, commit_hash, github_author, created_at, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data as $c) {
                    if (empty($c['sha'])) continue;
                    $msg = explode("\n", $c['commit']['message'])[0];
                    $author = $c['commit']['author']['name'] ?? 'GitHub User';
                    $date = date('Y-m-d H:i:s', strtotime($c['commit']['author']['date'] ?? 'now'));

                    $payload = json_encode([
                        'commit_url' => $c['html_url'] ?? '#',
                        'repo_name'  => $r['name'],
                        'message'    => $msg
                    ]);

                    $ins->execute([
                        null, 
                        $r['id'],
                        'pushed commit',
                        'github_commit',
                        $payload,
                        substr($c['sha'], 0, 40),
                        $author,
                        $date,
                        '0.0.0.0'
                    ]);
                }
            }
        }
    }

    file_put_contents($cacheFile, 'synced');
}

// ============================================================
// HELPERS
// ============================================================
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function userInitials(string $name): string
{
    $w = explode(' ', trim($name));
    return strtoupper(($w[0][0] ?? '?') . ($w[1][0] ?? ''));
}

function getUserTheme(): string
{
    $u = currentUser();
    return $u['theme'] ?? 'light';
}


function logActivity(int $uid, ?int $pid, ?int $tid, string $action, string $type, ?int $eid = null, string $old = '', string $new = ''): void
{
    try {
        db()->prepare('INSERT INTO tf_activity(user_id,project_id,task_id,action,entity_type,entity_id,old_value,new_value,ip) VALUES(?,?,?,?,?,?,?,?,?)')
            ->execute([$uid, $pid, $tid, $action, $type, $eid, $old, $new, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
    }
}

function notifyUser(int $uid, string $title, string $msg, string $type = 'system', string $link = ''): void
{
    try {
        db()->prepare('INSERT INTO tf_notifications(user_id,title,message,type,link) VALUES(?,?,?,?,?)')
            ->execute([$uid, $title, $msg, $type, $link]);
    } catch (Exception $e) {
    }
}

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)
        return 'just now';
    if ($diff < 3600)
        return floor($diff / 60) . 'm ago';
    if ($diff < 86400)
        return floor($diff / 3600) . 'h ago';
    if ($diff < 604800)
        return floor($diff / 86400) . 'd ago';
    return date('M j', strtotime($datetime));
}

function jsonResponse(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>