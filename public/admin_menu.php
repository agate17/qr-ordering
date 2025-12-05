<?php
session_start();
$ADMIN_PASS = 'demo123'; // Change for real use

// Function to resize background images for better repeating patterns
function resize_background_image($file_path, $max_width = 100, $max_height = 100) {
    if (!file_exists($file_path)) {
        error_log("Resize failed: File does not exist: $file_path");
        return false;
    }
    
    // Check if GD extension is available
    if (!extension_loaded('gd')) {
        error_log("Resize failed: GD extension not available");
        return false;
    }
    
    // Get image info
    $image_info = getimagesize($file_path);
    if (!$image_info) {
        error_log("Resize failed: Could not get image info for: $file_path");
        return false;
    }
    
    $original_width = $image_info[0];
    $original_height = $image_info[1];
    $mime_type = $image_info['mime'];
    
    // Don't resize if image is already smaller
    if ($original_width <= $max_width && $original_height <= $max_height) {
        error_log("Image already smaller ({$original_width}x{$original_height}), no resize needed");
        return true;
    }
    
    // Calculate new dimensions maintaining aspect ratio
    $ratio = min($max_width / $original_width, $max_height / $original_height);
    $new_width = (int)($original_width * $ratio);
    $new_height = (int)($original_height * $ratio);
    
    error_log("Resizing image from {$original_width}x{$original_height} to {$new_width}x{$new_height}");
    
    // Create image resource based on type
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $source = imagecreatefromjpeg($file_path);
            break;
        case 'image/png':
            $source = imagecreatefrompng($file_path);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($file_path);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $source = imagecreatefromwebp($file_path);
            } else {
                return false;
            }
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // Create new image with calculated dimensions
    $destination = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize image
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
    
    // Save resized image
    $success = false;
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $success = imagejpeg($destination, $file_path, 85); // 85% quality
            break;
        case 'image/png':
            $success = imagepng($destination, $file_path, 6); // Compression level 6
            break;
        case 'image/gif':
            $success = imagegif($destination, $file_path);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $success = imagewebp($destination, $file_path, 85);
            }
            break;
    }
    
    // Free memory
    imagedestroy($source);
    imagedestroy($destination);
    
    return $success;
}

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

if ($conn) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM menu_items LIKE 'included_sauces'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE menu_items ADD COLUMN included_sauces TINYINT UNSIGNED NOT NULL DEFAULT 0");
    }
    $columnCheck = $conn->query("SHOW COLUMNS FROM menu_items LIKE 'size_options'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE menu_items ADD COLUMN size_options TEXT NULL");
    }
}

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
        $included_sauces = isset($_POST['included_sauces']) ? max(0, min(5, intval($_POST['included_sauces']))) : 0;
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
        $conn->query("INSERT INTO menu_items (name, description, price, category_id, image_path, options, available, included_sauces, size_options) VALUES ('$name', '$description', $price, $cat_sql, " . ($image_path ? "'$image_path'" : 'NULL') . ", '$options', $available, $included_sauces, $size_options_sql)");
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
        $included_sauces = isset($_POST['included_sauces']) ? max(0, min(5, intval($_POST['included_sauces']))) : 0;
        // Handle size options
        $size_options = null;
        if (isset($_POST['has_sizes']) && $_POST['has_sizes'] == '1') {
            $small_price = isset($_POST['size_small_price']) ? floatval($_POST['size_small_price']) : 0;
            $large_price = isset($_POST['size_large_price']) ? floatval($_POST['size_large_price']) : 0;
            if ($small_price > 0 || $large_price > 0) {
                $size_options = json_encode([
                    'small' => ['name' => 'Parastais', 'price' => $small_price],
                    'large' => ['name' => 'Lielais', 'price' => $large_price]
                ], JSON_UNESCAPED_UNICODE);
            }
        }
        $size_options_sql = $size_options ? "'" . $conn->real_escape_string($size_options) . "'" : 'NULL';
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
        $conn->query("UPDATE menu_items SET name='$name', description='$description', price=$price, category_id=$cat_sql, options='$options', available=$available, included_sauces=$included_sauces, size_options=$size_options_sql" . ($image_path ? ", image_path='$image_path'" : '') . " WHERE id=$id");
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

