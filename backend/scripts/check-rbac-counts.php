<?php

declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli('127.0.0.1', 'root', '', 'bys_test', 3306);

$r  = $mysqli->query('SELECT COUNT(*) AS c FROM role_permissions WHERE deleted_at IS NULL AND is_active = 1');
$r2 = $mysqli->query('SELECT COUNT(*) AS c FROM permissions WHERE deleted_at IS NULL AND is_active = 1');
$r3 = $mysqli->query("SELECT id FROM roles WHERE company_id IS NULL AND code = 'company_admin'");
$row = $r3->fetch_assoc();
$rid = (int) ($row['id'] ?? 0);
$r4 = $mysqli->query('SELECT COUNT(*) AS c FROM role_permissions WHERE role_id = ' . $rid . ' AND deleted_at IS NULL');
$r5 = $mysqli->query(
    "SELECT p.code FROM role_permissions rp INNER JOIN permissions p ON p.id = rp.permission_id
     WHERE rp.role_id = {$rid} AND rp.is_active = 1 AND p.code = 'deposit.create'"
);

echo 'role_permissions active: ' . $r->fetch_assoc()['c'] . PHP_EOL;
echo 'permissions active: ' . $r2->fetch_assoc()['c'] . PHP_EOL;
echo 'company_admin role_permissions: ' . $r4->fetch_assoc()['c'] . PHP_EOL;
echo 'has deposit.create: ' . ($r5->num_rows > 0 ? 'yes' : 'no') . PHP_EOL;
