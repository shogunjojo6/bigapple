<?php

require_once __DIR__ . '/config.php';

function fetch_categories(bool $onlyActive = true): array
{
    $pdo = get_db_connection();
    $sql = 'SELECT id, name, description, is_active FROM categories';
    if ($onlyActive) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY display_order, name';
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function fetch_menus_by_category(int $categoryId): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT id, name, description, price, image_path, is_featured FROM menus WHERE is_active = 1 AND category_id = :category ORDER BY is_featured DESC, name');
    $stmt->execute(['category' => $categoryId]);
    return $stmt->fetchAll();
}

function fetch_full_menu(?string $search = null, bool $includeInactive = false): array
{
    $pdo = get_db_connection();
    $sql = 'SELECT m.*, c.id AS category_id, c.name AS category_name FROM menus m INNER JOIN categories c ON m.category_id = c.id';
    $conditions = [];
    $params = [];

    if (!$includeInactive) {
        $conditions[] = 'm.is_active = 1';
    }

    if ($search !== null && $search !== '') {
        $conditions[] = '(m.name LIKE :search_name OR c.name LIKE :search_category)';
        $params['search_name'] = '%' . $search . '%';
        $params['search_category'] = '%' . $search . '%';
    }

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY c.display_order, c.name, m.is_featured DESC, m.name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_menu_item(int $menuId): ?array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = :id');
    $stmt->execute(['id' => $menuId]);
    $item = $stmt->fetch();
    return $item ?: null;
}

function fetch_restaurant_tables(): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT id, table_number FROM restaurant_tables WHERE is_active = 1 ORDER BY table_number');
    return $stmt->fetchAll();
}

function create_menu(array $data): int
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('INSERT INTO menus (category_id, name, description, price, image_path, is_featured, is_active) VALUES (:category_id, :name, :description, :price, :image_path, :is_featured, :is_active)');
    $stmt->execute([
        'category_id' => $data['category_id'],
        'name' => $data['name'],
        'description' => $data['description'],
        'price' => $data['price'],
        'image_path' => $data['image_path'] ?? null,
        'is_featured' => $data['is_featured'] ?? 0,
        'is_active' => $data['is_active'] ?? 1,
    ]);
    return (int)$pdo->lastInsertId();
}

function update_menu(int $menuId, array $data): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('UPDATE menus SET category_id = :category_id, name = :name, description = :description, price = :price, image_path = :image_path, is_featured = :is_featured, is_active = :is_active WHERE id = :id');
    return $stmt->execute([
        'category_id' => $data['category_id'],
        'name' => $data['name'],
        'description' => $data['description'],
        'price' => $data['price'],
        'image_path' => $data['image_path'] ?? null,
        'is_featured' => $data['is_featured'] ?? 0,
        'is_active' => $data['is_active'] ?? 1,
        'id' => $menuId,
    ]);
}

function delete_menu(int $menuId): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('DELETE FROM menus WHERE id = :id');
    return $stmt->execute(['id' => $menuId]);
}

