<?php
// Database configuration and common settings for Big Apple Restaurant

define('DB_HOST', 'localhost');
define('DB_NAME', 'bigapple_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'Big Apple Restaurant');
define('APP_LOCALE', 'th_TH');
define('CURRENCY_SYMBOL', '฿');

define('PROMPTPAY_ACCOUNT', '0123456789');

define('BASE_URL', '/bigapple/public');
define('ADMIN_URL', '/bigapple/admin');

define('TELEGRAM_BOT_TOKEN', '');
define('TELEGRAM_CHAT_ID', '');

function get_db_connection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Database connection failed: ' . htmlspecialchars($e->getMessage())) ;
        }
    }

    return $pdo;
}

function format_currency(float $amount): string
{
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

