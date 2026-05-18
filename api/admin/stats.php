<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

requireAdmin();

$pdo = getDb();

$stats = [
    'users'          => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'students'       => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
    'organizers'     => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'organizer'")->fetchColumn(),
    'pendingUsers'   => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn(),
    'events'         => (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn(),
    'publishedEvents'=> (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'published'")->fetchColumn(),
    'registrations'  => (int) $pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn(),
];

jsonResponse([
    'success' => true,
    'stats'   => $stats,
]);
