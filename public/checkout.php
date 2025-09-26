<?php
require_once __DIR__ . '/bootstrap.php';

$cart = get_cart();
if (cart_item_count() === 0) {
    header('Location: index.php');
    exit;
}

$tables = fetch_restaurant_tables();
$total = calculate_cart_total();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ยืนยันออเดอร์ | Big Apple</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<header>
    <div class="container">
        <div class="hero">
            <div>
                <h1>ยืนยันออเดอร์</h1>
                <p>เลือกโต๊ะและวิธีชำระเงินให้ครบก่อนส่งคำสั่งซื้อ</p>
            </div>
            <div class="hero-actions">
                <a class="btn btn-light" href="cart.php">← กลับไปตะกร้า</a>
                <a class="btn btn-secondary" href="../admin/index.php">Admin</a>
            </div>
        </div>
    </div>
</header>

<main class="container" style="padding:3rem 0 4rem;display:grid;gap:2.25rem;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));align-items:flex-start;">
    <section class="glass-card">
        <h2 style="margin-top:0;">รายละเอียดออเดอร์</h2>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem;">
            <?php foreach ($cart as $item): ?>
                <li style="display:flex;justify-content:space-between;">
                    <span><?= htmlspecialchars($item['name']); ?> x <?= $item['quantity']; ?></span>
                    <strong><?= format_currency((float)$item['subtotal']); ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
        <hr style="margin:1.5rem 0;border:0;border-top:1px solid rgba(148,163,184,0.25);">
        <p style="display:flex;justify-content:space-between;font-size:1.2rem;font-weight:650;">
            <span>ยอดสุทธิ</span>
            <span><?= format_currency($total); ?></span>
        </p>
    </section>

    <form method="post" action="order_submit.php" class="glass-card" style="gap:1rem;display:flex;flex-direction:column;">
        <input type="hidden" name="total_amount" value="<?= $total; ?>">
        <div class="form-group">
            <label for="table_id">เลือกโต๊ะ</label>
            <select name="table_id" id="table_id" class="form-control" required>
                <option value="">-- กรุณาเลือก --</option>
                <?php foreach ($tables as $table): ?>
                    <option value="<?= $table['id']; ?>">โต๊ะ <?= htmlspecialchars($table['table_number']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>วิธีการชำระเงิน</label>
            <div style="display:flex;flex-direction:column;gap:0.65rem;">
                <label style="display:flex;align-items:center;gap:0.6rem;">
                    <input type="radio" name="payment_method" value="cash" checked>
                    เงินสด (จ่ายหลังทาน)
                </label>
                <label style="display:flex;align-items:center;gap:0.6rem;">
                    <input type="radio" name="payment_method" value="promptpay">
                    PromptPay QR Code
                </label>
                <label style="display:flex;align-items:center;gap:0.6rem;">
                    <input type="radio" name="payment_method" value="pay_later">
                    จ่ายทีหลัง / ใบเสร็จรวม
                </label>
            </div>
        </div>
        <div id="promptpay-section" class="glass-card" style="display:none;border:1px dashed rgba(249,115,22,0.55);background:rgba(249,115,22,0.08);padding:1rem;">
            <p style="margin:0 0 0.5rem;">📱 สแกน PromptPay หมายเลข <strong><?= PROMPTPAY_ACCOUNT; ?></strong></p>
            <p style="margin:0;color:#6b7280;">หลังจากสแกน กรุณาแจ้งพนักงานเพื่อยืนยันการชำระเงิน</p>
        </div>
        <div class="form-group">
            <label for="customer_note">หมายเหตุเพิ่มเติม</label>
            <textarea name="customer_note" id="customer_note" class="form-control" rows="3" placeholder="เช่น ไม่เผ็ด, ไม่ใส่ถั่ว"></textarea>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%;">ยืนยันออเดอร์</button>
    </form>
</main>
<footer>
    <p>© <?= date('Y'); ?> Big Apple Restaurant</p>
</footer>
<script src="../assets/js/main.js"></script>
</body>
</html>
