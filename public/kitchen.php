<?php
require_once __DIR__ . '/../includes/db.php';

// Handle acknowledge new order for KITCHEN ONLY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acknowledge_kitchen_order'])) {
    $oid = intval($_POST['acknowledge_kitchen_order']);
    acknowledge_kitchen_order($oid);
    header('Location: kitchen.php');
    exit;
}

// Handle mark as preparing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_preparing'])) {
    $oid = intval($_POST['mark_preparing']);
    update_order_status($oid, 'preparing');
    // Also acknowledge the kitchen order when starting to prepare
    acknowledge_kitchen_order($oid);
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

// Check for new orders to show popup - FIXED LOGIC
$has_new_orders = false;
$new_order_details = null;
foreach ($filtered_kitchen_orders as $order) {
    // Check for is_new_kitchen field (not is_new)
    if (isset($order['is_new_kitchen']) && $order['is_new_kitchen'] == 1) {
        $has_new_orders = true;
        if (!$new_order_details) { // Get details of first new order for popup
            $new_order_details = $order;
            $new_order_details['kitchen_items'] = get_kitchen_items($order['id'], $menu);
        }
        break;
    }
}
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
            <div style="display: flex; gap: 10px; align-items: center;">
                <button class="fullscreen-btn" id="fullscreenBtn" onclick="toggleFullscreen()" title="Toggle Fullscreen" style="margin-right: 0;">
                    <span id="fullscreenIcon">⛶</span>
                </button>
                <button onclick="playNewOrderSound(); unlockAudio();" style="padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9em; font-weight: 600;" title="Test sound notification">
                    🔊 Test Sound
                </button>
            </div>
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
                
                // FIXED: Check for is_new_kitchen field
                $isNewOrder = isset($order['is_new_kitchen']) && $order['is_new_kitchen'] == 1;
                ?>
                <div class="order-card <?php echo $isNewOrder ? 'new-order' : ''; ?>" data-order-id="<?php echo $order['id']; ?>">
                    <div class="order-header">
                        <div class="order-info">
                            <div class="table-number">Galds <?php echo $order['table_id']; ?></div>
                            <div class="order-time"><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                            <?php if ($isNewOrder): ?>
                                <div class="new-order-badge">JAUNS!</div>
                            <?php endif; ?>
                            <div class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <?php if ($isNewOrder): ?>
                                <form method="post" action="kitchen.php" style="margin:0; display:inline;">
                                    <!-- FIXED: Use kitchen-specific acknowledge -->
                                    <input type="hidden" name="acknowledge_kitchen_order" value="<?php echo $order['id']; ?>">
                                    <button class="action-btn acknowledge-btn" type="submit">👁️ Apstiprināt</button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="post" action="kitchen.php" style="margin:0; display:inline;">
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
                    </div>
                    
                    <div class="order-items">
                        <?php 
                        foreach ($kitchen_items as $item): 
                            $menuItem = $menu[$item['menu_item_id']];
                            $customizationDisplay = format_customizations(isset($item['customizations']) ? $item['customizations'] : '');
                        ?>
                            <div class="item-row">
                                <div class="item-details">
                                    <div class="item-name" style="font-size: 1.15em; font-weight: 700; color: #2c3e50; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($menuItem['name']); ?>
                                    </div>
                                    <?php if (!empty($menuItem['category_name'])): ?>
                                        <div class="item-category" style="font-size: 0.9em; color: #7f8c8d; margin-bottom: 8px;">
                                            <?php echo htmlspecialchars($menuItem['category_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-quantity" style="font-size: 1.3em; font-weight: 700; color: #e74c3c; background: #fff; padding: 8px 16px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    x<?php echo $item['quantity']; ?>
                                </div>
                            </div>
                            
                            <!-- Display customizations with better styling -->
                            <?php if (!empty($customizationDisplay)): ?>
                                <div class="customizations" style="margin-top: 12px; padding: 14px; background: #fff; border-radius: 8px; border-left: 4px solid #f39c12; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
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

<!-- New Order Popup Modal -->
<?php if ($has_new_orders && $new_order_details): ?>
<div class="new-order-popup" id="newOrderPopup">
    <div class="popup-content">
        <div class="popup-icon">🔔</div>
        <h2>Jauns pasūtījums!</h2>
        <div class="popup-details">
            <div class="popup-table">Galds #<?php echo $new_order_details['table_id']; ?></div>
            <div class="popup-items">
                <?php foreach ($new_order_details['kitchen_items'] as $item): ?>
                    <div><?php echo htmlspecialchars($menu[$item['menu_item_id']]['name']); ?> x<?php echo $item['quantity']; ?></div>
                <?php endforeach; ?>
            </div>
            <div class="popup-time"><?php echo date('H:i', strtotime($new_order_details['created_at'])); ?></div>
        </div>
        <button class="popup-acknowledge-btn" onclick="acknowledgePopup(<?php echo $new_order_details['id']; ?>)">
            Apstiprināt pasūtījumu
        </button>
    </div>
</div>
<?php endif; ?>

<script>
// Auto-refresh every 10 seconds, but with better UX
let refreshTimer;
let popupShown = <?php echo $has_new_orders ? 'true' : 'false'; ?>;

// Global audio context (reused to avoid creating new ones)
let audioContext = null;
let audioUnlocked = false;

// Unlock audio context on first user interaction (required by browser autoplay policies)
function unlockAudio() {
    if (audioUnlocked) return;
    
    try {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        
        // Resume audio context if suspended (browser autoplay policy)
        if (audioContext.state === 'suspended') {
            audioContext.resume().then(() => {
                audioUnlocked = true;
                console.log('Audio unlocked and ready');
            }).catch(err => {
                console.log('Could not unlock audio:', err);
            });
        } else {
            audioUnlocked = true;
        }
    } catch (error) {
        console.log('Could not create audio context:', error);
    }
}

// Unlock audio on any user interaction
document.addEventListener('click', unlockAudio, { once: true });
document.addEventListener('keydown', unlockAudio, { once: true });
document.addEventListener('touchstart', unlockAudio, { once: true });

// Sound notification for new orders
function playNewOrderSound() {
    // Try Web Audio API first
    try {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        
        // Resume if suspended
        if (audioContext.state === 'suspended') {
            audioContext.resume().then(() => {
                playSoundTones();
            }).catch(() => {
                // Fallback to HTML5 audio
                playFallbackSound();
            });
        } else {
            playSoundTones();
        }
    } catch (error) {
        console.log('Web Audio API not available, using fallback:', error);
        playFallbackSound();
    }
}

function playSoundTones() {
    if (!audioContext) return;
    
    try {
        const duration = 0.3;
        const frequency1 = 800;
        const frequency2 = 1000;
        
        // Play first tone
        const oscillator1 = audioContext.createOscillator();
        const gainNode1 = audioContext.createGain();
        
        oscillator1.connect(gainNode1);
        gainNode1.connect(audioContext.destination);
        
        oscillator1.frequency.value = frequency1;
        oscillator1.type = 'sine';
        
        gainNode1.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode1.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration);
        
        oscillator1.start(audioContext.currentTime);
        oscillator1.stop(audioContext.currentTime + duration);
        
        // Play second tone after a short delay
        setTimeout(() => {
            try {
                const oscillator2 = audioContext.createOscillator();
                const gainNode2 = audioContext.createGain();
                
                oscillator2.connect(gainNode2);
                gainNode2.connect(audioContext.destination);
                
                oscillator2.frequency.value = frequency2;
                oscillator2.type = 'sine';
                
                gainNode2.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode2.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration);
                
                oscillator2.start(audioContext.currentTime);
                oscillator2.stop(audioContext.currentTime + duration);
            } catch (e) {
                console.log('Error playing second tone:', e);
            }
        }, duration * 1000 + 50);
    } catch (error) {
        console.log('Error playing sound tones:', error);
        playFallbackSound();
    }
}

