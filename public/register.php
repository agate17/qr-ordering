<?php
require_once __DIR__ . '/../includes/db.php';

// Handle mark as paid - must be at the top before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $oid = intval($_POST['mark_paid']);
    update_order_status($oid, 'paid');
    header('Location: register.php');
    exit;
}

// Handle acknowledge order for register (separate from kitchen)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acknowledge_register_order'])) {
    $oid = intval($_POST['acknowledge_register_order']);
    acknowledge_register_order($oid);
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Handle new order creation from register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_register_order'])) {
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

    if ($table_id >= 1 && $table_id <= get_table_count() && !empty($items)) {
        $order_id = create_order($table_id, $items, $processed_customizations);
        $_SESSION['order_success'] = "Order #$order_id created successfully for Table $table_id";
    } else {
        $_SESSION['order_error'] = "Invalid table number or no items selected.";
    }
    
    header('Location: register.php');
    exit;
}

// Handle mark entire table as paid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_table_paid'])) {
    $table_id = intval($_POST['mark_table_paid']);
    
    // Get all unpaid orders for this table
    $unpaid_orders = get_table_unpaid_orders($table_id);
    
    if (!empty($unpaid_orders)) {
        // Mark all orders as paid
        $updated_count = 0;
        foreach ($unpaid_orders as $order) {
            if (update_order_status($order['id'], 'paid')) {
                $updated_count++;
            }
        }
        
        if ($updated_count > 0) {
            $_SESSION['order_success'] = "Galds $table_id - $updated_count pasūtījumi atzīmēti kā apmaksāti";
        } else {
            $_SESSION['order_error'] = "Neizdevās atzīmēt pasūtījumus kā apmaksātus";
        }
    } else {
        $_SESSION['order_error'] = "Nav neapmaksātu pasūtījumu galdam $table_id";
    }
    
    header('Location: register.php');
    exit;
}

// Start session AFTER handling POST requests
session_start();
$order_success = isset($_SESSION['order_success']) ? $_SESSION['order_success'] : '';
$order_error = isset($_SESSION['order_error']) ? $_SESSION['order_error'] : '';
unset($_SESSION['order_success'], $_SESSION['order_error']);
 
$orders = get_orders();
$orders = array_filter($orders, function($order) {
    return $order['status'] !== 'paid';
});
$menu = get_menu_items();

// Group orders by table and calculate table totals
$tables = [];
$table_totals = [];
foreach ($orders as $order) {
    $tables[$order['table_id']][] = $order;
    if (!isset($table_totals[$order['table_id']])) {
        $table_totals[$order['table_id']] = 0;
    }
    $table_totals[$order['table_id']] += get_order_total($order['id']);
}

// Get tables with orders for filter display
$tables_with_orders = array_keys($tables);
sort($tables_with_orders);

// Get menu items grouped by category for ordering interface
$categories = [];
$uncategorized = [];

foreach ($menu as $item) {
    if (!empty($item['category_name'])) {
        $categories[$item['category_name']][] = $item;
    } else {
        $uncategorized[] = $item;
    }
}

