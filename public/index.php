<?php
require_once __DIR__ . '/bootstrap.php';

$categories = fetch_categories();
$cartCount = cart_item_count();
$success = isset($_GET['added']) ? 'เพิ่มเมนูลงตะกร้าเรียบร้อยแล้ว' : null;
$error = isset($_GET['error']) ? 'ไม่สามารถเพิ่มเมนูได้' : null;
$activeTable = get_active_table();
$lastOrderCode = $_SESSION['last_order_code'] ?? null;

$orderStatusLabels = [
    'pending' => 'รอรับออเดอร์',
    'preparing' => 'กำลังทำอาหาร',
    'ready' => 'พร้อมเสิร์ฟ',
    'served' => 'เสิร์ฟแล้ว',
];

$paymentStatusLabels = [
    'pending' => 'รอชำระเงิน',
    'paid' => 'ชำระแล้ว',
];

$tableOrders = [];
$openOrders = [];
if ($activeTable) {
    $tableOrders = fetch_orders_by_table((int)$activeTable['id']);
    $openOrders = array_values(array_filter($tableOrders, function ($order) {
        return $order['payment_status'] !== 'paid' || $order['order_status'] !== 'served';
    }));
}

$displayOrders = !empty($openOrders) ? array_slice($openOrders, 0, 5) : [];
$hasAdditionalOrders = count($openOrders) > count($displayOrders);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Big Apple Restaurant | สั่งอาหารผ่าน QR</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<header>
    <div class="container">
        <div class="hero">
            <div>
                <h1>🍏 Big Apple Restaurant</h1>
                <p>สั่งเมนูยุโรป + ไทย ผ่าน QR แบบไร้สัมผัส</p>
            </div>
            <div class="hero-actions">
                <a class="btn btn-light" href="cart.php">ตะกร้า (<span id="cart-count"><?= $cartCount; ?></span>)</a>
                <a class="btn btn-secondary" href="../admin/index.php">Admin</a>
            </div>
        </div>
    </div>
</header>
<a href="cart.php" class="floating-cart-button" aria-label="ดูตะกร้า" aria-hidden="true">
    <span class="count-bubble" id="cart-count-floating"><?= $cartCount; ?></span>
    <span>🛒 ตะกร้า</span>
</a>

<?php if ($activeTable): ?>
<div class="table-banner">
    <div>
        <span class="table-chip">🪑 โต๊ะ <?= htmlspecialchars($activeTable['table_number']); ?></span>
        <?php if (!empty($lastOrderCode)): ?>
        <span class="last-order">เลขออเดอร์ล่าสุด #<?= htmlspecialchars($lastOrderCode); ?></span>
        <?php endif; ?>
    </div>
    <div class="table-banner-actions">
        <a class="btn btn-outline" href="order_status.php<?php if (!empty($lastOrderCode)): ?>?order_code=<?= urlencode($lastOrderCode); ?><?php endif; ?>">ติดตามสถานะ</a>
        <button type="button" class="btn btn-secondary" id="call-staff-btn">เรียกพนักงานเช็คบิล</button>
    </div>
</div>
<?php else: ?>
<div class="table-banner warning">
    <span>ไม่พบหมายเลขโต๊ะ กรุณาสแกน QR หรือแจ้งพนักงานเพื่อเปิดโต๊ะ</span>
