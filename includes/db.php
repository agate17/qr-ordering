<?php
// db.php: Database connection and fallback logic

// --- CONFIG ---
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'restaurant_demo';

// --- FALLBACK DATA ---
$MENU_ITEMS = [
    1 => ['id' => 1, 'name' => 'Margherita Pizza', 'description' => 'Classic pizza with tomato, mozzarella, and basil.', 'price' => 8.00, 'category_name' => 'Specials', 'image_path' => NULL, 'available' => 1],
    2 => ['id' => 2, 'name' => 'Spaghetti', 'description' => 'Spaghetti pasta with homemade tomato sauce.', 'price' => 9.00, 'category_name' => NULL, 'image_path' => NULL, 'available' => 1],
    3 => ['id' => 3, 'name' => 'Caesar Salad', 'description' => 'Crisp romaine lettuce with Caesar dressing.', 'price' => 7.00, 'category_name' => NULL, 'image_path' => NULL, 'available' => 1],
    4 => ['id' => 4, 'name' => 'Cola', 'description' => 'Chilled soft drink.', 'price' => 2.00, 'category_name' => NULL, 'image_path' => NULL, 'available' => 1],
    5 => ['id' => 5, 'name' => 'Water', 'description' => 'Bottled mineral water.', 'price' => 1.50, 'category_name' => NULL, 'image_path' => NULL, 'available' => 1],
];
$ORDERS = [];
$ORDER_ITEMS = [];

function db_connect() {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn->connect_errno) return false;
    return $conn;
}

function get_menu_items() {
    $conn = db_connect();
    global $MENU_ITEMS;
    if ($conn) {
        $sql = "SELECT mi.*, c.name as category_name 
                FROM menu_items mi 
                LEFT JOIN categories c ON mi.category_id = c.id 
                WHERE mi.available = 1 
                ORDER BY c.name, mi.name";
        $res = $conn->query($sql);
        $items = [];
        while ($row = $res->fetch_assoc()) $items[$row['id']] = $row;
        $conn->close();
        return $items;
    }
    return $MENU_ITEMS;
}

function create_order($table_id, $items, $customizations = []) {
    $conn = db_connect();
    global $ORDERS, $ORDER_ITEMS;
    if ($conn) {
        $conn->query("INSERT INTO orders (table_id, status, created_at) VALUES ($table_id, 'pending', NOW())");
        $order_id = $conn->insert_id;
        foreach ($items as $item_id => $qty) {
            $customization_data = isset($customizations[$item_id]) ? $conn->real_escape_string($customizations[$item_id]) : '';
            $conn->query("INSERT INTO order_items (order_id, menu_item_id, quantity, customizations) VALUES ($order_id, $item_id, $qty, '$customization_data')");
        }
        $conn->close();
        return $order_id;
    } else {
        $order_id = count($ORDERS) + 1;
        $ORDERS[$order_id] = [
            'id' => $order_id,
            'table_id' => $table_id,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        foreach ($items as $item_id => $qty) {
            $ORDER_ITEMS[] = [
                'id' => count($ORDER_ITEMS) + 1,
                'order_id' => $order_id,
                'menu_item_id' => $item_id,
                'quantity' => $qty,
                'customizations' => isset($customizations[$item_id]) ? $customizations[$item_id] : ''
            ];
        }
        return $order_id;
    }
}

function get_orders($status = null) {
    $conn = db_connect();
    global $ORDERS, $ORDER_ITEMS;
    if ($conn) {
        $sql = 'SELECT * FROM orders';
        if ($status) $sql .= " WHERE status='" . $conn->real_escape_string($status) . "'";
        $sql .= ' ORDER BY created_at DESC';
        $res = $conn->query($sql);
        $orders = [];
        while ($row = $res->fetch_assoc()) $orders[$row['id']] = $row;
        $conn->close();
        return $orders;
    }
    if ($status) {
        return array_filter($ORDERS, function($o) use ($status) { return $o['status'] === $status; });
    }
    return $ORDERS;
}

function get_order_items($order_id) {
    $conn = db_connect();
    global $ORDER_ITEMS;
    if ($conn) {
        $res = $conn->query("SELECT * FROM order_items WHERE order_id=$order_id");
        $items = [];
        while ($row = $res->fetch_assoc()) $items[] = $row;
        $conn->close();
        return $items;
    }
    return array_filter($ORDER_ITEMS, function($oi) use ($order_id) { return $oi['order_id'] == $order_id; });
}

function update_order_status($order_id, $status) {
    $conn = db_connect();
    global $ORDERS;
    $debug_log = __DIR__ . '/../debug.log';
    $query = "UPDATE orders SET status='" . $conn->real_escape_string($status) . "' WHERE id=$order_id";
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " update_order_status called with order_id=$order_id, status=$status\n", FILE_APPEND);
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " Query: $query\n", FILE_APPEND);
    if ($conn) {
        $result = $conn->query($query);
        if (!$result) {
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " MySQL error updating order status: " . $conn->error . "\n", FILE_APPEND);
        } else if ($conn->affected_rows === 0) {
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " Order status update failed for order_id=$order_id, status=$status\n", FILE_APPEND);
        }
        $conn->close();
    } else {
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " DB connection failed in update_order_status\n", FILE_APPEND);
        if (isset($ORDERS[$order_id])) $ORDERS[$order_id]['status'] = $status;
    }
}

function get_order_total($order_id) {
    $items = get_order_items($order_id);
    $menu = get_menu_items();
    $total = 0;
    foreach ($items as $item) {
        $total += $menu[$item['menu_item_id']]['price'] * $item['quantity'];
    }
    return $total;
}

// New function to format customization data for display
function format_customizations($customization_json) {
    if (empty($customization_json)) return '';
    
    try {
        $customizations = json_decode($customization_json, true);
        if (!$customizations) return '';
        
        $formatted = [];
        
        // Format allergies
        if (!empty($customizations['allergies'])) {
            $formatted[] = '<strong>Allergies:</strong> ' . implode(', ', $customizations['allergies']);
        }
        
        // Format removed ingredients
        if (!empty($customizations['remove_ingredients'])) {
            $formatted[] = '<strong>Remove:</strong> ' . implode(', ', $customizations['remove_ingredients']);
        }
        
        // Format special requests
        if (!empty($customizations['special_requests'])) {
            $formatted[] = '<strong>Special:</strong> ' . htmlspecialchars($customizations['special_requests']);
        }
        
        return implode('<br>', $formatted);
    } catch (Exception $e) {
        return '';
    }
} 