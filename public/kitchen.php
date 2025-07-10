<?php
require_once __DIR__ . '/../includes/db.php';
$orders = get_orders('pending');
$menu = get_menu_items();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kitchen - Incoming Orders</title>
    <style>
        body { font-family: sans-serif; background: #f3f3f3; }
        .container { max-width: 700px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        h1 { text-align: center; }
        .order { border: 1px solid #ddd; border-radius: 6px; margin-bottom: 18px; padding: 16px; background: #fafafa; }
        .order-header { display: flex; justify-content: space-between; align-items: center; }
        .order-items { margin: 10px 0 0 0; }
        .order-items td { padding: 4px 8px; }
        .btn { background: #2980b9; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 1em; }
        .btn:hover { background: #1c5d8c; }
        .empty { text-align: center; color: #888; margin: 40px 0; }
    </style>
    <script>
        setTimeout(function(){ location.reload(); }, 5000);
    </script>
</head>
<body>
<div class="container">
    <h1>Kitchen - Incoming Orders</h1>
    <?php if (empty($orders)): ?>
        <div class="empty">No pending orders.</div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order">
                <div class="order-header">
                    <div><b>Table <?php echo $order['table_id']; ?></b></div>
                    <div><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                    <form method="post" action="kitchen.php" style="margin:0;">
                        <input type="hidden" name="mark_prepared" value="<?php echo $order['id']; ?>">
                        <button class="btn" type="submit">✔ Mark as Prepared</button>
                    </form>
                </div>
                <table class="order-items">
                    <?php $items = get_order_items($order['id']);
                    foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($menu[$item['menu_item_id']]['name']); ?></td>
                            <td>x<?php echo $item['quantity']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php
// Handle mark as prepared
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_prepared'])) {
    $oid = intval($_POST['mark_prepared']);
    update_order_status($oid, 'prepared');
    header('Location: kitchen.php');
    exit;
}
?>
</body>
</html> 