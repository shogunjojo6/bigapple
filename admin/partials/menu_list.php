<?php if (!isset($totalMatches, $groupedMenus, $categoryColorMap)) { die('Menu context missing'); } ?>
<div class="card">
    <?php if ($totalMatches === 0): ?>
        <p style="text-align:center;padding:2rem;color:#9ca3af;">ไม่พบเมนูตามเงื่อนไขที่ค้นหา</p>
    <?php else: ?>
        <?php foreach ($groupedMenus as $groupId => $group): ?>
            <?php if (empty($group['items'])) { continue; } ?>
            <?php $sectionColor = $categoryColorMap[$groupId] ?? '#d97706'; ?>
            <section style="margin-bottom:1.5rem;">
                <h2 class="category-heading" style="--category-color: <?= $sectionColor; ?>;">
                    <span class="category-name"><?= htmlspecialchars($group['category_name']); ?></span>
                    <span class="badge"><?= count($group['items']); ?> รายการ</span>
                </h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>เมนู</th>
                            <th style="width:140px;">ราคา</th>
                            <th style="width:140px;">สถานะ</th>
                            <th style="width:180px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group['items'] as $menu): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($menu['name']); ?></strong>
                                    <?php if (!empty($menu['description'])): ?>
                                        <div style="color:#6b7280;font-size:0.85rem;"><?= htmlspecialchars($menu['description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= format_currency((float)$menu['price']); ?></td>
                                <td><?= $menu['is_active'] ? 'เปิดขาย' : 'ปิดชั่วคราว'; ?></td>
                                <td class="table-actions">
                                    <a class="btn btn-light" href="menu.php?action=edit&id=<?= $menu['id']; ?>">แก้ไข</a>
                                    <a class="btn btn-light" href="menu.php?action=delete&id=<?= $menu['id']; ?>" onclick="return confirm('ยืนยันการลบเมนูนี้?');">ลบ</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