function playFallbackSound() {
    // Fallback: Use HTML5 Audio with data URI (works in all browsers)
    try {
        // Generate a simple beep sound using data URI
        const audio = new Audio();
        audio.volume = 0.6;
        
        // Create a simple beep using oscillator (if supported) or use a data URI
        // For maximum compatibility, we'll use a simple approach
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = ctx.createOscillator();
        const gainNode = ctx.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
        
        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + 0.2);
        
        // Second beep
        setTimeout(() => {
            const oscillator2 = ctx.createOscillator();
            const gainNode2 = ctx.createGain();
            
            oscillator2.connect(gainNode2);
            gainNode2.connect(ctx.destination);
            
            oscillator2.frequency.value = 1000;
            oscillator2.type = 'sine';
            
            gainNode2.gain.setValueAtTime(0.3, ctx.currentTime);
            gainNode2.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
            
            oscillator2.start(ctx.currentTime);
            oscillator2.stop(ctx.currentTime + 0.2);
        }, 250);
    } catch (e) {
        console.log('All audio methods failed. Browser may not support audio or requires user interaction.');
    }
}

// Track which orders have already played their sound
function getAnnouncedOrders() {
    const stored = localStorage.getItem('kitchen_announced_orders');
    return stored ? JSON.parse(stored) : [];
}

