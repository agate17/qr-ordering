<?php
require_once __DIR__ . '/../includes/db.php';

$table_id = isset($_GET['table']) ? intval($_GET['table']) : 0;
$order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;

// Get order details if order_id is provided
$order_items = [];
$menu_items = [];
$total_price = 0;

if ($order_id > 0) {
    $order_items = get_order_items($order_id);
    $menu_items = get_menu_items();
    
    // Calculate total
    foreach ($order_items as $item) {
        if (isset($menu_items[$item['menu_item_id']])) {
            $menu_item = $menu_items[$item['menu_item_id']];
            $total_price += $menu_item['price'] * $item['quantity'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>
    <link rel="stylesheet" type="text/css" href="assets/css/thankyou.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="container">
    <div class="success-header">
        <span class="success-icon">✅</span>
        <h1 class="success-title">Jūsu pasūtījums ir apstiprināts!</h1>
    </div>
    
    <div class="content">
        <div class="order-info">
            <div class="order-details">
                <div class="order-detail">
                    <div class="detail-label">galds</div>
                    <div class="table-number"><?php echo $table_id; ?></div>
                </div>
                <?php if ($order_id): ?>
                <div class="order-detail">
                    <div class="detail-label">Order #</div>
                    <div class="detail-value"><?php echo $order_id; ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($order_items) && !empty($menu_items)): ?>
        <div class="order-details-section">
            <h3 style="margin-top: 0; margin-bottom: 15px;">📋 Jūsu pasūtījums:</h3>
            
            <?php foreach ($order_items as $item): ?>
                <?php 
                $menu_item = $menu_items[$item['menu_item_id']] ?? null;
                if (!$menu_item) continue;
                
                $item_total = $menu_item['price'] * $item['quantity'];
                $customizations = '';
                
                // Format customizations if they exist
                if (!empty($item['customizations'])) {
                    $customizations = format_customizations($item['customizations']);
                }
                ?>
                <div class="order-item">
                    <div class="item-info">
                        <div class="item-name"><?php echo htmlspecialchars($menu_item['name']); ?></div>
                        <?php if (!empty($menu_item['description'])): ?>
                        <div class="item-description"><?php echo htmlspecialchars($menu_item['description']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($customizations)): ?>
                        <div class="item-customizations">
                            <strong>🔧 Pielāgojumi:</strong><br>
                            <?php echo $customizations; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="item-quantity"><?php echo $item['quantity']; ?></div>
                    <div class="item-price">€<?php echo number_format($item_total, 2); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="total-section">
            <div class="total-label">Kopējā summa:</div>
            <div class="total-amount">€<?php echo number_format($total_price, 2); ?></div>
        </div>
        <?php else: ?>
        <div class="no-items">
            Nav atrasti pasūtījuma dati
        </div>
        <?php endif; ?>
        
        <div class="message">
            <p>Paldies par pasūtījumu!</p>
        </div>
        

        
        <div class="action-buttons">
            <a href="menu.php?table=<?php echo $table_id; ?>" class="btn btn-primary">Veikt papildus pasūtījumu</a>
        </div>
        
        <div class="footer-note">
            <p>💡 ja aizmirsi pievienot ko svarīgu, nekas - veic papildus pasūtījumu!</p>
        </div>
    </div>
</div>

</body>
</html>