<?php
/**
 * One-time fix for demo account passwords.
 * Open: http://localhost/event-booking/setup/fix-logins.php
 * Delete this file after use on a production server.
 */
header('Content-Type: text/html; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db.php';

$hash = password_hash('password123', PASSWORD_DEFAULT);
$emails = ['admin@hu.edu.et', 'organizer@hu.edu.et', 'student@hu.edu.et'];
$messages = [];

try {
    $pdo = getDb();

    foreach ($emails as $email) {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        $stmt->execute([$hash, $email]);
        if ($stmt->rowCount() > 0) {
            $messages[] = "Updated password for {$email}";
        }
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute(['admin@hu.edu.et']);
    if (!$check->fetch()) {
        $insert = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)'
        );
        $insert->execute(['HU Admin', 'admin@hu.edu.et', $hash, 'admin', 'active']);
        $messages[] = 'Created admin@hu.edu.et';
    }

    $messages[] = '<strong>Done.</strong> Login with password: <code>password123</code>';
} catch (Throwable $e) {
    $messages[] = '<span style="color:red">Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
    $messages[] = 'Import <code>database/schema.sql</code> first in phpMyAdmin.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Fix demo logins</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 560px; margin: 2rem auto; padding: 0 1rem; }
    li { margin: 0.5rem 0; }
    a { color: #047857; }
  </style>
</head>
<body>
  <h1>Fix demo logins</h1>
  <ul>
    <?php foreach ($messages as $m): ?>
      <li><?= $m ?></li>
    <?php endforeach; ?>
  </ul>
  <p><a href="../login.html">Go to login</a></p>
</body>
</html>
