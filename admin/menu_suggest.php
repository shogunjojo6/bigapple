<?php
require_once __DIR__ . '/bootstrap.php';
require_admin_auth();

header('Content-Type: application/json; charset=UTF-8');

$term = trim($_GET['term'] ?? '');
$result = [];

if ($term !== '') {
    $pdo = get_db_connection();
    $like = $term . '%';
    $stmt = $pdo->prepare('SELECT m.id, m.name, m.price, c.name AS category_name FROM menus m INNER JOIN categories c ON m.category_id = c.id WHERE m.name LIKE :term ORDER BY m.name LIMIT 10');
    $stmt->execute(['term' => $like]);
    $result = $stmt->fetchAll();
}

echo json_encode(['items' => $result], JSON_UNESCAPED_UNICODE);
