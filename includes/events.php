<?php

declare(strict_types=1);

require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/categories.php';

const EVENT_STATUSES = ['upcoming', 'completed', 'cancelled', 'published', 'draft'];

function requireOrganizer(): array
{
    $user = requireLogin();
    if ($user['role'] !== 'organizer') {
        jsonResponse(['success' => false, 'message' => 'Organizer access required.'], 403);
    }
    return $user;
}

function requireStudent(): array
{
    $user = requireLogin();
    if ($user['role'] !== 'student') {
        jsonResponse(['success' => false, 'message' => 'Student access required.'], 403);
    }
    return $user;
}

function isValidEventStatus(string $status): bool
{
    return in_array($status, EVENT_STATUSES, true);
}

function isPublicEventStatus(string $status): bool
{
    return in_array($status, ['upcoming', 'completed', 'published'], true);
}

function isRegisterableEventStatus(string $status): bool
{
    return in_array($status, ['upcoming', 'published'], true);
}

function normalizeEventStatus(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'published', 'draft' => 'upcoming',
        default              => $status,
    };
}

function formatEventTime(string $time): string
{
    return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
}

function resolveEventImageUrl(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    if (str_starts_with($path, '/')) {
        return $path;
    }

    return BASE_URL . '/' . ltrim($path, '/');
}

function formatEventRow(array $row): array
{
    $capacity = $row['capacity'] !== null ? (int) $row['capacity'] : null;
    $registrationCount = (int) ($row['registration_count'] ?? 0);

    return [
        'id'                  => (int) $row['id'],
        'title'               => $row['title'],
        'description'         => $row['description'],
        'categoryId'          => (int) ($row['category_id'] ?? 0),
        'category'            => $row['category_name'] ?? '',
        'categoryDescription' => $row['category_description'] ?? '',
        'date'                => $row['event_date'],
        'time'                => formatEventTime($row['event_time']),
        'location'            => $row['location'],
        'imageUrl'            => resolveEventImageUrl($row['image_path'] ?: null),
        'capacity'            => $capacity,
        'status'              => $row['status'],
        'organizerId'         => (int) $row['organizer_id'],
        'organizerName'       => $row['organizer_name'] ?? '',
        'organizerEmail'      => $row['organizer_email'] ?? '',
        'registrationCount'   => $registrationCount,
        'spotsLeft'           => $capacity !== null ? max(0, $capacity - $registrationCount) : null,
        'isFull'              => $capacity !== null && $registrationCount >= $capacity,
    ];
}

function eventSelectSql(): string
{
    return 'SELECT e.*, c.name AS category_name, c.description AS category_description,
                   u.name AS organizer_name, u.email AS organizer_email,
                   (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registration_count
            FROM events e
            INNER JOIN categories c ON c.id = e.category_id
            INNER JOIN users u ON u.id = e.organizer_id';
}

function fetchEventById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(eventSelectSql() . ' WHERE e.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function assertOrganizerOwnsEvent(array $user, array $event): void
{
    if ((int) $event['organizer_id'] !== (int) $user['id']) {
        jsonResponse(['success' => false, 'message' => 'You can only manage your own events.'], 403);
    }
}

function readEventRequestData(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    return readJsonBody();
}

function parseCapacity(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    $capacity = (int) $value;
    if ($capacity < 1) {
        jsonResponse(['success' => false, 'message' => 'Capacity must be at least 1.'], 422);
    }

    return $capacity;
}

function resolveEventImagePath(array $input, ?array $file, ?string $existingPath = null): ?string
{
    if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            return saveUploadedEventImage($file);
        } catch (Throwable $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    $imageUrl = trim((string) ($input['imageUrl'] ?? ''));
    if ($imageUrl !== '') {
        return $imageUrl;
    }

    if ($existingPath !== null && ($input['keepImage'] ?? '') === '1') {
        return $existingPath;
    }

    return $existingPath;
}

function validateEventFields(PDO $pdo, array $input): array
{
    $title = trim((string) ($input['title'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $categoryInput = $input['categoryId'] ?? $input['category'] ?? '';
    $date = trim((string) ($input['date'] ?? ''));
    $time = trim((string) ($input['time'] ?? ''));
    $location = trim((string) ($input['location'] ?? ''));
    $status = normalizeEventStatus((string) ($input['status'] ?? 'upcoming'));

    if ($title === '' || $description === '' || $location === '') {
        jsonResponse(['success' => false, 'message' => 'Please fill all required fields.'], 422);
    }

    $categoryId = resolveCategoryId($pdo, $categoryInput);

    if ($date === '' || $time === '') {
        jsonResponse(['success' => false, 'message' => 'Date and time are required.'], 422);
    }

    if (!isValidEventStatus($status)) {
        jsonResponse(['success' => false, 'message' => 'Invalid event status.'], 422);
    }

    if ($status === 'upcoming') {
        $eventStart = strtotime($date . ' ' . $time);
        if ($eventStart !== false && $eventStart < time()) {
            jsonResponse(['success' => false, 'message' => 'Upcoming events must have a future date and time.'], 422);
        }
    }

    return [
        'title'       => $title,
        'description' => $description,
        'categoryId'  => $categoryId,
        'date'        => $date,
        'time'        => $time,
        'location'    => $location,
        'status'      => $status,
        'capacity'    => parseCapacity($input['capacity'] ?? null),
    ];
}
