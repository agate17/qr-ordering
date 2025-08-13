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
        <title>Admin pieslēgšanās</title>
        <style>
            body { font-family: sans-serif; background: #f8f8f8; }
            .container { max-width: 350px; margin: 80px auto; background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
            h1 { text-align: center; }
            .error { color: #c0392b; text-align: center; }
        </style>
    </head>
    <body>
    <div class="container">
        <h1>Admin pieslēgšanās</h1>
        <?php if (!empty($error)) echo '<div class="error">'.$error.'</div>'; ?>
        <form method="post">
            <input type="password" name="password" placeholder="parole" required style="width:100%;padding:10px;margin-bottom:16px;">
            <button type="submit" name="admin_login" style="width:100%;padding:10px;">pieslēgties</button>
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
    if ($cres && $cres->num_rows > 0) {
        while ($row = $cres->fetch_assoc()) {
            $categories[] = $row;
        }
    }
}
// Ensure $categories is always an array
if (!is_array($categories)) {
    $categories = [];
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
    if ($cres && $cres->num_rows > 0) {
        while ($row = $cres->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    // Ensure $categories is always an array
    if (!is_array($categories)) {
        $categories = [];
    }
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
            try {
                $conn->query("DELETE FROM menu_items WHERE id=$id");
                $feedback = 'Menu item deleted.';
                $feedback_type = 'success';
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1451) {
                    $feedback = 'Cannot delete this menu item because it has been ordered. Set it as unavailable instead.';
                    $feedback_type = 'error';
                } else {
                    $feedback = 'Error deleting menu item: ' . $e->getMessage();
                    $feedback_type = 'error';
                }
            }
        }
    }
    // Fetch menu
    $res = $conn->query('SELECT * FROM menu_items ORDER BY id');
    $menu = [];
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            // Ensure all required keys exist with proper defaults
            $menu[] = array_merge([
                'id' => 0,
                'name' => '',
                'description' => '',
                'price' => 0,
                'category_id' => null,
                'image_path' => null,
                'options' => '',
                'available' => 1
            ], $row);
        }
    }
    // Ensure $menu is always an array
    if (!is_array($menu)) {
        $menu = [];
    }
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

// Handle restaurant settings update
if ($conn && isset($_POST['update_settings'])) {
    $table_count = intval($_POST['table_count']);
    if ($table_count >= 1 && $table_count <= 50) {
        update_restaurant_setting('table_count', $table_count);
        $feedback = 'Restaurant settings updated.';
        $feedback_type = 'success';
    } else {
        $feedback = 'Table count must be between 1 and 50.';
        $feedback_type = 'error';
    }
}

// Get current settings
$current_table_count = get_table_count();

// Handle database cleanup operations
if ($conn && isset($_POST['cleanup_action'])) {
    $cleanup_type = $_POST['cleanup_type'] ?? '';
    $confirmed = isset($_POST['confirm_cleanup']);
    
    if (!$confirmed) {
        $feedback = 'Please confirm cleanup by checking the confirmation box.';
        $feedback_type = 'error';
    } else {
        switch ($cleanup_type) {
            case 'paid_orders':
                // Delete paid orders and their items
                $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'paid'");
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    // Delete order items first (due to foreign key constraint)
                    $conn->query("DELETE oi FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status = 'paid'");
                    // Delete orders
                    $conn->query("DELETE FROM orders WHERE status = 'paid'");
                    $feedback = "Deleted $count paid orders and their items.";
                    $feedback_type = 'success';
                } else {
                    $feedback = 'No paid orders found to delete.';
                    $feedback_type = 'success';
                }
                break;
                
            case 'old_orders':
                $days = intval($_POST['days_old'] ?? 30);
                $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE created_at < DATE_SUB(NOW(), INTERVAL $days DAY)");
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    // Delete order items first
                    $conn->query("DELETE oi FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.created_at < DATE_SUB(NOW(), INTERVAL $days DAY)");
                    // Delete orders
                    $conn->query("DELETE FROM orders WHERE created_at < DATE_SUB(NOW(), INTERVAL $days DAY)");
                    $feedback = "Deleted $count orders older than $days days and their items.";
                    $feedback_type = 'success';
                } else {
                    $feedback = "No orders older than $days days found.";
                    $feedback_type = 'success';
                }
                break;
                
            case 'all_orders':
                $result = $conn->query("SELECT COUNT(*) as count FROM orders");
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    // Delete all order items first
                    $conn->query("DELETE FROM order_items");
                    // Delete all orders
                    $conn->query("DELETE FROM orders");
                    // Reset auto increment
                    $conn->query("ALTER TABLE orders AUTO_INCREMENT = 1");
                    $conn->query("ALTER TABLE order_items AUTO_INCREMENT = 1");
                    $feedback = "Deleted all $count orders and their items. Database reset.";
                    $feedback_type = 'success';
                } else {
                    $feedback = 'No orders found to delete.';
                    $feedback_type = 'success';
                }
                break;
                
            default:
                $feedback = 'Invalid cleanup type selected.';
                $feedback_type = 'error';
        }
    }
}