function markOrderAsAnnounced(orderId) {
    const announced = getAnnouncedOrders();
    if (!announced.includes(orderId)) {
        announced.push(orderId);
        // Keep only last 50 orders to prevent localStorage from growing too large
        if (announced.length > 50) {
            announced.shift();
        }
        localStorage.setItem('kitchen_announced_orders', JSON.stringify(announced));
    }
}

function checkForNewOrdersAndPlaySound() {
    const newOrderIds = [];
    <?php if ($has_new_orders && $new_order_details): ?>
        newOrderIds.push(<?php echo $new_order_details['id']; ?>);
    <?php endif; ?>
    
    // Also check all new order cards on the page
    document.querySelectorAll('.order-card.new-order').forEach(card => {
        const orderId = card.getAttribute('data-order-id');
        if (orderId) {
            const id = parseInt(orderId);
            if (!newOrderIds.includes(id)) {
                newOrderIds.push(id);
            }
        }
    });
    
    // Play sound for orders that haven't been announced yet
    const announced = getAnnouncedOrders();
    let hasNewUnannounced = false;
    
    newOrderIds.forEach(orderId => {
        if (!announced.includes(orderId)) {
            hasNewUnannounced = true;
            markOrderAsAnnounced(orderId);
        }
    });
    
    if (hasNewUnannounced) {
        playNewOrderSound();
    }
}

function startAutoRefresh() {
    refreshTimer = setTimeout(function() {
        // Check for new orders before refreshing
        checkForNewOrdersAndPlaySound();
        
        // Only refresh if no modals are open and user isn't interacting
        if (!document.querySelector('.order-card:hover') && !popupShown) {
            location.reload();
        } else {
            // Try again in 2 seconds if user is interacting
            refreshTimer = setTimeout(() => {
                checkForNewOrdersAndPlaySound();
                location.reload();
            }, 2000);
        }
    }, 10000);
}

function stopAutoRefresh() {
    if (refreshTimer) {
        clearTimeout(refreshTimer);
    }
}

// Handle new order popup - FIXED to use kitchen-specific acknowledge
function acknowledgePopup(orderId) {
    // Submit kitchen acknowledge form
    const form = document.createElement('form');
    form.method = 'post';
    form.action = 'kitchen.php';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'acknowledge_kitchen_order'; // FIXED: Use kitchen-specific field
    input.value = orderId;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

// Close popup when clicking outside
document.addEventListener('click', function(e) {
    const popup = document.getElementById('newOrderPopup');
    if (popup && e.target === popup) {
        popup.style.display = 'none';
        popupShown = false;
        startAutoRefresh();
    }
});

// Show popup on page load if there are new orders
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById('newOrderPopup');
    if (popup) {
        popup.style.display = 'flex';
        // Play sound notification for new orders
        checkForNewOrdersAndPlaySound();
        // Auto-close popup after 30 seconds if not acknowledged
        setTimeout(function() {
            if (popup.style.display !== 'none') {
                popup.style.display = 'none';
                popupShown = false;
                startAutoRefresh();
            }
        }, 30000);
    } else {
        // Check for new orders even if popup doesn't show
        checkForNewOrdersAndPlaySound();
    }
});

