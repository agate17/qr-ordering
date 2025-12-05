<?php
require_once __DIR__ . '/../includes/db.php';

// Test updating background with one of the existing images
$test_image = 'assets/images/backgrounds/bg_6932e58792e8a3.55272574_logo.jpg';

echo "<h2>Test Background Update</h2>";

$settings = [
    'background_color' => '#f0f0f0',
    'background_image' => $test_image,
    'background_repeat' => 'no-repeat',
    'background_size' => 'cover',
    'background_position' => 'center center'
];

echo "<p>Attempting to update with image: " . htmlspecialchars($test_image) . "</p>";

if (update_menu_background_settings($settings)) {
    echo "<p style='color: green;'>✓ Update successful!</p>";
    
    // Verify it was saved
    $saved = get_menu_background_settings();
    echo "<h3>Saved Settings:</h3>";
    echo "<pre>";
    print_r($saved);
    echo "</pre>";
    
    if ($saved['background_image'] === $test_image) {
        echo "<p style='color: green;'>✓ Image path matches!</p>";
    } else {
        echo "<p style='color: red;'>✗ Image path mismatch!</p>";
        echo "<p>Expected: " . htmlspecialchars($test_image) . "</p>";
        echo "<p>Got: " . htmlspecialchars($saved['background_image'] ?? 'NULL') . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Update failed!</p>";
}

// Check database directly
$conn = db_connect();
if ($conn) {
    $result = $conn->query("SELECT * FROM menu_background_settings WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<h3>Direct Database Query:</h3>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
    $conn->close();
}
?>

