<?php
require_once __DIR__ . '/../includes/db.php';

// Handle mark as paid - must be at the top before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $oid = intval($_POST['mark_paid']);
    update_order_status($oid, 'paid');
    header('Location: register.php');
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

session_start();
$order_success = isset($_SESSION['order_success']) ? $_SESSION['order_success'] : '';
$order_error = isset($_SESSION['order_error']) ? $_SESSION['order_error'] : '';
unset($_SESSION['order_success'], $_SESSION['order_error']);
 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Orders & Payments</title>
    <link rel="stylesheet" type="text/css" href="assets/css/register.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="container">
    <div class="header">
        <div class="header-content">
            <div class="header-text">
                <h1>💰 Kase</h1>
                <div class="subtitle">Pasūtījumu un maksājumu pārvaldība</div>
            </div>
            <button id="fullscreenBtn" class="fullscreen-btn" onclick="toggleFullscreen()" title="Toggle Fullscreen">
                <span id="fullscreenIcon">⛶</span>
            </button>
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
                        
                        <!-- Hidden customization fields (simplified for register) -->
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

<script>
// Menu data for JavaScript
const menuData = <?php echo json_encode($menu); ?>;

// AJAX polling variables
let pollInterval;
let isModalOpen = false;

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
    updateOrderSummary();
    isModalOpen = false;
    startPolling(); // Resume polling when modal is closed
    fetchOrderData(); // Immediate update when closing modal
}

// Fetch fresh order data via AJAX
async function fetchOrderData() {
    try {
        const response = await fetch('get_orders.php');
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();
        if (data.success) {
            updateOrdersDisplay(data);
        }
    } catch (error) {
        console.error('Error fetching orders:', error);
        // Don't auto-reload - just log the error for now
        console.log('AJAX failed - check if get_orders.php exists and works');
    }
}

// Update the orders display without full page reload
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
        return;
    }
    
    // Group orders by table
    const tables = {};
    data.orders.forEach(order => {
        if (!tables[order.table_id]) tables[order.table_id] = [];
        tables[order.table_id].push(order);
    });
    
    let html = '';
    Object.keys(tables).forEach(tableId => {
        const tableOrders = tables[tableId];
        const tableTotal = tableOrders.reduce((sum, order) => sum + parseFloat(order.total), 0);
        
        html += `
            <div class="table-section">
                <div class="table-header">
                    <div class="table-number">Galds ${tableId}</div>
                    <div class="table-total">Kopā: €${tableTotal.toFixed(2)}</div>
                </div>
                <div class="orders-grid">
        `;
        
        tableOrders.forEach(order => {
            html += generateOrderCardHTML(order, data.menu);
        });
        
        html += '</div></div>';
    });
    
    contentDiv.innerHTML = html;
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
    
    return `
        <div class="order-card">
            <div class="order-header">
                <div class="order-info">
                    <div class="order-id">Order #${order.id}</div>
                    <div class="order-time">${formatTime(order.created_at)}</div>
                    <div class="status-badge status-${order.status}">${order.status}</div>
                </div>
                ${order.status !== 'paid' ? `
                    <form method="post" action="register.php" style="margin:0;">
                        <input type="hidden" name="mark_paid" value="${order.id}">
                        <button class="action-btn" type="submit">💰 Atzīmēt kā apmaksātu</button>
                    </form>
                ` : ''}
            </div>
            <div class="order-items">${itemsHTML}</div>
            <div class="order-total">
                <span>Pasūtījuma kopsumma:</span>
                <span class="total-amount">€${parseFloat(order.total).toFixed(2)}</span>
            </div>
        </div>
    `;
}

// Helper functions
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
                
                summaryHTML += `
                    <div class="summary-item">
                        <span>${menuItem.name} x ${quantity}</span>
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

// Close modal when clicking outside
document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeOrderModal();
    }
});

// UPDATED Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateOrderSummary();
    
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

<style>
.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px;
    margin: 30px;
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

/* Make modal work well in fullscreen */
:fullscreen .order-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
}
</style>

</body>
</html>