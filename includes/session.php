<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $configPath = dirname(__DIR__) . '/config/config.php';
    if (is_file($configPath)) {
        require_once $configPath;
    }

    $cookiePath = defined('BASE_URL') && BASE_URL !== '' ? BASE_URL : '/';

    session_name('hu_events_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $cookiePath,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);

    session_start();
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id'    => (int) $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? '',
    ];
}

function requireLogin(): array
{
    $user = currentUser();
    if (!$user) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Login required.']);
        exit;
    }
    return $user;
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
