<?php
$table_id = isset($_GET['table']) ? intval($_GET['table']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thank You</title>
    <style>
        body { font-family: sans-serif; background: #f8f8f8; }
        .container { max-width: 400px; margin: 60px auto; background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 2px 8px #0001; text-align: center; }
        h1 { color: #27ae60; }
    </style>
</head>
<body>
<div class="container">
    <h1>Thank You!</h1>
    <p>Your order for <b>Table <?php echo $table_id; ?></b> has been received.</p>
    <p>We will serve you shortly.</p>
</div>
</body>
</html> 