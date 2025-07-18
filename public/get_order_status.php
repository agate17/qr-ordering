<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;
if ($order_id < 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}
$orders = get_orders();
if (!isset($orders[$order_id])) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}
$status = $orders[$order_id]['status'];
echo json_encode(['success' => true, 'status' => $status]); 