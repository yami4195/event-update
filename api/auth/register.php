<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

requirePost();

$body = readJsonBody();
$name = trim((string) ($body['name'] ?? ''));
$email = strtolower(trim((string) ($body['email'] ?? '')));
$password = (string) ($body['password'] ?? '');
$role = normalizeRole((string) ($body['role'] ?? ''));

if ($name === '') {
    jsonResponse(['success' => false, 'message' => 'Please enter your name.'], 422);
}

if ($email === '' || !validateEmail($email)) {
    jsonResponse(['success' => false, 'message' => 'Invalid email.'], 422);
}

if ($role === null) {
    jsonResponse(['success' => false, 'message' => 'Please select a valid role.'], 422);
}

if ($passwordError = validatePassword($password)) {
    jsonResponse(['success' => false, 'message' => $passwordError], 422);
}

$pdo = getDb();

$exists = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$exists->execute([$email]);
if ($exists->fetch()) {
    jsonResponse(['success' => false, 'message' => 'Email already exists.'], 409);
}

$status = defaultStatusForRole($role);
$hash = password_hash($password, PASSWORD_DEFAULT);

$insert = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)'
);
$insert->execute([$name, $email, $hash, $role, $status]);

$userId = (int) $pdo->lastInsertId();
$row = [
    'id'     => $userId,
    'name'   => $name,
    'email'  => $email,
    'role'   => $role,
    'status' => $status,
];

if ($status === 'pending') {
    jsonResponse([
        'success'         => true,
        'pendingApproval' => true,
        'message'         => 'Account created. An admin must approve your organizer account before you can log in.',
    ]);
}

loginUserSession($row, true);

jsonResponse([
    'success' => true,
    'message' => 'Account created successfully.',
    'user'    => publicUser($row),
]);
