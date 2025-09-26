<?php
$pageTitle = 'แดชบอร์ด';
$activeMenu = 'dashboard';
require_once __DIR__ . '/header.php';

$pdo = get_db_connection();
$today = date('Y-m-d');

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = :today');
$stmt->execute(['today' => $today]);
$todayOrders = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT IFNULL(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = :today AND payment_status = "paid"');
$stmt->execute(['today' => $today]);
$todayRevenue = (float)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE order_status = "pending"');
$pendingOrders = (int)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE payment_status = "pending"');
$pendingPayments = (int)$stmt->fetchColumn();

$latestOrders = $pdo->query('SELECT o.order_code, o.payment_status, o.order_status, o.total_amount, rt.table_number, o.created_at FROM orders o INNER JOIN restaurant_tables rt ON o.table_id = rt.id ORDER BY o.created_at DESC LIMIT 8')->fetchAll();
?>
<div class="admin-header">
    <div>
        <h1>ภาพรวมร้านวันนี้</h1>
        <p style="color:rgba(71,85,105,0.78);margin:0;">ช่วยตรวจสอบออเดอร์ใหม่และสถานะการชำระเงิน</p>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
        <a class="btn btn-primary" href="orders.php">ดูออเดอร์ทั้งหมด</a>
        <a class="btn btn-light" href="menu.php">เพิ่มเมนู</a>
    </div>
</div>

<section class="metric-grid">
    <div class="card metric-card">
        <p style="margin:0;color:rgba(71,85,105,0.72);">ออเดอร์วันนี้</p>
        <h2><?= $todayOrders; ?></h2>
    </div>
    <div class="card metric-card">
        <p style="margin:0;color:rgba(71,85,105,0.72);">ยอดชำระเงินวันนี้</p>
        <h2><?= format_currency($todayRevenue); ?></h2>
    </div>
    <div class="card metric-card">
        <p style="margin:0;color:rgba(71,85,105,0.72);">ออเดอร์รอทำ</p>
        <h2><?= $pendingOrders; ?></h2>
    </div>
    <div class="card metric-card">
        <p style="margin:0;color:rgba(71,85,105,0.72);">รอยืนยันชำระ</p>
        <h2><?= $pendingPayments; ?></h2>
    </div>
</section>

<section style="margin-top:2.5rem;">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
            <h2 style="margin:0;">ออเดอร์ล่าสุด</h2>
            <a href="orders.php">ดูทั้งหมด →</a>
        </div>
        <table class="admin-table" style="margin-top:1rem;">
            <thead>
                <tr>
                    <th>เวลา</th>
                    <th>ออเดอร์</th>
                    <th>โต๊ะ</th>
                    <th>ยอดรวม</th>
                    <th>สถานะ</th>
                    <th>ชำระเงิน</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$latestOrders): ?>
                    <tr><td colspan="6" style="text-align:center;padding:2rem;color:#9ca3af;">ยังไม่มีออเดอร์</td></tr>
                <?php else: ?>
                    <?php foreach ($latestOrders as $order): ?>
                        <tr>
                            <td><?= date('H:i', strtotime($order['created_at'])); ?></td>
                            <td><?= htmlspecialchars($order['order_code']); ?></td>
                            <td>โต๊ะ <?= htmlspecialchars($order['table_number']); ?></td>
                            <td><?= format_currency((float)$order['total_amount']); ?></td>
                            <td><span class="status-pill <?= $order['order_status']; ?>"><?= strtoupper($order['order_status']); ?></span></td>
                            <td><span class="status-pill <?= $order['payment_status'] === 'paid' ? 'paid' : 'pending'; ?>"><?= strtoupper($order['payment_status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
