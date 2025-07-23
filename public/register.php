<?php
require_once __DIR__ . '/../includes/db.php';

// Handle mark as paid - must be at the top before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $oid = intval($_POST['mark_paid']);
    update_order_status($oid, 'paid');
    header('Location: register.php');
    exit;
}
 
$orders = get_orders();
$orders = array_filter($orders, function($order) {
    return $order['status'] !== 'paid';
});
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Orders & Payments</title>
    <link rel="stylesheet" type="text/css" href="assets/css/register.css?v=<?php echo time(); ?>">
    <script>
        setTimeout(function(){ location.reload(); }, 10000);
    </script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>💰 Kase</h1>
        <div class="subtitle">Pasūtījumu un maksājumu pārvaldība</div>
    </div>
    
    <div class="content">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">💳</div>
                <div>Vēl nav pasūtījumu.</div>
                <div style="font-size: 0.9em; margin-top: 10px; color: #95a5a6;">Pasūtījumi tiks parādīti šeit, kad klienti tos veiks.</div>
            </div>
        <?php else: ?>
            <?php foreach ($tables as $table_id => $table_orders): ?>
                <div class="table-section">
                    <div class="table-header">
                        <div class="table-number">Galds <?php echo $table_id; ?></div>
                        <?php 
                        $tableTotal = 0;
                        foreach ($table_orders as $order) {
                            $tableTotal += get_order_total($order['id']);
                        }
                        ?>
                        <div class="table-total">Kopā: €<?php echo number_format($tableTotal, 2); ?></div>
                    </div>
                    
                    <div class="orders-grid">
                        <?php foreach ($table_orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-info">
                                        <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                        <div class="order-time"><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                                        <div class="status-badge status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></div>
                                    </div>
                                    
                                    <?php if ($order['status'] !== 'paid'): ?>
                                        <form method="post" action="register.php" style="margin:0;">
                                            <input type="hidden" name="mark_paid" value="<?php echo $order['id']; ?>">
                                            <button class="action-btn" type="submit">💰 Atzīmēt kā apmaksātu</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="order-items">
                                    <?php 
                                    $items = get_order_items($order['id']);
                                    foreach ($items as $item): 
                                        $menuItem = $menu[$item['menu_item_id']];
                                        $itemTotal = $menuItem['price'] * $item['quantity'];
                                        $customizationDisplay = format_customizations(isset($item['customizations']) ? $item['customizations'] : '');
                                    ?>
                                        <div class="item-row">
                                            <div class="item-name"><?php echo htmlspecialchars($menuItem['name']); ?></div>
                                            <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                                            <div class="item-price">€<?php echo number_format($itemTotal, 2); ?></div>
                                        </div>
                                        
                                        <?php if (!empty($customizationDisplay)): ?>
                                            <div class="customizations">
                                                <?php echo $customizationDisplay; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="order-total">
                                    <span>Pasūtījuma kopsumma:</span>
                                    <span class="total-amount">€<?php echo number_format(get_order_total($order['id']), 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html> 