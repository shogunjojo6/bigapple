<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$menuId = (int)($_POST['menu_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

try {
    if ($menuId <= 0) {
        throw new InvalidArgumentException('Invalid menu');
    }
    add_to_cart($menuId, $quantity);
    $cartCount = cart_item_count();
    $cartTotal = calculate_cart_total();
    echo json_encode([
        'success' => true,
        'count' => $cartCount,
        'total' => $cartTotal,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage() ?: 'ไม่สามารถเพิ่มเมนูได้กรุณาลองใหม่อีกครั้ง'
    ], JSON_UNESCAPED_UNICODE);
}
