<?php
require_once __DIR__ . '/../includes/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}
$table_id = isset($_POST['table_id']) ? intval($_POST['table_id']) : 0;
$qtys = isset($_POST['qty']) ? $_POST['qty'] : [];
$customizations = isset($_POST['customizations']) ? $_POST['customizations'] : [];

$items = [];
$processed_customizations = [];

foreach ($qtys as $item_id => $qty) {
    $qty = intval($qty);
    if ($qty > 0) {
        $items[$item_id] = $qty;
        
        // Process customizations for this item
        if (isset($customizations[$item_id]) && !empty($customizations[$item_id])) {
            $processed_customizations[$item_id] = $customizations[$item_id];
        }
    }
}

if ($table_id < 1 || $table_id > get_table_count()) {
    die('Invalid table number.');
}
if (empty($items)) {
    die('No items selected. <a href="menu.php?table=' . $table_id . '">Go back</a>');
}

$order_id = create_order($table_id, $items, $processed_customizations);
header('Location: thankyou.php?table=' . $table_id . '&order=' . $order_id);
exit; 