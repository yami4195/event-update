<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/events.php';

requirePost();

$user = requireOrganizer();
$body = readJsonBody();
$id = (int) ($body['id'] ?? 0);

if ($id <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid event id.'], 422);
}

$pdo = getDb();
$row = fetchEventById($pdo, $id);
if (!$row) {
    jsonResponse(['success' => false, 'message' => 'Event not found.'], 404);
}

assertOrganizerOwnsEvent($user, $row);

$delete = $pdo->prepare('DELETE FROM events WHERE id = ?');
$delete->execute([$id]);

jsonResponse([
    'success' => true,
    'message' => 'Event deleted successfully.',
]);
