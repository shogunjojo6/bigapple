<?php
require_once __DIR__ . '/bootstrap.php';

$categories = fetch_categories();
$cartCount = cart_item_count();
$success = isset($_GET['added']) ? 'เพิ่มเมนูลงตะกร้าเรียบร้อยแล้ว' : null;
$error = isset($_GET['error']) ? 'ไม่สามารถเพิ่มเมนูได้' : null;
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
