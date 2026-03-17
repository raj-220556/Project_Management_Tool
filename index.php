<?php
// sprintdesk/index.php — entry point
// Redirects logged-in users to their dashboard, others to landing page
require_once __DIR__ . '/backend/shared/includes/init.php';
if (isLoggedIn()) { goToDashboard(currentUser()['role']); }
go(URL_LANDING);
