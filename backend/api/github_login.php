<?php
// backend/api/github_login.php
require_once __DIR__ . '/../shared/includes/init.php';
requireLogin();

// This is a scaffolding for GitHub OAuth integration.
// Add your specific GitHub App credentials here:
$clientId = 'YOUR_GITHUB_CLIENT_ID';
$redirectUri = urlencode(APP_URL . '/backend/api/github_callback.php');
$scope = 'repo,user';

$url = "https://github.com/login/oauth/authorize?client_id={$clientId}&redirect_uri={$redirectUri}&scope={$scope}";
header("Location: $url");
exit;
