<?php
require_once __DIR__ . '/../includes/db.php';

// Sauce configuration - easy to modify without touching database or main logic
$sauce_config = [
    // By category - applies to all items in that category
    'categories' => [
        'zep cep' => 1,        // fries get 1 sauce
        'uzkodas' => 3,        // appetizers get 3 sauces
    ],
    // By specific item name - overrides category settings
    'items' => [
        'maizes nūjiņas' => 0,            // small fries get no sauce
    ]
];

// Function to get sauce count using the configuration
function get_sauce_count_from_config($item_name, $category_name, $config) {
    $item_lower = strtolower(trim($item_name ?? ''));
    $category_lower = strtolower(trim($category_name ?? ''));
    
    // First check if this specific item has a custom sauce count
    if (isset($config['items'][$item_lower])) {
        return $config['items'][$item_lower];
    }
    
    // Otherwise use the category default
    return $config['categories'][$category_lower] ?? 0;
}

// Helper function for backward compatibility
function get_sauce_count($item_name, $category_name, $config) {
    return get_sauce_count_from_config($item_name, $category_name, $config);
}

// Your existing code continues here...
$table_id = isset($_GET['table']) ? intval($_GET['table']) : 0;
if ($table_id < 1 || $table_id > get_table_count()) {
    die('Invalid or missing table number.');
}
$menu = get_menu_items();

// Get sauces for main food items
$sauces = [];
foreach ($menu as $item) {
    if (strtolower(trim($item['category_name'] ?? '')) === 'mērces') {
        $sauces[] = $item;
    }
}

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

// Simple ordering: drinks last, alcoholic drinks at bottom
$ordered_categories = [];
$drink_categories = [];
$alcoholic_categories = [];

foreach ($categories as $cat_name => $items) {
    $cat_lower = strtolower(trim($cat_name));
    
    if ($cat_lower === 'alkoholiskie dzērieni') {
        // Alcoholic drinks go last of all
        $alcoholic_categories[$cat_name] = $items;
    } elseif (in_array($cat_lower, ['dzērieni', 'bezalkoholiskie dzērieni', 'bāra uzkodas'])) {
        // Other drink categories go after food but before alcoholic
        $drink_categories[$cat_name] = $items;
    } else {
        // Food categories go first
        $ordered_categories[$cat_name] = $items;
    }
}

// Combine in the desired order: Food -> Drinks -> Alcoholic Drinks
$categories = array_merge($ordered_categories, $drink_categories, $alcoholic_categories);

// Function to check if item is alcoholic
function is_alcoholic_item($category_name) {
    return strtolower(trim($category_name ?? '')) === 'alkoholiskie dzērieni';
}

