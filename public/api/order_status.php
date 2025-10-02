<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

$orderCode = trim($_GET['order_code'] ?? '');
if ($orderCode === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing order code']);
    exit;
}

$order = fetch_order_by_code($orderCode);
if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลออเดอร์']);
    exit;
}

$response = [
    'success' => true,
    'order' => [
        'order_code' => $order['order_code'],
        'payment_status' => $order['payment_status'],
        'order_status' => $order['order_status'],
        'updated_at' => $order['updated_at'] ?? null,
        'table_number' => $order['table_number'],
        'total_amount' => (float)$order['total_amount'],
        'items' => array_map(static function ($item) {
            return [
                'name' => $item['name'],
                'quantity' => (int)$item['quantity'],
                'subtotal' => (float)$item['subtotal'],
            ];
        }, $order['items'] ?? []),
    ],
];

echo json_encode($response);
