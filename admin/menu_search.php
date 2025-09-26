<?php
require_once __DIR__ . '/bootstrap.php';
require_admin_auth();

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

ob_start();
include __DIR__ . '/partials/menu_list.php';
$html = ob_get_clean();

echo $html;
