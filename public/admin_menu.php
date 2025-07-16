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

// Fetch categories for dropdown
$categories = [];
if ($conn) {
    $cres = $conn->query('SELECT * FROM categories ORDER BY name');
    while ($row = $cres->fetch_assoc()) $categories[] = $row;
}

// Feedback message logic
$feedback = '';
$feedback_type = '';

// Handle add/edit/delete categories
if ($conn) {
    // Add category
    if (isset($_POST['add_category'])) {
        $cat_name = $conn->real_escape_string($_POST['cat_name']);
        if ($cat_name) {
            $conn->query("INSERT INTO categories (name) VALUES ('$cat_name')");
            $feedback = 'Category added.';
            $feedback_type = 'success';
        } else {
            $feedback = 'Category name required.';
            $feedback_type = 'error';
        }
    }
    // Edit category
    if (isset($_POST['edit_category'])) {
        $cat_id = intval($_POST['cat_id']);
        $cat_name = $conn->real_escape_string($_POST['cat_name']);
        if ($cat_id && $cat_name) {
            $conn->query("UPDATE categories SET name='$cat_name' WHERE id=$cat_id");
            $feedback = 'Category updated.';
            $feedback_type = 'success';
        } else {
            $feedback = 'Category name required.';
            $feedback_type = 'error';
        }
    }
    // Delete category
    if (isset($_POST['delete_category'])) {
        $cat_id = intval($_POST['cat_id']);
        if ($cat_id) {
            $conn->query("DELETE FROM categories WHERE id=$cat_id");
            $feedback = 'Category deleted.';
            $feedback_type = 'success';
        }
    }
    // Re-fetch categories after changes
    $categories = [];
    $cres = $conn->query('SELECT * FROM categories ORDER BY name');
    while ($row = $cres->fetch_assoc()) $categories[] = $row;
}

