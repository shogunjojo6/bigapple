<?php
require_once __DIR__ . '/bootstrap.php';

$orderId = (int)($_GET['order_id'] ?? 0);
$order = $orderId ? fetch_order_details($orderId) : null;

if (!$order) {
    http_response_code(404);
    echo '<h1>ไม่พบออเดอร์</h1>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ออเดอร์สำเร็จ | Big Apple</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<header>
    <div class="container">
        <div class="hero">
            <div>
                <h1>ขอบคุณสำหรับออเดอร์!</h1>
                <p>หมายเลขคำสั่งซื้อของคุณคือ <strong>#<?= htmlspecialchars($order['order_code']); ?></strong></p>
            </div>
            <div class="hero-actions">
                <a class="btn btn-light" href="index.php">กลับหน้าเมนู</a>
                <a class="btn btn-secondary" href="../admin/index.php">Admin</a>
            </div>
        </div>
    </div>
</header>

<main class="container" style="padding:3rem 0 4rem;display:grid;gap:2.25rem;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));align-items:flex-start;">
    <section class="glass-card">
        <h2 style="margin-top:0;">สถานะล่าสุด</h2>
        <p style="font-size:1.1rem;">สถานะออเดอร์: <strong><?= strtoupper($order['order_status']); ?></strong></p>
        <p>พนักงานจะเข้ามายืนยันการชำระเงินและเริ่มทำอาหารของคุณทันที</p>
        <hr style="margin:1.5rem 0;border:0;border-top:1px solid rgba(148,163,184,0.25);">
        <h3>รายการอาหาร</h3>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem;">
            <?php foreach ($order['items'] as $item): ?>
                <li style="display:flex;justify-content:space-between;">
                    <span><?= htmlspecialchars($item['name']); ?> x <?= (int)$item['quantity']; ?></span>
                    <strong><?= format_currency((float)$item['subtotal']); ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
        <p style="margin-top:1rem;font-weight:650;display:flex;justify-content:space-between;">
            <span>ยอดรวม</span>
            <span><?= format_currency((float)$order['total_amount']); ?></span>
        </p>
    </section>

    <section class="glass-card">
        <h2 style="margin-top:0;">ขั้นตอนถัดไป</h2>
        <ol style="margin:0;padding-left:1.25rem;display:flex;flex-direction:column;gap:0.75rem;">
            <li>รอพนักงานมาที่โต๊ะ <strong><?= htmlspecialchars($order['table_number']); ?></strong> เพื่อยืนยันออเดอร์</li>
            <li>หากชำระผ่าน PromptPay ให้แสดงสลิปการโอนกับพนักงาน</li>
            <li>ติดตามสถานะการทำอาหารผ่านพนักงานให้บริการ</li>
        </ol>
        <div style="margin-top:1.5rem;display:flex;gap:0.75rem;flex-wrap:wrap;">
            <a class="btn btn-primary" href="index.php">สั่งเมนูเพิ่ม</a>
            <a class="btn btn-light" href="order_status.php?order_code=<?= htmlspecialchars($order['order_code']); ?>">ติดตามสถานะ</a>
        </div>
    </section>
</main>
<footer>
    <p>© <?= date('Y'); ?> Big Apple Restaurant</p>
</footer>
</body>
</html>