</div>
<?php endif; ?>
<?php if ($activeTable && !empty($displayOrders)): ?>
<section class="container table-orders-section fade-in">
    <div class="surface-card table-orders-card">
        <div class="table-orders-header">
            <div>
                <h2 style="margin:0;">ออเดอร์ของโต๊ะนี้</h2>
                <p class="table-orders-subtitle">กดเลือกหมายเลขเพื่อดูรายละเอียดและติดตามสถานะ</p>
            </div>
            <span class="badge">ทั้งหมด <?= count($openOrders); ?> ออเดอร์</span>
        </div>
        <div class="table-orders-list">
            <?php foreach ($displayOrders as $order): ?>
                <?php
                    $orderStatusKey = $order['order_status'];
                    $paymentStatusKey = $order['payment_status'];
                    $orderStatusText = $orderStatusLabels[$orderStatusKey] ?? strtoupper($orderStatusKey);
                    $paymentStatusText = $paymentStatusLabels[$paymentStatusKey] ?? strtoupper($paymentStatusKey);
                    $paymentClass = $paymentStatusKey === 'paid' ? 'paid' : 'pending-payment';
                ?>
                <a class="table-order-item" href="order_status.php?order_code=<?= urlencode($order['order_code']); ?>">
                    <div class="table-order-item__top">
                        <strong>#<?= htmlspecialchars($order['order_code']); ?></strong>
                        <span class="status-pill <?= htmlspecialchars($orderStatusKey); ?>"><?= htmlspecialchars($orderStatusText); ?></span>
                    </div>
                    <p class="table-order-item__time">สั่งเมื่อ <?= date('d/m H:i', strtotime($order['created_at'])); ?></p>
                    <div class="table-order-item__status">
                        <span class="status-pill <?= htmlspecialchars($paymentClass); ?>"><?= htmlspecialchars($paymentStatusText); ?></span>
                        <span class="table-order-item__chevron">ดูสถานะ →</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($hasAdditionalOrders): ?>
            <p class="table-orders-note">แสดงเฉพาะ 5 ออเดอร์ล่าสุด หากต้องการดูเพิ่มเติมโปรดแจ้งพนักงาน</p>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<main class="container stack-xl" style="padding:3rem 0 4rem;">
    <section class="surface-card fade-in">
        <div class="section-stack">
            <div class="section-title">
                <h2>เมนูแนะนำ</h2>
                <span class="badge">Contactless Ordering</span>
            </div>
            <p class="lead-text">เลือกหมวดหมู่แล้วเพิ่มเมนูลงตะกร้า กดยืนยันออเดอร์ผ่าน PromptPay หรือจ่ายสดได้ทันที</p>
            <form action="cart.php" method="get" style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                <input type="hidden" name="action" value="view">
                <button type="submit" class="btn btn-primary">ดูตะกร้า / ยืนยันออเดอร์</button>
                <a href="#categories" class="btn btn-light">เลื่อนลงดูหมวดอาหาร</a>
            </form>
            <div class="category-nav">
                <?php foreach ($categories as $category): ?>
                    <a href="#category-<?= $category['id']; ?>" class="category-chip">
                        <strong><?= htmlspecialchars($category['name']); ?></strong>
                        <p style="margin:0.35rem 0 0;color:rgba(71,85,105,0.7);font-size:0.85rem;">
                            <?= htmlspecialchars($category['description'] ?? ''); ?>
                        </p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if ($success): ?>
        <div class="floating-alert" data-auto-hide data-variant="success" role="status"><?= htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="floating-alert" data-auto-hide data-variant="error" role="alert"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div id="categories" class="stack-xl">
        <?php foreach ($categories as $category): ?>
            <?php $menus = fetch_menus_by_category((int)$category['id']); ?>
            <section id="category-<?= $category['id']; ?>">
                <div class="section-title">
                    <h2><?= htmlspecialchars($category['name']); ?></h2>
                    <span class="badge"><?= count($menus); ?> เมนู</span>
                </div>
                <p class="lead-text" style="max-width:640px;">
                    <?= htmlspecialchars($category['description'] ?? ''); ?>
                </p>
                <div class="menu-grid">
                    <?php foreach ($menus as $menu): ?>
                        <article class="menu-card">
                            <img src="<?= htmlspecialchars($menu['image_path'] ?: '../assets/images/placeholder.svg'); ?>" alt="<?= htmlspecialchars($menu['name']); ?>">
                            <div class="content">
                                <div class="meta">
                                    <h3 style="margin:0;"><?= htmlspecialchars($menu['name']); ?></h3>
                                    <strong><?= format_currency((float)$menu['price']); ?></strong>
                                </div>
                                <p style="color:rgba(71,85,105,0.78);margin:0.35rem 0 0.55rem;">
                                    <?= htmlspecialchars($menu['description'] ?? ''); ?>
                                </p>
                                <form class="add-to-cart-form" data-menu-id="<?= $menu['id']; ?>" data-menu-name="<?= htmlspecialchars($menu['name'], ENT_QUOTES); ?>" method="post" action="cart.php">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="menu_id" value="<?= $menu['id']; ?>">
                                    <div class="quantity-field">
                                        <div class="qty-control" data-qty-control>
                                            <button type="button" class="qty-minus" aria-label="ลดจำนวน">−</button>
                                            <input type="number" name="quantity" value="1" min="1" class="qty-input" aria-label="จำนวน" inputmode="numeric">
                                            <button type="button" class="qty-plus" aria-label="เพิ่มจำนวน">+</button>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">เพิ่มลงตะกร้า</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</main>

<footer>
    <p>© <?= date('Y'); ?> Big Apple Restaurant • Scan & Order Experience</p>
</footer>
<script src="../assets/js/main.js"></script>
</body>
</html>