// Updated function to check if item needs sauce options
function is_main_food_item($item_name, $category_name, $config) {
    return get_sauce_count_from_config($item_name, $category_name, $config) > 0;
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
    <!-- Your existing styles remain the same -->
    <style>
        /* All your existing CSS styles remain unchanged */
        .food-image::before {
            display: none !important;
            content: none !important;
        }
        .food-image:before {
            display: none !important;
            content: none !important;
        }
        
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
        
        .category-title.alcoholic-category {
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
            padding-left: 15px;
        }
        
        .category-title.alcoholic-category::after {
            content: " 🍷";
        }
        
        .sauce-instances {
            margin-top: 12px;
            padding: 12px;
            background: #4b5563;
            border-radius: 6px;
            border-left: 3px solid #e67e22;
        }
        
        .sauce-instance {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            gap: 8px;
        }
        
        .sauce-instance:last-child {
            margin-bottom: 0;
        }
        
        .sauce-instance-label {
            font-weight: 600;
            font-size: 0.9em;
            color: #fff;
            min-width: 60px;
        }
        
        .sauce-select {
            flex: 1;
            padding: 4px 8px;
            border: 1px solid #e67e22;
            border-radius: 4px;
            font-size: 0.9em;
            background: #4b5563;
        }
        
        .sauce-select:focus {
            border-color: #e67e22;
            outline: none;
        }
        
        .sauce-instances-title {
            font-size: 0.9em;
            font-weight: 600;
            margin-bottom: 8px;
            color: #e67e22;
        }
        
        /* New styles for multiple sauce instances */
        .sauce-item-group {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #666;
        }
        
        .sauce-item-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .sauce-item-title {
            font-weight: bold;
            margin-bottom: 6px;
            color: #e67e22;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Galds <?php echo $table_id; ?></h1>
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
                        $sauce_count = get_sauce_count($item['name'], $item['category_name'], $sauce_config);
                        $is_main_food = $sauce_count > 0;
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
                                               onchange="updateOrderSummary()"
                                               data-is-main-food="<?php echo $is_main_food ? '1' : '0'; ?>"
                                               data-sauce-count="<?php echo $sauce_count; ?>">
                                        <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                    <button type="button" class="customize-btn" id="customize_<?php echo $item['id']; ?>" onclick="openCustomize(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                        Pielāgot
                                    </button>
                                </div>
                                
                                <!-- Sauce selection for main food items -->
                                <?php if ($is_main_food): ?>
                                <div class="sauce-instances" id="sauceInstances_<?php echo $item['id']; ?>" style="display: none;">
                                    <div class="sauce-instances-title">Mērces izvēle:</div>
                                    <div id="sauceInstancesContainer_<?php echo $item['id']; ?>"></div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Hidden customization fields -->
                                <input type="hidden" name="customizations[<?php echo $item['id']; ?>]" id="custom_<?php echo $item['id']; ?>" value="">
                                <?php if ($is_main_food): ?>
                                <input type="hidden" name="sauces[<?php echo $item['id']; ?>]" id="sauces_<?php echo $item['id']; ?>" value="">
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Display uncategorized items (unchanged) -->
            <?php if (!empty($uncategorized)): ?>
            <div class="category-section" data-category="Other Items">
                <h2 class="category-title">Citi ēdieni</h2>
                <div class="menu-grid">
                    <?php foreach ($uncategorized as $item): 
                        $sauce_count = get_sauce_count($item['name'], '', $sauce_config);
                        $is_main_food = $sauce_count > 0;
                    ?>
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
                                           onchange="updateOrderSummary()"
                                           data-is-main-food="<?php echo $is_main_food ? '1' : '0'; ?>"
                                           data-sauce-count="<?php echo $sauce_count; ?>">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                                </div>
                                <button type="button" class="customize-btn" id="customize_<?php echo $item['id']; ?>" onclick="openCustomize(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                    Pielāgot
                                </button>
                            </div>
                            
                            <!-- Sauce selection for main food items -->
                            <?php if ($is_main_food): ?>
                            <div class="sauce-instances" id="sauceInstances_<?php echo $item['id']; ?>" style="display: none;">
                                <div class="sauce-instances-title">Mērces izvēle:</div>
                                <div id="sauceInstancesContainer_<?php echo $item['id']; ?>"></div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Hidden customization fields -->
                            <input type="hidden" name="customizations[<?php echo $item['id']; ?>]" id="custom_<?php echo $item['id']; ?>" value="">
                            <?php if ($is_main_food): ?>
                            <input type="hidden" name="sauces[<?php echo $item['id']; ?>]" id="sauces_<?php echo $item['id']; ?>" value="">
                            <?php endif; ?>
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

<!-- Your existing customization modal remains unchanged -->
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
let sauceSelections = {};

// Available sauces from PHP
const availableSauces = <?php echo json_encode($sauces); ?>;

// Updated JavaScript for flexible sauce handling
function changeQuantity(itemId, change) {
    const input = document.getElementById('qty_' + itemId);
    const newValue = Math.max(0, Math.min(10, parseInt(input.value) + change));
    const oldValue = parseInt(input.value);
    input.value = newValue;
    
    // Handle sauce instances for main food items
    if (input.dataset.isMainFood === '1') {
        updateSauceInstances(itemId, newValue, oldValue);
    }
    
    updateOrderSummary();
}

function updateSauceInstances(itemId, newQuantity, oldQuantity) {
    const input = document.getElementById('qty_' + itemId);
    const sauceInstancesDiv = document.getElementById('sauceInstances_' + itemId);
    const container = document.getElementById('sauceInstancesContainer_' + itemId);
    
    // Get sauce count from data attribute
    const sauceCount = parseInt(input.dataset.sauceCount) || 1;
    
    if (newQuantity > 0) {
        sauceInstancesDiv.style.display = 'block';
        
        if (!sauceSelections[itemId]) {
            sauceSelections[itemId] = [];
        }
        
        // Each quantity gets its own set of sauces
        const totalSauceInstances = newQuantity * sauceCount;
        
        // Adjust array length
        if (totalSauceInstances > sauceSelections[itemId].length) {
            for (let i = sauceSelections[itemId].length; i < totalSauceInstances; i++) {
                sauceSelections[itemId][i] = '';
            }
        } else if (totalSauceInstances < sauceSelections[itemId].length) {
            sauceSelections[itemId] = sauceSelections[itemId].slice(0, totalSauceInstances);
        }
        
        rebuildSauceSelectionUI(itemId, newQuantity, sauceCount);
    } else {
        sauceInstancesDiv.style.display = 'none';
        sauceSelections[itemId] = [];
    }
    
    updateSauceHiddenField(itemId);
}

function rebuildSauceSelectionUI(itemId, quantity, sauceCount) {
    const container = document.getElementById('sauceInstancesContainer_' + itemId);
    container.innerHTML = '';
    
    for (let i = 0; i < quantity; i++) {
        // Create a group for each item quantity
        const itemGroup = document.createElement('div');
        itemGroup.className = 'sauce-item-group';
        
        if (quantity > 1) {
            const groupTitle = document.createElement('div');
            groupTitle.className = 'sauce-item-title';
            groupTitle.textContent = `${i + 1}. ēdiens:`;
            itemGroup.appendChild(groupTitle);
        }
        
        // Create sauce selectors for this item
        for (let j = 0; j < sauceCount; j++) {
            const sauceIndex = i * sauceCount + j;
            
            const instanceDiv = document.createElement('div');
            instanceDiv.className = 'sauce-instance';
            
            const label = document.createElement('div');
            label.className = 'sauce-instance-label';
            label.textContent = `${j + 1}. mērce:`;
            
            const select = document.createElement('select');
            select.className = 'sauce-select';
            select.onchange = function() {
                sauceSelections[itemId][sauceIndex] = this.value;
                updateSauceHiddenField(itemId);
                updateOrderSummary();
            };
            
            // Add "No sauce" option
            const noSauceOption = document.createElement('option');
            noSauceOption.value = '';
            noSauceOption.textContent = 'Bez mērces';
            select.appendChild(noSauceOption);
            
            // Add sauce options
            availableSauces.forEach(sauce => {
                const option = document.createElement('option');
                option.value = sauce.id;
                option.textContent = sauce.name;
                if (sauceSelections[itemId][sauceIndex] == sauce.id) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            
            instanceDiv.appendChild(label);
            instanceDiv.appendChild(select);
            itemGroup.appendChild(instanceDiv);
        }
        
        container.appendChild(itemGroup);
    }
}

function updateSauceHiddenField(itemId) {
    const hiddenField = document.getElementById('sauces_' + itemId);
    if (hiddenField) {
        hiddenField.value = JSON.stringify(sauceSelections[itemId] || []);
    }
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
                customizationInfo += '<br><small style="color: #e67e22;">✓ Pielāgots</small>';
            }
            
            // Add sauce info for main food items
            if (input.dataset.isMainFood === '1' && sauceSelections[itemId]) {
                const sauceCount = parseInt(input.dataset.sauceCount) || 1;
                const sauceInfo = [];
                
                for (let i = 0; i < quantity; i++) {
                    const itemSauces = [];
                    for (let j = 0; j < sauceCount; j++) {
                        const sauceIndex = i * sauceCount + j;
                        const sauceId = sauceSelections[itemId][sauceIndex];
                        
                        if (sauceId) {
                            const sauce = availableSauces.find(s => s.id == sauceId);
                            if (sauce) {
                                itemSauces.push(sauce.name);
                            }
                        } else {
                            itemSauces.push('Bez mērces');
                        }
                    }
                    
                    if (quantity > 1) {
                        sauceInfo.push(`${i + 1}: ${itemSauces.join(', ')}`);
                    } else {
                        sauceInfo.push(itemSauces.join(', '));
                    }
                }
                
                if (sauceInfo.length > 0) {
                    customizationInfo += '<br><small style="color: #27ae60;">🍯 ' + sauceInfo.join(' | ') + '</small>';
                }
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

// All your existing JavaScript functions remain the same (openCustomize, closeCustomize, saveCustomizations, etc.)
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