<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$table = get_active_table();
if (!$table) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ไม่พบหมายเลขโต๊ะ']);
    exit;
}

$type = $_POST['type'] ?? 'checkout';
if (create_table_request($table['id'], $type)) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกคำขอได้']);
}
