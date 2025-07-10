<?php
require_once __DIR__ . '/../includes/db.php';
$table_id = isset($_GET['table']) ? intval($_GET['table']) : 0;
if ($table_id < 1 || $table_id > 3) {
    die('Invalid or missing table number.');
}
$menu = get_menu_items();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table <?php echo $table_id; ?> - Menu</title>
    <link rel="stylesheet" type="text/css" href="assets/css/menu.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Table <?php echo $table_id; ?></h1>
        <div class="subtitle">Select your delicious meals</div>
    </div>
    
    <div class="menu-content">
        <form method="post" action="submit_order.php" id="orderForm">
            <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
            
            <div class="menu-grid">
                <?php foreach ($menu as $item): ?>
                <div class="menu-item">
                    <div class="food-image">
                        <!-- Placeholder for food image - will be replaced with actual images later -->
                        <!-- <img src="assets/images/<?php echo $item['id']; ?>.jpg" alt="<?php echo htmlspecialchars($item['name']); ?>"> -->
                    </div>
                    <div class="food-details">
                        <div class="food-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="food-description">
                            <!-- Placeholder for food description - will be added to database later -->
                            Delicious <?php echo htmlspecialchars($item['name']); ?> prepared with fresh ingredients
                        </div>
                        <div class="food-price">€<?php echo number_format($item['price'], 2); ?></div>
                        
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
                            <button type="button" class="customize-btn" onclick="openCustomize(<?php echo $item['id']; ?>)">
                                Customize
                            </button>
                        </div>
                        
                        <!-- Hidden customization fields -->
                        <input type="hidden" name="customizations[<?php echo $item['id']; ?>]" id="custom_<?php echo $item['id']; ?>" value="">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-summary" id="orderSummary" style="display: none;">
                <h3>Order Summary</h3>
                <div id="summaryItems"></div>
                <div class="summary-total" id="summaryTotal"></div>
            </div>
            
            <button class="submit-btn" type="submit" id="submitBtn" disabled>
                Place Order
            </button>
        </form>
    </div>
</div>

<script>
// JavaScript for quantity controls and order summary
function changeQuantity(itemId, change) {
    const input = document.getElementById('qty_' + itemId);
    const newValue = Math.max(0, Math.min(10, parseInt(input.value) + change));
    input.value = newValue;
    updateOrderSummary();
}

function updateOrderSummary() {
    const quantities = document.querySelectorAll('.quantity-input');
    const summaryDiv = document.getElementById('orderSummary');
    const summaryItems = document.getElementById('summaryItems');
    const summaryTotal = document.getElementById('summaryTotal');
    const submitBtn = document.getElementById('submitBtn');
    
    let hasItems = false;
    let total = 0;
    let summaryHTML = '';
    
    quantities.forEach(input => {
        const quantity = parseInt(input.value);
        if (quantity > 0) {
            hasItems = true;
            const itemId = input.name.match(/\[(\d+)\]/)[1];
            const menuItem = input.closest('.menu-item');
            const itemName = menuItem.querySelector('.food-name').textContent;
            const itemPrice = parseFloat(menuItem.querySelector('.food-price').textContent.replace('€', ''));
            const itemTotal = quantity * itemPrice;
            total += itemTotal;
            
            summaryHTML += `
                <div class="summary-item">
                    <span>${itemName} x ${quantity}</span>
                    <span>€${itemTotal.toFixed(2)}</span>
                </div>
            `;
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

function openCustomize(itemId) {
    // Placeholder for customization modal - will be implemented next
    alert('Customization feature coming soon! You can specify allergies, remove ingredients, or add special requests.');
}

// Initialize order summary on page load
document.addEventListener('DOMContentLoaded', function() {
    updateOrderSummary();
});
</script>
</body>
</html> 