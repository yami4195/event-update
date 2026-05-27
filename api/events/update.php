<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/events.php';

requirePost();

$user = requireOrganizer();
$input = readEventRequestData();
$id = (int) ($input['id'] ?? 0);

if ($id <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid event id.'], 422);
}

$pdo = getDb();
$row = fetchEventById($pdo, $id);
if (!$row) {
    jsonResponse(['success' => false, 'message' => 'Event not found.'], 404);
}

assertOrganizerOwnsEvent($user, $row);

$fields = validateEventFields($pdo, $input);
$imagePath = resolveEventImagePath($input, $_FILES['image'] ?? null, $row['image_path']);

$stmt = $pdo->prepare(
    'UPDATE events
     SET title = ?, description = ?, category_id = ?, event_date = ?, event_time = ?,
         location = ?, image_path = ?, capacity = ?, status = ?
     WHERE id = ?'
);
$stmt->execute([
    $fields['title'],
    $fields['description'],
    $fields['categoryId'],
    $fields['date'],
    $fields['time'],
    $fields['location'],
    $imagePath,
    $fields['capacity'],
    $fields['status'],
    $id,
]);

$row = fetchEventById($pdo, $id);

jsonResponse([
    'success' => true,
    'message' => 'Event updated successfully.',
    'event'   => formatEventRow($row),
]);