// Pause refresh when user is hovering over cards
document.addEventListener('DOMContentLoaded', function() {
    const orderCards = document.querySelectorAll('.order-card');
    
    orderCards.forEach(card => {
        card.addEventListener('mouseenter', stopAutoRefresh);
        card.addEventListener('mouseleave', startAutoRefresh);
    });
    
    // Start the refresh timer only if no popup is shown
    if (!popupShown) {
        startAutoRefresh();
    }
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

/* NEW ORDER STYLING */
.order-card.new-order {
    border: 2px solid #f59e0b;
    background: linear-gradient(135deg, #111827 0%, #1a1a2e 100%);
    box-shadow: 0 0 20px rgba(245, 158, 11, 0.3);
    animation: pulseGlow 2s infinite;
}

@keyframes pulseGlow {
    0% {
        box-shadow: 0 0 20px rgba(245, 158, 11, 0.3);
        border-color: #f59e0b;
    }
    50% {
        box-shadow: 0 0 30px rgba(245, 158, 11, 0.6);
        border-color: #fbbf24;
    }
    100% {
        box-shadow: 0 0 20px rgba(245, 158, 11, 0.3);
        border-color: #f59e0b;
    }
}

.new-order-badge {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    color: #92400e;
    padding: 6px 12px;
    border-radius: 15px;
    font-weight: 700;
    font-size: 0.8em;
    text-transform: uppercase;
    letter-spacing: 1px;
    animation: bounce 1s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-3px);
    }
    60% {
        transform: translateY(-2px);
    }
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
    flex-wrap: wrap;
}

.action-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
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
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 0.9em;
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

.acknowledge-btn {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

.acknowledge-btn:hover {
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
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

/* NEW ORDER POPUP STYLES */
.new-order-popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    backdrop-filter: blur(5px);
}

.popup-content {
    background: linear-gradient(135deg, #1f2937, #111827);
    border: 2px solid #f59e0b;
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    max-width: 500px;
    width: 90%;
    color: white;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    animation: popupSlideIn 0.5s ease-out;
}

@keyframes popupSlideIn {
    from {
        transform: translateY(-50px) scale(0.9);
        opacity: 0;
    }
    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

.popup-icon {
    font-size: 4em;
    margin-bottom: 20px;
    animation: bell 2s infinite;
}

@keyframes bell {
    0%, 50%, 100% {
        transform: rotate(0deg);
    }
    10%, 30% {
        transform: rotate(-10deg);
    }
    20% {
        transform: rotate(10deg);
    }
}

.popup-content h2 {
    font-size: 2em;
    margin-bottom: 20px;
    color: #fbbf24;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.popup-details {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.popup-table {
    font-size: 1.5em;
    font-weight: bold;
    color: #3b82f6;
    margin-bottom: 15px;
}

.popup-items {
    color: #d1d5db;
    margin-bottom: 15px;
    line-height: 1.6;
}

.popup-items div {
    padding: 5px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.popup-items div:last-child {
    border-bottom: none;
}

.popup-time {
    color: #9ca3af;
    font-size: 0.9em;
    font-style: italic;
}

.popup-acknowledge-btn {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    color: #92400e;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    font-size: 1.1em;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
}

.popup-acknowledge-btn:hover {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(245, 158, 11, 0.4);
}

.popup-acknowledge-btn:active {
    transform: translateY(0);
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
    
    .action-buttons {
        flex-direction: column;
        width: 100%;
        gap: 10px;
    }
    
    .action-btn {
        width: 100%;
    }
    
    .item-row {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .item-category {
        font-size: 0.8em;
    }
    
    .popup-content {
        padding: 30px 20px;
    }
    
    .popup-content h2 {
        font-size: 1.5em;
    }
    
    .popup-acknowledge-btn {
        padding: 12px 25px;
        font-size: 1em;
    }
}
</style>

</body>
</html>