<?php
// test_background.php
// Place this file in your /public folder to test background settings
// Access it at: http://yourdomain.com/test_background.php

require_once __DIR__ . '/../includes/db.php';

echo "<h1>Background Settings Test</h1>";
echo "<style>body { font-family: monospace; padding: 20px; background: #f5f5f5; }</style>";

// Test 1: Database Connection
echo "<h2>1. Database Connection</h2>";
$conn = db_connect();
if ($conn) {
    echo "✅ Database connected successfully<br>";
    echo "Database: " . $conn->get_server_info() . "<br><br>";
} else {
    echo "❌ Database connection failed<br><br>";
    die();
}

// Test 2: Check if table exists
echo "<h2>2. Check Table Existence</h2>";
$table_check = $conn->query("SHOW TABLES LIKE 'menu_background_settings'");
if ($table_check && $table_check->num_rows > 0) {
    echo "✅ Table 'menu_background_settings' exists<br><br>";
} else {
    echo "❌ Table 'menu_background_settings' does NOT exist<br>";
    echo "Creating table...<br>";
    
    $create_sql = "CREATE TABLE IF NOT EXISTS menu_background_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        background_color VARCHAR(20) DEFAULT '#f8f8f8',
        background_image VARCHAR(255) DEFAULT NULL,
        background_repeat VARCHAR(20) DEFAULT 'no-repeat',
        background_size VARCHAR(20) DEFAULT 'cover',
        background_position VARCHAR(50) DEFAULT 'center center',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_sql)) {
        echo "✅ Table created successfully<br><br>";
    } else {
        echo "❌ Failed to create table: " . $conn->error . "<br><br>";
    }
}

// Test 3: Show table structure
echo "<h2>3. Table Structure</h2>";
$columns = $conn->query("DESCRIBE menu_background_settings");
if ($columns) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    while ($row = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table><br><br>";
}

// Test 4: Current settings
echo "<h2>4. Current Settings in Database</h2>";
$result = $conn->query("SELECT * FROM menu_background_settings WHERE id = 1");
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
    echo "<pre>";
    print_r($settings);
    echo "</pre>";
} else {
    echo "❌ No settings found in database. Inserting defaults...<br>";
    $insert_sql = "INSERT INTO menu_background_settings (id, background_color, background_image, background_repeat, background_size, background_position) 
                  VALUES (1, '#f8f8f8', NULL, 'no-repeat', 'cover', 'center center')";
    if ($conn->query($insert_sql)) {
        echo "✅ Default settings inserted<br>";
    } else {
        echo "❌ Failed to insert: " . $conn->error . "<br>";
    }
}
echo "<br>";

// Test 5: Test get_menu_background_settings() function
echo "<h2>5. Test get_menu_background_settings() Function</h2>";
$bg_settings = get_menu_background_settings();
echo "<pre>";
print_r($bg_settings);
echo "</pre><br>";

// Test 6: Test update function
echo "<h2>6. Test Update Function</h2>";
echo "Attempting to update background color to #ff0000...<br>";
$test_settings = [
    'background_color' => '#ff0000',
    'background_image' => null,
    'background_repeat' => 'no-repeat',
    'background_size' => 'cover',
    'background_position' => 'center center'
];

if (update_menu_background_settings($test_settings)) {
    echo "✅ Update successful<br>";
    
    // Verify update
    $verify = $conn->query("SELECT background_color FROM menu_background_settings WHERE id = 1");
    if ($verify) {
        $row = $verify->fetch_assoc();
        echo "Verified color in DB: " . $row['background_color'] . "<br>";
        
        if ($row['background_color'] === '#ff0000') {
            echo "✅ Color update verified!<br>";
        } else {
            echo "❌ Color mismatch! Expected #ff0000, got " . $row['background_color'] . "<br>";
        }
    }
    
    // Reset to default
    echo "<br>Resetting to default (#f8f8f8)...<br>";
    reset_menu_background_to_default();
    echo "✅ Reset complete<br>";
} else {
    echo "❌ Update failed<br>";
}

echo "<br><h2>7. Test Complete</h2>";
echo "<p><a href='menu.php?table=1'>View Menu (Table 1)</a> | <a href='admin_menu.php'>Admin Panel</a></p>";

$conn->close();
?>