// Get available tables
$table_list = get_table_list();
$max_tables = get_table_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kases logs</title>
    <link rel="stylesheet" type="text/css" href="assets/css/register.css?v=<?php echo time(); ?>">
    <!-- Bootstrap Icons CDN for filter icon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
    /* New Order Notification Styles */
    .new-order-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: white;
        padding: 15px 20px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        display: none;
        z-index: 1000;
        animation: slideInFromRight 0.5s ease-out;
        min-width: 300px;
        border-left: 4px solid #d35400;
    }

    .new-order-notification.show {
        display: block;
    }

    .new-order-notification .notification-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .new-order-notification .notification-icon {
        font-size: 24px;
        animation: pulse 2s infinite;
    }

    .new-order-notification .notification-text h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: bold;
    }

    .new-order-notification .notification-text p {
        margin: 0;
        font-size: 14px;
        opacity: 0.9;
    }

    .new-order-notification .close-notification {
        position: absolute;
        top: 8px;
        right: 10px;
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.2s;
    }

    .new-order-notification .close-notification:hover {
        opacity: 1;
    }

    @keyframes slideInFromRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    /* Enhanced order card styles for new orders */
    .order-card.new-order {
        border: 2px solid #f39c12;
        background: linear-gradient(135deg, rgba(243, 156, 18, 0.1), rgba(230, 126, 34, 0.05));
        animation: newOrderGlow 2s infinite;
        position: relative;
        overflow: hidden;
    }

    .order-card.new-order::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: linear-gradient(45deg, #f39c12, #e67e22, #d35400);
        border-radius: inherit;
        z-index: -1;
        animation: borderRotate 3s linear infinite;
    }

    @keyframes newOrderGlow {
        0%, 100% {
            box-shadow: 0 0 15px rgba(243, 156, 18, 0.3);
        }
        50% {
            box-shadow: 0 0 25px rgba(243, 156, 18, 0.6);
        }
    }

    @keyframes borderRotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .new-order-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #e74c3c;
        color: white;
        font-size: 10px;
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: bold;
        animation: pulse 2s infinite;
        z-index: 10;
    }

    /* Acknowledge button styles */
    .acknowledge-btn {
        background: #27ae60;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        margin-left: 10px;
        transition: all 0.2s;
    }

    .acknowledge-btn:hover {
        background: #229954;
        transform: translateY(-1px);
    }

    /* Order item customization display enhancement */
    .customizations {
        background: rgba(52, 152, 219, 0.1);
        padding: 8px;
        border-radius: 6px;
        margin-top: 5px;
        border-left: 3px solid #3498db;
        font-size: 12px;
        line-height: 1.4;
    }

    /* Table section enhancements for new orders */
    .table-section.has-new-orders {
        animation: tableGlow 3s infinite;
    }

    @keyframes tableGlow {
        0%, 100% {
            box-shadow: 0 0 10px rgba(243, 156, 18, 0.2);
        }
        50% {
            box-shadow: 0 0 20px rgba(243, 156, 18, 0.4);
        }
    }

    /* Table pay button styles */
    .table-pay-btn {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
    }

    .table-pay-btn:hover {
        background: linear-gradient(135deg, #229954, #27ae60);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding: 15px;
        background: linear-gradient(135deg, #2c3e50, #404041ff);
        border-radius: 12px;
        border: 2px solid #0ed9f4ff;
    }

    .table-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .table-number {
        font-size: 1.5em;
        font-weight: bold;
        color: #2c3e50;
    }

    .table-total {
        font-size: 1.2em;
        color: #27ae60;
        font-weight: bold;
    }
    </style>
</head>
<body>
<div class="container">
    
    <!-- New Order Notification -->
    <div class="new-order-notification" id="newOrderNotification">
        <button class="close-notification" onclick="closeNotification()">&times;</button>
        <div class="notification-content">
            <div class="notification-icon">🔔</div>
            <div class="notification-text">
                <h4 id="notificationTitle">Jauns pasūtījums!</h4>
                <p id="notificationMessage">Galds #X - Jauns pasūtījums saņemts</p>
            </div>
        </div>
    </div>
    <?php if ($order_success): ?>
        <div class="feedback success"><?php echo htmlspecialchars($order_success); ?></div>
    <?php endif; ?>
    
    <?php if ($order_error): ?>
        <div class="feedback error"><?php echo htmlspecialchars($order_error); ?></div>
    <?php endif; ?>
    
    <button class="new-order-btn" onclick="openOrderModal()">
        ➕ Izveidot jaunu pasūtījumu
    </button>
    
    <!-- Table Filter -->
    <div class="table-filter" id="tableFilter">
        <button class="filter-btn active" onclick="filterByTable('all')" id="filter-all">
            Visi
        </button>
        <?php for ($i = 1; $i <= $max_tables; $i++): ?>
            <?php if (in_array($i, $tables_with_orders)): ?>
                <button class="filter-btn" onclick="filterByTable(<?php echo $i; ?>)" id="filter-<?php echo $i; ?>">
                    <?php echo $i; ?>
                </button>
            <?php endif; ?>
        <?php endfor; ?>
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
                <?php 
                $hasNewOrders = false;
                foreach ($table_orders as $order) {
                    if (isset($order['is_new_register']) && $order['is_new_register'] == 1) {
                        $hasNewOrders = true;
                        break;
                    }
                }
                $tableTotal = $table_totals[$table_id];
                ?>
                <div class="table-section <?php echo $hasNewOrders ? 'has-new-orders' : ''; ?>" data-table-id="<?php echo $table_id; ?>">
                    <div class="table-header">
                        <div class="table-info">
                            <div class="table-number">Galds <?php echo $table_id; ?></div>
                            <div class="table-total">Kopā: €<?php echo number_format($tableTotal, 2); ?></div>
                        </div>
                        
                        <div class="table-actions">
                            <!-- Mark entire table as paid button -->
                            <form method="post" action="register.php" style="margin:0; display: inline;" 
                                onsubmit="return confirm('Vai tiešām vēlaties atzīmēt visus galds <?php echo $table_id; ?> pasūtījumus kā apmaksātus?\n\nKopsumma: €<?php echo number_format($tableTotal, 2); ?>')">
                                <input type="hidden" name="mark_table_paid" value="<?php echo $table_id; ?>">
                                <button class="table-pay-btn" type="submit" title="Atzīmēt visu galdu kā apmaksātu">
                                    💰 Apmaksāt galdu (€<?php echo number_format($tableTotal, 2); ?>)
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="orders-grid">
                        <?php foreach ($table_orders as $order): ?>
                            <div class="order-card <?php echo (isset($order['is_new_register']) && $order['is_new_register'] == 1) ? 'new-order' : ''; ?>" data-order-id="<?php echo $order['id']; ?>">
                                <?php if (isset($order['is_new_register']) && $order['is_new_register'] == 1): ?>
                                    <div class="new-order-badge">JAUNS</div>
                                <?php endif; ?>
                                
                                <div class="order-header">
                                    <div class="order-info">
                                        <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                        <div class="order-time"><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                                        <div class="status-badge status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></div>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <?php if (isset($order['is_new_register']) && $order['is_new_register'] == 1): ?>
                                            <button class="acknowledge-btn" onclick="acknowledgeRegisterOrder(<?php echo $order['id']; ?>)">✓ Apskatīts</button>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] !== 'paid'): ?>
                                            <form method="post" action="register.php" style="margin:0; display: inline;">
                                                <input type="hidden" name="mark_paid" value="<?php echo $order['id']; ?>">
                                                <button class="action-btn" type="submit">💰 Atzīmēt kā apmaksātu</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
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