// Handle add/edit/delete
if ($conn) {
    // Add item
    if (isset($_POST['add_item'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $price = floatval($_POST['price']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : 'NULL';
        $options = $conn->real_escape_string($_POST['options'] ?? '');
        $available = isset($_POST['available']) ? 1 : 0;
        $image_path = NULL;
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $img_name = uniqid('menu_', true) . '_' . basename($_FILES['image']['name']);
            $target_dir = __DIR__ . '/assets/images/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . $img_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'assets/images/' . $img_name;
            }
        }
        $img_sql = $image_path ? ", image_path='$image_path'" : '';
        $cat_sql = $category_id !== 'NULL' ? $category_id : 'NULL';
        $conn->query("INSERT INTO menu_items (name, description, price, category_id, image_path, options, available) VALUES ('$name', '$description', $price, $cat_sql, " . ($image_path ? "'$image_path'" : 'NULL') . ", '$options', $available)");
        $feedback = 'Menu item added.';
        $feedback_type = 'success';
    }
    // Edit item
    if (isset($_POST['edit_item'])) {
        $id = intval($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $price = floatval($_POST['price']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : 'NULL';
        $options = $conn->real_escape_string($_POST['options'] ?? '');
        $available = isset($_POST['available']) ? 1 : 0;
        $image_path = NULL;
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $img_name = uniqid('menu_', true) . '_' . basename($_FILES['image']['name']);
            $target_dir = __DIR__ . '/assets/images/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . $img_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'assets/images/' . $img_name;
            }
        }
        $img_sql = $image_path ? ", image_path='$image_path'" : '';
        $cat_sql = $category_id !== 'NULL' ? $category_id : 'NULL';
        $conn->query("UPDATE menu_items SET name='$name', description='$description', price=$price, category_id=$cat_sql, options='$options', available=$available" . ($image_path ? ", image_path='$image_path'" : '') . " WHERE id=$id");
        $feedback = 'Menu item updated.';
        $feedback_type = 'success';
    }
    // Delete item
    if (isset($_POST['delete_item'])) {
        $id = intval($_POST['id']);
        if ($id) {
            $conn->query("DELETE FROM menu_items WHERE id=$id");
            $feedback = 'Menu item deleted.';
            $feedback_type = 'success';
        }
    }
    // Fetch menu
    $res = $conn->query('SELECT * FROM menu_items ORDER BY id');
    $menu = [];
    while ($row = $res->fetch_assoc()) $menu[] = $row;
} else {
    die('Database connection required for admin menu management.');
}

// Handle report date filter
$report_start = isset($_POST['report_start']) ? $_POST['report_start'] : date('Y-m-d');
$report_end = isset($_POST['report_end']) ? $_POST['report_end'] : date('Y-m-d');
$report_orders = [];
$report_total = 0;
if ($conn && isset($_POST['show_report'])) {
    $start = $conn->real_escape_string($report_start);
    $end = $conn->real_escape_string($report_end);
    $sql = "SELECT o.*, (
                SELECT SUM(oi.quantity * mi.price)
                FROM order_items oi
                JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE oi.order_id = o.id
            ) AS order_total
            FROM orders o
            WHERE DATE(o.created_at) >= '$start' AND DATE(o.created_at) <= '$end' ORDER BY o.created_at DESC";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $report_orders[] = $row;
        $report_total += floatval($row['order_total']);
    }
}
if ($conn) $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Menu</title>
    <style>
        body { font-family: sans-serif; background: #f8f8f8; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 32px; border-radius: 10px; box-shadow: 0 2px 12px #0002; }
        h1 { text-align: left; font-size: 1.6em; margin-top: 32px; margin-bottom: 16px; border-bottom: 2px solid #eee; padding-bottom: 6px; }
        h1:first-of-type { margin-top: 0; }
        .section { background: #fafbfc; border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 32px; padding: 20px 24px; }
        table { width: 100%; margin-bottom: 24px; border-collapse: collapse; font-size: 1em; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center; vertical-align: middle; }
        th { background: #f4f4f4; font-size: 1.08em; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f1f7fa; }
        form.inline { display: inline; }
        .actions { white-space: nowrap; }
        .logout {
            position: absolute;
            top: 24px;
            right: 36px;
            color: #fff;
            background: #e74c3c;
            border: none;
            border-radius: 5px;
            padding: 8px 18px;
            font-size: 1em;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 2px 6px #0001;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
            z-index: 1000;
        }
        .logout:hover {
            background: #c0392b;
            color: #fff;
            text-decoration: none;
        }
        .add-form, .section form { margin-bottom: 18px; }
        input[type=text], input[type=number], textarea, select { padding: 7px; width: 180px; margin-bottom: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em; }
        textarea { resize: vertical; min-height: 36px; }
        input[type=file] { margin-bottom: 8px; }
        label { margin-right: 8px; font-size: 1em; }
        button { padding: 7px 16px; margin-left: 4px; border: none; border-radius: 4px; font-size: 1em; cursor: pointer; }
        button[name=add_category], button[name=add_item], button[name=edit_category], button[name=edit_item] { background: #27ae60; color: #fff; }
        button[name=add_category]:hover, button[name=add_item]:hover, button[name=edit_category]:hover, button[name=edit_item]:hover { background: #219150; }
        button[name=delete_category], button[name=delete_item] { background: #e74c3c; color: #fff; }
        button[name=delete_category]:hover, button[name=delete_item]:hover { background: #c0392b; }
        button[name=show_report] { background: #2980b9; color: #fff; }
        button[name=show_report]:hover { background: #1c5d8c; }
        .form-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 8px; }
        .form-row label { min-width: 90px; }
        .form-row input, .form-row select, .form-row textarea { flex: 1; min-width: 120px; }
        .section-title { font-size: 1.2em; margin-bottom: 10px; color: #444; }
        .summary { margin-bottom: 18px; font-size: 1.1em; }
        .feedback {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 1.08em;
            font-weight: 500;
            background: #eafaf1;
            color: #218838;
            border: 1px solid #b7e4c7;
            transition: opacity 0.5s;
            max-width: 600px;
        }
        .feedback.error {
            background: #faeaea;
            color: #c0392b;
            border: 1px solid #e4b7b7;
        }
        .menu-item-grid {
            display: grid;
            grid-template-columns: 1.2fr 1.5fr 1fr 1.1fr 0.8fr 1.2fr;
            gap: 12px;
            align-items: start;
            flex-wrap: wrap;
            max-width: 1100px;
        }
        .menu-item-grid label { margin-bottom: 0; }
        .menu-item-img {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .menu-thumb {
            width: 38px;
            height: 38px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: #f4f4f4;
        }
        .menu-item-cell {
            max-width: 1200px;
            max-height: 320px;
            overflow: visible;
            padding: 0;
            margin: 0;
        }
        .menu-item-grid input[type="text"], .menu-item-grid input[type="number"], .menu-item-grid select {
            width: 100%;
            max-width: 140px;
            font-size: 0.98em;
            padding: 5px;
        }
        .menu-item-grid textarea {
            width: 100%;
            max-width: 260px;
            min-height: 38px;
            max-height: 120px;
            font-size: 0.98em;
            padding: 5px;
        }
        .menu-item-grid label { font-size: 0.98em; }
        @media (max-width: 1100px) {
            .menu-item-grid { grid-template-columns: 1fr 1fr 1fr; }
        }
        @media (max-width: 800px) {
            .menu-item-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 600px) {
            .menu-item-grid { grid-template-columns: 1fr; }
        }
        .menu-items-table-price {
            max-width: 70px;
            width: 70px;
            min-width: 60px;
        }
        .menu-items-table-price input[type="number"] {
            width: 60px;
            min-width: 50px;
            max-width: 70px;
            text-align: right;
        }
        .menu-item-block { transition: background 0.2s; }
        .menu-item-block.even { background: #f9f9f9; }
        .menu-item-block.odd { background: #fff; }
        .menu-item-block:hover, .menu-item-block.hover { background: #f1f7fa !important; }
        .menu-item-block.end-of-block {
            border-bottom: 2px solid #bbb;
        }
        .add-item-btn {
            background: #27ae60;
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 4px;
            padding: 10px 22px;
            font-size: 1.08em;
            margin-bottom: 18px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .add-item-btn:hover { background: #219150; }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.35);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            animation: fadeInModalBg 0.3s;
        }
        @keyframes fadeInModalBg {
            from { background: rgba(0,0,0,0); }
            to { background: rgba(0,0,0,0.35); }
        }
        .modal-overlay.active { display: flex; }
        .add-modal {
            background: #fff !important;
            border-radius: 8px;
            box-shadow: 0 8px 32px #0004;
            padding: 32px;
            width: 100%;
            max-width: 420px;
            min-width: unset;
            max-height: 90vh;
            overflow-y: auto;
            overflow-x: hidden;
            box-sizing: border-box;
            padding-right: 0;
            position: relative;
            z-index: 2100;
            font-size: 1em;
            animation: fadeInModal 0.25s;
            border-top: 6px solid #27ae60;
            margin-top: 32px;
            margin-bottom: 56px;
            margin-right: 20px;
        }
        .add-modal::-webkit-scrollbar {
            width: 10px;
            background: #f4f4f4;
            border-radius: 8px;
        }
        .add-modal::-webkit-scrollbar-thumb {
            background: #27ae60;
            border-radius: 8px;
        }
        .add-modal::-webkit-scrollbar-thumb:hover {
            background: #219150;
        }
        .add-modal::-webkit-scrollbar-track {
            background: #f4f4f4;
            border-radius: 8px;
        }
        @keyframes fadeInModal {
            from { opacity: 0; transform: translateY(30px) scale(0.98); }
            to { opacity: 1; transform: none; }
        }
        .add-modal h2 { margin-top: 0; margin-bottom: 22px; font-size: 1.32em; letter-spacing: 0.5px; }
        .add-modal .close-btn {
            position: absolute;
            top: 10px; right: 18px;
            background: none;
            border: none;
            font-size: 2.1em;
            color: #27ae60;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s, transform 0.2s;
            line-height: 1;
            padding: 0 6px;
        }
        .add-modal .close-btn:hover { color: #c0392b; transform: scale(1.15); }
        .add-modal label { display: block; margin-bottom: 16px; font-size: 1.07em; letter-spacing: 0.1px; }
        .add-modal input[type=text], .add-modal input[type=number], .add-modal textarea, .add-modal select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            margin-top: 6px;
            margin-bottom: 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1.07em;
            background: #fafbfc;
        }
        .add-modal textarea { resize: vertical; min-height: 44px; }
        .add-modal input[type=file] {
            display: none;
        }
        .add-modal .custom-file-label, .custom-file-label-table {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #2980b9;
            color: #fff;
            padding: 9px 18px;
            border-radius: 5px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 10px;
            transition: background 0.2s;
            border: none;
            outline: none;
        }
        .add-modal .custom-file-label:hover, .custom-file-label-table:hover {
            background: #1c5d8c;
        }
        .custom-file-label svg, .custom-file-label-table svg {
            width: 1.1em;
            height: 1.1em;
            fill: #fff;
            margin-right: 6px;
        }
        .add-modal .file-name {
            display: inline-block;
            margin-left: 10px;
            font-size: 0.98em;
            color: #2980b9;
            vertical-align: middle;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .add-modal .modal-actions { text-align: right; margin-top: 18px; }
        .add-modal .modal-actions button {
            margin-left: 8px;
            padding: 9px 22px;
            font-size: 1.07em;
            border-radius: 5px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .add-modal .modal-actions button[type=button] {
            background: #e0e0e0;
            color: #444;
        }
        .add-modal .modal-actions button[type=button]:hover {
            background: #bdbdbd;
            color: #222;
        }
        .add-modal .modal-actions button[name=add_item] {
            background: #27ae60;
            color: #fff;
            margin-right: 20px;
        }
        .add-modal .modal-actions button[name=add_item]:hover {
            background: #219150;
            color: #fff;
        }
    </style>
</head>
<body>
<a href="admin_menu.php?logout=1" class="logout">Logout</a>
<div class="container">
    <?php if ($feedback): ?>
        <div class="feedback<?php if ($feedback_type === 'error') echo ' error'; ?>"><?php echo htmlspecialchars($feedback); ?></div>
    <?php endif; ?>
    <div class="section">
        <h1>Daily/Weekly Report</h1>
        <form method="post" style="margin-bottom:16px;">
            <div class="form-row">
                <label>Start Date: <input type="date" name="report_start" value="<?php echo htmlspecialchars($report_start); ?>"></label>
                <label>End Date: <input type="date" name="report_end" value="<?php echo htmlspecialchars($report_end); ?>"></label>
                <button type="submit" name="show_report">Show Report</button>
            </div>
        </form>
        <?php if (isset($_POST['show_report'])): ?>
            <div class="summary">
                <strong>Total Orders:</strong> <?php echo count($report_orders); ?> &nbsp; | &nbsp;
                <strong>Total Revenue:</strong> €<?php echo number_format($report_total, 2); ?>
            </div>
            <table style="margin-bottom:0;">
                <tr><th>Date/Time</th><th>Table</th><th>Status</th><th>Total (€)</th></tr>
                <?php foreach ($report_orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($order['table_id']); ?></td>
                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                    <td><?php echo number_format($order['order_total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <div class="section">
        <h1>Manage Categories</h1>
        <form method="post" class="form-row">
            <label>New Category: <input type="text" name="cat_name" placeholder="Category name" required></label>
            <button type="submit" name="add_category">Add Category</button>
        </form>
        <table style="margin-bottom:0;">
            <tr><th>ID</th><th>Name</th><th>Actions</th></tr>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <form method="post" class="inline">
                    <td><?php echo $cat['id']; ?><input type="hidden" name="cat_id" value="<?php echo $cat['id']; ?>"></td>
                    <td><input type="text" name="cat_name" value="<?php echo htmlspecialchars($cat['name']); ?>" required></td>
                    <td>
                        <button type="submit" name="edit_category">Save</button>
                        <button type="submit" name="delete_category" onclick="return confirm('Delete this category?');">Delete</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="section">
        <h1>Manage Menu Items</h1>
        <button class="add-item-btn" onclick="openAddModal()">Add Menu Item</button>
        <div class="modal-overlay" id="addModalOverlay" onclick="if(event.target===this)closeAddModal()">
            <div class="add-modal">
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
                <h2>Add Menu Item</h2>
                <form class="add-form" method="post" enctype="multipart/form-data" autocomplete="off">
                    <label>Name:
                        <input type="text" name="name" placeholder="Item name" required>
                    </label>
                    <label>Description:
                        <textarea name="description" placeholder="Description"></textarea>
                    </label>
                    <label>Price:
                        <input type="number" name="price" placeholder="Price" step="0.01" min="0.01" required>
                    </label>
                    <label>Category:
                        <select name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Image:</label>
                    <input type="file" id="modalImageInput" name="image" accept="image/*" style="display:none;" onchange="showFileName(this)">
                    <label for="modalImageInput" class="custom-file-label">
                        <svg viewBox="0 0 20 20"><path d="M16.88 9.94a1 1 0 0 0-1.41 0l-3.17 3.17V3a1 1 0 1 0-2 0v10.11l-3.17-3.17a1 1 0 0 0-1.41 1.41l5 5a1 1 0 0 0 1.41 0l5-5a1 1 0 0 0 0-1.41z"/></svg>
                        Choose File
                    </label>
                    <span class="file-name" id="modalFileName"></span>
                    <label>Options:
                        <input type="text" name="options" placeholder="Options (e.g. sizes, spice level)">
                    </label>
                    <label><input type="checkbox" name="available" checked> Available</label>
                    <div class="modal-actions">
                        <button type="button" onclick="closeAddModal()">Cancel</button>
                        <button type="submit" name="add_item">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Category</th>
                <th class="menu-items-table-price">Price (€)</th>
                <th>Available</th>
            </tr>
            <?php foreach ($menu as $idx => $item): ?>
            <tr class="menu-item-block <?php echo $idx % 2 === 0 ? 'even' : 'odd'; ?>">
                <form class="inline" method="post" enctype="multipart/form-data">
                    <td><?php echo $item['id']; ?><input type="hidden" name="id" value="<?php echo $item['id']; ?>"></td>
                    <td><input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required></td>
                    <td><textarea name="description" placeholder="Description" style="width:120px;vertical-align:top;"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea></td>
                    <td>
                        <select name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php if (isset($item) && isset($item['category_id']) && $item['category_id'] == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="menu-items-table-price"><input type="number" name="price" value="<?php echo number_format($item['price'],2,'.',''); ?>" step="0.01" min="0.01" required></td>
                    <td><input type="checkbox" name="available" <?php if (!isset($item['available']) || $item['available']) echo 'checked'; ?>></td>
                    <td colspan="3"></td>
                </form>
            </tr>
            <tr class="menu-item-block <?php echo $idx % 2 === 0 ? 'even' : 'odd'; ?> end-of-block">
                <form class="inline" method="post" enctype="multipart/form-data">
                    <td colspan="9">
                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:18px; justify-content:center;">
                            <div>
                                <input type="file" id="tableImageInput<?php echo $item['id']; ?>" name="image" accept="image/*" style="display:none;" onchange="showTableFileName(this, <?php echo $item['id']; ?>)">
                                <label for="tableImageInput<?php echo $item['id']; ?>" class="custom-file-label-table">
                                    <svg viewBox="0 0 20 20"><path d="M16.88 9.94a1 1 0 0 0-1.41 0l-3.17 3.17V3a1 1 0 1 0-2 0v10.11l-3.17-3.17a1 1 0 0 0-1.41 1.41l5 5a1 1 0 0 0 1.41 0l5-5a1 1 0 0 0 0-1.41z"/></svg>
                                    Choose File
                                </label>
                                <span class="file-name" id="tableFileName<?php echo $item['id']; ?>"></span>
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="menu-thumb" alt="Image">
                                <?php endif; ?>
                            </div>
                            <div>
                                <input type="text" name="options" value="<?php echo htmlspecialchars($item['options'] ?? ''); ?>" placeholder="Options" style="width:160px;">
                            </div>
                            <div class="actions">
                                <button type="submit" name="edit_item">Save</button>
                                <button type="submit" name="delete_item" onclick="return confirm('Delete this item?');">Delete</button>
                            </div>
                        </div>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<script>
// Auto-dismiss feedback messages after 5 seconds
window.addEventListener('DOMContentLoaded', function() {
    var feedback = document.querySelector('.feedback');
    if (feedback) {
        setTimeout(function() {
            feedback.style.opacity = '0';
            setTimeout(function() { feedback.style.display = 'none'; }, 500);
        }, 5000);
    }
});
// Menu item block hover effect
window.addEventListener('DOMContentLoaded', function() {
    var blocks = document.querySelectorAll('.menu-item-block');
    for (let i = 0; i < blocks.length; i += 2) {
        blocks[i].addEventListener('mouseenter', function() {
            blocks[i].classList.add('hover');
            if (blocks[i+1]) blocks[i+1].classList.add('hover');
        });
        blocks[i].addEventListener('mouseleave', function() {
            blocks[i].classList.remove('hover');
            if (blocks[i+1]) blocks[i+1].classList.remove('hover');
        });
        if (blocks[i+1]) {
            blocks[i+1].addEventListener('mouseenter', function() {
                blocks[i].classList.add('hover');
                blocks[i+1].classList.add('hover');
            });
            blocks[i+1].addEventListener('mouseleave', function() {
                blocks[i].classList.remove('hover');
                blocks[i+1].classList.remove('hover');
            });
        }
    }
});

function openAddModal() {
    document.getElementById('addModalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeAddModal() {
    document.getElementById('addModalOverlay').classList.remove('active');
    document.body.style.overflow = '';
}
function showFileName(input) {
    var fileName = input.files && input.files.length > 0 ? input.files[0].name : '';
    document.getElementById('modalFileName').textContent = fileName;
}
function showTableFileName(input, id) {
    var fileName = input.files && input.files.length > 0 ? input.files[0].name : '';
    document.getElementById('tableFileName' + id).textContent = fileName;
}
</script>
</body>
</html> 