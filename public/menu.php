<?php
require_once __DIR__ . '/../includes/db.php';

// Sauce configuration - easy to modify without touching database or main logic
$sauce_config = [
    // By category - applies to all items in that category
    'categories' => [
        'uzkodas' => 0,        // appetizers get 0 sauces
    ],
    // By specific item name - overrides category settings
    'items' => [
        'uzkodu plate' => 3,            // uzkodu plate gets 3 sauces
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

function get_item_sauce_count($item, $config) {
    if (isset($item['included_sauces'])) {
        $count = intval($item['included_sauces']);
        if ($count > 0) {
            return $count;
        }
    }
    return get_sauce_count_from_config($item['name'], $item['category_name'], $config);
}

// Your existing code continues here...
$table_id = isset($_GET['table']) ? intval($_GET['table']) : 0;
if ($table_id < 1 || $table_id > get_table_count()) {
    die('Invalid or missing table number.');
}
$menu = get_menu_items();

// Get sauces for main food items
$sauces = [];
$sauce_category_variants = ['mērcītes', 'mercites', 'mērces', 'merces', 'sauces'];
foreach ($menu as $item) {
    $cat_lower = strtolower(trim($item['category_name'] ?? ''));
    if (in_array($cat_lower, $sauce_category_variants)) {
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
    } elseif (in_array($cat_lower, ['dzērieni', 'bezalkoholiskie dzērieni','bezalkoholiskie kokteiļi', 'bāra uzkodas'])) {
        // Other drink categories go after food but before alcoholic
        $drink_categories[$cat_name] = $items;
    } else {
        // Food categories go first
        $ordered_categories[$cat_name] = $items;
    }
}

// Combine in the desired order: Food -> Drinks -> Alcoholic Drinks
$categories = array_merge($ordered_categories, $drink_categories, $alcoholic_categories);

$drink_category_names = array_map('strtolower', get_drink_categories());

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
            padding: 16px;
            background: linear-gradient(135deg, #fff5e6 0%, #ffe8cc 100%);
            border-radius: 8px;
            border-left: 4px solid #e67e22;
            box-shadow: 0 2px 8px rgba(230, 126, 34, 0.1);
        }
        
        .sauce-instance {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
            padding: 8px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .sauce-instance:hover {
            background: rgba(255, 255, 255, 0.9);
        }
        
        .sauce-instance:last-child {
            margin-bottom: 0;
        }
        
        .sauce-instance-label {
            font-weight: 700;
            font-size: 0.95em;
            color: #d35400;
            min-width: 80px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .sauce-instance-label::before {
            content: "🍯";
            font-size: 1.1em;
        }
        
        .sauce-select {
            flex: 1;
            padding: 8px 12px;
            border: 2px solid #e67e22;
            border-radius: 6px;
            font-size: 0.95em;
            background: #fff;
            color: #2c3e50;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .sauce-select:hover {
            border-color: #d35400;
            background: #fffbf0;
        }
        
        .sauce-select:focus {
            border-color: #d35400;
            outline: none;
            box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.2);
        }
        
        .sauce-instances-title {
            font-size: 1em;
            font-weight: 700;
            margin-bottom: 12px;
            color: #d35400;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .sauce-instances-title::before {
            content: "🍯";
            font-size: 1.2em;
        }
        
        /* New styles for multiple sauce instances */
        .sauce-item-group {
            margin-bottom: 14px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 6px;
            border: 1px solid rgba(230, 126, 34, 0.2);
        }
        
        .sauce-item-group:last-child {
            margin-bottom: 0;
        }
        
        .sauce-item-title {
            font-weight: 700;
            margin-bottom: 10px;
            color: #d35400;
            font-size: 0.95em;
            padding-bottom: 6px;
            border-bottom: 2px solid rgba(230, 126, 34, 0.3);
        }
        
        /* Size options styles */
        .size-options {
            display: flex;
            gap: 8px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        
        .size-option {
            flex: 1;
            min-width: 120px;
            padding: 12px 16px;
            border: 2px solid #3498db;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            background: #fff;
            box-shadow: 0 2px 4px rgba(52, 152, 219, 0.1);
            position: relative;
        }
        
        .size-option:hover {
            background: #ebf5fb;
            border-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
        }
        
        .size-option.selected {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border-color: #21618c;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
        }
        
        .size-option.selected::after {
            content: "✓";
            position: absolute;
            top: 4px;
            right: 8px;
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .size-name {
            display: block;
            font-weight: 700;
            font-size: 1em;
            margin-bottom: 6px;
        }
        
        .size-price {
            display: block;
            font-size: 0.9em;
            opacity: 0.85;
            font-weight: 600;
        }
        
        .size-option.selected .size-price {
            opacity: 1;
        }
        
        .size-options-preview {
            display: flex;
            gap: 12px;
            margin: 12px 0;
            flex-wrap: wrap;
            font-size: 0.95em;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px dashed #3498db;
        }
        
        .size-preview {
            color: #2980b9;
            font-weight: 700;
            padding: 6px 12px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 4px;
        }
        
        .size-instances {
            margin-top: 12px;
            padding: 16px;
            background: linear-gradient(135deg, #e8f4f8 0%, #d6eaf8 100%);
            border-radius: 8px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.1);
        }
        
        .size-instances-title {
            font-size: 1em;
            font-weight: 700;
            margin-bottom: 12px;
            color: #2980b9;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .size-instances-title::before {
            content: "📏";
            font-size: 1.2em;
        }
        
        .size-item-group {
            margin-bottom: 14px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 6px;
            border: 1px solid rgba(52, 152, 219, 0.2);
        }
        
        .size-item-group:last-child {
            margin-bottom: 0;
        }
        
        .size-item-title {
            font-weight: 700;
            margin-bottom: 10px;
            color: #2980b9;
            font-size: 0.95em;
            padding-bottom: 6px;
            border-bottom: 2px solid rgba(52, 152, 219, 0.3);
        }
        
        .size-instance {
            display: flex;
            gap: 10px;
            margin-bottom: 0;
            flex-wrap: wrap;
        }
        
        .filters {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .station-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .station-filter-btn {
            border: 1px solid #d1d5db;
            background: #f3f4f6;
            color: #1f2937;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .station-filter-btn:hover {
            background: #e5e7eb;
        }
        
        .station-filter-btn.active {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
            box-shadow: 0 8px 15px rgba(37, 99, 235, 0.2);
        }
        
        .category-filter label {
            font-weight: 600;
            margin-right: 8px;
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
        <div class="filters">
            <div class="station-filters">
                <button type="button" class="station-filter-btn active" data-station-filter="all">Viss</button>
                <button type="button" class="station-filter-btn" data-station-filter="kitchen">Virtuve</button>
                <button type="button" class="station-filter-btn" data-station-filter="bar">Bārs</button>
            </div>
        </div>
        <form method="post" action="submit_order.php" id="orderForm">
            <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
            
            <!-- Display categorized items -->
            <?php foreach ($categories as $category_name => $category_items): 
                $category_station = in_array(strtolower(trim($category_name)), $drink_category_names) ? 'bar' : 'kitchen';
            ?>
            <div class="category-section" data-category="<?php echo htmlspecialchars($category_name); ?>" data-station="<?php echo $category_station; ?>">
                <h2 class="category-title <?php echo is_alcoholic_item($category_name) ? 'alcoholic-category' : ''; ?>">
                    <?php echo htmlspecialchars($category_name); ?>
                </h2>
                <div class="menu-grid">
                    <?php foreach ($category_items as $item): 
                        $is_alcoholic = is_alcoholic_item($item['category_name']);
                        $sauce_count = get_item_sauce_count($item, $sauce_config);
                        $is_main_food = $sauce_count > 0;
                        // Parse size options
                        $size_options = null;
                        $has_sizes = false;
                        if (isset($item['size_options']) && !empty($item['size_options'])) {
                            $size_options = json_decode($item['size_options'], true);
                            $has_sizes = ($size_options && (isset($size_options['small']) || isset($size_options['large'])));
                        }
                    ?>
                    <div class="menu-item <?php echo $is_alcoholic ? 'alcoholic' : ''; ?>" data-item-id="<?php echo $item['id']; ?>" data-has-sizes="<?php echo $has_sizes ? '1' : '0'; ?>">
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
                            <?php if ($has_sizes): ?>
                                <div class="size-options-preview" id="sizeOptionsPreview_<?php echo $item['id']; ?>" data-small-price="<?php echo isset($size_options['small']) ? $size_options['small']['price'] : ''; ?>" data-large-price="<?php echo isset($size_options['large']) ? $size_options['large']['price'] : ''; ?>">
                                    <?php if (isset($size_options['small'])): ?>
                                        <span class="size-preview"><?php echo htmlspecialchars($size_options['small']['name']); ?>: €<?php echo number_format($size_options['small']['price'], 2); ?></span>
                                    <?php endif; ?>
                                    <?php if (isset($size_options['large'])): ?>
                                        <span class="size-preview"><?php echo htmlspecialchars($size_options['large']['name']); ?>: €<?php echo number_format($size_options['large']['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="food-price">€<?php echo number_format($item['price'], 2); ?></div>
                            <?php endif; ?>
                            
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
                                
                                <!-- Size selection for items with sizes -->
                                <?php if ($has_sizes): ?>
                                <div class="size-instances" id="sizeInstances_<?php echo $item['id']; ?>" style="display: none;">
                                    <div class="size-instances-title">Izmēru izvēle:</div>
                                    <div id="sizeInstancesContainer_<?php echo $item['id']; ?>"></div>
                                </div>
                                <?php endif; ?>
                                
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
                                <?php if ($has_sizes): ?>
                                <input type="hidden" name="sizes[<?php echo $item['id']; ?>]" id="sizes_<?php echo $item['id']; ?>" value="">
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
            <div class="category-section" data-category="Other Items" data-station="kitchen">
                <h2 class="category-title">Citi ēdieni</h2>
                <div class="menu-grid">
                    <?php foreach ($uncategorized as $item): 
                        $sauce_count = get_item_sauce_count($item, $sauce_config);
                        $is_main_food = $sauce_count > 0;
                        // Parse size options
                        $size_options = null;
                        $has_sizes = false;
                        if (isset($item['size_options']) && !empty($item['size_options'])) {
                            $size_options = json_decode($item['size_options'], true);
                            $has_sizes = ($size_options && (isset($size_options['small']) || isset($size_options['large'])));
                        }
                    ?>
                    <div class="menu-item" data-item-id="<?php echo $item['id']; ?>" data-has-sizes="<?php echo $has_sizes ? '1' : '0'; ?>">
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
                            <?php if ($has_sizes): ?>
                                <div class="size-options" id="sizeOptions_<?php echo $item['id']; ?>">
                                    <?php if (isset($size_options['small'])): ?>
                                        <div class="size-option" data-size="small" data-price="<?php echo $size_options['small']['price']; ?>">
                                            <span class="size-name"><?php echo htmlspecialchars($size_options['small']['name']); ?></span>
                                            <span class="size-price">€<?php echo number_format($size_options['small']['price'], 2); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($size_options['large'])): ?>
                                        <div class="size-option" data-size="large" data-price="<?php echo $size_options['large']['price']; ?>">
                                            <span class="size-name"><?php echo htmlspecialchars($size_options['large']['name']); ?></span>
                                            <span class="size-price">€<?php echo number_format($size_options['large']['price'], 2); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="food-price">€<?php echo number_format($item['price'], 2); ?></div>
                            <?php endif; ?>
                            
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
                            <?php if ($has_sizes): ?>
                            <input type="hidden" name="sizes[<?php echo $item['id']; ?>]" id="sizes_<?php echo $item['id']; ?>" value="">
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
let sizeSelections = {}; // Store selected sizes for each item

// Available sauces from PHP
const availableSauces = <?php echo json_encode($sauces); ?>;
console.log('Available sauces:', availableSauces);
if (availableSauces.length === 0) {
    console.warn('No sauces found! Make sure you have items in the "mērcītes" category.');
}

function updateSizeInstances(itemId, newQuantity, oldQuantity) {
    const menuItem = document.querySelector(`[data-item-id="${itemId}"]`);
    const sizeInstancesDiv = document.getElementById('sizeInstances_' + itemId);
    const container = document.getElementById('sizeInstancesContainer_' + itemId);
    
    if (!sizeInstancesDiv || !container) return;
    
    if (newQuantity > 0) {
        sizeInstancesDiv.style.display = 'block';
        
        if (!sizeSelections[itemId]) {
            sizeSelections[itemId] = [];
        }
        
        // Adjust array length
        if (newQuantity > sizeSelections[itemId].length) {
            for (let i = sizeSelections[itemId].length; i < newQuantity; i++) {
                sizeSelections[itemId][i] = '';
            }
        } else if (newQuantity < sizeSelections[itemId].length) {
            sizeSelections[itemId] = sizeSelections[itemId].slice(0, newQuantity);
        }
        
        rebuildSizeSelectionUI(itemId, newQuantity, menuItem);
    } else {
        sizeInstancesDiv.style.display = 'none';
        sizeSelections[itemId] = [];
    }
    
    updateSizeHiddenField(itemId);
}

function rebuildSizeSelectionUI(itemId, quantity, menuItem) {
    const container = document.getElementById('sizeInstancesContainer_' + itemId);
    if (!container) return;
    
    container.innerHTML = '';
    
    // Get size options from the preview data attributes
    const preview = menuItem.querySelector('.size-options-preview');
    if (!preview) return;
    
    const sizeOptions = [];
    const smallPrice = preview.getAttribute('data-small-price');
    const largePrice = preview.getAttribute('data-large-price');
    
    if (smallPrice && smallPrice !== '') {
        sizeOptions.push({
            size: 'small',
            name: 'Parastais',
            price: parseFloat(smallPrice)
        });
    }
    
    if (largePrice && largePrice !== '') {
        sizeOptions.push({
            size: 'large',
            name: 'Lielais',
            price: parseFloat(largePrice)
        });
    }
    
    for (let i = 0; i < quantity; i++) {
        // Create a group for each item quantity
        const itemGroup = document.createElement('div');
        itemGroup.className = 'size-item-group';
        
        if (quantity > 1) {
            const groupTitle = document.createElement('div');
            groupTitle.className = 'size-item-title';
            groupTitle.textContent = `${i + 1}. ēdiens:`;
            itemGroup.appendChild(groupTitle);
        }
        
        // Create size selectors for this item
        const instanceDiv = document.createElement('div');
        instanceDiv.className = 'size-instance';
        
        sizeOptions.forEach(sizeOpt => {
            const sizeButton = document.createElement('div');
            sizeButton.className = 'size-option';
            sizeButton.setAttribute('data-size', sizeOpt.size);
            sizeButton.setAttribute('data-price', sizeOpt.price);
            sizeButton.setAttribute('data-instance', i);
            
            const sizeName = document.createElement('span');
            sizeName.className = 'size-name';
            sizeName.textContent = sizeOpt.name;
            
            const sizePrice = document.createElement('span');
            sizePrice.className = 'size-price';
            sizePrice.textContent = `€${sizeOpt.price.toFixed(2)}`;
            
            sizeButton.appendChild(sizeName);
            sizeButton.appendChild(sizePrice);
            
            // Check if this size is selected for this instance
            if (sizeSelections[itemId] && sizeSelections[itemId][i] === sizeOpt.size) {
                sizeButton.classList.add('selected');
            }
            
            sizeButton.addEventListener('click', function() {
                // Remove selected from all sizes in this instance
                instanceDiv.querySelectorAll('.size-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add selected to clicked option
                this.classList.add('selected');
                
                // Store selection
                if (!sizeSelections[itemId]) {
                    sizeSelections[itemId] = [];
                }
                sizeSelections[itemId][i] = this.getAttribute('data-size');
                
                updateSizeHiddenField(itemId);
                updateOrderSummary();
            });
            
            instanceDiv.appendChild(sizeButton);
        });
        
        itemGroup.appendChild(instanceDiv);
        container.appendChild(itemGroup);
    }
}

function updateSizeHiddenField(itemId) {
    const hiddenField = document.getElementById('sizes_' + itemId);
    if (hiddenField) {
        hiddenField.value = JSON.stringify(sizeSelections[itemId] || []);
    }
}

// Updated JavaScript for flexible sauce handling
function changeQuantity(itemId, change) {
    const input = document.getElementById('qty_' + itemId);
    const menuItem = input.closest('.menu-item');
    const hasSizes = menuItem.getAttribute('data-has-sizes') === '1';
    
    const newValue = Math.max(0, Math.min(10, parseInt(input.value) + change));
    const oldValue = parseInt(input.value);
    input.value = newValue;
    
    // Handle size instances for items with sizes
    if (hasSizes) {
        updateSizeInstances(itemId, newValue, oldValue);
    }
    
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
            const itemId = input.name.match(/\[(\d+)\]/)[1];
            const menuItem = input.closest('.menu-item');
            const itemName = menuItem.querySelector('.food-name').textContent;
            const hasSizes = menuItem.getAttribute('data-has-sizes') === '1';
            
            // Get prices - handle multiple sizes per quantity
            let itemTotal = 0;
            let sizeInfo = '';
            const preview = menuItem.querySelector('.size-options-preview');
            
            if (hasSizes && sizeSelections[itemId] && sizeSelections[itemId].length > 0) {
                // Calculate total for each quantity with its selected size
                const sizeDetails = [];
                for (let i = 0; i < quantity; i++) {
                    const selectedSize = sizeSelections[itemId][i];
                    if (selectedSize) {
                        let sizePrice = 0;
                        let sizeName = '';
                        
                        if (selectedSize === 'small' && preview) {
                            sizePrice = parseFloat(preview.getAttribute('data-small-price') || 0);
                            sizeName = 'Parastais';
                        } else if (selectedSize === 'large' && preview) {
                            sizePrice = parseFloat(preview.getAttribute('data-large-price') || 0);
                            sizeName = 'Lielais';
                        }
                        
                        if (sizePrice > 0) {
                            itemTotal += sizePrice;
                            if (quantity > 1) {
                                sizeDetails.push(`${i + 1}: ${sizeName}`);
                            } else {
                                sizeDetails.push(sizeName);
                            }
                        }
                    }
                }
                
                if (sizeDetails.length > 0) {
                    sizeInfo = ` (${sizeDetails.join(', ')})`;
                }
            } else if (!hasSizes) {
                const priceElement = menuItem.querySelector('.food-price');
                if (priceElement) {
                    const itemPrice = parseFloat(priceElement.textContent.replace('€', ''));
                    itemTotal = quantity * itemPrice;
                }
            }
            
            // Only add to summary if we have valid prices
            if (itemTotal > 0) {
                hasItems = true;
                total += itemTotal;
                
                // Add customization info if exists
                let customizationInfo = '';
                if (customizations[itemId] && Object.keys(customizations[itemId]).length > 0) {
                    customizationInfo += '<br><small style="color: #e67e22;">✓ Pielāgots</small>';
                }
                
                // Add size info
                if (sizeInfo) {
                    customizationInfo += '<br><small style="color: #3498db;">📏' + sizeInfo + '</small>';
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
                
                // Check if all sizes are selected for items with sizes
                let allSizesSelected = true;
                if (hasSizes) {
                    for (let i = 0; i < quantity; i++) {
                        if (!sizeSelections[itemId] || !sizeSelections[itemId][i]) {
                            allSizesSelected = false;
                            break;
                        }
                    }
                }
                
                // Only add to summary if all sizes are selected (or no sizes)
                if (allSizesSelected) {
                    summaryHTML += `
                        <div class="summary-item">
                            <span>${itemName}${sizeInfo} x ${quantity}${customizationInfo}</span>
                            <span>€${itemTotal.toFixed(2)}</span>
                        </div>
                    `;
                }
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

const stationFilterButtons = document.querySelectorAll('.station-filter-btn');
let activeStationFilter = 'all';

function applyFilters() {
    document.querySelectorAll('.category-section').forEach(section => {
        const station = section.getAttribute('data-station') || 'kitchen';
        const stationMatches = activeStationFilter === 'all' || station === activeStationFilter;
        section.style.display = stationMatches ? '' : 'none';
    });
}

stationFilterButtons.forEach(button => {
    button.addEventListener('click', function() {
        activeStationFilter = this.dataset.stationFilter;
        stationFilterButtons.forEach(btn => btn.classList.toggle('active', btn === this));
        applyFilters();
    });
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateOrderSummary();
    applyFilters();
});
</script>
</body>
</html>