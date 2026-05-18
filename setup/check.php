<?php
/**
 * XAMPP setup check — open in browser:
 * http://localhost/event-booking/setup/check.php
 */
header('Content-Type: text/html; charset=utf-8');

$checks = [];

// PHP version
$checks[] = [
    'label' => 'PHP version (8.0+)',
    'ok'    => version_compare(PHP_VERSION, '8.0.0', '>='),
    'detail'=> 'Running PHP ' . PHP_VERSION,
];

// PDO MySQL
$checks[] = [
    'label' => 'PDO MySQL extension',
    'ok'    => extension_loaded('pdo_mysql'),
    'detail'=> extension_loaded('pdo_mysql') ? 'Enabled' : 'Enable pdo_mysql in php.ini',
];

// Database connection
$dbOk = false;
$dbDetail = '';
try {
    require dirname(__DIR__) . '/includes/db.php';
    $pdo = getDb();
    $pdo->query('SELECT 1');
    $count = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
    $dbOk = true;
    $dbDetail = "Connected. Events in database: {$count}";
} catch (Throwable $e) {
    $dbDetail = $e->getMessage();
}
$checks[] = [
    'label'  => 'Database hu_events',
    'ok'     => $dbOk,
    'detail' => $dbDetail,
];

// Uploads folder
$uploadDir = dirname(__DIR__) . '/uploads/events';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}
$checks[] = [
    'label'  => 'Uploads folder writable',
    'ok'     => is_dir($uploadDir) && is_writable($uploadDir),
    'detail' => $uploadDir,
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HU Events | XAMPP Setup Check</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
    h1 { color: #047857; }
    li { margin: 1rem 0; padding: 1rem; border-radius: 8px; list-style: none; }
    .ok { background: #d1fae5; }
    .fail { background: #fee2e2; }
    code { background: #f1f5f9; padding: 0.15rem 0.4rem; border-radius: 4px; }
    a { color: #047857; }
  </style>
</head>
<body>
  <h1>HU Events — XAMPP setup check</h1>
  <ul>
    <?php foreach ($checks as $c): ?>
      <li class="<?= $c['ok'] ? 'ok' : 'fail' ?>">
        <strong><?= htmlspecialchars($c['label']) ?></strong><br>
        <?= htmlspecialchars($c['detail']) ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <p><a href="../index.html">Go to homepage</a></p>
  <?php if ($dbOk): ?>
    <p><strong>Test logins</strong> (after seed.sql):<br>
      Admin: <code>admin@hu.edu.et</code><br>
      Organizer: <code>organizer@hu.edu.et</code><br>
      Student: <code>student@hu.edu.et</code><br>
      Password: <code>password123</code></p>
    <p><a href="../admin-dashboard.html">Admin dashboard</a></p>
  <?php else: ?>
    <p>Import <code>database/schema.sql</code> then <code>database/seed.sql</code> in phpMyAdmin.</p>
  <?php endif; ?>
</body>
</html>