<!-- Order Creation Modal -->
<div class="order-modal" id="orderModal">
    <div class="order-modal-content">
        <div class="modal-header">
            <h2>Izveidot jaunu pasūtījumu</h2>
            <button class="close-modal" onclick="closeOrderModal()">&times;</button>
        </div>
        
        <form method="post" action="register.php" id="newOrderForm">
            <input type="hidden" name="create_register_order" value="1">
            
            <div class="table-selector">
                <label for="tableSelector"><strong>Izvēlēties galdu:</strong></label>
                <select name="table_id" id="tableSelector" required>
                    <option value="">-- Izvēlēties galdu --</option>
                    <?php foreach ($table_list as $table_id): ?>
                        <option value="<?php echo $table_id; ?>">Galds <?php echo $table_id; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Display categorized items -->
            <?php foreach ($categories as $category_name => $category_items): ?>
                <h3 class="category-title"><?php echo htmlspecialchars($category_name); ?></h3>
                <div class="menu-grid">
                    <?php foreach ($category_items as $item): ?>
                    <div class="menu-item">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-description"><?php echo htmlspecialchars($item['description']); ?></div>
                        <div class="item-price">€<?php echo number_format($item['price'], 2); ?></div>
                        
                        <div class="item-controls">
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                                <input type="number" 
                                       class="quantity-input" 
                                       name="qty[<?php echo $item['id']; ?>]" 
                                       id="qty_<?php echo $item['id']; ?>"
                                       min="0" 
                                       max="10" 
                                       value="0"
                                       onchange="updateOrderSummary()">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                            </div>
                            <button type="button" class="customize-btn" id="customize_<?php echo $item['id']; ?>" onclick="openCustomize(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                Pielāgot
                            </button>
                        </div>
                        
                        <!-- Hidden customization fields -->
                        <input type="hidden" name="customizations[<?php echo $item['id']; ?>]" id="custom_<?php echo $item['id']; ?>" value="">
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Display uncategorized items -->
            <?php if (!empty($uncategorized)): ?>
                <h3 class="category-title">Citi ēdieni</h3>
                <div class="menu-grid">
                    <?php foreach ($uncategorized as $item): ?>
                    <div class="menu-item">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-description"><?php echo htmlspecialchars($item['description']); ?></div>
                        <div class="item-price">€<?php echo number_format($item['price'], 2); ?></div>
                        
                        <div class="item-controls">
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                                <input type="number" 
                                       class="quantity-input" 
                                       name="qty[<?php echo $item['id']; ?>]" 
                                       id="qty_<?php echo $item['id']; ?>"
                                       min="0" 
                                       max="10" 
                                       value="0"
                                       onchange="updateOrderSummary()">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                            </div>
                            <button type="button" class="customize-btn" id="customize_<?php echo $item['id']; ?>" onclick="openCustomize(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                Pielāgot
                            </button>
                        </div>
                        
                        <!-- Hidden customization fields -->
                        <input type="hidden" name="customizations[<?php echo $item['id']; ?>]" id="custom_<?php echo $item['id']; ?>" value="">
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="order-summary" id="orderSummary" style="display: none;">
                <h3>Pasūtījuma kopsavilkums:</h3>
                <div id="summaryItems"></div>
                <div class="summary-total" id="summaryTotal"></div>
            </div>
            
            <button class="submit-order-btn" type="submit" id="submitOrderBtn" disabled>
                Izveidot pasūtījumu
            </button>
        </form>
    </div>
