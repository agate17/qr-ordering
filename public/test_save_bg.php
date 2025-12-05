<?php
require_once __DIR__ . '/../includes/db.php';

echo "<h2>Test Background Save</h2>";

// Test 1: Change color only
echo "<h3>Test 1: Change color to red</h3>";
$test_settings = [
    'background_color' => '#ff0000',
    'background_repeat' => 'no-repeat',
    'background_size' => 'cover',
    'background_position' => 'center center',
    'background_image' => null
];

echo "<p>Attempting to save: " . print_r($test_settings, true) . "</p>";

if (update_menu_background_settings($test_settings)) {
    echo "<p style='color: green;'>✓ Save function returned true</p>";
    
    // Verify
    $saved = get_menu_background_settings();
    echo "<p>Retrieved from DB:</p><pre>" . print_r($saved, true) . "</pre>";
    
    if ($saved['background_color'] === '#ff0000') {
        echo "<p style='color: green;'>✓ Color saved correctly!</p>";
    } else {
        echo "<p style='color: red;'>✗ Color mismatch! Expected #ff0000, got: " . htmlspecialchars($saved['background_color']) . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Save function returned false</p>";
}

// Test 2: Change color and add image
echo "<hr><h3>Test 2: Change color to blue and set image</h3>";
$test_settings2 = [
    'background_color' => '#0000ff',
    'background_repeat' => 'repeat',
    'background_size' => 'contain',
    'background_position' => 'top left',
    'background_image' => 'assets/images/backgrounds/bg_6932e58792e8a3.55272574_logo.jpg'
];

echo "<p>Attempting to save: " . print_r($test_settings2, true) . "</p>";

if (update_menu_background_settings($test_settings2)) {
    echo "<p style='color: green;'>✓ Save function returned true</p>";
    
    // Verify
    $saved2 = get_menu_background_settings();
    echo "<p>Retrieved from DB:</p><pre>" . print_r($saved2, true) . "</pre>";
    
    if ($saved2['background_color'] === '#0000ff' && $saved2['background_image'] === $test_settings2['background_image']) {
        echo "<p style='color: green;'>✓ All settings saved correctly!</p>";
    } else {
        echo "<p style='color: red;'>✗ Settings mismatch!</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Save function returned false</p>";
}

// Check database directly
echo "<hr><h3>Direct Database Check</h3>";
$conn = db_connect();
if ($conn) {
    $result = $conn->query("SELECT * FROM menu_background_settings WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<p>Direct query result:</p><pre>" . print_r($row, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>✗ No row found with id = 1</p>";
    }
    
    // Check table structure
    echo "<h4>Table Structure:</h4>";
    $structure = $conn->query("DESCRIBE menu_background_settings");
    if ($structure) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($col = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    $conn->close();
}
?>

