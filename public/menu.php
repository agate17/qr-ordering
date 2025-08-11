<?php
require_once __DIR__ . '/../includes/db.php';
$table_id = isset($_GET['table']) ? intval($_GET['table']) : 0;
if ($table_id < 1 || $table_id > get_table_count()) {
    die('Invalid or missing table number.');
}
$menu = get_menu_items();

// Group items by category
$categories = [];
$uncategorized = [];

foreach ($menu as $item) {
    if (!empty($item['category_name'])) {
        $categories[$item['category_name']][] = $item;
    } else {
        $uncategorized[] = $item;
    }
}

// Function to check if item is alcoholic
function is_alcoholic_item($category_name) {
    return strtolower(trim($category_name ?? '')) === 'alkoholiskie dzērieni';
}
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table <?php echo $table_id; ?> - Menu</title>
    <link rel="stylesheet" type="text/css" href="assets/css/menu.css?v=<?php echo time(); ?>">
    <!-- Bootstrap Icons CDN for filter icon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* Force remove any placeholder icons */
        .food-image::before {
            display: none !important;
            content: none !important;
        }
        .food-image:before {
            display: none !important;
            content: none !important;
        }
        
        /* Styles for alcoholic items */
        .menu-item.alcoholic {
            position: relative;
            opacity: 0.8;
        }
        
        .menu-item.alcoholic::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            border-radius: inherit;
            pointer-events: none;
        }
        
        .alcoholic-notice {
            background: #e74c3c;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
            text-align: center;
            margin-top: 10px;
            box-shadow: 0 2px 4px rgba(231, 76, 60, 0.2);
        }
        
        .alcoholic-notice i {
            margin-right: 5px;
        }
        
        .item-controls.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .item-controls.disabled .quantity-btn,
        .item-controls.disabled .quantity-input,
        .item-controls.disabled .customize-btn {
            cursor: not-allowed;
            background: #bdc3c7;
        }
        
        /* Add visual indicator to category title */
        .category-title.alcoholic-category {
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
            padding-left: 15px;
        }
        
        .category-title.alcoholic-category::after {
            content: " 🍷";
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Galds <?php echo $table_id; ?></h1>
        <div class="subtitle">Izvēlies kādu no gardajiem ēdieniem! <br>
        <strong> !! Alkoholisko dzērienu iegādāšanas notiek tikai pie bāra !!
        </strong>
        </div>
    </div>
    
    <div class="menu-content">
        <!-- Category Filter Dropdown -->
        <div style="margin-bottom: 24px; text-align: right;">
            <label for="categoryFilter" style="font-weight: 600; margin-right: 8px;">
                <i class="bi bi-filter" style="font-size:1.2em; vertical-align:middle; margin-right:4px;"></i>
                izvēlēties kategoriju:
            </label>
            <select id="categoryFilter" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ccc;">
                <option value="all">Viss</option>
                <?php foreach (array_keys($categories) as $category_name): ?>
                    <option value="<?php echo htmlspecialchars($category_name); ?>"><?php echo htmlspecialchars($category_name); ?></option>
                <?php endforeach; ?>
                <?php if (!empty($uncategorized)): ?>
                    <option value="Other Items">Citi ēdieni</option>
                <?php endif; ?>
            </select>
        </div>
        <form method="post" action="submit_order.php" id="orderForm">
            <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
            
            <!-- Display categorized items -->
            <?php foreach ($categories as $category_name => $category_items): ?>
            <div class="category-section" data-category="<?php echo htmlspecialchars($category_name); ?>">
                <h2 class="category-title <?php echo is_alcoholic_item($category_name) ? 'alcoholic-category' : ''; ?>">
                    <?php echo htmlspecialchars($category_name); ?>
                </h2>
                <div class="menu-grid">
                    <?php foreach ($category_items as $item): 
                        $is_alcoholic = is_alcoholic_item($item['category_name']);
                    ?>
                    <div class="menu-item <?php echo $is_alcoholic ? 'alcoholic' : ''; ?>">
                        <div class="food-image<?php if (!empty($item['image_path'])) echo ' has-image'; ?>">
                            <?php if (!empty($item['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="food-details">
                            <div class="food-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="food-description">
                                <?php echo htmlspecialchars($item['description']); ?>
                            </div>
                            <div class="food-price">€<?php echo number_format($item['price'], 2); ?></div>
                            
                            <?php if ($is_alcoholic): ?>
                                <!-- Alcoholic items - show notice instead of controls -->
                                <div class="alcoholic-notice">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Pieejams tikai pie bāra
                                </div>
                            <?php else: ?>
                                <!-- Regular items - normal controls -->
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
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Display uncategorized items -->
            <?php if (!empty($uncategorized)): ?>
            <div class="category-section" data-category="Other Items">
                <h2 class="category-title">Citi ēdieni</h2>
                <div class="menu-grid">
                    <?php foreach ($uncategorized as $item): ?>
                    <div class="menu-item">
                        <div class="food-image<?php if (!empty($item['image_path'])) echo ' has-image'; ?>">
                            <?php if (!empty($item['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="food-details">
                            <div class="food-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="food-description">
                                <?php echo htmlspecialchars($item['description']); ?>
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
                                    Pielāgot
                                </button>
                            </div>
                            
                            <!-- Hidden customization fields -->
                            <input type="hidden" name="customizations[<?php echo $item['id']; ?>]" id="custom_<?php echo $item['id']; ?>" value="">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="order-summary" id="orderSummary" style="display: none;">
                <h3>Pasūtījuma kopsavilkums:</h3>
                <div id="summaryItems"></div>
                <div class="summary-total" id="summaryTotal"></div>
            </div>
            
            <button class="submit-btn" type="submit" id="submitBtn" disabled>
                Pasūtīt!
            </button>
        </form>
    </div>
</div>

<!-- Customization Modal -->
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

// Category filter logic
const categoryFilter = document.getElementById('categoryFilter');
categoryFilter.addEventListener('change', function() {
    const selected = this.value;
    document.querySelectorAll('.category-section').forEach(section => {
        if (selected === 'all' || section.getAttribute('data-category') === selected) {
            section.style.display = '';
        } else {
            section.style.display = 'none';
        }
    });
});
</script>
</body>
</html>