<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}

$table_id = isset($_POST['table_id']) ? intval($_POST['table_id']) : 0;
$qtys = isset($_POST['qty']) ? $_POST['qty'] : [];
$customizations = isset($_POST['customizations']) ? $_POST['customizations'] : [];
$sauces = isset($_POST['sauces']) ? $_POST['sauces'] : [];
$sizes = isset($_POST['sizes']) ? $_POST['sizes'] : [];

$items = [];
$processed_customizations = [];

foreach ($qtys as $item_id => $qty) {
    $qty = intval($qty);
    if ($qty > 0) {
        $items[$item_id] = $qty;
        
        // Start with existing customizations
        $item_customizations = [];
        if (isset($customizations[$item_id]) && !empty($customizations[$item_id])) {
            $decoded_custom = json_decode($customizations[$item_id], true);
            if (is_array($decoded_custom)) {
                $item_customizations = $decoded_custom;
            }
        }
        
        // FIXED: Add sauce information if exists
        if (isset($sauces[$item_id]) && !empty($sauces[$item_id])) {
            $sauce_data = json_decode($sauces[$item_id], true);
            if (is_array($sauce_data)) {
                // Get sauce names for display
                $sauce_details = [];
                foreach ($sauce_data as $index => $sauce_id) {
                    if (!empty($sauce_id)) {
                        // Get sauce name from database
                        $sauce_info = get_menu_item_by_id($sauce_id);
                        if ($sauce_info) {
                            $sauce_details[] = [
                                'instance' => $index + 1,
                                'sauce_id' => intval($sauce_id), // Ensure it's an integer
                                'sauce_name' => $sauce_info['name'] // This should be clean text
                            ];
                        }
                    } else {
                        $sauce_details[] = [
                            'instance' => $index + 1,
                            'sauce_id' => null,
                            'sauce_name' => 'Bez mērces' // Use clean text, not encoded
                        ];
                    }
                }
                
                if (!empty($sauce_details)) {
                    $item_customizations['sauces'] = $sauce_details;
                }
            }
        }
        
        // Add size information if exists
        if (isset($sizes[$item_id]) && !empty($sizes[$item_id])) {
            $size_data = json_decode($sizes[$item_id], true);
            if (is_array($size_data) && !empty($size_data)) {
                $item_customizations['sizes'] = $size_data;
            }
        }
        
        // FIXED: Store processed customizations properly
        if (!empty($item_customizations)) {
            // Use JSON_UNESCAPED_UNICODE to prevent encoding issues
            $processed_customizations[$item_id] = json_encode($item_customizations, JSON_UNESCAPED_UNICODE);
        }
    }
}

if ($table_id < 1 || $table_id > get_table_count()) {
    die('Invalid table number.');
}

if (empty($items)) {
    die('No items selected. <a href="menu.php?table=' . $table_id . '">Go back</a>');
}

// DEBUG: Let's see what we're storing
error_log("DEBUG - Processed customizations: " . print_r($processed_customizations, true));

$order_id = create_order($table_id, $items, $processed_customizations);
header('Location: thankyou.php?table=' . $table_id . '&order=' . $order_id);
exit;
?>