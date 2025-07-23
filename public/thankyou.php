<?php
$table_id = isset($_GET['table']) ? intval($_GET['table']) : 0;
$order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>
    <link rel="stylesheet" type="text/css" href="assets/css/thankyou.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="container">
    <div class="success-header">
        <span class="success-icon">✅</span>
        <h1 class="success-title">Jūsu pasūtījums ir apstiprināts!</h1>
    </div>
    
    <div class="content">
        <div class="order-info">
            <div class="order-details">
                <div class="order-detail">
                    <div class="detail-label">galds</div>
                    <div class="table-number"><?php echo $table_id; ?></div>
                </div>
                <?php if ($order_id): ?>
                <div class="order-detail">
                    <div class="detail-label">Order #</div>
                    <div class="detail-value"><?php echo $order_id; ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="message">
            <p>Paldies par pasūtījumu! Mūsu virtuves komanda ir informēta un nekavējoties sāks gatavot jūsu gardo maltīti.</p>
        </div>
        
        <div class="status-indicator">
            <div class="status-text" id="orderStatusText">🕐 Jūsu pasūtījums tiek gatavots</div>
        </div>
        
        <div class="action-buttons">
            <a href="menu.php?table=<?php echo $table_id; ?>" class="btn btn-primary">Veikt papildus pasūtījumu</a>
        </div>
        
        <div class="footer-note">
            <p>💡 ja aizmirsi pievienot ko svarīgu, nekas - veic papildus pasūtijumu!</p>
        </div>
    </div>
</div>
<script>
// Live order status polling
const orderId = <?php echo json_encode($order_id); ?>;
function updateOrderStatus() {
    if (!orderId) return;
    fetch('get_order_status.php?order=' + orderId)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let statusText = '';
                switch (data.status) {
                    case 'pending':
                        statusText = '🕐 Jūsu pasūtījums vēl netiek gatavots';
                        break;
                    case 'prepared':
                        statusText = '✅ Jūsu pasūtījums ir gatavs, un drīz būs pie jums!';
                        break;
                    case 'paid':
                        statusText = '💸 Jūsu pasūtījums ir apmaksāts. Paldies!';
                        break;
                    default:
                        statusText = 'jūsu pasūtījums tiek gatavots';
                }
                document.getElementById('orderStatusText').textContent = statusText;
            }
        });
}
setInterval(updateOrderStatus, 5000);
updateOrderStatus();
</script>
</body>
</html>