// Get cleanup statistics
$cleanup_stats = [];
if ($conn) {
    // Total orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    $cleanup_stats['total_orders'] = $result->fetch_assoc()['count'];
    
    // Paid orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'paid'");
    $cleanup_stats['paid_orders'] = $result->fetch_assoc()['count'];
    
    // Orders older than 30 days
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $cleanup_stats['old_orders_30'] = $result->fetch_assoc()['count'];
    
    // Orders older than 7 days
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $cleanup_stats['old_orders_7'] = $result->fetch_assoc()['count'];
    
    // Total order items
    $result = $conn->query("SELECT COUNT(*) as count FROM order_items");
    $cleanup_stats['total_order_items'] = $result->fetch_assoc()['count'];
    
    // Database size estimation (approximate)
    $result = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size (MB)' FROM information_schema.tables WHERE table_schema=DATABASE()");
    $size_data = $result->fetch_assoc();
    $cleanup_stats['db_size'] = $size_data['DB Size (MB)'] ?? 'Unknown';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Menu</title>
    <link rel="stylesheet" type="text/css" href="assets/css/admin_menu.css?v=<?php echo time(); ?>">
</head>
<body>
<a href="admin_menu.php?logout=1" class="logout">Logout</a>
<div class="container">
    <?php if ($feedback): ?>
        <div class="feedback<?php if ($feedback_type === 'error') echo ' error'; ?>"><?php echo htmlspecialchars($feedback); ?></div>
    <?php endif; ?>
    <div class="section">
        <h1>pārskati</h1>
        <form method="post" style="margin-bottom:16px;">
            <div class="form-row">
                <label>Sākuma datums: <input type="date" name="report_start" value="<?php echo htmlspecialchars($report_start); ?>"></label>
                <label>Beigu datums: <input type="date" name="report_end" value="<?php echo htmlspecialchars($report_end); ?>"></label>
                <button type="submit" name="show_report">Rādīt atskaiti</button>
            </div>
        </form>
        <?php if (isset($_POST['show_report'])): ?>
            <div class="summary">
                <strong>Pasūtījumu kopskaits:</strong> <?php echo count($report_orders); ?> &nbsp; | &nbsp;
                <strong>Kopējie ieņēmumi:</strong> €<?php echo number_format($report_total, 2); ?>
            </div>
            <table style="margin-bottom:0;">
                <tr><th>Datums/Laiks</th><th>galds</th><th>statuss</th><th>Kopā (€)</th></tr>
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
    <!-- Restaurant Settings Section -->
    <div class="section">
        <h1>Restorāna iestatījumi</h1>
        <form method="post" class="form-row">
            <label>Galdu skaits: 
                <input type="number" name="table_count" value="<?php echo $current_table_count; ?>" min="1" max="50" required>
            </label>
            <button type="submit" name="update_settings">Saglabāt iestatījumus</button>
        </form>
        <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
            Pašreiz ir <?php echo $current_table_count; ?> galdi. Mainot šo skaitu, automātiski tiks atjaunināti QR kodi un ēdienkartes saites.
        </p>
    </div>
    
    <!-- Categories Section -->
    <div class="section">
        <h1>Pārvaldīt kategorijas</h1>
        <form method="post" class="form-row">
            <label>Jauna kategorija: <input type="text" name="cat_name" placeholder="kategorijas nosaukums" required></label>
            <button type="submit" name="add_category">Pievienot kategoriju</button>
        </form>
        <table style="margin-bottom:0;">
            <tr><th>ID</th><th>Nosaukums</th><th>Darbības</th></tr>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <form method="post" class="inline">
                    <td><?php echo $cat['id']; ?><input type="hidden" name="cat_id" value="<?php echo $cat['id']; ?>"></td>
                    <td><input type="text" name="cat_name" value="<?php echo htmlspecialchars($cat['name']); ?>" required></td>
                    <td>
                        <button type="submit" name="edit_category">Saglabāt</button>
                        <button type="submit" name="delete_category" onclick="return confirm('Delete this category?');">Dzēst</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <!-- Menu Items Section -->
    <div class="section">
        <h1> Pārvaldīt ēdienkarti</h1>
        <button class="add-item-btn" onclick="openAddModal()">pievienot ēdienu</button>
        <div class="modal-overlay" id="addModalOverlay" onclick="if(event.target===this)closeAddModal()">
            <div class="add-modal">
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
                <h2>pievienot ēdienu</h2>
                <form class="add-form" method="post" enctype="multipart/form-data" autocomplete="off">
                    <label>nosaukums:
                        <input type="text" name="name" placeholder="ēdiena nosaukums" required>
                    </label>
                    <label>apraksts (sastāvdaļas):
                        <textarea name="description" placeholder="Description"></textarea>
                    </label>
                    <label>Cena:
                        <input type="number" name="price" placeholder="cena" step="0.01" min="0.01" required>
                    </label>
                    <label>kategorija:
                        <select name="category_id">
                            <option value="">izvēlies kategoriju</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>attēls:</label>
                    <input type="file" id="modalImageInput" name="image" accept="image/*" style="display:none;" onchange="showFileName(this)">
                    <label for="modalImageInput" class="custom-file-label">
                        <svg viewBox="0 0 20 20"><path d="M16.88 9.94a1 1 0 0 0-1.41 0l-3.17 3.17V3a1 1 0 1 0-2 0v10.11l-3.17-3.17a1 1 0 0 0-1.41 1.41l5 5a1 1 0 0 0 1.41 0l5-5a1 1 0 0 0 0-1.41z"/></svg>
                        izvēlies failu
                    </label>
                    <span class="file-name" id="modalFileName"></span>
                    <label><input type="checkbox" name="available" checked> pieejams</label>
                    <div class="modal-actions">
                        <button type="button" onclick="closeAddModal()">atcelt</button>
                        <button type="submit" name="add_item">pievienot ēdienu</button>
                    </div>
                </form>
            </div>
        </div>
        <table>
            <tr>
                <th>ID</th>
                <th>Nosaukums</th>
                <th>Apraksts</th>
                <th>kategorija</th>
                <th class="menu-items-table-price">cena (€)</th>
                <th>pieejams</th>
            </tr>
            <?php if (!empty($menu) && is_array($menu)): ?>
            <?php foreach ($menu as $idx => $item): ?>
            <form class="inline" method="post" enctype="multipart/form-data">
            <tr class="menu-item-block <?php echo $idx % 2 === 0 ? 'even' : 'odd'; ?>">
                <td><?php echo isset($item['id']) ? $item['id'] : ''; ?><input type="hidden" name="id" value="<?php echo isset($item['id']) ? $item['id'] : ''; ?>"></td>
                <td><input type="text" name="name" value="<?php echo htmlspecialchars(isset($item['name']) ? $item['name'] : ''); ?>" required></td>
                <td><textarea name="description" placeholder="apraksts" style="width:120px;vertical-align:top;"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea></td>
                <td>
                    <select name="category_id">
                        <option value="">izvēlies kategoriju</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php if (isset($item) && isset($item['category_id']) && $item['category_id'] == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="menu-items-table-price"><input type="number" name="price" value="<?php echo number_format(isset($item['price']) ? $item['price'] : 0,2,'.',''); ?>" step="0.01" min="0.01" required></td>
                <td><input type="checkbox" name="available" <?php if (!isset($item['available']) || $item['available']) echo 'checked'; ?>></td>
                <td colspan="3"></td>
            </tr>
            <tr class="menu-item-block <?php echo $idx % 2 === 0 ? 'even' : 'odd'; ?> end-of-block">
                <td colspan="9">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:18px; justify-content:center;">
                        <div>
                            <input type="file" id="tableImageInput<?php echo isset($item['id']) ? $item['id'] : ''; ?>" name="image" accept="image/*" style="display:none;" onchange="showTableFileName(this, <?php echo isset($item['id']) ? $item['id'] : ''; ?>)">
                            <label for="tableImageInput<?php echo isset($item['id']) ? $item['id'] : ''; ?>" class="custom-file-label-table">
                                <svg viewBox="0 0 20 20"><path d="M16.88 9.94a1 1 0 0 0-1.41 0l-3.17 3.17V3a1 1 0 1 0-2 0v10.11l-3.17-3.17a1 1 0 0 0-1.41 1.41l5 5a1 1 0 0 0 1.41 0l5-5a1 1 0 0 0 0-1.41z"/></svg>
                                izvēlies failu
                            </label>
                            <span class="file-name" id="tableFileName<?php echo isset($item['id']) ? $item['id'] : ''; ?>"></span>
                            <?php if (!empty($item['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="menu-thumb" alt="Image">
                            <?php endif; ?>
                        </div>
                        <div class="actions">
                            <button type="submit" name="edit_item">saglabāt</button>
                            <button type="submit" name="delete_item" onclick="return confirm('Delete this item?');">dzēst</button>
                        </div>
                    </div>
                </td>
            </tr>
            </form>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="6" style="text-align: center; padding: 20px;">ēdienkarte ir tukša. pievieno ēdienu lai veiktu darbības</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Database Cleanup Section -->
    <div class="section">
        <h1>Datu bāzes tīrīšana</h1>
        
        <!-- Statistics -->
        <div style="background: #f0f8ff; border: 1px solid #b8daff; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
            <div class="section-title" style="margin-bottom: 12px;">Datu bāzes statistika</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 0.95em;">
                <div><strong>Kopā pasūtījumi:</strong> <?php echo $cleanup_stats['total_orders'] ?? 0; ?></div>
                <div><strong>Apmaksāti pasūtījumi:</strong> <?php echo $cleanup_stats['paid_orders'] ?? 0; ?></div>
                <div><strong>Vecāki par 7 dienām:</strong> <?php echo $cleanup_stats['old_orders_7'] ?? 0; ?></div>
                <div><strong>Vecāki par 30 dienām:</strong> <?php echo $cleanup_stats['old_orders_30'] ?? 0; ?></div>
                <div><strong>Kopā pasūtījuma pozīcijas:</strong> <?php echo $cleanup_stats['total_order_items'] ?? 0; ?></div>
                <div><strong>DB izmērs (aptuveni):</strong> <?php echo $cleanup_stats['db_size'] ?? 'Unknown'; ?> MB</div>
            </div>
        </div>
        
        <!-- Cleanup Options -->
        <form method="post" style="margin-bottom: 20px;">
            <div style="background: #fff5f5; border: 1px solid #ffcccc; border-radius: 6px; padding: 16px; margin-bottom: 16px;">
                <div class="section-title" style="color: #c0392b; margin-bottom: 12px;">⚠️ Brīdinājums</div>
                <p style="margin: 0; font-size: 0.95em; color: #666;">
                    Datu dzēšana ir neatgriezeniska darbība. Pirms turpināšanas pārliecinieties, ka esat izveidojuši datu bāzes dublējumu.
                    Dzēstie dati netiks saglabāti un tos nebūs iespējams atjaunot.
                </p>
            </div>
            
            <div class="form-row" style="align-items: flex-start; margin-bottom: 16px;">
                <label style="min-width: 120px;">Tīrīšanas veids:</label>
                <div style="flex: 1;">
                    <div style="margin-bottom: 8px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: normal; cursor: pointer;">
                            <input type="radio" name="cleanup_type" value="paid_orders" style="margin-right: 6px;" required>
                            Dzēst tikai apmaksātos pasūtījumus (<?php echo $cleanup_stats['paid_orders'] ?? 0; ?> pasūtījumi)
                        </label>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: normal; cursor: pointer;">
                            <input type="radio" name="cleanup_type" value="old_orders" style="margin-right: 6px;" required>
                            Dzēst vecos pasūtījumus (vecāki par 
                            <input type="number" name="days_old" value="30" min="1" max="365" style="width: 60px; margin: 0 4px;">
                            dienām)
                        </label>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: normal; cursor: pointer; color: #c0392b;">
                            <input type="radio" name="cleanup_type" value="all_orders" style="margin-right: 6px;" required>
                            <strong>Dzēst VISUS pasūtījumus (<?php echo $cleanup_stats['total_orders'] ?? 0; ?> pasūtījumi)</strong>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="form-row" style="margin-bottom: 16px;">
                <label style="display: flex; align-items: center; cursor: pointer; color: #c0392b; font-weight: 600;">
                    <input type="checkbox" name="confirm_cleanup" style="margin-right: 8px;" required>
                    Es apstiprinu, ka vēlos dzēst izvēlētos datus un apzinos, ka šī darbība ir neatgriezeniska
                </label>
            </div>
            
            <div class="form-row">
                <button type="submit" name="cleanup_action" style="background: #e74c3c; color: #fff; padding: 10px 20px; font-weight: 600;" 
                        onclick="return confirm('Vai esat pārliecināti, ka vēlaties turpināt datu dzēšanu? Šī darbība ir neatgriezeniska!');">
                    Izpildīt datu tīrīšanu
                </button>
            </div>
        </form>
        
        <!-- Additional Info -->
        <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 14px; font-size: 0.9em; color: #666;">
            <strong>Ieteikumi:</strong>
            <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                <li>Regulāri dzēsiet apmaksātos pasūtījumus, lai uzturētu datu bāzi tīru</li>
                <li>Saglabājiet vecus pasūtījumus 7-30 dienas grāmatvedības vajadzībām</li>
                <li>Izveidojiet datu bāzes dublējumu pirms lieliem dzēšanas darbiem</li>
                <li>Uzraugiet datu bāzes izmēru, lai izvairītos no veiktspējas problēmām</li>
            </ul>
        </div>
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

<?php
// Close database connection at the very end
if ($conn) $conn->close();
?>