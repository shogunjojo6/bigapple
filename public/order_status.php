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
        <section class="glass-card" id="order-detail" data-order-code="<?= htmlspecialchars($order['order_code']); ?>">
            <h2 style="margin-top:0;">รายละเอียดออเดอร์</h2>
            <p><strong>หมายเลข:</strong> <span id="order-code-text">#<?= htmlspecialchars($order['order_code']); ?></span></p>
            <p><strong>โต๊ะ:</strong> <span id="order-table-text"><?= htmlspecialchars($order['table_number']); ?></span></p>
            <p><strong>สถานะการชำระเงิน:</strong> <span id="order-payment-status"><?= strtoupper($order['payment_status']); ?></span></p>
            <p><strong>สถานะออเดอร์:</strong> <span id="order-status-text" data-status="<?= htmlspecialchars($order['order_status']); ?>"><?= $statusLabel[$order['order_status']] ?? strtoupper($order['order_status']); ?></span></p>
            <p id="order-updated-at" style="color:rgba(71,85,105,0.65);font-size:0.9rem;">อัปเดตล่าสุด: <?= date('d/m H:i', strtotime($order['updated_at'] ?? $order['created_at'])); ?></p>
            <hr style="margin:1rem 0;border:0;border-top:1px solid rgba(148,163,184,0.25);">
            <ul id="order-items-list" style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem;">
                <?php foreach ($order['items'] as $item): ?>
                    <li style="display:flex;justify-content:space-between;">
                        <span><?= htmlspecialchars($item['name']); ?> x <?= (int)$item['quantity']; ?></span>
                        <span><?= format_currency((float)$item['subtotal']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p style="margin-top:1rem;font-weight:650;display:flex;justify-content:space-between;">
                <span>ยอดรวม</span>
                <span id="order-total-amount"><?= format_currency((float)$order['total_amount']); ?></span>
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
<?php if ($order): ?>
<script>
(() => {
    const orderDetailEl = document.getElementById('order-detail');
    if (!orderDetailEl) {
        return;
    }
    const STATUS_LABELS = <?= json_encode($statusLabel, JSON_UNESCAPED_UNICODE); ?>;
    const PAYMENT_LABELS = { pending: 'PENDING', paid: 'PAID' };
    const orderCode = orderDetailEl.dataset.orderCode;
    const statusEl = document.getElementById('order-status-text');
    const paymentEl = document.getElementById('order-payment-status');
    const itemsListEl = document.getElementById('order-items-list');
    const totalEl = document.getElementById('order-total-amount');
    const updatedEl = document.getElementById('order-updated-at');
    const tableEl = document.getElementById('order-table-text');
    const codeEl = document.getElementById('order-code-text');
    const currencyFormatter = new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' });

    let pendingTimer = null;
    let controller = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',''':'&#39;'}[char]));
    const renderItems = (items) => {
        if (!Array.isArray(items)) {
            return;
        }
        itemsListEl.innerHTML = items.map(item => {
            const name = escapeHtml(item.name ?? '');
            const qty = item.quantity ?? 0;
            const subtotal = typeof item.subtotal === 'number' ? currencyFormatter.format(item.subtotal) : '-';
            return `<li style="display:flex;justify-content:space-between;"><span>${name} x ${qty}</span><span>${subtotal}</span></li>`;
        }).join('');
    };

    const applyOrder = (order) => {
        if (!order) {
            return;
        }
        if (order.order_code) {
            codeEl.textContent = '#' + order.order_code;
        }
        if (order.table_number) {
            tableEl.textContent = order.table_number;
        }
        if (order.order_status) {
            const text = STATUS_LABELS[order.order_status] || order.order_status.toUpperCase();
            statusEl.textContent = text;
            statusEl.dataset.status = order.order_status;
        }
        if (order.payment_status) {
            const label = PAYMENT_LABELS[order.payment_status] || order.payment_status.toUpperCase();
            paymentEl.textContent = label;
        }
        if (typeof order.total_amount === 'number') {
            totalEl.textContent = currencyFormatter.format(order.total_amount);
        }
        if (order.updated_at) {
            try {
                updatedEl.textContent = 'อัปเดตล่าสุด: ' + new Date(order.updated_at).toLocaleString('th-TH', { hour12: false });
            } catch (error) {
                updatedEl.textContent = 'อัปเดตล่าสุด: ' + order.updated_at;
            }
        }
        renderItems(order.items || []);
    };

    const fetchLatestStatus = () => {
        if (controller) {
            controller.abort();
        }
        controller = new AbortController();
        fetch(`api/order_status.php?order_code=${encodeURIComponent(orderCode)}`, {
            signal: controller.signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('ไม่สามารถโหลดสถานะล่าสุดได้');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success || !data.order) {
                    throw new Error(data.message || 'ไม่สามารถโหลดสถานะล่าสุดได้');
                }
                applyOrder(data.order);
            })
            .catch(error => {
                if (error.name === 'AbortError') {
                    return;
                }
                console.warn(error.message || error);
            });
    };

    const startPolling = () => {
        if (pendingTimer) {
            return;
        }
        fetchLatestStatus();
        pendingTimer = setInterval(fetchLatestStatus, 5000);
    };

    const stopPolling = () => {
        if (pendingTimer) {
            clearInterval(pendingTimer);
            pendingTimer = null;
        }
        if (controller) {
            controller.abort();
            controller = null;
        }
    };

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            stopPolling();
        } else {
            startPolling();
        }
    });

    window.addEventListener('beforeunload', stopPolling);

    startPolling();
})();
</script>
<?php endif; ?>
</body>
</html>
