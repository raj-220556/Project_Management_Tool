<?php
require_once __DIR__ . '/backend/shared/includes/init.php';

$sql = file_get_contents(__DIR__ . '/database/migration_v3_github.sql');
try {
    db()->exec($sql);
    echo "Migration successful.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
