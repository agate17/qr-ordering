<?php
require_once __DIR__ . '/../includes/db.php';

// Debug background settings
echo "<h2>Background Settings Debug</h2>";

// Check if table exists
$conn = db_connect();
if ($conn) {
    $table_check = $conn->query("SHOW TABLES LIKE 'menu_background_settings'");
    if ($table_check && $table_check->num_rows > 0) {
        echo "<p style='color: green;'>✓ Table 'menu_background_settings' exists</p>";
        
        // Get all data from table
        $result = $conn->query("SELECT * FROM menu_background_settings WHERE id = 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo "<h3>Database Values:</h3>";
            echo "<pre>";
            print_r($row);
            echo "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠ No row found with id = 1</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Table 'menu_background_settings' does NOT exist</p>";
    }
    
    $conn->close();
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
}

// Test function
echo "<h3>Function get_menu_background_settings() returns:</h3>";
$settings = get_menu_background_settings();
echo "<pre>";
print_r($settings);
echo "</pre>";

// Check if image file exists
if (!empty($settings['background_image'])) {
    $image_path = __DIR__ . '/' . $settings['background_image'];
    echo "<h3>Image File Check:</h3>";
    echo "<p>Stored path: " . htmlspecialchars($settings['background_image']) . "</p>";
    echo "<p>Full path: " . htmlspecialchars($image_path) . "</p>";
    if (file_exists($image_path)) {
        echo "<p style='color: green;'>✓ Image file exists</p>";
        echo "<p>File size: " . filesize($image_path) . " bytes</p>";
        echo "<img src='" . htmlspecialchars($settings['background_image']) . "' style='max-width: 300px; border: 1px solid #ccc; margin-top: 10px;' alt='Background image'>";
    } else {
        echo "<p style='color: red;'>✗ Image file does NOT exist at that path</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ No background image set</p>";
}

// Check backgrounds directory
$bg_dir = __DIR__ . '/assets/images/backgrounds/';
echo "<h3>Backgrounds Directory:</h3>";
echo "<p>Path: " . htmlspecialchars($bg_dir) . "</p>";
if (is_dir($bg_dir)) {
    echo "<p style='color: green;'>✓ Directory exists</p>";
    $files = scandir($bg_dir);
    $image_files = array_filter($files, function($file) {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    });
    if (count($image_files) > 0) {
        echo "<p>Files in directory:</p><ul>";
        foreach ($image_files as $file) {
            echo "<li>" . htmlspecialchars($file) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ No image files found in directory</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Directory does NOT exist</p>";
}
?>

