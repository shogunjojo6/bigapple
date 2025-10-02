<?php
require_once __DIR__ . '/bootstrap.php';
require_admin_auth();

$orderId = (int)($_GET['order_id'] ?? 0);
$order = $orderId ? fetch_order_details($orderId) : null;

if (!$order) {
    echo '<h1>ไม่พบออเดอร์</h1>';
    exit;
}

?><!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จ #<?= htmlspecialchars($order['order_code']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background:#fff; color:#111827; }
        .receipt { width: min(600px, 92%); margin: 2rem auto; font-family: 'Segoe UI', sans-serif; }
        .receipt header { border-bottom: 1px solid #e5e7eb; margin-bottom: 1.5rem; padding-bottom: 1rem; }
        .receipt table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .receipt th, .receipt td { padding: 0.6rem 0; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .receipt tfoot td { font-weight: 600; }
    </style>
</head>
<body>
<div class="receipt">
    <header>
        <h1>ใบเสร็จ #<?= htmlspecialchars($order['order_code']); ?></h1>
        <p>โต๊ะ <?= htmlspecialchars($order['table_number']); ?> • วันที่ <?= date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
    </header>
    <table>
        <thead>
            <tr>
                <th>รายการ</th>
                <th style="width:80px;">จำนวน</th>
                <th style="width:120px;">รวม</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order['items'] as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']); ?></td>
                <td><?= (int)$item['quantity']; ?></td>
                <td><?= format_currency((float)$item['subtotal']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">ยอดรวม</td>
                <td><?= format_currency((float)$order['total_amount']); ?></td>
            </tr>
        </tfoot>
    </table>
    <?php if (!empty($order['customer_note'])): ?>
        <p style="margin-top:1.5rem;">หมายเหตุ: <?= nl2br(htmlspecialchars($order['customer_note'])); ?></p>
    <?php endif; ?>
</div>
</body>
</html>
