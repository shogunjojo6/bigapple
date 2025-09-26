<?php
require_once __DIR__ . '/bootstrap.php';

if (is_admin_authenticated()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (admin_login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'เข้าสู่ระบบไม่สำเร็จ กรุณาตรวจสอบข้อมูลอีกครั้ง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ | Big Apple Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h1>🍏 Big Apple Admin</h1>
        <p style="margin-top:0.35rem;color:rgba(226,232,240,0.75);">เข้าสู่ระบบเพื่อจัดการออเดอร์</p>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="background:rgba(220,38,38,0.15);color:#fca5a5;">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="post" style="margin-top:1.5rem;display:flex;flex-direction:column;gap:1rem;">
            <div>
                <label for="username">ชื่อผู้ใช้</label>
                <input class="form-control" type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="password">รหัสผ่าน</label>
                <input class="form-control" type="password" id="password" name="password" required>
            </div>
            <button class="btn btn-primary" type="submit">เข้าสู่ระบบ</button>
        </form>
        <p style="margin-top:1.5rem;color:#6b7280;font-size:0.85rem;">ตัวอย่างเข้าสู่ระบบ: admin / 12345</p>
        <div style="margin-top:1.5rem;">
            <a href="../public/index.php" class="auth-footer-link">← กลับสู่หน้าลูกค้า</a>
        </div>
    </div>
</div>
</body>
</html>
