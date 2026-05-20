<?php

declare(strict_types=1);

function normalizeRole(string $role): ?string
{
    $role = strtolower(trim($role));

    return in_array($role, ['student', 'organizer'], true) ? $role : null;
}

function defaultStatusForRole(string $role): string
{
    return $role === 'organizer' ? 'pending' : 'active';
}

function publicUser(array $row): array
{
    return [
        'id'     => (int) $row['id'],
        'name'   => $row['name'],
        'email'  => $row['email'],
        'role'   => $row['role'],
        'status' => $row['status'] ?? 'active',
    ];
}

function loginUserSession(array $row, bool $regenerate = false): void
{
    if ($regenerate && session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $_SESSION['user_id']    = (int) $row['id'];
    $_SESSION['user_name']  = $row['name'];
    $_SESSION['user_email'] = $row['email'];
    $_SESSION['user_role']  = $row['role'];
}

function clearUserSession(): void
{
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role']);
}

function logoutUserSession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function validateEmail(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword(string $password): ?string
{
    if (strlen($password) < 6) {
        return 'Password must be at least 6 characters.';
    }

    return null;
}

function accountStatusMessage(string $status): ?string
{
    return match ($status) {
        'suspended' => 'Your account has been suspended. Contact the university admin.',
        'pending'   => 'Your account is pending approval. Please try again later.',
        default     => null,
    };
}
