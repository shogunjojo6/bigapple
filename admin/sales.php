<?php
$pageTitle = 'รายงานยอดขาย';
$activeMenu = 'sales';
require_once __DIR__ . '/header.php';

$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');

$sales = calculate_sales_summary($start, $end);
$totalAmount = array_sum(array_column($sales, 'total_sales'));
$totalOrders = array_sum(array_column($sales, 'order_count'));
?>
<div class="admin-header">
    <div>
        <h1>รายงานยอดขาย</h1>
        <p style="color:rgba(71,85,105,0.78);margin:0;">ดูยอดขายตามช่วงเวลา</p>
    </div>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <form method="get" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label for="start">วันที่เริ่มต้น</label>
            <input class="form-control" type="date" id="start" name="start" value="<?= htmlspecialchars($start); ?>">
        </div>
        <div>
            <label for="end">วันที่สิ้นสุด</label>
            <input class="form-control" type="date" id="end" name="end" value="<?= htmlspecialchars($end); ?>">
        </div>
        <button class="btn btn-primary" type="submit">แสดงรายงาน</button>
    </form>
</div>

<section class="metric-grid">
    <div class='card metric-card'>
        <p style='margin:0;color:rgba(71,85,105,0.72);'>ยอดขายรวม</p>
        <h2><?= format_currency((float)$totalAmount); ?></h2>
    </div>
    <div class='card metric-card'>
        <p style='margin:0;color:rgba(71,85,105,0.72);'>จำนวนออเดอร์ที่ชำระแล้ว</p>
        <h2><?= (int)$totalOrders; ?></h2>
    </div>
</section>

<div class="card" style="margin-top:2rem;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>วันที่</th>
                <th>จำนวนออเดอร์</th>
                <th>ยอดขาย</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$sales): ?>
                <tr><td colspan="3" style="text-align:center;padding:2rem;color:#9ca3af;">ยังไม่มีข้อมูลการชำระในช่วงเวลานี้</td></tr>
            <?php else: ?>
                <?php foreach ($sales as $row): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['order_date'])); ?></td>
                        <td><?= (int)$row['order_count']; ?></td>
                        <td><?= format_currency((float)$row['total_sales']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
