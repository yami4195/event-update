<?php

declare(strict_types=1);

function requireAdmin(): array
{
    $user = requireLogin();
    if ($user['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Admin access required.'], 403);
    }
    return $user;
}
