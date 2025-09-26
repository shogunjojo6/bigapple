<?php
$pageTitle = 'จัดการเมนู';
$activeMenu = 'menu';
require_once __DIR__ . '/header.php';

$action = $_GET['action'] ?? 'list';
$message = null;
$error = null;
$categories = fetch_categories(false);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $menuId = (int)($_POST['id'] ?? 0);
    $defaultImage = '../assets/images/placeholder.svg';
    $existingMenu = $menuId > 0 ? fetch_menu_item($menuId) : null;

    $imagePathInput = trim($_POST['image_path'] ?? '');
    $imagePath = $imagePathInput !== '' ? $imagePathInput : ($existingMenu['image_path'] ?? '');
    $uploadError = null;

    if (!empty($_FILES['image_file']['name'] ?? '')) {
        if (($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            $uploadedName = $_FILES['image_file']['name'] ?? '';
            $extension = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions, true)) {
                $uploadError = 'อนุญาตเฉพาะไฟล์ JPG, PNG, GIF, SVG หรือ WEBP เท่านั้น';
            } elseif (($_FILES['image_file']['size'] ?? 0) > $maxSize) {
                $uploadError = 'ไฟล์รูปภาพต้องมีขนาดไม่เกิน 2MB';
            } else {
                $uploadDir = dirname(__DIR__) . '/assets/images/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $uniqueName = str_replace('.', '', uniqid('menu_', true));
                $fileName = $uniqueName . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                $tmpName = $_FILES['image_file']['tmp_name'] ?? null;
                if ($tmpName && move_uploaded_file($tmpName, $targetPath)) {
                    $imagePath = '../assets/images/uploads/' . $fileName;
                } else {
                    $uploadError = 'ไม่สามารถอัพโหลดไฟล์รูปภาพได้ กรุณาลองใหม่';
                }
            }
        } else {
            $uploadError = 'เกิดข้อผิดพลาดระหว่างอัพโหลดไฟล์';
        }
    }

    if ($imagePath === '') {
        $imagePath = $defaultImage;
    }

    $data = [
        'category_id' => (int)$_POST['category_id'],
        'name' => trim($_POST['name']),
        'description' => trim($_POST['description']),
        'price' => (float)$_POST['price'],
        'image_path' => $imagePath,
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($uploadError !== null) {
        $error = $uploadError;
        $action = $menuId > 0 ? 'edit' : 'create';
        $currentMenu = array_merge($existingMenu ?? [], $data, ['id' => $menuId]);
    } else {
        try {
            if ($menuId > 0) {
                update_menu($menuId, $data);
                $message = 'อัพเดทเมนูเรียบร้อย';
            } else {
                create_menu($data);
                $message = 'เพิ่มเมนูใหม่สำเร็จ';
            }
            $action = 'list';
        } catch (Throwable $e) {
            $error = 'ไม่สามารถบันทึกข้อมูลเมนูได้';
            $action = $menuId > 0 ? 'edit' : 'create';
        }
    }
}

if ($action === 'delete') {
    $menuId = (int)($_GET['id'] ?? 0);
    if ($menuId > 0) {
        try {
            delete_menu($menuId);
            $message = 'ลบเมนูเรียบร้อย';
        } catch (Throwable $e) {
            $error = 'ไม่สามารถลบเมนูได้';
        }
    }
    $action = 'list';
}

if ($action === 'edit') {
    $menuId = (int)($_GET['id'] ?? 0);
    $currentMenu = $menuId ? fetch_menu_item($menuId) : null;
    if (!$currentMenu) {
        $error = 'ไม่พบเมนูที่ต้องการแก้ไข';
        $action = 'list';
    }
}

$search = trim($_GET['q'] ?? '');
$categoryFilter = $_GET['category'] ?? 'all';

$menusRaw = fetch_full_menu($search, true);
$menus = [];
foreach ($menusRaw as $menu) {
    if ($categoryFilter !== 'all' && (string)$menu['category_id'] !== (string)$categoryFilter) {
        continue;
    }
    $menus[] = $menu;
}

