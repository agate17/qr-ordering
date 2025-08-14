<?php
// get_orders.php - Enhanced with kitchen filtering capability
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../includes/db.php';

try {
    // Check if this is a kitchen request
    $view_type = isset($_GET['view']) ? $_GET['view'] : 'register';
    $is_kitchen_view = ($view_type === 'kitchen');
    
    $orders = get_orders();
    
    // Filter out paid orders (same as in register.php)
    $orders = array_filter($orders, function($order) {
        return $order['status'] !== 'paid';
    });
    
    $menu = get_menu_items();
    
    // Add order items and totals to each order
    foreach ($orders as &$order) {
        $all_items = get_order_items($order['id']);
        
        if ($is_kitchen_view) {
            // For kitchen view, filter out drink items
            $order['items'] = filter_kitchen_items($all_items, $menu);
            $order['all_items_count'] = count($all_items);
            $order['kitchen_items_count'] = count($order['items']);
            $order['drinks_count'] = $order['all_items_count'] - $order['kitchen_items_count'];
        } else {
            // For register view, show all items
            $order['items'] = $all_items;
        }
        
        // Calculate total (always based on all items)
        $order['total'] = get_order_total($order['id']);
        
        // Process customizations for each item
        foreach ($order['items'] as &$item) {
            // Keep customizations as raw JSON string for JavaScript to parse
            // Don't format here - let JavaScript handle it
        }
    }
    
    // If this is a kitchen view, filter out orders that have no kitchen items
    if ($is_kitchen_view) {
        $orders = array_filter($orders, function($order) use ($menu) {
            return has_kitchen_items($order['id'], $menu);
        });
    }
    
    echo json_encode([
        'success' => true,
        'orders' => array_values($orders), // Re-index array after filtering
        'menu' => $menu,
        'view_type' => $view_type,
        'is_kitchen_view' => $is_kitchen_view,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch orders',
        'message' => $e->getMessage(),
        'view_type' => $view_type ?? 'unknown'
    ]);
}
?>