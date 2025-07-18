<?php
require_once __DIR__ . '/../includes/db.php';

// Handle mark as preparing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_preparing'])) {
    $oid = intval($_POST['mark_preparing']);
    update_order_status($oid, 'preparing');
    header('Location: kitchen.php');
    exit;
}
// Handle mark as prepared
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_prepared'])) {
    $oid = intval($_POST['mark_prepared']);
    update_order_status($oid, 'prepared');
    header('Location: kitchen.php');
    exit;
}

// Show all orders that are not paid, done, or prepared
$orders = get_orders();
$menu = get_menu_items();
$kitchen_orders = array_filter($orders, function($o) {
    return in_array($o['status'], ['pending', 'preparing']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen - Incoming Orders</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: #fff; 
            padding: 30px; 
            border-radius: 16px; 
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f0f0;
        }
        
        .header h1 {
            font-size: 2.5em;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            color: #7f8c8d;
            font-size: 1.1em;
        }
        
        .order { 
            border: 2px solid #e9ecef; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            padding: 20px; 
            background: #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .order:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .order-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .order-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .table-number {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .order-time {
            color: #7f8c8d;
            font-size: 0.95em;
        }
        
        .btn { 
            background: linear-gradient(135deg, #27ae60, #2ecc71); 
            color: #fff; 
            border: none; 
            padding: 12px 24px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover { 
            background: linear-gradient(135deg, #219a52, #27ae60);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .order-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .order-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #3498db;
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .item-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1em;
        }
        
        .item-quantity {
            background: #3498db;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .customizations {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 12px;
            margin-top: 10px;
            font-size: 0.9em;
            line-height: 1.4;
        }
        
        .customizations:empty {
            display: none;
        }
        
        .customizations strong {
            color: #e67e22;
        }
        
        .empty { 
            text-align: center; 
            color: #7f8c8d; 
            margin: 60px 0;
            font-size: 1.2em;
        }
        
        .empty-icon {
            font-size: 3em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .order-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .order-info {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
    <script>
        setTimeout(function(){ location.reload(); }, 10000);
    </script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🍳 Kitchen Orders</h1>
        <div class="subtitle">Incoming orders from customers</div>
    </div>
    
    <?php if (empty($kitchen_orders)): ?>
        <div class="empty">
            <div class="empty-icon">🍽️</div>
            <div>No pending orders at the moment.</div>
            <div style="font-size: 0.9em; margin-top: 10px; color: #95a5a6;">Orders will appear here automatically.</div>
        </div>
    <?php else: ?>
        <?php foreach ($kitchen_orders as $order): ?>
            <div class="order" style="background:
                <?php if ($order['status'] === 'pending') echo '#fffbe6';
                elseif ($order['status'] === 'preparing') echo '#e6f7ff';
                else echo '#fff'; ?>;">
                <div class="order-header">
                    <div class="order-info">
                        <div class="table-number">Table <?php echo $order['table_id']; ?></div>
                        <div class="order-time"><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                        <span class="status-badge status-<?php echo $order['status']; ?>" style="margin-left:10px; padding: 6px 12px; border-radius: 15px; font-weight: 600; font-size: 0.95em; text-transform: uppercase; letter-spacing: 1px; background: <?php
                             if ($order['status'] === 'pending') echo '#fff3cd; color: #856404; border: 1px solid #ffeaa7;';
                             elseif ($order['status'] === 'preparing') echo '#d1ecf1; color: #0c5460; border: 1px solid #bee5eb;';
                             else echo '#d4edda; color: #155724; border: 1px solid #c3e6cb;';
                         ?>">
                             <?php echo ucfirst($order['status']); ?>
                         </span>
                    </div>
                    <form method="post" action="kitchen.php" style="margin:0;">
                        <?php if ($order['status'] === 'pending'): ?>
                            <input type="hidden" name="mark_preparing" value="<?php echo $order['id']; ?>">
                            <button class="btn" type="submit">🍳 Mark as Preparing</button>
                        <?php elseif ($order['status'] === 'preparing'): ?>
                            <input type="hidden" name="mark_prepared" value="<?php echo $order['id']; ?>">
                            <button class="btn" type="submit">✅ Mark as Prepared</button>
                        <?php else: ?>
                            <button class="btn" type="button" disabled>✔ Ready</button>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="order-items">
                    <?php 
                    $items = get_order_items($order['id']);
                    foreach ($items as $item): 
                        $menuItem = $menu[$item['menu_item_id']];
                        $customizationDisplay = format_customizations(isset($item['customizations']) ? $item['customizations'] : '');
                    ?>
                        <div class="order-item">
                            <div class="item-header">
                                <div class="item-name"><?php echo htmlspecialchars($menuItem['name']); ?></div>
                                <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                            </div>
                            
                            <?php if (!empty($customizationDisplay)): ?>
                                <div class="customizations">
                                    <?php echo $customizationDisplay; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


</body>
</html> 