$groupedMenus = [];
$categoryColorMap = [];
$palette = ['#d97706','#2563eb','#059669','#7c3aed','#f97316','#0ea5e9','#f43f5e','#14b8a6'];
$paletteIndex = 0;
foreach ($menus as $menu) {
    $groupId = (int)$menu['category_id'];
    if (!isset($groupedMenus[$groupId])) {
        $groupedMenus[$groupId] = [
            'category_name' => $menu['category_name'],
            'items' => [],
        ];
        $categoryColorMap[$groupId] = $palette[$paletteIndex % count($palette)];
        $paletteIndex++;
    }
    $groupedMenus[$groupId]['items'][] = $menu;
}
$totalMatches = count($menus);
?>
<div class="admin-header">
    <div>
        <h1>จัดการเมนูอาหาร</h1>
        <p style="color:rgba(71,85,105,0.78);margin:0;">เพิ่ม / แก้ไข / ลบ เมนูแต่ละหมวดหมู่</p>
    </div>
    <a class="btn btn-primary" href="menu.php?action=create">+ เพิ่มเมนูใหม่</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem;">
    <form method="get" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;">
        <input type="hidden" name="action" value="list">
        <div style="flex:1 1 220px; position:relative;">
            <label for="search">ค้นหาเมนู/หมวดหมู่</label>
            <input class="form-control" type="text" id="search" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="เช่น ต้มยำ, Breakfast" autocomplete="off">
            <div id="search-suggestions" class="suggestion-panel" hidden></div>
        </div>
        <div style="flex:1 1 200px;">
            <label for="category">หมวดหมู่</label>
            <select class="form-control" id="category" name="category">
                <option value="all" <?= $categoryFilter === 'all' ? 'selected' : ''; ?>>ทุกหมวดหมู่</option>
                <?php foreach ($categories as $category): ?>
                    <?php $label = $category['name'] . ($category['is_active'] ? '' : ' (ปิด)'); ?>
                    <option value="<?= $category['id']; ?>" <?= (string)$category['id'] === (string)$categoryFilter ? 'selected' : ''; ?>><?= htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;gap:0.5rem;">
            <button class="btn btn-primary" type="submit">ค้นหา</button>
            <?php if ($search !== '' || $categoryFilter !== 'all'): ?>
                <a class="btn btn-light" href="menu.php">รีเซ็ต</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (in_array($action, ['create', 'edit'], true)): ?>
    <?php $menuData = $currentMenu ?? ['id' => 0, 'category_id' => $categories[0]['id'] ?? 0, 'name' => '', 'description' => '', 'price' => 0, 'image_path' => '../assets/images/placeholder.svg', 'is_featured' => 0, 'is_active' => 1]; ?>
    <div class="card" style="margin-bottom:2rem;">
        <h2 style="margin-top:0;"><?= $action === 'create' ? 'เพิ่มเมนูใหม่' : 'แก้ไขเมนู'; ?></h2>
        <form method="post" enctype="multipart/form-data" style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $menuData['id'] ?? 0; ?>">
            <div>
                <label for="name">ชื่อเมนู</label>
                <input class="form-control" type="text" id="name" name="name" value="<?= htmlspecialchars($menuData['name']); ?>" required>
            </div>
            <div>
                <label for="category_id">หมวดหมู่</label>
                <select class="form-control" id="category_id" name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id']; ?>" <?= (int)$menuData['category_id'] === (int)$category['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="price">ราคา (บาท)</label>
                <input class="form-control" type="number" step="0.01" id="price" name="price" value="<?= htmlspecialchars($menuData['price']); ?>" required>
            </div>
            <div>
                <label for="image_file">อัพโหลดรูปภาพเมนู</label>
                <input class="form-control" type="file" id="image_file" name="image_file" accept="image/png,image/jpeg,image/jpg,image/webp,image/gif,image/svg+xml">
                <small style="color:#6b7280;display:block;margin-top:0.35rem;">รองรับไฟล์ JPG, PNG, GIF, SVG หรือ WEBP ไม่เกิน 2MB</small>
            </div>
            <div>
                <label for="image_path">หรือใส่ลิงก์รูปภาพ (URL หรือ Path)</label>
                <input class="form-control" type="text" id="image_path" name="image_path" value="<?= htmlspecialchars($menuData['image_path']); ?>">
            </div>
            <div style="grid-column:1 / -1;">
                <label for="description">รายละเอียดเมนู</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($menuData['description']); ?></textarea>
            </div>
            <div>
                <label><input type="checkbox" name="is_featured" <?= !empty($menuData['is_featured']) ? 'checked' : ''; ?>> เมนูแนะนำ</label>
            </div>
            <div>
                <label><input type="checkbox" name="is_active" <?= !empty($menuData['is_active']) ? 'checked' : ''; ?>> แสดงในเมนูลูกค้า</label>
            </div>
            <div style="grid-column:1 / -1;display:flex;gap:0.5rem;">
                <button class="btn btn-primary" type="submit">บันทึกเมนู</button>
                <a class="btn btn-light" href="menu.php">ยกเลิก</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<div id="menu-results">
    <?php include __DIR__ . '/partials/menu_list.php'; ?>
