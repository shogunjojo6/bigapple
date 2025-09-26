<?php
$pageTitle = 'รายละเอียดออเดอร์';
$activeMenu = 'orders';
require_once __DIR__ . '/header.php';

$orderId = (int)($_GET['id'] ?? 0);
$order = $orderId ? fetch_order_details($orderId) : null;

if (!$order) {
    echo '<div class="card"><p>ไม่พบออเดอร์</p></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$statusOptions = [
    'pending' => 'รอรับออเดอร์',
    'preparing' => 'กำลังทำ',
    'ready' => 'พร้อมเสิร์ฟ',
    'served' => 'เสิร์ฟแล้ว',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_POST['action'] === 'update_status') {
            update_order_status($orderId, $_POST['order_status'], $_SESSION['admin_id'] ?? null, $_POST['note'] ?? null);
            $order = fetch_order_details($orderId);
        } elseif ($_POST['action'] === 'update_payment') {
            update_payment_status($orderId, $_POST['payment_status']);
            $order = fetch_order_details($orderId);
        }
    } catch (Throwable $e) {
        echo '<div class="alert alert-danger">เกิดข้อผิดพลาดในการอัพเดทข้อมูล</div>';
    }
}
?>
<div class="admin-header">
    <div>
        <h1>ออเดอร์ #<?= htmlspecialchars($order['order_code']); ?></h1>
        <p style="color:rgba(71,85,105,0.78);margin:0;">โต๊ะ <?= htmlspecialchars($order['table_number']); ?> • เวลาสร้าง <?= date('d/m H:i', strtotime($order['created_at'])); ?></p>
    </div>
    <a class="btn btn-light" href="orders.php">← กลับรายการ</a>
</div>

<section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.75rem;align-items:flex-start;">
    <div class="card">
        <h2 style="margin-top:0;">รายการอาหาร</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>เมนู</th>
                    <th>จำนวน</th>
                    <th>รวม</th>
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
        </table>
        <p style="text-align:right;font-weight:650;margin-top:1rem;">ยอดรวม: <?= format_currency((float)$order['total_amount']); ?></p>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">อัพเดทสถานะ</h2>
        <form method="post" style="display:flex;flex-direction:column;gap:0.85rem;">
            <input type="hidden" name="action" value="update_status">
            <label for="order_status">สถานะออเดอร์</label>
            <select id="order_status" name="order_status" class="form-control">
                <?php foreach ($statusOptions as $value => $label): ?>
                    <option value="<?= $value; ?>" <?= $order['order_status'] === $value ? 'selected' : ''; ?>><?= $label; ?></option>
                <?php endforeach; ?>
            </select>
            <label for="note">หมายเหตุ (ถ้ามี)</label>
            <textarea id="note" name="note" class="form-control" rows="3" placeholder="เช่น ใช้เวลาประมาณ 10 นาที"></textarea>
            <button class="btn btn-primary" type="submit">บันทึกสถานะ</button>
        </form>

        <hr style="margin:1.5rem 0;border:0;border-top:1px solid rgba(148,163,184,0.25);">

        <h2 style="margin-top:0;">การชำระเงิน</h2>
        <form method="post" style="display:flex;flex-direction:column;gap:0.85rem;">
            <input type="hidden" name="action" value="update_payment">
            <label for="payment_status">สถานะการชำระ</label>
            <select id="payment_status" name="payment_status" class="form-control">
                <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>รอชำระ</option>
                <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>ชำระแล้ว</option>
            </select>
            <button class="btn btn-secondary" type="submit">อัพเดทการชำระเงิน</button>
        </form>
    </div>
</section>

<?php if (!empty($order['customer_note'])): ?>
    <section style="margin-top:2rem;" class="card">
        <h2 style="margin-top:0;">หมายเหตุจากลูกค้า</h2>
        <p><?= nl2br(htmlspecialchars($order['customer_note'])); ?></p>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