// Handle menu background settings update
if (isset($_POST['update_background'])) {
    // Always store POST data in session for debugging
    $_SESSION['last_post'] = $_POST;
    $_SESSION['last_files'] = isset($_FILES['background_image']) ? [
        'name' => $_FILES['background_image']['name'] ?? 'none',
        'error' => $_FILES['background_image']['error'] ?? 'none'
    ] : 'no file';
    
    // Debug: Log what we received
    error_log("=== BACKGROUND UPDATE DEBUG ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    error_log("Database connection status: " . ($conn ? 'CONNECTED' : 'NOT CONNECTED'));
    
    // Check database connection
    if (!$conn) {
        $feedback = 'Database connection failed. Cannot save settings.';
        $feedback_type = 'error';
        $_SESSION['bg_debug'] = [
            'save_success' => false,
            'db_error' => 'Database connection is null or false. $conn variable is: ' . var_export($conn, true),
            'post_data' => $_POST,
            'before_save' => 'Could not get current settings - no DB connection'
        ];
    } else {
    
    // Get current settings first to preserve image if not changed
    $current_settings = get_menu_background_settings();
    
    $background_settings = [
        'background_color' => isset($_POST['background_color']) ? $_POST['background_color'] : '#f8f8f8',
        'background_repeat' => isset($_POST['background_repeat']) ? $_POST['background_repeat'] : 'no-repeat',
        'background_size' => isset($_POST['background_size']) ? $_POST['background_size'] : 'cover',
        'background_position' => isset($_POST['background_position']) ? $_POST['background_position'] : 'center center',
        'background_image' => $current_settings['background_image'] // Default to existing image
    ];
    
    error_log("Initial background_settings: " . print_r($background_settings, true));
    
    // Handle background image upload - PRIORITY: new upload > remove > keep existing
    $image_handled = false;
    
    // FIRST: Check if a new image is being uploaded (highest priority)
    if (isset($_FILES['background_image']) && !empty($_FILES['background_image']['name']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
        error_log("New image upload detected! File: " . $_FILES['background_image']['name']);
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['background_image']['type'];
        $file_size = $_FILES['background_image']['size'];
        $file_error = $_FILES['background_image']['error'];
        
        error_log("File type: $file_type, Size: $file_size, Error: $file_error");
        
        // Check for upload errors
        if ($file_error !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
            ];
            $feedback = 'Upload error: ' . ($error_messages[$file_error] ?? 'Unknown error');
            $feedback_type = 'error';
            $image_handled = true;
        } elseif (!in_array($file_type, $allowed_types)) {
            $feedback = 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed. Got: ' . $file_type;
            $feedback_type = 'error';
            $image_handled = true;
            error_log("Invalid file type: $file_type");
        } elseif ($file_size > $max_size) {
            $feedback = 'File size too large. Maximum size is 5MB. Got: ' . round($file_size / 1024 / 1024, 2) . 'MB';
            $feedback_type = 'error';
            $image_handled = true;
            error_log("File too large: $file_size bytes");
        } else {
            $img_name = uniqid('bg_', true) . '_' . basename($_FILES['background_image']['name']);
            $target_dir = __DIR__ . '/assets/images/backgrounds/';
            if (!is_dir($target_dir)) {
                if (!mkdir($target_dir, 0777, true)) {
                    $feedback = 'Failed to create backgrounds directory.';
                    $feedback_type = 'error';
                    $image_handled = true;
                    error_log("Failed to create directory: $target_dir");
                }
            }
            
            if (empty($feedback)) {
                $target_file = $target_dir . $img_name;
                error_log("Attempting to move file from: " . $_FILES['background_image']['tmp_name'] . " to: " . $target_file);
                
                if (move_uploaded_file($_FILES['background_image']['tmp_name'], $target_file)) {
                    // Resize image to make it smaller for better repeating patterns (max 100x100px)
                    // This creates many more tiles when repeating, making the background less bland
                    $resized = resize_background_image($target_file, 100, 100);
                    if ($resized) {
                        error_log("Background image resized successfully to max 100x100px for better repeating pattern");
                    } else {
                        error_log("Warning: Image uploaded but resize failed (may already be small enough or GD not available)");
                    }
                    $background_settings['background_image'] = 'assets/images/backgrounds/' . $img_name;
                    $image_handled = true;
                    error_log("Image uploaded successfully: " . $background_settings['background_image']);
                } else {
                    $last_error = error_get_last();
                    $error_msg = $last_error ? $last_error['message'] : 'Unknown error';
                    $feedback = 'Failed to upload image. Check directory permissions. Error: ' . $error_msg;
                    $feedback_type = 'error';
                    $image_handled = true;
                    error_log("Image upload failed. Target: $target_file, Error: $error_msg");
                }
            } else {
                error_log("Skipping file move because feedback is already set: " . $feedback);
            }
        }
    } elseif (isset($_FILES['background_image']) && !empty($_FILES['background_image']['name'])) {
        // File was provided but has an error
        error_log("File upload has error: " . $_FILES['background_image']['error']);
        $image_handled = true; // Mark as handled so remove doesn't override
    }
    // SECOND PRIORITY: If no upload, check if user wants to remove the image
    elseif (isset($_POST['remove_background_image']) && $_POST['remove_background_image'] == '1' && !$image_handled) {
        $background_settings['background_image'] = null;
        $image_handled = true;
        error_log("Background image removal requested");
    }
    // If no new upload and no remove, keep existing (already set as default on line 315)
    
    // Always process the save, even if feedback was set (for image upload errors, we still want to save other settings)
    // Debug: Store what we're about to save for display
    $debug_info = [];
    $debug_info['before_save'] = $background_settings;
    $debug_info['post_data'] = $_POST;
    $debug_info['files_data'] = isset($_FILES['background_image']) ? [
        'name' => $_FILES['background_image']['name'] ?? 'none',
        'error' => $_FILES['background_image']['error'] ?? 'none',
        'size' => $_FILES['background_image']['size'] ?? 'none'
    ] : 'no file';
    $debug_info['feedback_before_save'] = $feedback; // Store any existing feedback
    
    // Only proceed with database save if no critical error occurred
    if (empty($feedback) || $feedback === '') {
        // Log what we're about to save
        error_log("Final background_settings before save: " . print_r($background_settings, true));
        
        $update_result = update_menu_background_settings($background_settings);
        
        // Always verify what was actually saved, regardless of return value
        $saved_settings = get_menu_background_settings();
        $debug_info['after_save'] = $saved_settings;
        $debug_info['update_function_returned'] = $update_result;
        
        // Check if values actually match what we tried to save
        $color_matches = ($saved_settings['background_color'] === $background_settings['background_color']);
        $repeat_matches = ($saved_settings['background_repeat'] === $background_settings['background_repeat']);
        $size_matches = ($saved_settings['background_size'] === $background_settings['background_size']);
        $position_matches = ($saved_settings['background_position'] === $background_settings['background_position']);
        $image_matches = ($saved_settings['background_image'] === $background_settings['background_image']);
        
        $all_match = $color_matches && $repeat_matches && $size_matches && $position_matches && $image_matches;
        
        $debug_info['values_match'] = [
            'color' => $color_matches,
            'repeat' => $repeat_matches,
            'size' => $size_matches,
            'position' => $position_matches,
            'image' => $image_matches,
            'all_match' => $all_match
        ];
        
        if ($update_result && $all_match) {
            $debug_info['save_success'] = true;
            error_log("Settings after save: " . print_r($saved_settings, true));
            
            $feedback = 'Menu background settings updated successfully.';
            if (!empty($background_settings['background_image'])) {
                $feedback .= ' Image: ' . htmlspecialchars($background_settings['background_image']);
            } elseif (isset($_POST['remove_background_image']) && $_POST['remove_background_image'] == '1') {
                $feedback .= ' Background image removed.';
            }
            $feedback_type = 'success';
        } else {
            $debug_info['save_success'] = false;
            if (!$update_result) {
                $debug_info['db_error'] = 'Database update function returned false';
            } else {
                $debug_info['db_error'] = 'Database update returned true but values do not match!';
                $debug_info['db_error'] .= ' Color match: ' . ($color_matches ? 'YES' : 'NO');
                $debug_info['db_error'] .= ' Expected: ' . $background_settings['background_color'] . ', Got: ' . $saved_settings['background_color'];
            }
            $feedback = 'Failed to update background settings. Check debug info below.';
            $feedback_type = 'error';
            error_log("Database update failed or values don't match!");
            error_log("Expected: " . print_r($background_settings, true));
            error_log("Got: " . print_r($saved_settings, true));
        }
    } else {
        // Feedback was set (probably an image upload error), but we should still try to save other settings
        $debug_info['save_success'] = false;
        $debug_info['db_error'] = 'Save skipped because feedback was already set: ' . $feedback;
        error_log("Save skipped - feedback already set: " . $feedback);
    }
    
    // Always store debug info in session for display
    $_SESSION['bg_debug'] = $debug_info;
    } // End of else block for $conn check
}

// Handle reset background to default
if ($conn && isset($_POST['reset_background'])) {
    if (reset_menu_background_to_default()) {
        $feedback = 'Background reset to default successfully.';
        $feedback_type = 'success';
    } else {
        $feedback = 'Failed to reset background.';
        $feedback_type = 'error';
    }
}

// Get current background settings
$current_bg_settings = get_menu_background_settings();

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
    
    <!-- Menu Background Customization Section -->
    <div class="section">
        <h1>Fona dizains</h1>
        <p style="font-size: 0.9em; color: #666; margin-bottom: 20px;">
            Pielāgojiet ēdienkartes fonu. Izmaiņas tiks redzamas uzreiz visās QR ēdienkartēs.
        </p>
        
        <!-- Always show if form was submitted -->
        <?php if (isset($_POST['update_background'])): ?>
            <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                <strong>📤 Form was submitted!</strong> POST data received: <?php echo count($_POST); ?> fields.
            </div>
        <?php endif; ?>
        
        <?php if (isset($feedback)): ?>
            <div style="background: <?php echo $feedback_type === 'error' ? '#fee' : '#efe'; ?>; border: 1px solid <?php echo $feedback_type === 'error' ? '#fcc' : '#cfc'; ?>; padding: 12px; border-radius: 6px; margin-bottom: 20px; color: <?php echo $feedback_type === 'error' ? '#c33' : '#3c3'; ?>;">
                <strong><?php echo $feedback_type === 'error' ? '⚠ Kļūda:' : '✓ Veiksmīgi:'; ?></strong> <?php echo htmlspecialchars($feedback); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_POST['update_background']) || isset($_SESSION['last_post'])): ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9em;">
                <strong>📤 Form Submitted:</strong> The form was received by the server.
                <?php if (isset($_SESSION['last_post'])): ?>
                    <details style="margin-top: 8px;">
                        <summary style="cursor: pointer; color: #856404;">View POST data</summary>
                        <pre style="background: white; padding: 8px; margin-top: 8px; border-radius: 4px; overflow-x: auto; font-size: 0.85em;"><?php print_r($_SESSION['last_post']); ?></pre>
                    </details>
                <?php endif; ?>
            </div>
            <?php unset($_SESSION['last_post'], $_SESSION['last_files']); ?>
        <?php else: ?>
            <div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9em; color: #0c5460;">
                <strong>ℹ️ Info:</strong> No form submission detected. Make sure to click "💾 Saglabāt izmaiņas" button after making changes.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['bg_debug']) || isset($_POST['update_background'])): ?>
            <div style="background: #f0f8ff; border: 2px solid #3498db; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-family: monospace; font-size: 0.9em; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h4 style="margin-top: 0; color: #2c3e50; font-size: 1.2em; font-weight: bold;">🔍 DEBUG INFO (Last Save Attempt)</h4>
                <?php if (!isset($_SESSION['bg_debug'])): ?>
                    <p style="color: red;"><strong>⚠️ Warning:</strong> Debug info not found in session. This might mean the form was submitted but the processing code didn't run.</p>
                    <p><strong>POST data received:</strong></p>
                    <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;"><?php print_r($_POST); ?></pre>
                <?php else: ?>
                    <details open>
                        <summary style="cursor: pointer; color: #3498db; font-weight: 600;">Click to view debug details</summary>
                        <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px;">
                            <p style="font-size: 1.1em;"><strong>Save Success:</strong> <span style="color: <?php echo isset($_SESSION['bg_debug']['save_success']) && $_SESSION['bg_debug']['save_success'] ? 'green' : 'red'; ?>; font-weight: bold; font-size: 1.2em;"><?php echo isset($_SESSION['bg_debug']['save_success']) && $_SESSION['bg_debug']['save_success'] ? '✅ YES' : '❌ NO'; ?></span></p>
                            <?php if (isset($_SESSION['bg_debug']['values_match'])): ?>
                                <p><strong>Values Match Check:</strong></p>
                                <ul style="margin: 5px 0; list-style: none; padding-left: 0;">
                                    <li>Color: <?php echo $_SESSION['bg_debug']['values_match']['color'] ? '✅' : '❌'; ?> (Expected: <?php echo htmlspecialchars($_SESSION['bg_debug']['before_save']['background_color'] ?? 'N/A'); ?>, Got: <?php echo htmlspecialchars($_SESSION['bg_debug']['after_save']['background_color'] ?? 'N/A'); ?>)</li>
                                    <li>Repeat: <?php echo $_SESSION['bg_debug']['values_match']['repeat'] ? '✅' : '❌'; ?></li>
                                    <li>Size: <?php echo $_SESSION['bg_debug']['values_match']['size'] ? '✅' : '❌'; ?></li>
                                    <li>Position: <?php echo $_SESSION['bg_debug']['values_match']['position'] ? '✅' : '❌'; ?></li>
                                    <li>Image: <?php echo $_SESSION['bg_debug']['values_match']['image'] ? '✅' : '❌'; ?></li>
                                </ul>
                            <?php endif; ?>
                            <p><strong>Settings Being Saved:</strong></p>
                            <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;"><?php print_r($_SESSION['bg_debug']['before_save'] ?? 'N/A'); ?></pre>
                            <?php if (isset($_SESSION['bg_debug']['after_save'])): ?>
                                <p><strong>Settings After Save (from DB):</strong></p>
                                <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;"><?php print_r($_SESSION['bg_debug']['after_save']); ?></pre>
                            <?php endif; ?>
                            <p><strong>POST Data Received:</strong></p>
                            <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;"><?php print_r($_SESSION['bg_debug']['post_data'] ?? 'N/A'); ?></pre>
                            <p><strong>File Upload Info:</strong></p>
                            <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;"><?php print_r($_SESSION['bg_debug']['files_data'] ?? 'N/A'); ?></pre>
                            <?php if (isset($_SESSION['bg_debug']['db_error'])): ?>
                                <p style="color: red;"><strong>Database Error:</strong> <?php echo htmlspecialchars($_SESSION['bg_debug']['db_error']); ?></p>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endif; ?>
            </div>
            <?php if (isset($_SESSION['bg_debug'])) unset($_SESSION['bg_debug']); // Clear after display ?>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" id="backgroundForm" onsubmit="return validateBackgroundForm()">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                <!-- Left Column: Settings -->
                <div>
                    <h3 style="margin-bottom: 16px; color: #2c3e50;">Fona iestatījumi</h3>
                    
                    <!-- Background Color -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                            Fona krāsa:
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" 
                                   id="background_color" 
                                   name="background_color" 
                                   value="<?php echo htmlspecialchars($current_bg_settings['background_color']); ?>"
                                   style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 6px; cursor: pointer;">
                            <input type="text" 
                                   id="background_color_text" 
                                   value="<?php echo htmlspecialchars($current_bg_settings['background_color']); ?>"
                                   style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;"
                                   placeholder="#f8f8f8">
                        </div>
                    </div>
                    
                    <!-- Background Image -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                            Fona attēls:
                        </label>
                        <input type="file" 
                               id="background_image" 
                               name="background_image" 
                               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px;">
                        <p style="font-size: 0.85em; color: #666; margin-bottom: 8px;">
                            Maksimālais faila izmērs: 5MB. Atbalstītie formāti: JPEG, PNG, GIF, WebP
                        </p>
                        <?php if (!empty($current_bg_settings['background_image'])): ?>
                            <div style="margin-top: 10px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="keep_existing_image" value="1" checked>
                                    <span>Saglabāt esošo attēlu</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 6px;">
                                    <input type="checkbox" name="remove_background_image" value="1">
                                    <span style="color: #e74c3c;">Noņemt fona attēlu</span>
                                </label>
                                <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                                    <strong>Pašreizējais attēls:</strong><br>
                                    <img src="<?php echo htmlspecialchars($current_bg_settings['background_image']); ?>" 
                                         alt="Current background" 
                                         style="max-width: 200px; max-height: 100px; margin-top: 8px; border-radius: 4px; border: 1px solid #ddd;">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Background Repeat -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                            Attēla atkārtošana:
                        </label>
                        <select name="background_repeat" id="background_repeat" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="no-repeat" <?php echo $current_bg_settings['background_repeat'] === 'no-repeat' ? 'selected' : ''; ?>>Nav atkārtošanas</option>
                            <option value="repeat" <?php echo $current_bg_settings['background_repeat'] === 'repeat' ? 'selected' : ''; ?>>Atkārtot (visos virzienos)</option>
                            <option value="repeat-x" <?php echo $current_bg_settings['background_repeat'] === 'repeat-x' ? 'selected' : ''; ?>>Atkārtot horizontāli</option>
                            <option value="repeat-y" <?php echo $current_bg_settings['background_repeat'] === 'repeat-y' ? 'selected' : ''; ?>>Atkārtot vertikāli</option>
                        </select>
                    </div>
                    
                    <!-- Background Size -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                            Attēla izmērs:
                        </label>
                        <select name="background_size" id="background_size" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="auto" <?php echo $current_bg_settings['background_size'] === 'auto' ? 'selected' : ''; ?>>Auto (oriģinālais izmērs)</option>
                            <option value="cover" <?php echo $current_bg_settings['background_size'] === 'cover' ? 'selected' : ''; ?>>Cover (pārklāj visu laukumu)</option>
                            <option value="contain" <?php echo $current_bg_settings['background_size'] === 'contain' ? 'selected' : ''; ?>>Contain (iekļauj visu attēlu)</option>
                        </select>
                    </div>
                    
                    <!-- Background Position -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                            Attēla pozīcija:
                        </label>
                        <select name="background_position" id="background_position" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="center center" <?php echo $current_bg_settings['background_position'] === 'center center' ? 'selected' : ''; ?>>Centrs</option>
                            <option value="top left" <?php echo $current_bg_settings['background_position'] === 'top left' ? 'selected' : ''; ?>>Augšā pa kreisi</option>
                            <option value="top center" <?php echo $current_bg_settings['background_position'] === 'top center' ? 'selected' : ''; ?>>Augšā centrā</option>
                            <option value="top right" <?php echo $current_bg_settings['background_position'] === 'top right' ? 'selected' : ''; ?>>Augšā pa labi</option>
                            <option value="center left" <?php echo $current_bg_settings['background_position'] === 'center left' ? 'selected' : ''; ?>>Centrā pa kreisi</option>
                            <option value="center right" <?php echo $current_bg_settings['background_position'] === 'center right' ? 'selected' : ''; ?>>Centrā pa labi</option>
                            <option value="bottom left" <?php echo $current_bg_settings['background_position'] === 'bottom left' ? 'selected' : ''; ?>>Apakšā pa kreisi</option>
                            <option value="bottom center" <?php echo $current_bg_settings['background_position'] === 'bottom center' ? 'selected' : ''; ?>>Apakšā centrā</option>
                            <option value="bottom right" <?php echo $current_bg_settings['background_position'] === 'bottom right' ? 'selected' : ''; ?>>Apakšā pa labi</option>
                        </select>
                    </div>
                    
                </div>
                
                <!-- Right Column: Preview -->
                <div>
                    <h3 style="margin-bottom: 16px; color: #2c3e50;">Priekšskatījums</h3>
                    <div id="backgroundPreview" style="width: 100%; min-height: 400px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo htmlspecialchars($current_bg_settings['background_color']); ?>; 
                        <?php if (!empty($current_bg_settings['background_image'])): ?>
                            background-image: url('<?php echo htmlspecialchars($current_bg_settings['background_image']); ?>');
                        <?php endif; ?>
                        background-repeat: <?php echo htmlspecialchars($current_bg_settings['background_repeat']); ?>;
                        background-size: <?php echo htmlspecialchars($current_bg_settings['background_size']); ?>;
                        background-position: <?php echo htmlspecialchars($current_bg_settings['background_position']); ?>;
                        position: relative; overflow: hidden;">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #2c3e50; font-weight: 600; padding: 20px; background: rgba(255, 255, 255, 0.9); border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="font-size: 1.2em; margin-bottom: 8px;">📋 Ēdienkarte</div>
                            <div style="font-size: 0.9em; color: #666;">Fona priekšskatījums</div>
                        </div>
                    </div>
                    <p style="font-size: 0.85em; color: #666; margin-top: 12px; font-style: italic;">
                        💡 Priekšskatījums tiek atjaunināts automātiski, mainot iestatījumus.
                    </p>
                </div>
            </div>
            
            <!-- Action Buttons - Full Width Below Grid -->
            <div style="display: flex; gap: 10px; margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                <button type="submit" name="update_background" style="flex: 1; padding: 14px 20px; background: #3498db; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 1em; box-shadow: 0 2px 4px rgba(52, 152, 219, 0.3);">
                    💾 Saglabāt izmaiņas
                </button>
                <button type="submit" name="reset_background" onclick="return confirm('Vai tiešām vēlaties atiestatīt fonu uz noklusējuma iestatījumiem?');" style="padding: 14px 20px; background: #95a5a6; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 1em;">
                    🔄 Atiestatīt
                </button>
            </div>
        </form>
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
                    <label>Mērču skaits (iekļauts cenā):
                        <input type="number" name="included_sauces" value="0" min="0" max="5" step="1">
                    </label>
                    <p style="font-size: 0.85em; color: #6b7280; margin-top: -10px;">
                        Norādi, cik mērces klients var izvēlēties šim ēdienam. 0 nozīmē bez mērču izvēles.
                    </p>
                    <label>
                        <input type="checkbox" name="has_sizes" value="1" id="hasSizesCheck" onchange="toggleSizeOptions()">
                        Ir izmēru opcijas (Parastais/Lielais)
                    </label>
                    <div id="sizeOptionsContainer" style="display: none; margin-top: 10px; padding: 12px; background: #f8f9fa; border-radius: 6px;">
                        <label style="display: block; margin-bottom: 8px;">
                            Parastais cena (€):
                            <input type="number" name="size_small_price" id="sizeSmallPrice" step="0.01" min="0" style="width: 100px; margin-left: 8px;">
                        </label>
                        <label style="display: block;">
                            Lielais cena (€):
                            <input type="number" name="size_large_price" id="sizeLargePrice" step="0.01" min="0" style="width: 100px; margin-left: 8px;">
                        </label>
                        <p style="font-size: 0.85em; color: #6b7280; margin-top: 8px;">
                            Ja nav izmēru opciju, izmanto tikai pamatcenu augstāk.
                        </p>
                    </div>
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
                        <div style="min-width:180px;">
                            <label style="font-weight:600; display:block; margin-bottom:6px;">Mērču skaits</label>
                            <input type="number" name="included_sauces" value="<?php echo isset($item['included_sauces']) ? intval($item['included_sauces']) : 0; ?>" min="0" max="5" step="1" style="width:80px; padding:6px;">
                            <div style="font-size:0.8em; color:#6b7280; margin-top:4px;">Cik mērces iekļautas cenā.</div>
                        </div>
                        <div style="min-width:220px;">
                            <label style="font-weight:600; display:block; margin-bottom:6px;">
                                <input type="checkbox" name="has_sizes" value="1" id="hasSizesCheck<?php echo isset($item['id']) ? $item['id'] : ''; ?>" 
                                    <?php 
                                    $has_sizes = false;
                                    $size_data = null;
                                    if (isset($item['size_options']) && !empty($item['size_options'])) {
                                        $size_data = json_decode($item['size_options'], true);
                                        $has_sizes = ($size_data && (isset($size_data['small']) || isset($size_data['large'])));
                                    }
                                    echo $has_sizes ? 'checked' : ''; 
                                    ?> 
                                    onchange="toggleSizeOptionsEdit(<?php echo isset($item['id']) ? $item['id'] : ''; ?>)">
                                Izmēru opcijas
                            </label>
                            <div id="sizeOptionsContainer<?php echo isset($item['id']) ? $item['id'] : ''; ?>" style="display: <?php echo $has_sizes ? 'block' : 'none'; ?>; margin-top: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                                <label style="display: block; margin-bottom: 6px; font-size: 0.9em;">
                                    Parastais (€):
                                    <input type="number" name="size_small_price" value="<?php echo $size_data && isset($size_data['small']['price']) ? number_format($size_data['small']['price'], 2, '.', '') : ''; ?>" step="0.01" min="0" style="width: 80px; margin-left: 4px; padding: 4px;">
                                </label>
                                <label style="display: block; font-size: 0.9em;">
                                    Lielais (€):
                                    <input type="number" name="size_large_price" value="<?php echo $size_data && isset($size_data['large']['price']) ? number_format($size_data['large']['price'], 2, '.', '') : ''; ?>" step="0.01" min="0" style="width: 80px; margin-left: 4px; padding: 4px;">
                                </label>
                            </div>
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
function toggleSizeOptions() {
    var checkbox = document.getElementById('hasSizesCheck');
    var container = document.getElementById('sizeOptionsContainer');
    container.style.display = checkbox.checked ? 'block' : 'none';
}
function toggleSizeOptionsEdit(itemId) {
    var checkbox = document.getElementById('hasSizesCheck' + itemId);
    var container = document.getElementById('sizeOptionsContainer' + itemId);
    if (container) {
        container.style.display = checkbox.checked ? 'block' : 'none';
    }
}

