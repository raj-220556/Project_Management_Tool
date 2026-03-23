<?php
require_once __DIR__ . '/../shared/includes/init.php';
requireLogin();

header('Content-Type: application/json');

function apiResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Invalid request method');
}

$action = $_POST['action'] ?? '';
$projectId = (int)($_POST['project_id'] ?? 0);

if (!$projectId) {
    apiResponse(false, 'Project ID is required');
}

$db = db();
$orgId = currentUser()['org_id'];

// Verify project access
$projStmt = $db->prepare("SELECT * FROM tf_projects WHERE id=? AND org_id=?");
$projStmt->execute([$projectId, $orgId]);
$project = $projStmt->fetch();

if (!$project) {
    apiResponse(false, 'Project not found or access denied');
}

if (empty($project['github_url'])) {
    apiResponse(false, 'No GitHub repository linked to this project');
}

// Get the PAT token
$token = $project['github_pat'] ?: ($_ENV['GITHUB_TOKEN'] ?? '');

if (!$token) {
    apiResponse(false, 'No GitHub PAT token available. Please add one in project settings or .env');
}

// Helper to make GitHub API requests
function githubApiRequest($url, $token, $method = 'GET', $data = null) {
    $ch = curl_init($url);
    $headers = [
        'User-Agent: SprintDesk',
        'Authorization: Bearer ' . $token,
        'Accept: application/vnd.github.v3+json'
    ];
    
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => $method
    ];

    if ($data) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    curl_setopt_array($ch, $options);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $code, 'body' => json_decode($res, true) ?: $res];
}

$repoUrl = preg_replace('/\.git$/', '', $project['github_url']);
$parts = parse_url($repoUrl, PHP_URL_PATH);
$repoPath = trim($parts, '/'); // "owner/repo"

if ($action === 'sync') {
    // 1. Fetch Commits (stats for hotspots/leaderboard)
    $commitsRes = githubApiRequest("https://api.github.com/repos/{$repoPath}/commits?per_page=30", $token);
    $syncedCommits = 0;
    
    if ($commitsRes['code'] === 200 && is_array($commitsRes['body'])) {
        $insCommit = $db->prepare("INSERT IGNORE INTO tf_github_commits (project_id, sha, message, author_name, author_email, author_avatar, commit_date, additions, deletions, files_changed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($commitsRes['body'] as $c) {
            $sha = $c['sha'];
            
            // Check if commit already exists
            $chk = $db->prepare("SELECT id FROM tf_github_commits WHERE project_id=? AND sha=?");
            $chk->execute([$projectId, $sha]);
            if ($chk->fetch()) continue;
            
            // Fetch individual commit to get file stats
            $singleRes = githubApiRequest("https://api.github.com/repos/{$repoPath}/commits/{$sha}", $token);
            if ($singleRes['code'] === 200) {
                $sc = $singleRes['body'];
                $msg = $sc['commit']['message'] ?? '';
                $authorName = $sc['commit']['author']['name'] ?? '';
                $authorEmail = $sc['commit']['author']['email'] ?? '';
                $authorAvatar = $sc['author']['avatar_url'] ?? '';
                $date = date('Y-m-d H:i:s', strtotime($sc['commit']['author']['date'] ?? 'now'));
                $additions = $sc['stats']['additions'] ?? 0;
                $deletions = $sc['stats']['deletions'] ?? 0;
                
                $files = [];
                if (!empty($sc['files'])) {
                    foreach ($sc['files'] as $f) {
                        $files[] = $f['filename'];
                    }
                }
                
                $insCommit->execute([
                    $projectId, $sha, $msg, $authorName, $authorEmail, $authorAvatar, $date, $additions, $deletions, json_encode($files)
                ]);
                $syncedCommits++;
            }
        }
    }

    // 2. Fetch Branches
    $branchesRes = githubApiRequest("https://api.github.com/repos/{$repoPath}/branches?per_page=50", $token);
    if ($branchesRes['code'] === 200 && is_array($branchesRes['body'])) {
        $db->prepare("DELETE FROM tf_github_branches WHERE project_id=?")->execute([$projectId]);
        $insBranch = $db->prepare("INSERT INTO tf_github_branches (project_id, name, last_commit_sha) VALUES (?, ?, ?)");
        foreach ($branchesRes['body'] as $b) {
            $insBranch->execute([$projectId, $b['name'], $b['commit']['sha']]);
        }
    }

    // 3. Fetch Pull Requests
    $prsRes = githubApiRequest("https://api.github.com/repos/{$repoPath}/pulls?state=all&per_page=30", $token);
    if ($prsRes['code'] === 200 && is_array($prsRes['body'])) {
        $db->prepare("DELETE FROM tf_github_prs WHERE project_id=?")->execute([$projectId]);
        $insPr = $db->prepare("INSERT INTO tf_github_prs (project_id, pr_number, title, state, author_name, author_avatar) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($prsRes['body'] as $pr) {
            $state = $pr['state'] === 'open' && isset($pr['draft']) && $pr['draft'] ? 'draft' : ($pr['merged_at'] ? 'merged' : $pr['state']);
            $insPr->execute([
                $projectId,
                $pr['number'],
                $pr['title'],
                $state,
                $pr['user']['login'] ?? '',
                $pr['user']['avatar_url'] ?? ''
            ]);
        }
    }

    apiResponse(true, "Sync complete. Synced $syncedCommits new commits and updated branches/PRs.");
}

