<?php

require_once __DIR__ . '/config.php';

function generate_order_code(): string
{
    return 'BA' . date('ymdHis') . strtoupper(bin2hex(random_bytes(2)));
}

function create_order(array $orderData, array $cartItems): array
{
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    try {
        $orderCode = generate_order_code();
        $stmt = $pdo->prepare('INSERT INTO orders (order_code, table_id, customer_note, payment_method, payment_status, order_status, total_amount) VALUES (:order_code, :table_id, :customer_note, :payment_method, :payment_status, :order_status, :total_amount)');
        $stmt->execute([
            'order_code' => $orderCode,
            'table_id' => $orderData['table_id'],
            'customer_note' => $orderData['customer_note'] ?? null,
            'payment_method' => $orderData['payment_method'],
            'payment_status' => $orderData['payment_status'] ?? 'pending',
            'order_status' => 'pending',
            'total_amount' => $orderData['total_amount'],
        ]);

        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, menu_id, quantity, unit_price, subtotal) VALUES (:order_id, :menu_id, :quantity, :unit_price, :subtotal)');

        foreach ($cartItems as $item) {
            $itemStmt->execute([
                'order_id' => $orderId,
                'menu_id' => $item['menu_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        $pdo->commit();
        return ['id' => $orderId, 'order_code' => $orderCode];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function fetch_orders(string $statusFilter = null, string $paymentStatus = null): array
{
    $pdo = get_db_connection();
    $sql = 'SELECT o.*, rt.table_number FROM orders o INNER JOIN restaurant_tables rt ON o.table_id = rt.id WHERE 1=1';
    $params = [];

    if ($statusFilter !== null) {
        $sql .= ' AND o.order_status = :status';
        $params['status'] = $statusFilter;
    }

    if ($paymentStatus !== null) {
        $sql .= ' AND o.payment_status = :payment_status';
        $params['payment_status'] = $paymentStatus;
    }

    $sql .= ' ORDER BY o.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function fetch_order_details(int $orderId): ?array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT o.*, rt.table_number FROM orders o INNER JOIN restaurant_tables rt ON o.table_id = rt.id WHERE o.id = :id');
    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        return null;
    }

    $itemsStmt = $pdo->prepare('SELECT oi.*, m.name FROM order_items oi INNER JOIN menus m ON oi.menu_id = m.id WHERE oi.order_id = :order_id');
    $itemsStmt->execute(['order_id' => $orderId]);
    $order['items'] = $itemsStmt->fetchAll();

    return $order;
}

function update_order_status(int $orderId, string $status, ?int $adminId = null, string $note = null): bool
{
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('UPDATE orders SET order_status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $orderId]);

        $logStmt = $pdo->prepare('INSERT INTO order_status_logs (order_id, status, note, created_by) VALUES (:order_id, :status, :note, :created_by)');
        $logStmt->execute([
            'order_id' => $orderId,
            'status' => $status,
            'note' => $note,
            'created_by' => $adminId,
        ]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function update_payment_status(int $orderId, string $paymentStatus): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('UPDATE orders SET payment_status = :payment_status WHERE id = :id');
    return $stmt->execute([
        'payment_status' => $paymentStatus,
        'id' => $orderId,
    ]);
}

function calculate_sales_summary(string $startDate, string $endDate): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT DATE(created_at) as order_date, SUM(total_amount) as total_sales, COUNT(*) as order_count FROM orders WHERE DATE(created_at) BETWEEN :start AND :end AND payment_status = "paid" GROUP BY DATE(created_at) ORDER BY DATE(created_at)');
    $stmt->execute([
        'start' => $startDate,
        'end' => $endDate,
    ]);

    return $stmt->fetchAll();
}




function fetch_order_by_code(string $orderCode): ?array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT o.*, rt.table_number FROM orders o INNER JOIN restaurant_tables rt ON o.table_id = rt.id WHERE o.order_code = :code LIMIT 1');
    $stmt->execute(['code' => $orderCode]);
    $order = $stmt->fetch();

    if (!$order) {
        return null;
    }

    $itemsStmt = $pdo->prepare('SELECT oi.*, m.name FROM order_items oi INNER JOIN menus m ON oi.menu_id = m.id WHERE oi.order_id = :order_id');
    $itemsStmt->execute(['order_id' => $order['id']]);
    $order['items'] = $itemsStmt->fetchAll();

    return $order;
}

