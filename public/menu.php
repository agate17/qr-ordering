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
    <title>Table <?php echo $table_id; ?> - Menu</title>
    <style>
        body { font-family: sans-serif; background: #f8f8f8; margin: 0; padding: 0; }
        .container { max-width: 400px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        h1 { text-align: center; }
        table { width: 100%; margin-bottom: 16px; }
        th, td { padding: 8px; text-align: left; }
        th { background: #eee; }
        input[type=number] { width: 60px; }
        .submit-btn { width: 100%; padding: 12px; background: #27ae60; color: #fff; border: none; border-radius: 4px; font-size: 1.1em; cursor: pointer; }
        .submit-btn:hover { background: #219150; }
    </style>
</head>
<body>
<div class="container">
    <h1>Table <?php echo $table_id; ?></h1>
    <form method="post" action="submit_order.php">
        <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
        <table>
            <tr><th>Item</th><th>Price</th><th>Qty</th></tr>
            <?php foreach ($menu as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td>€<?php echo number_format($item['price'], 2); ?></td>
                <td><input type="number" name="qty[<?php echo $item['id']; ?>]" min="0" max="10" value="0"></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button class="submit-btn" type="submit">Place Order</button>
    </form>
</div>
</body>
</html> 