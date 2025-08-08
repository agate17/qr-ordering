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
    <link rel="stylesheet" type="text/css" href="assets/css/kitchen.css?v=<?php echo time(); ?>">
    <script>
        setTimeout(function(){ location.reload(); }, 10000);
    </script>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="header-content">
            <div class="header-text">
                <h1>🍳 Pasūtījumi</h1>
                <div class="subtitle">Ienākošie pasūtījumi no klientiem</div>
            </div>
            <button id="fullscreenBtn" class="fullscreen-btn" onclick="toggleFullscreen()" title="Toggle Fullscreen">
                <span id="fullscreenIcon">⛶</span>
            </button>
        </div>
    </div>
    
    <?php if (empty($kitchen_orders)): ?>
        <div class="empty">
            <div class="empty-icon">🍽️</div>
            <div>Pašlaik nav pasūtījumu.</div>
            <div style="font-size: 0.9em; margin-top: 10px; color: #95a5a6;">Pasūtījumi šeit parādīsies automātiski.</div>
        </div>
    <?php else: ?>
        <?php foreach ($kitchen_orders as $order): ?>
            <div class="order" style="background:
                <?php if ($order['status'] === 'pending') echo '#fffbe6';
                elseif ($order['status'] === 'preparing') echo '#e6f7ff';
                else echo '#fff'; ?>;">
                <div class="order-header">
                    <div class="order-info">
                        <div class="table-number">Galds <?php echo $order['table_id']; ?></div>
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
                            <button class="btn" type="submit">🍳 Atzīmēt kā gatavojas</button>
                        <?php elseif ($order['status'] === 'preparing'): ?>
                            <input type="hidden" name="mark_prepared" value="<?php echo $order['id']; ?>">
                            <button class="btn" type="submit">✅ Atzīmēt kā sagatavotu</button>
                        <?php else: ?>
                            <button class="btn" type="button" disabled>✔ Gatavs</button>
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

<script>
function toggleFullscreen() {
    const btn = document.getElementById('fullscreenBtn');
    const icon = document.getElementById('fullscreenIcon');
    
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            icon.textContent = '⛷'; // Exit fullscreen icon
            btn.title = 'Exit Fullscreen';
        }).catch(err => {
            console.error('Error attempting to enable fullscreen:', err);
        });
    } else {
        document.exitFullscreen().then(() => {
            icon.textContent = '⛶'; // Enter fullscreen icon
            btn.title = 'Toggle Fullscreen';
        }).catch(err => {
            console.error('Error attempting to exit fullscreen:', err);
        });
    }
}

// Listen for fullscreen changes (e.g., when user presses ESC)
document.addEventListener('fullscreenchange', function() {
    const icon = document.getElementById('fullscreenIcon');
    const btn = document.getElementById('fullscreenBtn');
    
    if (document.fullscreenElement) {
        icon.textContent = '⛷';
        btn.title = 'Exit Fullscreen';
    } else {
        icon.textContent = '⛶';
        btn.title = 'Toggle Fullscreen';
    }
});
</script>

<style>
.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-text {
    flex: 1;
}

.fullscreen-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.fullscreen-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.05);
}

.fullscreen-btn:active {
    transform: scale(0.95);
}

/* Fullscreen adjustments */
:fullscreen .container {
    max-width: none;
    padding: 20px;
}

:fullscreen .header {
    margin-bottom: 20px;
}
</style>

</body>
</html>