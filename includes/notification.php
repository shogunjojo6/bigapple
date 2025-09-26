<?php

require_once __DIR__ . '/config.php';

function send_telegram_notification(string $message, array $buttons = null): bool
{
    if (empty(TELEGRAM_BOT_TOKEN) || empty(TELEGRAM_CHAT_ID)) {
        return false;
    }

    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";

    $payload = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML',
    ];

    if ($buttons) {
        $payload['reply_markup'] = json_encode([
            'inline_keyboard' => $buttons,
        ]);
    }

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($payload),
            'timeout' => 5,
        ],
    ];

    $context = stream_context_create($options);

    try {
        $result = file_get_contents($url, false, $context);
        return $result !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function format_order_message(array $order, array $items): string
{
    $lines = [];
    $lines[] = "🍽️ <b>Big Apple - ออเดอร์ใหม่!</b>";
    $lines[] = "📋 หมายเลข: #" . htmlspecialchars($order['order_code']);
    $lines[] = "🪑 โต๊ะ: " . htmlspecialchars($order['table_number']);
    $lines[] = "💳 การชำระ: " . strtoupper($order['payment_method']);
    $lines[] = "────────────";

    foreach ($items as $item) {
        $lines[] = "• " . htmlspecialchars($item['name']) . ' x' . (int)$item['quantity'];
    }

    $lines[] = "────────────";
    $lines[] = "💰 รวม: " . format_currency((float)$order['total_amount']);
    $lines[] = "⏰ เวลา: " . date('H:i');

    return implode("\n", $lines);
}

