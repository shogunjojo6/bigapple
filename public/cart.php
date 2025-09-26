<?php
require_once __DIR__ . '/bootstrap.php';

$action = $_REQUEST['action'] ?? 'view';
$message = null;
$error = null;

try {
    switch ($action) {
        case 'add':
            $menuId = (int)($_POST['menu_id'] ?? 0);
            $quantity = max(1, (int)($_POST['quantity'] ?? 1));
            add_to_cart($menuId, $quantity);
            header('Location: index.php?added=1');
            exit;
        case 'update':
            foreach ($_POST['quantities'] ?? [] as $menuId => $qty) {
                update_cart_item((int)$menuId, max(0, (int)$qty));
            }
            $message = 'อัพเดทตะกร้าเรียบร้อยแล้ว';
            break;
        case 'remove':
            $menuId = (int)($_GET['menu_id'] ?? 0);
            remove_cart_item($menuId);
            $message = 'ลบเมนูออกจากตะกร้าแล้ว';
            break;
        case 'clear':
            clear_cart();
            $message = 'ล้างตะกร้าเรียบร้อยแล้ว';
            break;
        default:
            break;
    }
} catch (Throwable $e) {
    $error = 'ไม่สามารถจัดการตะกร้าได้ กรุณาลองใหม่อีกครั้ง';
}

$cart = get_cart();
$total = calculate_cart_total();
$cartCount = cart_item_count();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตะกร้าออเดอร์ | Big Apple</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<header>
    <div class="container">
        <div class="hero">
            <div>
                <h1>ตะกร้าออเดอร์</h1>
                <p>ตรวจสอบรายการอาหารก่อนกดยืนยันและเลือกวิธีชำระเงิน</p>
            </div>
            <div class="hero-actions">
                <a class="btn btn-light" href="index.php">← กลับไปเลือกเมนู</a>
                <a class="btn btn-secondary" href="../admin/index.php">Admin</a>
            </div>
        </div>
    </div>
</header>

<main class="container stack-xl" style="padding:3rem 0 4rem;">
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($cartCount === 0): ?>
        <section class="glass-card" style="text-align:center;">
            <p style="font-size:1.15rem;font-weight:600;margin-bottom:0.75rem;">ตะกร้ายังว่างอยู่</p>
            <a class="btn btn-primary" href="index.php">เลือกเมนูเพิ่ม</a>
        </section>
    <?php else: ?>
        <form method="post" action="cart.php?action=update">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>เมนู</th>
                        <th style="width:120px;">ราคา</th>
                        <th style="width:120px;">จำนวน</th>
                        <th style="width:120px;">รวม</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cart as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']); ?></td>
                        <td><?= format_currency((float)$item['unit_price']); ?></td>
                        <td>
                            <div class="qty-control" data-qty-control>
                                <button type="button" class="qty-minus" aria-label="ลดจำนวน">−</button>
                                <input class="qty-input" type="number" inputmode="numeric" name="quantities[<?= $item['menu_id']; ?>]" value="<?= $item['quantity']; ?>" min="0">
                                <button type="button" class="qty-plus" aria-label="เพิ่มจำนวน">+</button>
                            </div>
                        </td>
                        <td><?= format_currency((float)$item['subtotal']); ?></td>
                        <td>
                            <a class="btn btn-light" href="cart.php?action=remove&menu_id=<?= $item['menu_id']; ?>">ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:1.75rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;gap:0.65rem;flex-wrap:wrap;">
                    <button class="btn btn-light" type="submit">อัพเดทตะกร้า</button>
                    <a class="btn btn-outline" href="cart.php?action=clear">ล้างตะกร้า</a>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0;font-size:1.2rem;font-weight:650;">ยอดสุทธิ: <?= format_currency($total); ?></p>
                    <div style="margin-top:0.85rem;display:flex;gap:0.65rem;flex-wrap:wrap;justify-content:flex-end;">
                        <a class="btn btn-outline" href="index.php">เลือกเมนูเพิ่ม</a>
                        <a class="btn btn-primary" href="checkout.php">ไปขั้นตอนยืนยันออเดอร์</a>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</main>
<footer>
    <p>© <?= date('Y'); ?> Big Apple Restaurant</p>
</footer>
<script src="../assets/js/main.js"></script>
</body>
</html>
