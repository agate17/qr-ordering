<?php
$table_id = isset($_GET['table']) ? intval($_GET['table']) : 0;
$order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Order Confirmed</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
            animation: slideUp 0.6s ease-out;
        }
        
        .success-header {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 40px 30px;
            position: relative;
            overflow: hidden;
        }
        
        .success-header::before {
            content: "🎉";
            font-size: 4em;
            position: absolute;
            top: 10px;
            right: 20px;
            opacity: 0.3;
        }
        
        .success-icon {
            font-size: 3em;
            margin-bottom: 20px;
            display: block;
        }
        
        .success-title {
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .success-subtitle {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .order-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #27ae60;
        }
        
        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .order-detail {
            text-align: center;
        }
        
        .detail-label {
            font-size: 0.9em;
            color: #7f8c8d;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .detail-value {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .table-number {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .message {
            color: #7f8c8d;
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .status-indicator {
            background: #e8f5e8;
            border: 2px solid #27ae60;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
        }
        
        .status-text {
            color: #27ae60;
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #1f5f8b);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #e9ecef;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            color: #495057;
            transform: translateY(-2px);
        }
        
        .footer-note {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #95a5a6;
            font-size: 0.9em;
        }
        
        /* Animations */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .success-icon {
            animation: pulse 2s infinite;
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .success-header {
                padding: 30px 20px;
            }
            
            .success-title {
                font-size: 1.8em;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .order-details {
                flex-direction: column;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="success-header">
        <span class="success-icon">✅</span>
        <h1 class="success-title">Order Confirmed!</h1>
        <p class="success-subtitle">Your order has been successfully placed</p>
    </div>
    
    <div class="content">
        <div class="order-info">
            <div class="order-details">
                <div class="order-detail">
                    <div class="detail-label">Table</div>
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
            <p>Thank you for your order! Our kitchen team has been notified and will start preparing your delicious meal right away.</p>
        </div>
        
        <div class="status-indicator">
            <div class="status-text" id="orderStatusText">🕐 Your order is being prepared</div>
        </div>
        
        <div class="action-buttons">
            <a href="menu.php?table=<?php echo $table_id; ?>" class="btn btn-primary">Add More Items</a>
            <a href="kitchen.php" class="btn btn-secondary">View Kitchen</a>
        </div>
        
        <div class="footer-note">
            <p>💡 Tip: You can add more items to your order at any time!</p>
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
                        statusText = '🕐 Your order is pending';
                        break;
                    case 'prepared':
                        statusText = '✅ Your order is ready!';
                        break;
                    case 'paid':
                        statusText = '💸 Your order has been paid. Thank you!';
                        break;
                    default:
                        statusText = 'Status: ' + data.status;
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