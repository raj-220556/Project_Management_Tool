<?php
// backend/api/search.php
require_once __DIR__ . '/../shared/includes/init.php';
header('Content-Type: application/json');

if (!isLoggedIn())
    jsonResponse(['ok' => false, 'err' => 'Auth required'], 401);

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2)
    jsonResponse(['ok' => true, 'results' => []]);

$db = db();
$results = [];

// 0. Specialized search for Sprints by Project (for Modal)
if (isset($_GET['type']) && $_GET['type'] === 'sprint' && isset($_GET['project_id'])) {
    $sp = $db->prepare("SELECT id, name as title FROM tf_sprints WHERE project_id=? AND status='active' ORDER BY created_at DESC");
    $sp->execute([$_GET['project_id']]);
    jsonResponse(['ok' => true, 'results' => $sp->fetchAll()]);
}

// 1. Search Projects
$ps = $db->prepare("SELECT id, name as title, code as subtitle, 'project' as type FROM tf_projects WHERE name LIKE ? OR code LIKE ? LIMIT 5");
$ps->execute(["%$q%", "%$q%"]);
$results = array_merge($results, $ps->fetchAll());

// 2. Search Tasks
$ts = $db->prepare("SELECT id, title, (SELECT name FROM tf_projects WHERE id=project_id) as subtitle, 'task' as type FROM tf_tasks WHERE title LIKE ? OR description LIKE ? LIMIT 5");
$ts->execute(["%$q%", "%$q%"]);
$results = array_merge($results, $ts->fetchAll());

// 3. Search Users (Admin only)
if (currentUser()['role'] === 'admin') {
    $us = $db->prepare("SELECT id, name as title, role as subtitle, 'user' as type FROM tf_users WHERE name LIKE ? OR email LIKE ? LIMIT 5");
    $us->execute(["%$q%", "%$q%"]);
    $results = array_merge($results, $us->fetchAll());
}

jsonResponse(['ok' => true, 'results' => $results]);
