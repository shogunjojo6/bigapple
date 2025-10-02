<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

$cart = get_cart();
if (cart_item_count() === 0) {
    header('Location: index.php');
    exit;
}

$tableId = (int)($_POST['table_id'] ?? 0);
$activeTable = get_active_table();
if ($activeTable) {
    $tableId = (int)$activeTable['id'];
}
$paymentMethod = $_POST['payment_method'] ?? 'cash';
$note = $_POST['customer_note'] ?? '';

$allowedPaymentMethods = ['cash', 'promptpay', 'pay_later'];
if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
    $paymentMethod = 'cash';
}

if ($tableId <= 0) {
    header('Location: checkout.php');
    exit;
}

try {
    $cartItems = [];
    $total = 0;
    foreach ($cart as $item) {
        $menu = fetch_menu_item((int)$item['menu_id']);
        if (!$menu) {
            continue;
        }
        $quantity = max(1, (int)$item['quantity']);
        $price = (float)$menu['price'];
        $subtotal = $price * $quantity;
        $total += $subtotal;

        $cartItems[] = [
            'menu_id' => (int)$menu['id'],
            'quantity' => $quantity,
            'unit_price' => $price,
            'subtotal' => $subtotal,
            'name' => $menu['name'],
        ];
    }

    if (empty($cartItems)) {
        header('Location: cart.php?action=clear');
        exit;
    }

    $orderData = [
        'table_id' => $tableId,
        'customer_note' => trim($note),
        'payment_method' => $paymentMethod,
        'payment_status' => 'pending',
        'total_amount' => $total,
    ];

    $order = create_order($orderData, $cartItems);
    $_SESSION['last_order_code'] = $order['order_code'];
    $orderDetails = fetch_order_details($order['id']);

    if ($orderDetails) {
        $message = format_order_message($orderDetails, $orderDetails['items']);
        send_telegram_notification($message, [
            [
                ['text' => '✅ ยืนยันจ่าย', 'callback_data' => 'confirm_payment_' . $order['id']],
                ['text' => '📄 เปิดออเดอร์', 'url' => ADMIN_URL . '/order.php?id=' . $order['id']],
            ],
        ]);
    }

    clear_cart();
    header('Location: order_success.php?order_id=' . $order['id']);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>ขออภัย เกิดข้อผิดพลาด</h1>';
    echo '<p>กรุณาลองใหม่อีกครั้ง หรือแจ้งพนักงาน</p>';
}

