<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/menu.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/order.php';
require_once __DIR__ . '/../includes/notification.php';
require_once __DIR__ . '/../includes/table.php';

ensure_session();

if (isset($_GET['table']) && $_GET['table'] !== '') {
    $tableParam = preg_replace('/[^A-Za-z0-9_-]/', '', $_GET['table']);
    if ($tableParam !== '') {
        set_active_table($tableParam);
    }
}