</div>

<!-- Customization Modal (same as in menu.php) -->
<div class="modal-overlay" id="customizeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Pielāgo savu ēdienu:</h3>
            <button class="close-btn" onclick="closeCustomize()">&times;</button>
        </div>

        <div class="customization-section">
            <div class="section-title">Īpašas prasības:</div>
            <div class="special-requests">
                <textarea id="specialRequests" name="special_requests" placeholder="īpaši norādījumi, sastāvdaļu izņemšana vai papildu pieprasījumi..."></textarea>
            </div>
        </div>
        
        <div class="modal-actions">
            <button type="button" class="modal-btn cancel-btn" onclick="closeCustomize()">Atcelt</button>
            <button type="button" class="modal-btn save-btn" onclick="saveCustomizations()">Saglabāt pielāgojumus</button>
        </div>
    </div>
</div>

<script>
// Menu data for JavaScript
const menuData = <?php echo json_encode($menu); ?>;
const maxTables = <?php echo $max_tables; ?>;

// AJAX polling variables
let pollInterval;
let isModalOpen = false;
let currentTableFilter = 'all'; // Track current filter
let lastOrderIds = new Set(); // Track order IDs to detect new ones

// Customization variables (added from menu.php)
let currentItemId = null;
let customizations = {};



function showNewOrderNotification(tableId, orderId) {
    const notification = document.getElementById('newOrderNotification');
    const title = document.getElementById('notificationTitle');
    const message = document.getElementById('notificationMessage');
    
    title.textContent = 'Jauns pasūtījums!';
    message.textContent = `Galds #${tableId} - Pasūtījums #${orderId}`;
    
    notification.classList.add('show');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        closeNotification();
    }, 5000);
}

function closeNotification() {
    const notification = document.getElementById('newOrderNotification');
    notification.classList.remove('show');
}

function acknowledgeRegisterOrder(orderId) {
    // Send AJAX request to acknowledge the order for register
    fetch('register.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `acknowledge_register_order=${orderId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove new order styling immediately
            const orderCard = document.querySelector(`[data-order-id="${orderId}"]`);
            if (orderCard) {
                orderCard.classList.remove('new-order');
                const badge = orderCard.querySelector('.new-order-badge');
                if (badge) badge.remove();
                const ackBtn = orderCard.querySelector('.acknowledge-btn');
                if (ackBtn) ackBtn.remove();
                
                // Remove table glow if no more new orders in this table
                const tableSection = orderCard.closest('.table-section');
                const remainingNewOrders = tableSection.querySelectorAll('.order-card.new-order');
                if (remainingNewOrders.length === 0) {
                    tableSection.classList.remove('has-new-orders');
                }
            }
        }
    })
    .catch(error => {
        console.error('Error acknowledging register order:', error);
    });
}

