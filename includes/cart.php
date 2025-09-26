<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/menu.php';

function get_cart(): array
{
    ensure_session();
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    return $_SESSION['cart'];
}

function save_cart(array $cart): void
{
    ensure_session();
    $_SESSION['cart'] = $cart;
}

function add_to_cart(int $menuId, int $quantity): void
{
    $cart = get_cart();
    $menu = fetch_menu_item($menuId);

    if (!$menu) {
        throw new InvalidArgumentException('Invalid menu item.');
    }

    if (isset($cart[$menuId])) {
        $cart[$menuId]['quantity'] += $quantity;
    } else {
        $cart[$menuId] = [
            'menu_id' => $menuId,
            'name' => $menu['name'],
            'unit_price' => (float)$menu['price'],
            'quantity' => $quantity,
        ];
    }

    $cart[$menuId]['subtotal'] = $cart[$menuId]['unit_price'] * $cart[$menuId]['quantity'];
    save_cart($cart);
}

function update_cart_item(int $menuId, int $quantity): void
{
    $cart = get_cart();
    if (isset($cart[$menuId])) {
        if ($quantity <= 0) {
            unset($cart[$menuId]);
        } else {
            $cart[$menuId]['quantity'] = $quantity;
            $cart[$menuId]['subtotal'] = $cart[$menuId]['unit_price'] * $quantity;
        }
    }
    save_cart($cart);
}

function clear_cart(): void
{
    save_cart([]);
}

function remove_cart_item(int $menuId): void
{
    $cart = get_cart();
    if (isset($cart[$menuId])) {
        unset($cart[$menuId]);
        save_cart($cart);
    }
}

function calculate_cart_total(): float
{
    $cart = get_cart();
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}

function cart_item_count(): int
{
    $cart = get_cart();
    $count = 0;
    foreach ($cart as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

