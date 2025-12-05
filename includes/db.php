<?php
// db.php: Database connection and fallback logic

// --- CONFIG ---
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'restaurant_demo';

// --- FALLBACK DATA ---
$MENU_ITEMS = [
    1 => ['id' => 1, 'name' => 'Margherita Pizza', 'description' => 'Classic pizza with tomato, mozzarella, and basil.', 'price' => 8.00, 'category_name' => 'Specials', 'image_path' => NULL, 'available' => 1, 'included_sauces' => 0],
    2 => ['id' => 2, 'name' => 'Spaghetti', 'description' => 'Spaghetti pasta with homemade tomato sauce.', 'price' => 9.00, 'category_name' => NULL, 'image_path' => NULL, 'available' => 1, 'included_sauces' => 0],
    3 => ['id' => 3, 'name' => 'Caesar Salad', 'description' => 'Crisp romaine lettuce with Caesar dressing.', 'price' => 7.00, 'category_name' => NULL, 'image_path' => NULL, 'available' => 1, 'included_sauces' => 0],
    4 => ['id' => 4, 'name' => 'Cola', 'description' => 'Chilled soft drink.', 'price' => 2.00, 'category_name' => NULL, 'image_path' => NULL, 'available' => 1, 'included_sauces' => 0],
    5 => ['id' => 5, 'name' => 'Water', 'description' => 'Bottled mineral water.', 'price' => 1.50, 'category_name' => NULL, 'image_path' => NULL, 'available' => 1, 'included_sauces' => 0],
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
        // Create order with is_new_kitchen = 1 and is_new_register = 1 (new order for both)
        $conn->query("INSERT INTO orders (table_id, status, is_new_kitchen, is_new_register, created_at) VALUES ($table_id, 'pending', 1, 1, NOW())");
        $order_id = $conn->insert_id;
        foreach ($items as $item_id => $qty) {
            // Handle customizations properly
            $customization_data = '';
            if (isset($customizations[$item_id]) && !empty($customizations[$item_id])) {
                // The customization data is already JSON encoded from submit_order.php
                $customization_data = $conn->real_escape_string($customizations[$item_id]);
            }
            
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
            'is_new_kitchen' => 1,
            'is_new_register' => 1,
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
    $query = "UPDATE orders SET status='" . $conn->real_escape_string($status) . "' WHERE id=$order_id";
    if ($conn) {
        $result = $conn->query($query);
        $conn->close();
    } else {
        if (isset($ORDERS[$order_id])) $ORDERS[$order_id]['status'] = $status;
    }
}

// Mark order as acknowledged for kitchen only
function acknowledge_kitchen_order($order_id) {
    $conn = db_connect();
    global $ORDERS;
    if ($conn) {
        $conn->query("UPDATE orders SET is_new_kitchen = 0 WHERE id = $order_id");
        $conn->close();
    } else {
        if (isset($ORDERS[$order_id])) {
            $ORDERS[$order_id]['is_new_kitchen'] = 0;
        }
    }
}

// Mark order as acknowledged for register only
function acknowledge_register_order($order_id) {
    $conn = db_connect();
    global $ORDERS;
    if ($conn) {
        $conn->query("UPDATE orders SET is_new_register = 0 WHERE id = $order_id");
        $conn->close();
    } else {
        if (isset($ORDERS[$order_id])) {
            $ORDERS[$order_id]['is_new_register'] = 0;
        }
    }
}

// DEPRECATED: Keep for backward compatibility but rename to avoid confusion
function acknowledge_order($order_id) {
    // This function now acknowledges for both kitchen and register
    // Only use this if you want to acknowledge for both systems at once
    acknowledge_kitchen_order($order_id);
    acknowledge_register_order($order_id);
}

// Get count of new orders for kitchen
function get_new_kitchen_orders_count() {
    $conn = db_connect();
    if ($conn) {
        $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE is_new_kitchen = 1 AND status IN ('pending', 'preparing')");
        $row = $result->fetch_assoc();
        $conn->close();
        return intval($row['count']);
    }
    
    // Fallback for no database
    global $ORDERS;
    return count(array_filter($ORDERS, function($order) {
        return isset($order['is_new_kitchen']) && $order['is_new_kitchen'] == 1 && in_array($order['status'], ['pending', 'preparing']);
    }));
}

// Get count of new orders for register
function get_new_register_orders_count() {
    $conn = db_connect();
    if ($conn) {
        $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE is_new_register = 1 AND status IN ('pending', 'preparing', 'ready')");
        $row = $result->fetch_assoc();
        $conn->close();
        return intval($row['count']);
    }
    
    // Fallback for no database
    global $ORDERS;
    return count(array_filter($ORDERS, function($order) {
        return isset($order['is_new_register']) && $order['is_new_register'] == 1 && in_array($order['status'], ['pending', 'preparing', 'ready']);
    }));
}

// Check if there are any new kitchen orders
function has_new_kitchen_orders() {
    $conn = db_connect();
    if ($conn) {
        $menu_items = get_menu_items();
        $orders = get_orders();
        
        foreach ($orders as $order) {
            if ($order['is_new_kitchen'] == 1 && in_array($order['status'], ['pending', 'preparing'])) {
                if (has_kitchen_items($order['id'], $menu_items)) {
                    return true;
                }
            }
        }
    }
    return false;
}

// Check if there are any new register orders
function has_new_register_orders() {
    $conn = db_connect();
    if ($conn) {
        $orders = get_orders();
        foreach ($orders as $order) {
            if ($order['is_new_register'] == 1 && in_array($order['status'], ['pending', 'preparing', 'ready'])) {
                return true;
            }
        }
    }
    return false;
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

// Format customization data for display
function format_customizations($customization_json) {
    if (empty($customization_json)) return '';
    
    try {
        $customizations = json_decode($customization_json, true);
        if (!$customizations) return '';
        
        $formatted = [];
        
        // Format allergies
        if (!empty($customizations['allergies'])) {
            $formatted[] = '<strong>Alerģijas:</strong> ' . implode(', ', $customizations['allergies']);
        }
        
        // Format removed ingredients
        if (!empty($customizations['remove_ingredients'])) {
            $formatted[] = '<strong>Izņemt:</strong> ' . implode(', ', $customizations['remove_ingredients']);
        }
        
        // Format special requests
        if (!empty($customizations['special_requests'])) {
            $formatted[] = '<strong>Īpaši:</strong> ' . htmlspecialchars($customizations['special_requests']);
        }
        
        // Format size selections (show first, as it's more important)
        if (!empty($customizations['sizes']) && is_array($customizations['sizes'])) {
            $size_info = [];
            foreach ($customizations['sizes'] as $index => $size) {
                $instance_num = $index + 1;
                if ($size === 'small') {
                    $size_info[] = "<span style='background: #e8f4f8; padding: 4px 8px; border-radius: 4px; font-weight: 600; color: #2980b9;'>📏 {$instance_num}. Parastais</span>";
                } elseif ($size === 'large') {
                    $size_info[] = "<span style='background: #e8f4f8; padding: 4px 8px; border-radius: 4px; font-weight: 600; color: #2980b9;'>📏 {$instance_num}. Lielais</span>";
                } else {
                    $size_info[] = "<span style='background: #e8f4f8; padding: 4px 8px; border-radius: 4px; font-weight: 600; color: #2980b9;'>📏 {$instance_num}. " . ucfirst($size) . "</span>";
                }
            }
            if (!empty($size_info)) {
                $formatted[] = '<div style="margin: 8px 0;"><strong style="color: #2980b9; font-size: 1.05em;">Izmēri:</strong><br><div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 6px;">' . implode('', $size_info) . '</div></div>';
            }
        }
        
        // Format sauce selections - Remove "priekš"
        if (!empty($customizations['sauces'])) {
            $sauce_info = [];
            foreach ($customizations['sauces'] as $sauce) {
                $instance_num = $sauce['instance'];
                $sauce_name = $sauce['sauce_name'];
                if ($sauce_name === 'Bez mērces') {
                    $sauce_info[] = "<span style='background: #fff5e6; padding: 4px 8px; border-radius: 4px; color: #d35400;'>{$instance_num}. <em>Bez mērces</em></span>";
                } else {
                    $sauce_info[] = "<span style='background: #fff5e6; padding: 4px 8px; border-radius: 4px; font-weight: 600; color: #d35400;'>{$instance_num}. {$sauce_name}</span>";
                }
            }
            if (!empty($sauce_info)) {
                $formatted[] = '<div style="margin: 8px 0;"><strong style="color: #d35400; font-size: 1.05em;">🍯 Mērces:</strong><br><div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 6px;">' . implode('', $sauce_info) . '</div></div>';
            }
        }
        
        return implode('<br>', $formatted);
    } catch (Exception $e) {
        return '';
    }
} 

function get_restaurant_setting($key, $default = null) {
    $conn = db_connect();
    if ($conn) {
        $key_escaped = $conn->real_escape_string($key);
        $res = $conn->query("SELECT setting_value FROM restaurant_settings WHERE setting_key='$key_escaped'");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $conn->close();
            return $row['setting_value'];
        }
        $conn->close();
    }
    return $default;
}

function update_restaurant_setting($key, $value) {
    $conn = db_connect();
    if ($conn) {
        $key_escaped = $conn->real_escape_string($key);
        $value_escaped = $conn->real_escape_string($value);
        $conn->query("INSERT INTO restaurant_settings (setting_key, setting_value) VALUES ('$key_escaped', '$value_escaped') ON DUPLICATE KEY UPDATE setting_value='$value_escaped'");
        $conn->close();
        return true;
    }
    return false;
}

// Background customization functions
function get_menu_background_settings() {
    $conn = db_connect();
    if ($conn) {
        // Check if background_settings table exists, if not create it
        $table_check = $conn->query("SHOW TABLES LIKE 'menu_background_settings'");
        if (!$table_check || $table_check->num_rows === 0) {
            $conn->query("CREATE TABLE IF NOT EXISTS menu_background_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                background_color VARCHAR(20) DEFAULT '#f8f8f8',
                background_image VARCHAR(255) DEFAULT NULL,
                background_repeat VARCHAR(20) DEFAULT 'no-repeat',
                background_size VARCHAR(20) DEFAULT 'cover',
                background_position VARCHAR(50) DEFAULT 'center center',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            // Insert default settings
            $conn->query("INSERT INTO menu_background_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id=1");
        }
        
        $result = $conn->query("SELECT * FROM menu_background_settings WHERE id = 1 LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $settings = $result->fetch_assoc();
            $conn->close();
            return $settings;
        }
        $conn->close();
    }
    
    // Return defaults if no settings found
    return [
        'background_color' => '#f8f8f8',
        'background_image' => null,
        'background_repeat' => 'no-repeat',
        'background_size' => 'cover',
        'background_position' => 'center center'
    ];
}

function update_menu_background_settings($settings) {
    $conn = db_connect();
    if (!$conn) {
        error_log("Database connection failed in update_menu_background_settings");
        return false;
    }
    
    // Ensure table exists with proper structure
    $table_check = $conn->query("SHOW TABLES LIKE 'menu_background_settings'");
    if (!$table_check || $table_check->num_rows === 0) {
        $create_sql = "CREATE TABLE IF NOT EXISTS menu_background_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            background_color VARCHAR(20) DEFAULT '#f8f8f8',
            background_image VARCHAR(255) DEFAULT NULL,
            background_repeat VARCHAR(20) DEFAULT 'no-repeat',
            background_size VARCHAR(20) DEFAULT 'cover',
            background_position VARCHAR(50) DEFAULT 'center center',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        if (!$conn->query($create_sql)) {
            error_log("Failed to create table: " . $conn->error);
            $conn->close();
            return false;
        }
    } else {
        // Check if PRIMARY KEY exists on id
        $key_check = $conn->query("SHOW KEYS FROM menu_background_settings WHERE Key_name = 'PRIMARY'");
        if (!$key_check || $key_check->num_rows === 0) {
            // Add PRIMARY KEY if missing
            $conn->query("ALTER TABLE menu_background_settings ADD PRIMARY KEY (id)");
        }
    }
    
    $color = isset($settings['background_color']) ? $conn->real_escape_string($settings['background_color']) : '#f8f8f8';
    $repeat = isset($settings['background_repeat']) ? $conn->real_escape_string($settings['background_repeat']) : 'no-repeat';
    $size = isset($settings['background_size']) ? $conn->real_escape_string($settings['background_size']) : 'cover';
    $position = isset($settings['background_position']) ? $conn->real_escape_string($settings['background_position']) : 'center center';
    
    // Handle image - can be null, empty string, or a path
    if (isset($settings['background_image']) && $settings['background_image'] !== null && $settings['background_image'] !== '') {
        $image = $conn->real_escape_string($settings['background_image']);
        $image_sql = "'$image'";
    } else {
        $image_sql = 'NULL';
    }
    
    // Check if row exists first
    $check_sql = "SELECT id FROM menu_background_settings WHERE id = 1";
    $check_result = $conn->query($check_sql);
    $row_exists = $check_result && $check_result->num_rows > 0;
    
    if ($row_exists) {
        // Update existing record
        $update_sql = "UPDATE menu_background_settings SET 
            background_color='$color', 
            background_image=$image_sql, 
            background_repeat='$repeat', 
            background_size='$size', 
            background_position='$position'
            WHERE id = 1";
        
        $result = $conn->query($update_sql);
        $sql_attempted = $update_sql;
        $affected = $conn->affected_rows;
        
        if (!$result) {
            error_log("Background settings UPDATE failed: " . $conn->error);
            error_log("SQL: " . $update_sql);
            $conn->close();
            return false;
        }
        
        if ($affected === 0) {
            error_log("WARNING: UPDATE query succeeded but 0 rows affected! This means the row exists but values didn't change OR WHERE clause didn't match.");
            // Even if 0 rows affected, if query succeeded, we consider it OK (values might be the same)
            $result = true;
        }
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO menu_background_settings (id, background_color, background_image, background_repeat, background_size, background_position) 
                VALUES (1, '$color', $image_sql, '$repeat', '$size', '$position')";
        $result = $conn->query($insert_sql);
        $sql_attempted = $insert_sql;
        $affected = $conn->affected_rows;
        
        if (!$result) {
            error_log("Background settings INSERT failed: " . $conn->error);
            error_log("SQL: " . $insert_sql);
            $conn->close();
            return false;
        }
    }
    
    error_log("Background settings saved successfully. Affected rows: " . ($affected ?? 0));
    error_log("SQL used: " . $sql_attempted);
    
    // Verify by reading back
    $verify_sql = "SELECT * FROM menu_background_settings WHERE id = 1";
    $verify_result = $conn->query($verify_sql);
    if ($verify_result && $verify_result->num_rows > 0) {
        $verify_row = $verify_result->fetch_assoc();
        error_log("Verified saved values: " . print_r($verify_row, true));
    }
    
    $conn->close();
    return $result;
}

function reset_menu_background_to_default() {
    $defaults = [
        'background_color' => '#f8f8f8',
        'background_image' => null,
        'background_repeat' => 'no-repeat',
        'background_size' => 'cover',
        'background_position' => 'center center'
    ];
    return update_menu_background_settings($defaults);
}

function get_table_count() {
    return intval(get_restaurant_setting('table_count', 3));
}

function get_table_list() {
    $count = get_table_count();
    $tables = [];
    for ($i = 1; $i <= $count; $i++) {
        $tables[] = $i;
    }
    return $tables;
}

// Function to get all drink category names (centralized configuration)
function get_drink_categories() {
    return [
        'dzērieni',                    // Current drinks category
        'bezalkoholiskie dzērieni',    // Non-alcoholic drinks
        'alkoholiskie dzērieni',        // Alcoholic drinks
        'bāra uzkodas',                  // Bar snacks
        'bezalkoholiskie kokteiļi'   // Non-alcoholic cocktails
        // Add more drink categories here as needed
    ];
}

// Check if an order has kitchen-relevant items
function has_kitchen_items($order_id, $menu_items) {
    $items = get_order_items($order_id);
    $drink_categories = array_map('strtolower', get_drink_categories());
    
    foreach ($items as $item) {
        $menu_item = $menu_items[$item['menu_item_id']];
        $category = strtolower(trim($menu_item['category_name'] ?? ''));
        
        // If item has no category or is not a drink category, it belongs in kitchen
        if (empty($category) || !in_array($category, $drink_categories)) {
            return true;
        }
    }
    return false;
}

// Get only kitchen-relevant items from an order
function get_kitchen_items($order_id, $menu_items) {
    $items = get_order_items($order_id);
    $drink_categories = array_map('strtolower', get_drink_categories());
    $kitchen_items = [];
    
    foreach ($items as $item) {
        $menu_item = $menu_items[$item['menu_item_id']];
        $category = strtolower(trim($menu_item['category_name'] ?? ''));
        
        // Include item if it has no category or is not a drink category
        if (empty($category) || !in_array($category, $drink_categories)) {
            $kitchen_items[] = $item;
        }
    }
    
    return $kitchen_items;
}

// Filter items for kitchen view (for get_orders.php)
function filter_kitchen_items($items, $menu_items) {
    $drink_categories = array_map('strtolower', get_drink_categories());
    $filtered_items = [];
    
    foreach ($items as $item) {
        $menu_item = $menu_items[$item['menu_item_id']];
        $category = strtolower(trim($menu_item['category_name'] ?? ''));
        
        // Include item if it has no category or is not a drink category
        if (empty($category) || !in_array($category, $drink_categories)) {
            $filtered_items[] = $item;
        }
    }
    
    return $filtered_items;
}

// Check if a specific item is a drink
function is_drink_item($menu_item) {
    $drink_categories = array_map('strtolower', get_drink_categories());
    $category = strtolower(trim($menu_item['category_name'] ?? ''));
    
    return !empty($category) && in_array($category, $drink_categories);
}

// Get only drink items from an order (for bar/drinks station)
function get_drink_items($order_id, $menu_items) {
    $items = get_order_items($order_id);
    $drink_categories = array_map('strtolower', get_drink_categories());
    $drink_items = [];
    
    foreach ($items as $item) {
        $menu_item = $menu_items[$item['menu_item_id']];
        $category = strtolower(trim($menu_item['category_name'] ?? ''));
        
        // Include item if it's in a drink category
        if (!empty($category) && in_array($category, $drink_categories)) {
            $drink_items[] = $item;
        }
    }
    
    return $drink_items;
}

/**
 * Get all unpaid orders for a specific table
 * @param int $table_id The table ID
 * @return array Array of unpaid orders for the table
 */
function get_table_unpaid_orders($table_id) {
    $conn = db_connect();
    global $ORDERS;
    
    if ($conn) {
        $table_id_escaped = intval($table_id);
        $sql = "SELECT id, table_id, status, created_at 
                FROM orders 
                WHERE table_id = $table_id_escaped AND status != 'paid' 
                ORDER BY created_at ASC";
        
        $result = $conn->query($sql);
        $orders = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        
        $conn->close();
        return $orders;
    }
    
    // Fallback for no database - use global ORDERS array
    return array_filter($ORDERS, function($order) use ($table_id) {
        return $order['table_id'] == $table_id && $order['status'] !== 'paid';
    });
}

function get_menu_item_by_id($item_id) {
    $conn = db_connect();
    global $MENU_ITEMS;
    
    if ($conn) {
        $item_id_escaped = intval($item_id);
        $sql = "SELECT mi.*, c.name as category_name 
                FROM menu_items mi 
                LEFT JOIN categories c ON mi.category_id = c.id 
                WHERE mi.id = $item_id_escaped AND mi.available = 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $item = $result->fetch_assoc();
            $conn->close();
            return $item;
        }
        $conn->close();
    }
    
    // Fallback to global array
    return isset($MENU_ITEMS[$item_id]) ? $MENU_ITEMS[$item_id] : false;
}