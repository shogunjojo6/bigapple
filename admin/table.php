<?php
$activeMenu = 'tables';
$tableId = (int)($_GET['table_id'] ?? 0);

require_once __DIR__ . '/header.php';

if ($tableId <= 0) {
    echo '<div class="card"><p>ไม่พบโต๊ะ</p></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$pdo = get_db_connection();
$stmt = $pdo->prepare('SELECT * FROM restaurant_tables WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $tableId]);
$table = $stmt->fetch();

if (!$table) {
    echo '<div class="card"><p>ไม่พบโต๊ะ</p></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$pageTitle = 'โต๊ะ ' . htmlspecialchars($table['table_number']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'close_table') {
    mark_table_orders_closed($tableId);
    close_table_requests($tableId);
    header('Location: table.php?table_id=' . $tableId . '&closed=1');
    exit;
}

$orders = fetch_orders_by_table($tableId);
$openOrders = array_values(array_filter($orders, function ($order) {
    return $order['order_status'] !== 'served' || $order['payment_status'] !== 'paid';
}));
$closedOrders = array_values(array_filter($orders, function ($order) {
    return $order['order_status'] === 'served' && $order['payment_status'] === 'paid';
}));
$requests = fetch_table_requests_by_table($tableId, 'open');
$orderDetailsCache = [];
?>
<div class="admin-header">
    <div>
        <h1>โต๊ะ <?= htmlspecialchars($table['table_number']); ?></h1>
        <p style="color:rgba(71,85,105,0.78);margin:0;">ติดตามออเดอร์และปิดบิลสำหรับโต๊ะนี้</p>
    </div>
    <a class="btn btn-light" href="tables.php">← กลับรายการโต๊ะ</a>
</div>

<?php if (isset($_GET['closed'])): ?>
    <div class="alert alert-success">ปิดบิลเรียบร้อยแล้ว</div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">สถานะโต๊ะ</h2>
    <p>ออเดอร์ที่ยังไม่ปิด: <strong><?= count($openOrders); ?></strong></p>
    <p>การเรียกพนักงาน: <strong><?= count($requests); ?></strong></p>
    <?php if ($openOrders): ?>
        <form method="post" style="margin-top:1rem;">
            <input type="hidden" name="action" value="close_table">
            <button class="btn btn-secondary" type="submit">เช็คบิลแล้ว</button>
        </form>
    <?php endif; ?>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">การเรียกพนักงาน</h2>
    <?php if (!$requests): ?>
        <p style="color:rgba(71,85,105,0.78);">ไม่มีการเรียกพนักงานค้างอยู่</p>
    <?php else: ?>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem;">
            <?php foreach ($requests as $req): ?>
                <li class="glass-card" style="padding:0.85rem;display:flex;justify-content:space-between;">
                    <span><?= $req['request_type'] === 'checkout' ? 'เรียกเช็คบิล' : 'เรียกพนักงาน'; ?> • <?= date('H:i', strtotime($req['created_at'])); ?></span>
                    <span class="status-pill warning">รอดำเนินการ</span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h2 style="margin-top:0;">ออเดอร์ปัจจุบัน</h2>
    <?php if (!$openOrders): ?>
        <p style="color:rgba(71,85,105,0.78);">ไม่มีออเดอร์ที่เปิดอยู่ในขณะนี้</p>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:1rem;">
            <?php foreach ($openOrders as $order): ?>
                <div class="glass-card" style="padding:1.1rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <strong>ออเดอร์ #<?= htmlspecialchars($order['order_code']); ?></strong>
                            <span style="margin-left:0.75rem;color:rgba(71,85,105,0.75);"><?= date('d/m H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div style="display:flex;gap:0.5rem;">
                            <span class="status-pill <?= $order['order_status']; ?>"><?= strtoupper($order['order_status']); ?></span>
                            <span class="status-pill <?= $order['payment_status'] === 'paid' ? 'paid' : 'pending'; ?>"><?= strtoupper($order['payment_status']); ?></span>
                        </div>
                    </div>
                    <?php
                    if (!isset($orderDetailsCache[$order['id']])) {
                        $orderDetailsCache[$order['id']] = fetch_order_details($order['id']);
                    }
                    $details = $orderDetailsCache[$order['id']];
                    ?>
                    <?php if ($details): ?>
                        <ul style="list-style:none;padding:0;margin:0.75rem 0 0;display:flex;flex-direction:column;gap:0.4rem;color:rgba(71,85,105,0.9);">
                            <?php foreach ($details['items'] as $item): ?>
                                <li><?= htmlspecialchars($item['name']); ?> x <?= (int)$item['quantity']; ?> — <?= format_currency((float)$item['subtotal']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.75rem;">
                        <strong><?= format_currency((float)$order['total_amount']); ?></strong>
                        <a class="btn btn-light" href="receipt.php?order_id=<?= $order['id']; ?>" target="_blank">ดูใบเสร็จ</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($closedOrders)): ?>
<div class="card" style="margin-top:1.5rem;">
    <h2 style="margin-top:0;">ออเดอร์ที่ปิดแล้ว</h2>
    <div style="display:flex;flex-direction:column;gap:1rem;">
        <?php foreach ($closedOrders as $order): ?>
            <div class="glass-card" style="padding:1.1rem;background:rgba(248,250,252,0.95);">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <strong>#<?= htmlspecialchars($order['order_code']); ?></strong>
                        <span style="margin-left:0.75rem;color:rgba(71,85,105,0.65);"><?= date('d/m H:i', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div style="display:flex;gap:0.5rem;">
                        <span class="status-pill served">SERVED</span>
                        <span class="status-pill paid">PAID</span>
                    </div>
                </div>
                <?php
                if (!isset($orderDetailsCache[$order['id']])) {
                    $orderDetailsCache[$order['id']] = fetch_order_details($order['id']);
                }
                $details = $orderDetailsCache[$order['id']];
                ?>
                <?php if ($details): ?>
                    <ul style="list-style:none;padding:0;margin:0.75rem 0 0;display:flex;flex-direction:column;gap:0.4rem;color:rgba(71,85,105,0.8);">
                        <?php foreach ($details['items'] as $item): ?>
                            <li><?= htmlspecialchars($item['name']); ?> x <?= (int)$item['quantity']; ?> — <?= format_currency((float)$item['subtotal']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.75rem;">
                    <strong><?= format_currency((float)$order['total_amount']); ?></strong>
                    <a class="btn btn-light" href="receipt.php?order_id=<?= $order['id']; ?>" target="_blank">ดูใบเสร็จ</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
