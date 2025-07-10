<?php
require_once __DIR__ . '/../includes/db.php';
// Handle mark as paid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $oid = intval($_POST['mark_paid']);
    update_order_status($oid, 'paid');
    header('Location: register.php');
    exit;
}
$orders = get_orders();
$menu = get_menu_items();
// Group orders by table
$tables = [];
foreach ($orders as $order) {
    $tables[$order['table_id']][] = $order;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Orders & Totals</title>
    <style>
        body { font-family: sans-serif; background: #f3f3f3; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        h1 { text-align: center; }
        .table-block { margin-bottom: 32px; }
        .order { border: 1px solid #ddd; border-radius: 6px; margin-bottom: 14px; padding: 12px 16px; background: #fafafa; }
        .order-header { display: flex; justify-content: space-between; align-items: center; }
        .order-items { margin: 8px 0 0 0; }
        .order-items td { padding: 4px 8px; }
        .btn { background: #e67e22; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 1em; }
        .btn:hover { background: #b95d13; }
        .status { font-weight: bold; text-transform: capitalize; }
        .status.pending { color: #c0392b; }
        .status.prepared { color: #2980b9; }
        .status.paid { color: #27ae60; }
        .empty { text-align: center; color: #888; margin: 40px 0; }
    </style>
    <script>
        setTimeout(function(){ location.reload(); }, 5000);
    </script>
</head>
<body>
<div class="container">
    <h1>Register - Orders & Totals</h1>
    <?php if (empty($orders)): ?>
        <div class="empty">No orders yet.</div>
    <?php else: ?>
        <?php foreach ($tables as $table_id => $table_orders): ?>
            <div class="table-block">
                <h2>Table <?php echo $table_id; ?></h2>
                <?php foreach ($table_orders as $order): ?>
                    <div class="order">
                        <div class="order-header">
                            <div>Order #<?php echo $order['id']; ?> | <span class="status <?php echo $order['status']; ?>"><?php echo $order['status']; ?></span></div>
                            <div><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                            <?php if ($order['status'] !== 'paid'): ?>
                            <form method="post" action="register.php" style="margin:0;">
                                <input type="hidden" name="mark_paid" value="<?php echo $order['id']; ?>">
                                <button class="btn" type="submit">💰 Mark as Paid</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <table class="order-items">
                            <?php $items = get_order_items($order['id']);
                            foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($menu[$item['menu_item_id']]['name']); ?></td>
                                    <td>x<?php echo $item['quantity']; ?></td>
                                    <td>€<?php echo number_format($menu[$item['menu_item_id']]['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        <div style="text-align:right; font-weight:bold; margin-top:8px;">Total: €<?php echo number_format(get_order_total($order['id']), 2); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html> 