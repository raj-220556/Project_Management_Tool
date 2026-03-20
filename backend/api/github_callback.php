<?php
// backend/api/github_callback.php
require_once __DIR__ . '/../shared/includes/init.php';
requireLogin();

// This is the callback endpoint for GitHub OAuth.
// Implement your token exchange here using your Client Secret.
$code = $_GET['code'] ?? '';
if (!$code) {
    die("<h3>Error</h3><p>No code received from GitHub.</p>");
}

$clientId = 'YOUR_GITHUB_CLIENT_ID';
$clientSecret = 'YOUR_GITHUB_CLIENT_SECRET';

// Example token exchange (Uncomment and configure):
/*
$ch = curl_init('https://github.com/login/oauth/access_token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'code' => $code
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);
$accessToken = $data['access_token'] ?? null;

// Store the token in tf_users or tf_projects securely.
$db = db();
$db->prepare("UPDATE tf_users SET github_token = ? WHERE id = ?")->execute([$accessToken, currentUser()['id']]);
*/

echo "<div style='font-family:sans-serif; text-align:center; padding: 50px;'>";
echo "<h3>GitHub OAuth Scaffolding 🐙</h3>";
echo "<p>OAuth flow triggered successfully. Received Code: <code>" . htmlspecialchars($code) . "</code></p>";
echo "<p><strong>Next Step:</strong> Open <code>backend/api/github_callback.php</code>, add your Client Secret, and uncomment the cURL request to exchange the code for an Access Token!</p>";
echo "<button onclick='window.history.go(-2)' style='padding:10px 20px; background:#6366F1; color:#fff; border:none; border-radius:8px; cursor:pointer;'>Return to Application</button>";
echo "</div>";
