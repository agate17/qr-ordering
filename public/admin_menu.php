<?php
session_start();
$ADMIN_PASS = 'demo123'; // Change for real use

// Handle login
if (isset($_POST['admin_login'])) {
    if ($_POST['password'] === $ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = 'Incorrect password.';
    }
}
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_menu.php');
    exit;
}
if (empty($_SESSION['admin_logged_in'])) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Admin Login</title>
        <style>
            body { font-family: sans-serif; background: #f8f8f8; }
            .container { max-width: 350px; margin: 80px auto; background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
            h1 { text-align: center; }
            .error { color: #c0392b; text-align: center; }
        </style>
    </head>
    <body>
    <div class="container">
        <h1>Admin Login</h1>
        <?php if (!empty($error)) echo '<div class="error">'.$error.'</div>'; ?>
        <form method="post">
            <input type="password" name="password" placeholder="Password" required style="width:100%;padding:10px;margin-bottom:16px;">
            <button type="submit" name="admin_login" style="width:100%;padding:10px;">Login</button>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}
require_once __DIR__ . '/../includes/db.php';
$conn = db_connect();
// Handle add/edit/delete
if ($conn) {
    // Add item
    if (isset($_POST['add_item'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $price = floatval($_POST['price']);
        if ($name && $price > 0) {
            $conn->query("INSERT INTO menu_items (name, price) VALUES ('$name', $price)");
        }
    }
    // Edit item
    if (isset($_POST['edit_item'])) {
        $id = intval($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        $price = floatval($_POST['price']);
        if ($id && $name && $price > 0) {
            $conn->query("UPDATE menu_items SET name='$name', price=$price WHERE id=$id");
        }
    }
    // Delete item
    if (isset($_POST['delete_item'])) {
        $id = intval($_POST['id']);
        if ($id) {
            $conn->query("DELETE FROM menu_items WHERE id=$id");
        }
    }
    // Fetch menu
    $res = $conn->query('SELECT * FROM menu_items ORDER BY id');
    $menu = [];
    while ($row = $res->fetch_assoc()) $menu[] = $row;
    $conn->close();
} else {
    die('Database connection required for admin menu management.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Menu</title>
    <style>
        body { font-family: sans-serif; background: #f8f8f8; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        h1 { text-align: center; }
        table { width: 100%; margin-bottom: 24px; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid #eee; }
        th { background: #eee; }
        form.inline { display: inline; }
        .actions { white-space: nowrap; }
        .logout { float: right; }
        .add-form { margin-bottom: 24px; }
        input[type=text], input[type=number] { padding: 6px; width: 120px; }
        button { padding: 6px 12px; margin-left: 4px; }
    </style>
</head>
<body>
<div class="container">
    <a href="admin_menu.php?logout=1" class="logout">Logout</a>
    <h1>Manage Menu Items</h1>
    <form class="add-form" method="post">
        <input type="text" name="name" placeholder="Item name" required>
        <input type="number" name="price" placeholder="Price" step="0.01" min="0.01" required>
        <button type="submit" name="add_item">Add Item</button>
    </form>
    <table>
        <tr><th>ID</th><th>Name</th><th>Price (€)</th><th>Actions</th></tr>
        <?php foreach ($menu as $item): ?>
        <tr>
            <form class="inline" method="post">
                <td><?php echo $item['id']; ?><input type="hidden" name="id" value="<?php echo $item['id']; ?>"></td>
                <td><input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required></td>
                <td><input type="number" name="price" value="<?php echo number_format($item['price'],2,'.',''); ?>" step="0.01" min="0.01" required></td>
                <td class="actions">
                    <button type="submit" name="edit_item">Save</button>
                    <button type="submit" name="delete_item" onclick="return confirm('Delete this item?');">Delete</button>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html> 