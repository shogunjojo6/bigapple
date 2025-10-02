<?php

require_once __DIR__ . '/config.php';

function set_active_table(string $tableNumber): void
{
    require_once __DIR__ . '/session.php';
    ensure_session();

    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT id, table_number FROM restaurant_tables WHERE table_number = :num AND is_active = 1');
    $stmt->execute(['num' => $tableNumber]);
    $table = $stmt->fetch();

    if ($table) {
        $_SESSION['active_table_id'] = (int) $table['id'];
        $_SESSION['active_table_number'] = $table['table_number'];
    }
}

function get_active_table(): ?array
{
    require_once __DIR__ . '/session.php';
    ensure_session();

    if (!isset($_SESSION['active_table_id'], $_SESSION['active_table_number'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['active_table_id'],
        'table_number' => $_SESSION['active_table_number'],
    ];
}

function clear_active_table(): void
{
    require_once __DIR__ . '/session.php';
    ensure_session();
    unset($_SESSION['active_table_id'], $_SESSION['active_table_number']);
}

function create_table_request(int $tableId, string $type = 'checkout'): bool
{
    $pdo = get_db_connection();
    $type = in_array($type, ['checkout', 'assistance'], true) ? $type : 'checkout';

    $check = $pdo->prepare('SELECT id FROM table_service_requests WHERE table_id = :table AND request_type = :type AND status = "open" LIMIT 1');
    $check->execute(['table' => $tableId, 'type' => $type]);
    $existingId = $check->fetchColumn();
    if ($existingId) {
        $update = $pdo->prepare('UPDATE table_service_requests SET created_at = NOW(), closed_at = NULL, status = "open" WHERE id = :id');
        return $update->execute(['id' => $existingId]);
    }

    $stmt = $pdo->prepare('INSERT INTO table_service_requests (table_id, request_type) VALUES (:table, :type)');
    return $stmt->execute(['table' => $tableId, 'type' => $type]);
}

function close_table_requests(int $tableId, ?string $type = null): void
{
    $pdo = get_db_connection();
    $sql = 'UPDATE table_service_requests SET status = "closed", closed_at = NOW() WHERE table_id = :table AND status = "open"';
    $params = ['table' => $tableId];
    if ($type !== null) {
        $sql .= ' AND request_type = :type';
        $params['type'] = $type;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function fetch_table_overview(): array
{
    $pdo = get_db_connection();
    $sql = 'SELECT t.id, t.table_number,
                   SUM(CASE WHEN o.order_status != "served" OR o.payment_status != "paid" THEN 1 ELSE 0 END) AS open_orders,
                   COUNT(DISTINCT CASE WHEN r.status = "open" THEN r.id END) AS open_requests
            FROM restaurant_tables t
            LEFT JOIN orders o ON o.table_id = t.id
            LEFT JOIN table_service_requests r ON r.table_id = t.id AND r.status = "open"
            GROUP BY t.id
            ORDER BY t.table_number';
    $stmt = $pdo->query($sql);
    $tables = [];
    while ($row = $stmt->fetch()) {
        $row['open_orders'] = (int) $row['open_orders'];
        $row['open_requests'] = (int) $row['open_requests'];
        $tables[] = $row;
    }
    return $tables;
}

function fetch_table_active_orders(int $tableId): array
{
    $pdo = get_db_connection();
    $sql = 'SELECT * FROM orders WHERE table_id = :table ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['table' => $tableId]);
    return $stmt->fetchAll();
}

function fetch_table_requests_by_table(int $tableId, string $status = 'open'): array
{
    $pdo = get_db_connection();
    $statusClause = '';
    $params = ['table' => $tableId];
    if (in_array($status, ['open', 'closed'], true)) {
        $statusClause = ' AND status = :status';
        $params['status'] = $status;
    }
    $stmt = $pdo->prepare('SELECT * FROM table_service_requests WHERE table_id = :table' . $statusClause . ' ORDER BY created_at DESC');
    $stmt->execute($params);
    return $stmt->fetchAll();
}
