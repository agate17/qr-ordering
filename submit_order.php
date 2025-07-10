<?php
require_once 'db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}
$table_id = isset($_POST['table_id']) ? intval($_POST['table_id']) : 0;
$qtys = isset($_POST['qty']) ? $_POST['qty'] : [];
$items = [];
foreach ($qtys as $item_id => $qty) {
    $qty = intval($qty);
    if ($qty > 0) {
        $items[$item_id] = $qty;
    }
}
if ($table_id < 1 || $table_id > 3) {
    die('Invalid table number.');
}
if (empty($items)) {
    die('No items selected. <a href="menu.php?table=' . $table_id . '">Go back</a>');
}
$order_id = create_order($table_id, $items);
header('Location: thankyou.php?table=' . $table_id . '&order=' . $order_id);
exit; 