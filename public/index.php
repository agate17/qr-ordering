<?php
require_once __DIR__ . '/../includes/db.php';
$tables = get_table_list();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR pasūtīšanas sistēma - index</title>
    <style>
        body { font-family: sans-serif; background: #f8f8f8; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 32px; border-radius: 10px; box-shadow: 0 2px 8px #0001; }
        h1 { text-align: center; margin-bottom: 32px; }
        .links { display: flex; flex-direction: column; gap: 18px; }
        a { display: block; text-align: center; padding: 16px; background: #2980b9; color: #fff; text-decoration: none; border-radius: 6px; font-size: 1.1em; transition: background 0.2s; }
        a:hover { background: #1c5d8c; }
        .section { margin-bottom: 24px; }
        .section-title { font-weight: bold; margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="container">
    <h1>QR pasūtīšanas sistēma</h1>
    <div class="section">
        <div class="section-title">klientu ēdienkartes</div>
        <div class="links">
            <?php foreach ($tables as $table_id): ?>
                <a href="menu.php?table=<?php echo $table_id; ?>">galds <?php echo $table_id; ?> ēdienkarte</a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="section">
        <div class="section-title">darbiniekiem:</div>
        <div class="links">
            <a href="kitchen.php">virtuves skats</a>
            <a href="register.php">kases skats</a>
        </div>
    </div>
    <div class="section">
        <div class="section-title">Admin</div>
        <div class="links">
            <a href="admin_menu.php">ēdienkaršu pārvaldība</a>
        </div>
    </div>
    <div class="section">
        <div class="section-title">QR kodi:</div>
        <div class="links">
            <a href="qr-codes.php">skatīt QR kodus</a>
        </div>
    </div>
</div>
</body>
</html>