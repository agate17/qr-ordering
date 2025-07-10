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
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .header .subtitle {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .table-section {
            margin-bottom: 40px;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            border-left: 4px solid #e67e22;
        }
        
        .table-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table-number {
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.2em;
        }
        
        .table-total {
            font-size: 1.1em;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .orders-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .order-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .order-id {
            background: #3498db;
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .order-time {
            color: #7f8c8d;
            font-size: 0.95em;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-prepared {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .order-items {
            margin-bottom: 15px;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: 500;
            color: #2c3e50;
            flex-grow: 1;
        }
        
        .item-quantity {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.85em;
            font-weight: 600;
            margin: 0 10px;
        }
        
        .item-price {
            font-weight: 600;
            color: #e67e22;
            min-width: 60px;
            text-align: right;
        }
        
        .customizations {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 10px;
            margin-top: 10px;
            font-size: 0.85em;
            line-height: 1.4;
        }
        
        .customizations:empty {
            display: none;
        }
        
        .customizations strong {
            color: #e67e22;
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 2px solid #e9ecef;
            font-weight: 700;
            font-size: 1.1em;
            color: #2c3e50;
        }
        
        .total-amount {
            color: #e67e22;
            font-size: 1.2em;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: linear-gradient(135deg, #219a52, #27ae60);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .action-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .empty-state {
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
                border-radius: 12px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .content {
                padding: 20px;
            }
            
            .table-section {
                padding: 20px;
            }
            
            .table-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
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
            
            .item-row {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            
            .item-quantity {
                margin: 0;
            }
            
            .item-price {
                text-align: left;
                min-width: auto;
            }
            
            .order-total {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .action-btn {
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
        <h1>💰 Register</h1>
        <div class="subtitle">Orders & Payment Management</div>
    </div>
    
    <div class="content">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">💳</div>
                <div>No orders yet.</div>
                <div style="font-size: 0.9em; margin-top: 10px; color: #95a5a6;">Orders will appear here when customers place them.</div>
            </div>
        <?php else: ?>
            <?php foreach ($tables as $table_id => $table_orders): ?>
                <div class="table-section">
                    <div class="table-header">
                        <div class="table-number">Table <?php echo $table_id; ?></div>
                        <?php 
                        $tableTotal = 0;
                        foreach ($table_orders as $order) {
                            $tableTotal += get_order_total($order['id']);
                        }
                        ?>
                        <div class="table-total">Total: €<?php echo number_format($tableTotal, 2); ?></div>
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
                                            <button class="action-btn" type="submit">💰 Mark as Paid</button>
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
                                    <span>Order Total:</span>
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