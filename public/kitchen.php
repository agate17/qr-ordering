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


// Filter orders to only show those with kitchen-relevant items
$filtered_kitchen_orders = array_filter($kitchen_orders, function($order) use ($menu) {
    return has_kitchen_items($order['id'], $menu);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen - Incoming Orders</title>
    <link rel="stylesheet" type="text/css" href="assets/css/kitchen.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="container">
    <div class="header">
        <div class="header-content">
            <div class="header-text">
                <h1>🍳 Pasūtījumi 🍳</h1>
                <div class="subtitle">Virtuves pasūtījumi (bez dzērieniem)</div>
            </div>
            <button class="fullscreen-btn" id="fullscreenBtn" onclick="toggleFullscreen()" title="Toggle Fullscreen">
                <span id="fullscreenIcon">⛶</span>
            </button>
        </div>
    </div>
    
    <div class="content">
        <?php if (empty($filtered_kitchen_orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">🍽️</div>
                <div>Pašlaik nav virtuves pasūtījumu.</div>
                <div style="font-size: 0.9em; margin-top: 10px; color: #9ca3af;">Pasūtījumi šeit parādīsies automātiski.</div>
                <div style="font-size: 0.8em; margin-top: 15px; color: #6b7280; font-style: italic;">
                    (Dzērieni netiek rādīti virtuves logā)
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($filtered_kitchen_orders as $order): ?>
                <?php 
                $kitchen_items = get_kitchen_items($order['id'], $menu);
                // Skip orders with no kitchen items (shouldn't happen due to filtering, but safety check)
                if (empty($kitchen_items)) continue;
                ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <div class="table-number">Galds <?php echo $order['table_id']; ?></div>
                            <div class="order-time"><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                            <div class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </div>
                        </div>
                        <form method="post" action="kitchen.php" style="margin:0;">
                            <?php if ($order['status'] === 'pending'): ?>
                                <input type="hidden" name="mark_preparing" value="<?php echo $order['id']; ?>">
                                <button class="action-btn preparing-btn" type="submit">🍳 Atzīmēt kā gatavojas</button>
                            <?php elseif ($order['status'] === 'preparing'): ?>
                                <input type="hidden" name="mark_prepared" value="<?php echo $order['id']; ?>">
                                <button class="action-btn prepared-btn" type="submit">✅ Atzīmēt kā sagatavotu</button>
                            <?php else: ?>
                                <button class="action-btn" type="button" disabled>✔ Gatavs</button>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="order-items">
                        <?php 
                        // Only show kitchen-relevant items
                        foreach ($kitchen_items as $item): 
                            $menuItem = $menu[$item['menu_item_id']];
                            $customizationDisplay = format_customizations(isset($item['customizations']) ? $item['customizations'] : '');
                        ?>
                            <div class="item-row">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($menuItem['name']); ?></div>
                                    <?php if (!empty($menuItem['category_name'])): ?>
                                        <div class="item-category"><?php echo htmlspecialchars($menuItem['category_name']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                            </div>
                            
                            <?php if (!empty($customizationDisplay)): ?>
                                <div class="customizations">
                                    <?php echo $customizationDisplay; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php
                    // Show info about filtered items if there are drinks in the original order
                    $all_items = get_order_items($order['id']);
                    $drinks_count = count($all_items) - count($kitchen_items);
                    if ($drinks_count > 0):
                    ?>
                        <div class="drinks-info">
                            <small>ℹ️ <?php echo $drinks_count; ?> dzērieni nav rādīti (tiek gatavoti pie bāra)</small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-refresh every 10 seconds, but with better UX
let refreshTimer;

function startAutoRefresh() {
    refreshTimer = setTimeout(function() {
        // Only refresh if no modals are open and user isn't interacting
        if (!document.querySelector('.order-card:hover')) {
            location.reload();
        } else {
            // Try again in 2 seconds if user is interacting
            refreshTimer = setTimeout(() => location.reload(), 2000);
        }
    }, 10000);
}

function stopAutoRefresh() {
    if (refreshTimer) {
        clearTimeout(refreshTimer);
    }
}

// Pause refresh when user is hovering over cards
document.addEventListener('DOMContentLoaded', function() {
    const orderCards = document.querySelectorAll('.order-card');
    
    orderCards.forEach(card => {
        card.addEventListener('mouseenter', stopAutoRefresh);
        card.addEventListener('mouseleave', startAutoRefresh);
    });
    
    // Start the refresh timer
    startAutoRefresh();
});

function toggleFullscreen() {
    const btn = document.getElementById('fullscreenBtn');
    const icon = document.getElementById('fullscreenIcon');
    
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            icon.textContent = '⛷';
            btn.title = 'Exit Fullscreen';
        }).catch(err => {
            console.error('Error attempting to enable fullscreen:', err);
        });
    } else {
        document.exitFullscreen().then(() => {
            icon.textContent = '⛶';
            btn.title = 'Toggle Fullscreen';
        }).catch(err => {
            console.error('Error attempting to exit fullscreen:', err);
        });
    }
}

// Listen for fullscreen changes
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
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #111827;
    min-height: 100vh;
    padding: 20px 0;
    color: #f9fafb;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    background: #1f2937;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    border: 1px solid #374151;
}

.header {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    color: white;
    padding: 30px;
    border-bottom: 1px solid #374151;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-text {
    flex: 1;
}

.header h1 {
    font-size: 2.5em;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
    color: #f9fafb;
}

.header .subtitle {
    font-size: 1.1em;
    opacity: 0.8;
    color: #d1d5db;
}

.fullscreen-btn {
    background: rgba(55, 65, 81, 0.5);
    border: 2px solid rgba(107, 114, 128, 0.3);
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.fullscreen-btn:hover {
    background: rgba(75, 85, 99, 0.7);
    border-color: rgba(156, 163, 175, 0.5);
    transform: scale(1.05);
}

.fullscreen-btn:active {
    transform: scale(0.95);
}

.content {
    padding: 30px;
    background: #1f2937;
}

.order-card {
    background: #111827;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    border: 1px solid #374151;
    transition: all 0.3s ease;
}

.order-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    transform: translateY(-2px);
    border-color: #4b5563;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #374151;
}

.order-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.table-number {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 1.2em;
}

.order-time {
    color: #9ca3af;
    font-size: 0.95em;
    font-weight: 500;
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
    background: #fbbf24;
    color: #92400e;
}

.status-preparing {
    background: #60a5fa;
    color: #1e40af;
}

.status-prepared {
    background: #34d399;
    color: #047857;
}

.order-items {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #1f2937;
    border-radius: 8px;
    border-left: 4px solid #3b82f6;
}

.item-details {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #f9fafb;
    font-size: 1.1em;
    margin-bottom: 5px;
}

.item-category {
    font-size: 0.85em;
    color: #9ca3af;
    background: rgba(156, 163, 175, 0.1);
    padding: 2px 8px;
    border-radius: 12px;
    display: inline-block;
}

.item-quantity {
    background: #3b82f6;
    color: white;
    padding: 6px 12px;
    border-radius: 15px;
    font-weight: 600;
    font-size: 0.9em;
}

.customizations {
    background: rgba(251, 191, 36, 0.1);
    border: 1px solid #fbbf24;
    border-radius: 6px;
    padding: 12px;
    margin-top: 10px;
    font-size: 0.9em;
    line-height: 1.4;
    color: #d1d5db;
}

.customizations:empty {
    display: none;
}

.customizations strong {
    color: #fbbf24;
}

.drinks-info {
    margin-top: 15px;
    padding: 10px;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 6px;
    text-align: center;
    color: #93c5fd;
    font-style: italic;
}

.action-btn {
    background: linear-gradient(135deg, #059669, #047857);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.action-btn:hover {
    background: linear-gradient(135deg, #047857, #065f46);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(5, 150, 105, 0.4);
}

.preparing-btn {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.preparing-btn:hover {
    background: linear-gradient(135deg, #d97706, #b45309);
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
}

.prepared-btn {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.prepared-btn:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
}

.action-btn:disabled {
    background: #6b7280;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
    opacity: 0.6;
}

.empty-state {
    text-align: center;
    color: #9ca3af;
    margin: 60px 0;
    font-size: 1.2em;
}

.empty-icon {
    font-size: 3em;
    margin-bottom: 20px;
    opacity: 0.5;
}

/* Fullscreen adjustments */
:fullscreen .container {
    max-width: none;
    padding: 20px;
    margin: 0;
    height: 100vh;
    border-radius: 0;
}

:fullscreen .header {
    margin-bottom: 0;
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
    
    .order-card {
        padding: 20px;
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
        gap: 10px;
        align-items: flex-start;
    }
    
    .action-btn {
        width: 100%;
    }
    
    .item-category {
        font-size: 0.8em;
    }
}
</style>

</body>
</html>