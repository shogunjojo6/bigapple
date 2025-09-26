<?php
$pageTitle = 'จัดการออเดอร์';
$activeMenu = 'orders';
require_once __DIR__ . '/header.php';

$filter = $_GET['filter'] ?? 'pending_payment';
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_POST['action'] === 'update_payment') {
            $orderId = (int)$_POST['order_id'];
            $status = $_POST['payment_status'] === 'paid' ? 'paid' : 'pending';
            update_payment_status($orderId, $status);
            $message = 'อัพเดทสถานะการชำระเงินเรียบร้อย';
        } elseif ($_POST['action'] === 'update_status') {
            $orderId = (int)$_POST['order_id'];
            $status = $_POST['order_status'];
            update_order_status($orderId, $status, $_SESSION['admin_id'] ?? null);
            $message = 'อัพเดทสถานะออเดอร์สำเร็จ';
        }
    } catch (Throwable $e) {
        $error = 'ไม่สามารถอัพเดทข้อมูลได้ กรุณาลองใหม่';
    }
}

switch ($filter) {
    case 'paid':
        $orders = fetch_orders(null, 'paid');
        break;
    case 'all':
        $orders = fetch_orders();
        break;
    case 'pending_payment':
    default:
        $orders = fetch_orders(null, 'pending');
        $filter = 'pending_payment';
        break;
}

$orderStatusOptions = [
    'pending' => 'รอรับออเดอร์',
    'preparing' => 'กำลังทำ',
    'ready' => 'พร้อมเสิร์ฟ',
    'served' => 'เสิร์ฟแล้ว',
];
?>
<div class="admin-header">
    <div>
        <h1>รายการออเดอร์</h1>
        <p style="color:rgba(71,85,105,0.78);margin:0;">จัดการสถานะและยืนยันการชำระเงิน</p>
    </div>
</div>

<nav style="margin-bottom:1.25rem;display:flex;gap:0.65rem;flex-wrap:wrap;">
    <a class="btn <?= $filter === 'pending_payment' ? 'btn-primary' : 'btn-light'; ?>" href="?filter=pending_payment">รอชำระเงิน</a>
    <a class="btn <?= $filter === 'paid' ? 'btn-primary' : 'btn-light'; ?>" href="?filter=paid">ชำระแล้ว</a>
    <a class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-light'; ?>" href="?filter=all">ทั้งหมด</a>
</nav>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>หมายเลข</th>
                <th>โต๊ะ</th>
                <th>ยอดรวม</th>
                <th>สถานะ</th>
                <th>ชำระเงิน</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$orders): ?>
                <tr><td colspan="6" style="text-align:center;padding:2rem;color:#9ca3af;">ยังไม่มีออเดอร์ในหมวดนี้</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><a href="order.php?id=<?= $order['id']; ?>">#<?= htmlspecialchars($order['order_code']); ?></a></strong><br><small><?= date('d/m H:i', strtotime($order['created_at'])); ?></small></td>
                        <td>โต๊ะ <?= htmlspecialchars($order['table_number']); ?></td>
                        <td><?= format_currency((float)$order['total_amount']); ?></td>
                        <td>
                            <form method="post" style="margin:0;display:flex;flex-direction:column;gap:0.35rem;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                                <select name="order_status" class="form-control" onchange="this.form.submit();">
                                    <?php foreach ($orderStatusOptions as $value => $label): ?>
                                        <option value="<?= $value; ?>" <?= $order['order_status'] === $value ? 'selected' : ''; ?>><?= $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="action" value="update_payment">
                                <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                                <select name="payment_status" class="form-control" onchange="this.form.submit();">
                                    <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>รอชำระ</option>
                                    <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>ชำระแล้ว</option>
                                </select>
                            </form>
                        </td>
                        <td class="table-actions">
                            <a class="btn btn-light" href="order.php?id=<?= $order['id']; ?>">รายละเอียด</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
