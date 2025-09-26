<?php
require_once __DIR__ . '/bootstrap.php';

$orderCode = sanitize($_GET['order_code'] ?? '');
$order = null;
$statusLabel = [
    'pending' => 'รอรับออเดอร์',
    'preparing' => 'กำลังทำอาหาร',
    'ready' => 'พร้อมเสิร์ฟ',
    'served' => 'เสิร์ฟแล้ว',
];

if ($orderCode) {
    $order = fetch_order_by_code($orderCode);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ติดตามสถานะออเดอร์ | Big Apple</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<header>
    <div class="container">
        <div class="hero">
            <div>
                <h1>ติดตามสถานะออเดอร์</h1>
                <p>กรอกหมายเลขคำสั่งซื้อ (เช่น #BA250101XXXX)</p>
            </div>
            <div class="hero-actions">
                <a class="btn btn-secondary" href="../admin/index.php">Admin</a>
            </div>
        </div>
    </div>
</header>

<main class="container" style="padding:3rem 0 4rem;display:grid;gap:2.25rem;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));align-items:flex-start;">
    <section class="glass-card">
        <form method="get" style="display:flex;flex-direction:column;gap:0.75rem;">
            <label for="order_code">หมายเลขออเดอร์</label>
            <input class="form-control" type="text" id="order_code" name="order_code" value="<?= htmlspecialchars($orderCode); ?>" placeholder="#BA250101XXXX" required>
            <button class="btn btn-primary" type="submit">ค้นหา</button>
        </form>
        <div style="margin-top:1.5rem;">
            <a class="btn btn-light" href="index.php">กลับไปหน้าเมนู</a>
        </div>
    </section>

    <?php if ($order): ?>
        <section class="glass-card">
            <h2 style="margin-top:0;">รายละเอียดออเดอร์</h2>
            <p><strong>หมายเลข:</strong> #<?= htmlspecialchars($order['order_code']); ?></p>
            <p><strong>โต๊ะ:</strong> <?= htmlspecialchars($order['table_number']); ?></p>
            <p><strong>สถานะการชำระเงิน:</strong> <?= strtoupper($order['payment_status']); ?></p>
            <p><strong>สถานะออเดอร์:</strong> <?= $statusLabel[$order['order_status']] ?? strtoupper($order['order_status']); ?></p>
            <hr style="margin:1rem 0;border:0;border-top:1px solid rgba(148,163,184,0.25);">
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem;">
                <?php foreach ($order['items'] as $item): ?>
                    <li style="display:flex;justify-content:space-between;">
                        <span><?= htmlspecialchars($item['name']); ?> x <?= (int)$item['quantity']; ?></span>
                        <span><?= format_currency((float)$item['subtotal']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p style="margin-top:1rem;font-weight:650;display:flex;justify-content:space-between;">
                <span>ยอดรวม</span>
                <span><?= format_currency((float)$order['total_amount']); ?></span>
            </p>
        </section>
    <?php elseif ($orderCode): ?>
        <section class="glass-card" style="box-shadow:0 10px 26px rgba(239,68,68,0.18);">
            <p style="color:#dc2626;">ไม่พบหมายเลขออเดอร์ที่ค้นหา กรุณาตรวจสอบอีกครั้งหรือสอบถามพนักงาน</p>
        </section>
    <?php endif; ?>
</main>
<footer>
    <p>© <?= date('Y'); ?> Big Apple Restaurant</p>
</footer>
</body>
</html>
