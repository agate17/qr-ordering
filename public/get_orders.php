<?php
// get_orders.php - Place this file in your public/ folder
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../includes/db.php';

try {
    $orders = get_orders();
    
    // Filter out paid orders (same as in register.php)
    $orders = array_filter($orders, function($order) {
        return $order['status'] !== 'paid';
    });
    
    $menu = get_menu_items();
    
    // Add order items and totals to each order
    foreach ($orders as &$order) {
        $order['items'] = get_order_items($order['id']);
        $order['total'] = get_order_total($order['id']);
        
        // Process customizations for each item
        foreach ($order['items'] as &$item) {
            // Keep customizations as raw JSON string for JavaScript to parse
            // Don't format here - let JavaScript handle it
        }
    }
    
    echo json_encode([
        'success' => true,
        'orders' => array_values($orders), // Re-index array after filtering
        'menu' => $menu,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch orders',
        'message' => $e->getMessage()
    ]);
}
?>