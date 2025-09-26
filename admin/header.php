<?php
require_once __DIR__ . '/bootstrap.php';
require_admin_auth();
$pageTitle = $pageTitle ?? 'Big Apple Admin';
$activeMenu = $activeMenu ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle); ?> | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <h2 style="margin:0;">🍏 Big Apple</h2>
        <p style="color:rgba(203,213,225,0.82);margin:0;">สวัสดี <?= htmlspecialchars($_SESSION['admin_username'] ?? ''); ?></p>
        <nav>
            <a href="dashboard.php" class="<?= $activeMenu === 'dashboard' ? 'active' : ''; ?>">แดชบอร์ด</a>
            <a href="orders.php" class="<?= $activeMenu === 'orders' ? 'active' : ''; ?>">ออเดอร์</a>
            <a href="menu.php" class="<?= $activeMenu === 'menu' ? 'active' : ''; ?>">จัดการเมนู</a>
            <a href="sales.php" class="<?= $activeMenu === 'sales' ? 'active' : ''; ?>">รายงานยอดขาย</a>
            <a href="../public/index.php" target="_blank">ดูหน้าลูกค้า</a>
        </nav>
        <div style="margin-top:auto;">
            <a href="logout.php" class="btn btn-light" style="width:100%;text-align:center;">ออกจากระบบ</a>
        </div>
    </aside>
    <main class="admin-content">
