<?php

declare(strict_types=1);

function fetchAllCategories(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, name, description, created_at
         FROM categories
         ORDER BY name ASC'
    );

    return array_map('formatCategoryRow', $stmt->fetchAll());
}

function fetchCategoryById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, description, created_at
         FROM categories
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ? formatCategoryRow($row) : null;
}

function formatCategoryRow(array $row): array
{
    return [
        'id'          => (int) $row['id'],
        'name'        => $row['name'],
        'description' => $row['description'] ?? '',
        'createdAt'   => $row['created_at'] ?? null,
    ];
}

function resolveCategoryId(PDO $pdo, mixed $input): int
{
    if (is_numeric($input)) {
        $id = (int) $input;
        if ($id > 0 && fetchCategoryById($pdo, $id)) {
            return $id;
        }
    }

    $name = trim((string) $input);
    if ($name === '') {
        jsonResponse(['success' => false, 'message' => 'Category is required.'], 422);
    }

    $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse(['success' => false, 'message' => 'Invalid category.'], 422);
    }

    return (int) $row['id'];
}
