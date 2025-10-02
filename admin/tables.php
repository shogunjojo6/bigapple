<?php
$pageTitle = 'โต๊ะลูกค้า';
$activeMenu = 'tables';
require_once __DIR__ . '/header.php';

$tables = fetch_table_overview();
?>
<div class="admin-header">
    <div>
        <h1>ภาพรวมโต๊ะ</h1>
        <p style="color:rgba(71,85,105,0.78);margin:0;">ตรวจสอบสถานะโต๊ะ การเรียกพนักงาน และออเดอร์ที่ค้างอยู่</p>
    </div>
</div>

<div class="card" style="padding:1.5rem;">
    <div style="display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
        <?php foreach ($tables as $table): ?>
            <?php
                $occupied = $table['open_orders'] > 0;
                $statusLabel = $occupied ? 'มีลูกค้า' : 'ว่าง';
                $statusClass = $occupied ? 'pending' : 'ready';
            ?>
            <div class="glass-card" style="padding:1.25rem;display:flex;flex-direction:column;gap:0.75rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <h2 style="margin:0;">โต๊ะ <?= htmlspecialchars($table['table_number']); ?></h2>
                    <span class="status-pill <?= $statusClass; ?>"><?= $statusLabel; ?></span>
                </div>
                <p style="margin:0;color:rgba(71,85,105,0.78);">ออเดอร์ที่ยังไม่ปิด: <strong><?= (int)$table['open_orders']; ?></strong></p>
                <?php if ((int)$table['open_requests'] > 0): ?>
                    <div class="alert alert-warning" style="margin:0;">มีการเรียกพนักงาน</div>
                <?php endif; ?>
                <div style="margin-top:auto;">
                    <a class="btn btn-primary" href="table.php?table_id=<?= $table['id']; ?>">ดูรายละเอียดโต๊ะ</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
