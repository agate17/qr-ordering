<?php
require_once __DIR__ . '/../includes/db.php';
$tables = get_table_list();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Table QR Codes</title>
    <style>
        body { font-family: sans-serif; background: #f8f8f8; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        h1 { text-align: center; }
        .qr-block { display: flex; align-items: center; margin-bottom: 32px; }
        .qr-block img { border: 1px solid #ccc; margin-right: 24px; background: #fff; }
        .qr-label { font-size: 1.2em; font-weight: bold; }
        .table-count { text-align: center; margin: 20px 0; padding: 10px; background: #e8f4f8; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Table QR Codes</h1>
    <div class="table-count">Total Tables: <?php echo count($tables); ?></div>
    
    <?php foreach ($tables as $table_id): ?>
    <div class="qr-block">
        <img src="generate_qr.php?table=<?php echo $table_id; ?>" width="220" height="220" alt="QR Table <?php echo $table_id; ?>">
        <div class="qr-label">Table <?php echo $table_id; ?><br><small>menu.php?table=<?php echo $table_id; ?></small></div>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>