// Helper functions (DEFINE THESE FIRST)
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'});
}

function formatCustomizations(customizations) {
    if (!customizations) return '';
    
    let parsed;
    
    // If it's a string, try to parse as JSON
    if (typeof customizations === 'string') {
        try {
            parsed = JSON.parse(customizations);
        } catch (e) {
            // If it's not JSON, just return the string
            return customizations;
        }
    } else {
        parsed = customizations;
    }
    
    let result = [];
    
    // Handle allergies
    if (parsed.allergies && parsed.allergies.length > 0) {
        result.push(`<strong>Alerģijas:</strong> ${parsed.allergies.join(', ')}`);
    }
    
    // Handle removed ingredients
    if (parsed.remove_ingredients && parsed.remove_ingredients.length > 0) {
        result.push(`<strong>Neietvert:</strong> ${parsed.remove_ingredients.join(', ')}`);
    }
    
    // Handle special requests
    if (parsed.special_requests && parsed.special_requests.trim() !== '') {
        result.push(`<strong>Īpašas prasības:</strong> ${parsed.special_requests}`);
    }
    
    return result.join('<br>');
}

// Customization functions (added from menu.php)
function openCustomize(itemId, itemName) {
    currentItemId = itemId;
    document.getElementById('modalTitle').textContent = `Pielāgo: ${itemName}`;
    
    // Load existing customizations if any
    if (customizations[itemId]) {
        loadCustomizations(itemId);
    } else {
        // Reset form
        document.querySelectorAll('#customizeModal input[type="checkbox"]').forEach(cb => cb.checked = false);
        document.getElementById('specialRequests').value = '';
    }
    
    // Show modal
    document.getElementById('customizeModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCustomize() {
    document.getElementById('customizeModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    currentItemId = null;
}

function loadCustomizations(itemId) {
    const itemCustomizations = customizations[itemId];
    
    // Load allergies
    if (itemCustomizations.allergies) {
        itemCustomizations.allergies.forEach(allergy => {
            const checkbox = document.getElementById(`allergy_${allergy}`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    // Load removed ingredients
    if (itemCustomizations.remove_ingredients) {
        itemCustomizations.remove_ingredients.forEach(ingredient => {
            const checkbox = document.getElementById(`remove_${ingredient}`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    // Load special requests
    if (itemCustomizations.special_requests) {
        document.getElementById('specialRequests').value = itemCustomizations.special_requests;
    }
}

function saveCustomizations() {
    if (!currentItemId) return;
    
    const allergies = [];
    const removeIngredients = [];
    
    // Collect allergies
    document.querySelectorAll('#customizeModal input[name="allergies[]"]:checked').forEach(cb => {
        allergies.push(cb.value);
    });
    
    // Collect removed ingredients
    document.querySelectorAll('#customizeModal input[name="remove_ingredients[]"]:checked').forEach(cb => {
        removeIngredients.push(cb.value);
    });
    
    // Get special requests
    const specialRequests = document.getElementById('specialRequests').value.trim();
    
    // Save customizations
    customizations[currentItemId] = {
        allergies: allergies,
        remove_ingredients: removeIngredients,
        special_requests: specialRequests
    };
    
    // Update hidden field
    document.getElementById(`custom_${currentItemId}`).value = JSON.stringify(customizations[currentItemId]);
    
    // Update customize button appearance
    const customizeBtn = document.getElementById(`customize_${currentItemId}`);
    if (allergies.length > 0 || removeIngredients.length > 0 || specialRequests) {
        customizeBtn.classList.add('has-customizations');
        customizeBtn.textContent = 'Pielāgots ✓';
    } else {
        customizeBtn.classList.remove('has-customizations');
        customizeBtn.textContent = 'Pielāgot';
    }
    
    // Update order summary
    updateOrderSummary();
    
    // Close modal
    closeCustomize();
}

// Table filter functions
function filterByTable(tableId) {
    currentTableFilter = tableId;
    
    // Update button states
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById('filter-' + tableId).classList.add('active');
    
    // Show/hide table sections
    const tableSections = document.querySelectorAll('.table-section');
    const emptyState = document.querySelector('.empty-state');
    
    if (tableId === 'all') {
        // Show all tables
        tableSections.forEach(section => {
            section.style.display = 'block';
        });
        if (emptyState) emptyState.style.display = tableSections.length === 0 ? 'block' : 'none';
    } else {
        // Show only specific table
        let hasVisibleTable = false;
        tableSections.forEach(section => {
            const sectionTableId = section.getAttribute('data-table-id');
            if (sectionTableId == tableId) {
                section.style.display = 'block';
                hasVisibleTable = true;
            } else {
                section.style.display = 'none';
            }
        });
        
        // Show empty state if no orders for this table
        if (emptyState) {
            emptyState.style.display = hasVisibleTable ? 'none' : 'block';
        }
    }
}

// UPDATED modal functions
function openOrderModal() {
    document.getElementById('orderModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    isModalOpen = true;
    stopPolling(); // Stop polling when modal is open
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    // Reset form
    document.getElementById('newOrderForm').reset();
    // Reset customizations
    customizations = {};
    document.querySelectorAll('.customize-btn').forEach(btn => {
        btn.classList.remove('has-customizations');
        btn.textContent = 'Pielāgot';
    });
    updateOrderSummary();
    isModalOpen = false;
    startPolling(); // Resume polling when modal is closed
    fetchOrderData(); // Immediate update when closing modal
}

// UPDATED updateOrdersDisplay function with proper table total calculation
function updateOrdersDisplay(data) {
    const contentDiv = document.querySelector('.content');
    
    if (!data.orders || data.orders.length === 0) {
        contentDiv.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">💳</div>
                <div>Vēl nav pasūtījumu.</div>
                <div style="font-size: 0.9em; margin-top: 10px; color: #95a5a6;">
                    Pasūtījumi tiks parādīti šeit, kad klienti tos veiks.
                </div>
            </div>
        `;
        // Remove all table filter buttons except "All" when no orders
        document.querySelectorAll('.filter-btn:not(#filter-all)').forEach(btn => btn.remove());
        filterByTable('all');
        return;
    }
    
    // Get current table IDs before updating DOM
    const currentTableIds = Array.from(document.querySelectorAll('.table-section')).map(section => 
        parseInt(section.getAttribute('data-table-id'))
    );
    
    // Group orders by table and calculate table totals
    const tables = {};
    const tableTotals = {};
    data.orders.forEach(order => {
        if (!tables[order.table_id]) {
            tables[order.table_id] = [];
            tableTotals[order.table_id] = 0;
        }
        tables[order.table_id].push(order);
        tableTotals[order.table_id] += parseFloat(order.total || 0);
    });
    
    const newTableIds = Object.keys(tables).map(id => parseInt(id));
    
    let html = '';
    Object.keys(tables).sort((a, b) => parseInt(a) - parseInt(b)).forEach(tableId => {
        const tableOrders = tables[tableId];
        const tableTotal = tableTotals[tableId];
        
        // Check if table has new orders for register
        const hasNewOrders = tableOrders.some(order => order.is_new_register == 1);
        
        html += `
            <div class="table-section ${hasNewOrders ? 'has-new-orders' : ''}" data-table-id="${tableId}">
                <div class="table-header">
                    <div class="table-info">
                        <div class="table-number">Galds ${tableId}</div>
                        <div class="table-total">Kopā: €${tableTotal.toFixed(2)}</div>
                    </div>
                    
                    <div class="table-actions">
                        <form method="post" action="register.php" style="margin:0; display: inline;" 
                              onsubmit="return confirm('Vai tiešām vēlaties atzīmēt visus galds ${tableId} pasūtījumus kā apmaksātus?\\n\\nKopsumma: €${tableTotal.toFixed(2)}')">
                            <input type="hidden" name="mark_table_paid" value="${tableId}">
                            <button class="table-pay-btn" type="submit" title="Atzīmēt visu galdu kā apmaksātu">
                                💰 Apmaksāt galdu (€${tableTotal.toFixed(2)})
                            </button>
                        </form>
                    </div>
                </div>
                <div class="orders-grid">
        `;
        
        tableOrders.forEach(order => {
            html += generateOrderCardHTML(order, data.menu);
        });
        
        html += '</div></div>';
    });
    
    contentDiv.innerHTML = html;
    
    // Remove filter buttons for tables that no longer exist
    currentTableIds.forEach(tableId => {
        if (!newTableIds.includes(tableId)) {
            const btn = document.getElementById('filter-' + tableId);
            if (btn) {
                btn.remove();
                // If we were filtering by this table, switch to all
                if (currentTableFilter == tableId) {
                    filterByTable('all');
                }
            }
        }
    });
    
    // Apply current filter after updating content
    if (currentTableFilter !== 'all') {
        filterByTable(currentTableFilter);
    }
}

// Fetch fresh order data via AJAX
async function fetchOrderData() {
    try {
        const response = await fetch('get_orders.php');
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();
        if (data.success) {
            // Check for new orders before updating display
            checkForNewOrders(data.orders);
            updateOrdersDisplay(data);
            updateTableFilter(data.orders);
        }
    } catch (error) {
        console.error('Error fetching orders:', error);
    }
}

// Check for new orders and show notifications
function checkForNewOrders(orders) {
    const currentOrderIds = new Set();
    
    orders.forEach(order => {
        currentOrderIds.add(order.id);
        
        // Check if this is a truly new order (not in our previous set and marked as new for register)
        if (!lastOrderIds.has(order.id) && order.is_new_register == 1) {
            // Show notification for new order
            showNewOrderNotification(order.table_id, order.id);
        }
    });
    
    // Update our tracking set
    lastOrderIds = currentOrderIds;
}

// Initialize order tracking on first load
function initializeOrderTracking() {
    // Get current order IDs to initialize tracking
    const currentOrders = document.querySelectorAll('[data-order-id]');
    currentOrders.forEach(orderElement => {
        const orderId = parseInt(orderElement.getAttribute('data-order-id'));
        lastOrderIds.add(orderId);
    });
}

// Update table filter based on current orders
function updateTableFilter(orders) {
    // Get tables that have unpaid orders
    const tablesWithUnpaidOrders = [...new Set(orders.map(order => order.table_id))].sort((a, b) => a - b);
    
    // Only add buttons for tables with orders, don't remove existing ones
    tablesWithUnpaidOrders.forEach(tableId => {
        const btn = document.getElementById('filter-' + tableId);
        if (!btn) {
            // Create button if it doesn't exist
            const newBtn = document.createElement('button');
            newBtn.className = 'filter-btn';
            newBtn.id = 'filter-' + tableId;
            newBtn.textContent = tableId;
            newBtn.onclick = () => filterByTable(tableId);
            document.getElementById('tableFilter').appendChild(newBtn);
        }
    });
    
    // Only switch away from current filter if that table truly has no orders visible
    if (currentTableFilter !== 'all') {
        const currentTableSection = document.querySelector(`.table-section[data-table-id="${currentTableFilter}"]`);
        if (!currentTableSection || currentTableSection.style.display === 'none') {
            filterByTable('all');
        }
    }
}

// Generate HTML for individual order card
function generateOrderCardHTML(order, menuItems) {
    let itemsHTML = '';
    order.items.forEach(item => {
        const menuItem = menuItems[item.menu_item_id];
        if (menuItem) {
            const itemTotal = menuItem.price * item.quantity;
            
            itemsHTML += `
                <div class="item-row">
                    <div class="item-name">${escapeHtml(menuItem.name)}</div>
                    <div class="item-quantity">x${item.quantity}</div>
                    <div class="item-price">€${itemTotal.toFixed(2)}</div>
                </div>
            `;
            
            // Add customizations if they exist - format them properly
            if (item.customizations) {
                const formattedCustomizations = formatCustomizations(item.customizations);
                if (formattedCustomizations) {
                    itemsHTML += `<div class="customizations">${formattedCustomizations}</div>`;
                }
            }
        }
    });
    
    const isNewOrder = order.is_new_register == 1;
    const newOrderBadge = isNewOrder ? '<div class="new-order-badge">JAUNS</div>' : '';
    const acknowledgeButton = isNewOrder ? `<button class="acknowledge-btn" onclick="acknowledgeRegisterOrder(${order.id})">✓ Apskatīts</button>` : '';
    
    return `
        <div class="order-card ${isNewOrder ? 'new-order' : ''}" data-order-id="${order.id}">
            ${newOrderBadge}
            <div class="order-header">
                <div class="order-info">
                    <div class="order-id">Order #${order.id}</div>
                    <div class="order-time">${formatTime(order.created_at)}</div>
                    <div class="status-badge status-${order.status}">${order.status}</div>
                </div>
                <div class="order-actions">
                    ${acknowledgeButton}
                    ${order.status !== 'paid' ? `
                        <form method="post" action="register.php" style="margin:0; display: inline;">
                            <input type="hidden" name="mark_paid" value="${order.id}">
                            <button class="action-btn" type="submit">💰 Atzīmēt kā apmaksātu</button>
                        </form>
                    ` : ''}
                </div>
            </div>
            <div class="order-items">${itemsHTML}</div>
            <div class="order-total">
                <span>Pasūtījuma kopsumma:</span>
                <span class="total-amount">€${parseFloat(order.total).toFixed(2)}</span>
            </div>
        </div>
    `;
}

// Polling control
function startPolling() {
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(() => {
        if (!isModalOpen) {
            fetchOrderData();
        }
    }, 2000); // Poll every 2 seconds - fast and responsive!
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

// EXISTING functions (keep these as they were)
function changeQuantity(itemId, change) {
    const input = document.getElementById('qty_' + itemId);
    const newValue = Math.max(0, Math.min(10, parseInt(input.value || 0) + change));
    input.value = newValue;
    updateOrderSummary();
}

function updateOrderSummary() {
    const quantities = document.querySelectorAll('.quantity-input');
    const summaryDiv = document.getElementById('orderSummary');
    const summaryItems = document.getElementById('summaryItems');
    const summaryTotal = document.getElementById('summaryTotal');
    const submitBtn = document.getElementById('submitOrderBtn');
    
    let hasItems = false;
    let total = 0;
    let summaryHTML = '';
    
    quantities.forEach(input => {
        const quantity = parseInt(input.value || 0);
        if (quantity > 0) {
            hasItems = true;
            const itemId = input.name.match(/\[(\d+)\]/)[1];
            const menuItem = menuData[itemId];
            if (menuItem) {
                const itemTotal = quantity * parseFloat(menuItem.price);
                total += itemTotal;
                
                // Add customization info if exists
                let customizationInfo = '';
                if (customizations[itemId] && Object.keys(customizations[itemId]).length > 0) {
                    customizationInfo = '<br><small style="color: #e67e22;">✓ Pielāgots</small>';
                }
                
                summaryHTML += `
                    <div class="summary-item">
                        <span>${menuItem.name} x ${quantity}${customizationInfo}</span>
                        <span>€${itemTotal.toFixed(2)}</span>
                    </div>
                `;
            }
        }
    });
    
    if (hasItems) {
        summaryDiv.style.display = 'block';
        summaryItems.innerHTML = summaryHTML;
        summaryTotal.innerHTML = `<span>Total:</span><span>€${total.toFixed(2)}</span>`;
        submitBtn.disabled = false;
    } else {
        summaryDiv.style.display = 'none';
        submitBtn.disabled = true;
    }
}

// Close modal when clicking outside (for both modals)
document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeOrderModal();
    }
});

document.getElementById('customizeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCustomize();
    }
});

// Close modal with Escape key (for both modals)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('customizeModal').classList.contains('active')) {
            closeCustomize();
        } else if (document.getElementById('orderModal').classList.contains('active')) {
            closeOrderModal();
        } else if (document.getElementById('newOrderNotification').classList.contains('show')) {
            closeNotification();
        }
    }
});

// UPDATED Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateOrderSummary();
    initializeOrderTracking(); // Initialize order tracking
    
    // Auto-dismiss feedback messages
    const feedbacks = document.querySelectorAll('.feedback');
    feedbacks.forEach(feedback => {
        setTimeout(() => {
            feedback.style.opacity = '0';
            setTimeout(() => feedback.remove(), 500);
        }, 5000);
    });
    
    // Start the AJAX polling system
    startPolling();
    
    // Stop polling when page is about to unload
    window.addEventListener('beforeunload', stopPolling);
    
    // Pause polling when tab is not visible (saves resources)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else if (!isModalOpen) {
            startPolling();
        }
    });
});
</script>
</body>
</html>