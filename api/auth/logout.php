<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

requirePost();

logoutUserSession();

jsonResponse([
    'success' => true,
    'message' => 'Logged out successfully.',
]);