if ($action === 'create_branch') {
    $branchName = trim($_POST['branch_name'] ?? '');
    if (!$branchName) apiResponse(false, 'Branch name is required');
    
    // Get default branch SHA
    $repoRes = githubApiRequest("https://api.github.com/repos/{$repoPath}", $token);
    if ($repoRes['code'] !== 200) apiResponse(false, 'Failed to fetch repository details');
    $defaultBranch = $repoRes['body']['default_branch'] ?? 'main';
    
    $refRes = githubApiRequest("https://api.github.com/repos/{$repoPath}/git/refs/heads/{$defaultBranch}", $token);
    if ($refRes['code'] !== 200) apiResponse(false, 'Failed to fetch default branch ref');
    $sha = $refRes['body']['object']['sha'];
    
    $createRes = githubApiRequest("https://api.github.com/repos/{$repoPath}/git/refs", $token, 'POST', [
        'ref' => "refs/heads/" . $branchName,
        'sha' => $sha
    ]);
    
    if ($createRes['code'] === 201) {
        $db->prepare("INSERT INTO tf_github_branches (project_id, name, last_commit_sha) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE last_commit_sha=?")
           ->execute([$projectId, $branchName, $sha, $sha]);
        apiResponse(true, "Branch '$branchName' created successfully.");
    } else {
        $msg = is_array($createRes['body']) ? ($createRes['body']['message'] ?? 'Unknown error') : 'Unknown error';
        apiResponse(false, "Failed to create branch: " . $msg);
    }
}

if ($action === 'revert_commit') {
    $sha = trim($_POST['sha'] ?? '');
    if (!$sha) apiResponse(false, 'Commit SHA is required');
    
    // GitHub API doesn't have a direct "revert commit" endpoint.
    // To revert, we'd need to manually create a tree and commit, or use a workaround.
    // For simplicity in this PM tool, we can simulate the UI feedback or use the REST API to revert a PR if it's a merge commit.
    // Reverting an arbitrary commit via REST API requires fetching the commit, reverting its tree... too advanced for this demo script.
    apiResponse(false, 'Reverting arbitrary commits via API is complex. Please revert locally using `git revert ' . substr($sha, 0, 7) . '` and push.');
}

if ($action === 'cherry_pick') {
    $sha = trim($_POST['sha'] ?? '');
    $targetBranch = trim($_POST['target_branch'] ?? '');
    if (!$sha) apiResponse(false, 'Commit SHA is required');
    if (!$targetBranch) apiResponse(false, 'Target branch is required');
    
    apiResponse(false, 'Cherry-picking arbitrary commits via API is complex. Please cherry-pick locally using `git cherry-pick ' . substr($sha, 0, 7) . '` onto branch `' . $targetBranch . '` and push.');
}

apiResponse(false, 'Invalid action');
