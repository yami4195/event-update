<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

requirePost();

$body = readJsonBody();
$email = strtolower(trim((string) ($body['email'] ?? '')));
$password = (string) ($body['password'] ?? '');

if ($email === '' || !validateEmail($email)) {
    jsonResponse(['success' => false, 'message' => 'Invalid email.'], 422);
}

if ($password === '') {
    jsonResponse(['success' => false, 'message' => 'Please enter your password.'], 422);
}

$pdo = getDb();
$stmt = $pdo->prepare(
    'SELECT id, name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1'
);
$stmt->execute([$email]);
$row = $stmt->fetch();

if (!$row || !password_verify($password, $row['password_hash'])) {
    jsonResponse(['success' => false, 'message' => 'Incorrect email or password.'], 401);
}

if ($statusMessage = accountStatusMessage($row['status'])) {
    jsonResponse(['success' => false, 'message' => $statusMessage], 403);
}

unset($row['password_hash']);
loginUserSession($row, true);

jsonResponse([
    'success' => true,
    'message' => 'Logged in successfully.',
    'user'    => publicUser($row),
]);
