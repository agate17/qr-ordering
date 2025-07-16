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
                            <button type="button" class="customize-btn" id="customize_<?php echo $item['id']; ?>" onclick="openCustomize(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
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

<!-- Customization Modal -->
<div class="modal-overlay" id="customizeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Customize Your Order</h3>
            <button class="close-btn" onclick="closeCustomize()">&times;</button>
        </div>
        
        <div class="customization-section">
            <div class="section-title">Allergy Information</div>
            <div class="allergy-options">
                <div class="allergy-option">
                    <input type="checkbox" id="allergy_gluten" name="allergies[]" value="gluten">
                    <label for="allergy_gluten">Gluten Free</label>
                </div>
                <div class="allergy-option">
                    <input type="checkbox" id="allergy_dairy" name="allergies[]" value="dairy">
                    <label for="allergy_dairy">Dairy Free</label>
                </div>
                <div class="allergy-option">
                    <input type="checkbox" id="allergy_nuts" name="allergies[]" value="nuts">
                    <label for="allergy_nuts">No Nuts</label>
                </div>
                <div class="allergy-option">
                    <input type="checkbox" id="allergy_eggs" name="allergies[]" value="eggs">
                    <label for="allergy_eggs">No Eggs</label>
                </div>
                <div class="allergy-option">
                    <input type="checkbox" id="allergy_seafood" name="allergies[]" value="seafood">
                    <label for="allergy_seafood">No Seafood</label>
                </div>
                <div class="allergy-option">
                    <input type="checkbox" id="allergy_soy" name="allergies[]" value="soy">
                    <label for="allergy_soy">No Soy</label>
                </div>
            </div>
        </div>
        
        <div class="customization-section">
            <div class="section-title">Remove Ingredients</div>
            <div class="ingredient-removal">
                <div class="ingredient-list">
                    <div class="ingredient-item">
                        <input type="checkbox" id="remove_onions" name="remove_ingredients[]" value="onions">
                        <label for="remove_onions">Onions</label>
                    </div>
                    <div class="ingredient-item">
                        <input type="checkbox" id="remove_garlic" name="remove_ingredients[]" value="garlic">
                        <label for="remove_garlic">Garlic</label>
                    </div>
                    <div class="ingredient-item">
                        <input type="checkbox" id="remove_cheese" name="remove_ingredients[]" value="cheese">
                        <label for="remove_cheese">Cheese</label>
                    </div>
                    <div class="ingredient-item">
                        <input type="checkbox" id="remove_mushrooms" name="remove_ingredients[]" value="mushrooms">
                        <label for="remove_mushrooms">Mushrooms</label>
                    </div>
                    <div class="ingredient-item">
                        <input type="checkbox" id="remove_tomatoes" name="remove_ingredients[]" value="tomatoes">
                        <label for="remove_tomatoes">Tomatoes</label>
                    </div>
                    <div class="ingredient-item">
                        <input type="checkbox" id="remove_peppers" name="remove_ingredients[]" value="peppers">
                        <label for="remove_peppers">Peppers</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="customization-section">
            <div class="section-title">Special Requests</div>
            <div class="special-requests">
                <textarea id="specialRequests" name="special_requests" placeholder="Any special instructions, preferences, or additional requests..."></textarea>
            </div>
        </div>
        
        <div class="modal-actions">
            <button type="button" class="modal-btn cancel-btn" onclick="closeCustomize()">Cancel</button>
            <button type="button" class="modal-btn save-btn" onclick="saveCustomizations()">Save Customizations</button>
        </div>
    </div>
</div>

<script>
// Global variables
let currentItemId = null;
let customizations = {};

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
            
            // Add customization info if exists
            let customizationInfo = '';
            if (customizations[itemId] && Object.keys(customizations[itemId]).length > 0) {
                customizationInfo = '<br><small style="color: #e67e22;">✓ Customized</small>';
            }
            
            summaryHTML += `
                <div class="summary-item">
                    <span>${itemName} x ${quantity}${customizationInfo}</span>
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

function openCustomize(itemId, itemName) {
    currentItemId = itemId;
    document.getElementById('modalTitle').textContent = `Customize: ${itemName}`;
    
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
        customizeBtn.textContent = 'Customized ✓';
    } else {
        customizeBtn.classList.remove('has-customizations');
        customizeBtn.textContent = 'Customize';
    }
    
    // Update order summary
    updateOrderSummary();
    
    // Close modal
    closeCustomize();
}

// Close modal when clicking outside
document.getElementById('customizeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCustomize();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('customizeModal').classList.contains('active')) {
        closeCustomize();
    }
});

// Initialize order summary on page load
document.addEventListener('DOMContentLoaded', function() {
    updateOrderSummary();
});
</script>
</body>
</html> 