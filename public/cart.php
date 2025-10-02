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
        case 'update_item':
            $menuId = (int)($_POST['menu_id'] ?? 0);
            $quantity = max(0, (int)($_POST['quantity'] ?? 0));
            if ($menuId <= 0) {
                header('Content-Type: application/json', true, 400);
                echo json_encode(['success' => false, 'message' => 'ไม่พบเมนูที่ต้องการปรับ']);
                exit;
            }
            update_cart_item($menuId, $quantity);
            $cartSnapshot = get_cart();
            $updatedItem = null;
            foreach ($cartSnapshot as $cartItem) {
                if ((int)$cartItem['menu_id'] === $menuId) {
                    $updatedItem = $cartItem;
                    break;
                }
            }
            $cartTotal = calculate_cart_total();
            $cartCount = cart_item_count();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'count' => $cartCount,
                'total' => $cartTotal,
                'total_formatted' => format_currency($cartTotal),
                'removed' => $updatedItem === null,
                'item' => $updatedItem ? [
                    'menu_id' => (int)$updatedItem['menu_id'],
                    'quantity' => (int)$updatedItem['quantity'],
                    'subtotal' => (float)$updatedItem['subtotal'],
                    'subtotal_formatted' => format_currency((float)$updatedItem['subtotal']),
                ] : null,
            ]);
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
    if ($action === 'update_item') {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถจัดการตะกร้าได้ กรุณาลองใหม่อีกครั้ง']);
        exit;
    }
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
        <form method="post" action="cart.php?action=update" data-cart-form>
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
                    <tr data-cart-row data-menu-id="<?= $item['menu_id']; ?>" data-unit-price="<?= (float)$item['unit_price']; ?>" data-current-qty="<?= (int)$item['quantity']; ?>">
                        <td data-label="เมนู"><?= htmlspecialchars($item['name']); ?></td>
                        <td data-label="ราคา" data-unit-price-cell data-unit-price="<?= (float)$item['unit_price']; ?>"><?= format_currency((float)$item['unit_price']); ?></td>
                        <td data-label="จำนวน">
                            <div class="qty-control" data-qty-control>
                                <button type="button" class="qty-minus" aria-label="ลดจำนวน">−</button>
                                <span class="qty-display" data-cart-qty-display><?= $item['quantity']; ?></span>
                                <input type="hidden" name="quantities[<?= $item['menu_id']; ?>]" value="<?= $item['quantity']; ?>" data-cart-qty-hidden>
                                <button type="button" class="qty-plus" aria-label="เพิ่มจำนวน">+</button>
                            </div>
                        </td>
                        <td data-label="รวม"><span data-cart-subtotal><?= format_currency((float)$item['subtotal']); ?></span></td>
                        <td data-label="" class="cart-row-actions">
                            <a class="btn btn-light" href="cart.php?action=remove&menu_id=<?= $item['menu_id']; ?>">ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:1.75rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;gap:0.65rem;flex-wrap:wrap;">
                    <a class="btn btn-outline" href="cart.php?action=clear">ล้างตะกร้า</a>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0;font-size:1.2rem;font-weight:650;">ยอดสุทธิ: <span id="cart-total-value" data-cart-total><?= format_currency($total); ?></span></p>
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