</div>


<script>
(function() {
    const input = document.getElementById('search');
    const panel = document.getElementById('search-suggestions');
    const results = document.getElementById('menu-results');
    const categorySelect = document.getElementById('category');
    if (!input || !panel || !results) {
        return;
    }
    let suggestionsTimer = null;
    let searchTimer = null;

    function hidePanel() {
        panel.innerHTML = '';
        panel.hidden = true;
    }

    function renderSuggestions(items) {
        panel.innerHTML = '';
        if (!items.length) {
            panel.hidden = true;
            return;
        }
        const fragment = document.createDocumentFragment();
        items.forEach(item => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'suggestion-item';
            button.dataset.name = item.name;
            button.dataset.id = item.id;

            const textWrap = document.createElement('span');
            const title = document.createElement('strong');
            title.textContent = item.name;
            const subtitle = document.createElement('small');
            subtitle.textContent = item.category_name;
            textWrap.appendChild(title);
            textWrap.appendChild(subtitle);

            const price = document.createElement('em');
            try {
                price.textContent = new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(item.price);
            } catch (_) {
                price.textContent = item.price;
            }

            button.appendChild(textWrap);
            button.appendChild(price);
            fragment.appendChild(button);
        });
        panel.appendChild(fragment);
        panel.hidden = false;
    }

    function performSearch() {
        const params = new URLSearchParams({
            q: input.value.trim(),
            category: categorySelect ? categorySelect.value : 'all'
        });
        fetch('menu_search.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(resp => resp.ok ? resp.text() : Promise.reject())
            .then(html => {
                results.innerHTML = html;
            })
            .catch(() => {
                results.innerHTML = '<div class="card"><p style="text-align:center;padding:2rem;color:#9ca3af;">ไม่สามารถแสดงผลการค้นหาได้</p></div>';
            });
    }

    function scheduleSearch() {
        if (searchTimer) {
            clearTimeout(searchTimer);
        }
        searchTimer = setTimeout(performSearch, 220);
    }

    input.addEventListener('input', () => {
        const term = input.value.trim();
        if (suggestionsTimer) {
            clearTimeout(suggestionsTimer);
        }
        if (term.length < 2) {
            hidePanel();
        } else {
            suggestionsTimer = setTimeout(() => {
                fetch('menu_suggest.php?term=' + encodeURIComponent(term), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(resp => resp.ok ? resp.json() : { items: [] })
                    .then(data => renderSuggestions((data && data.items) || []))
                    .catch(() => hidePanel());
            }, 180);
        }
        scheduleSearch();
    });

    if (categorySelect) {
        categorySelect.addEventListener('change', scheduleSearch);
    }

    panel.addEventListener('click', event => {
        const target = event.target.closest('.suggestion-item');
        if (!target) {
            return;
        }
        input.value = target.dataset.name || '';
        hidePanel();
        performSearch();
    });

    document.addEventListener('click', event => {
        if (!panel.contains(event.target) && event.target !== input) {
            hidePanel();
        }
    });
})();
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