// Background preview functionality
document.addEventListener('DOMContentLoaded', function() {
    const colorPicker = document.getElementById('background_color');
    const colorText = document.getElementById('background_color_text');
    const backgroundRepeat = document.getElementById('background_repeat');
    const backgroundSize = document.getElementById('background_size');
    const backgroundPosition = document.getElementById('background_position');
    const backgroundImage = document.getElementById('background_image');
    const preview = document.getElementById('backgroundPreview');
    
    if (!preview) return;
    
    function updatePreview() {
        const color = colorPicker.value;
        const repeat = backgroundRepeat.value;
        const size = backgroundSize.value;
        const position = backgroundPosition.value;
        
        let bgImage = '';
        if (backgroundImage.files && backgroundImage.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.style.backgroundImage = 'url(' + e.target.result + ')';
                preview.style.backgroundColor = color;
                preview.style.backgroundRepeat = repeat;
                preview.style.backgroundSize = size;
                preview.style.backgroundPosition = position;
            };
            reader.readAsDataURL(backgroundImage.files[0]);
        } else {
            // Use existing image or no image
            const existingImage = preview.style.backgroundImage || '';
            preview.style.backgroundImage = existingImage;
            preview.style.backgroundColor = color;
            preview.style.backgroundRepeat = repeat;
            preview.style.backgroundSize = size;
            preview.style.backgroundPosition = position;
        }
    }
    
    // Sync color picker and text input
    if (colorPicker && colorText) {
        colorPicker.addEventListener('input', function() {
            colorText.value = this.value;
            updatePreview();
        });
        
        colorText.addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                colorPicker.value = this.value;
                updatePreview();
            }
        });
    }
    
    // Update preview on change
    if (backgroundRepeat) backgroundRepeat.addEventListener('change', updatePreview);
    if (backgroundSize) backgroundSize.addEventListener('change', updatePreview);
    if (backgroundPosition) backgroundPosition.addEventListener('change', updatePreview);
    if (backgroundImage) backgroundImage.addEventListener('change', updatePreview);
    
    // Initial preview update
    updatePreview();
});
</script>
</body>
</html>

<?php
// Close database connection at the very end
if ($conn) $conn->close();
?>