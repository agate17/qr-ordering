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

function openOrderModal() {
    document.getElementById('orderModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    // Reset form
    document.getElementById('newOrderForm').reset();
    updateOrderSummary();
}

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

// Close modal when clicking outside
document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeOrderModal();
    }
});

// Initialize on page load
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
});
</script>
</body>
</html>