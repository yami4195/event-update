<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/admin.php';
require_once dirname(__DIR__, 2) . '/includes/events.php';

$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAdmin();

    $stmt = $pdo->query(
        'SELECT e.*, u.name AS organizer_name, u.email AS organizer_email,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registration_count
         FROM events e
         INNER JOIN users u ON u.id = e.organizer_id
         ORDER BY e.event_date DESC, e.event_time DESC'
    );
    $events = array_map('formatEventRow', $stmt->fetchAll());

    jsonResponse(['success' => true, 'events' => $events]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdmin();
    $body = readJsonBody();
    $eventId = (int) ($body['eventId'] ?? 0);
    $status = trim((string) ($body['status'] ?? ''));

    if ($eventId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid event id.'], 422);
    }

    if (!isValidEventStatus($status)) {
        jsonResponse(['success' => false, 'message' => 'Invalid event status.'], 422);
    }

    $update = $pdo->prepare('UPDATE events SET status = ? WHERE id = ?');
    $update->execute([$status, $eventId]);

    if ($update->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => 'Event not found.'], 404);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Event status updated.',
    ]);
}

jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
