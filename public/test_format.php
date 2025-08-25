<?php
// Create a test file: test_format.php in your public folder
require_once __DIR__ . '/../includes/db.php';

// Test the format_customizations function
$test_data = json_encode([
    'special_requests' => 'No onions please',
    'sauces' => [
        [
            'instance' => 1,
            'sauce_id' => 5,
            'sauce_name' => 'Ketchup'
        ],
        [
            'instance' => 2,
            'sauce_id' => null,
            'sauce_name' => 'Bez mērces'
        ]
    ]
]);

echo "<h1>Testing format_customizations function</h1>";
echo "<h2>Input JSON:</h2>";
echo "<pre>" . htmlspecialchars($test_data) . "</pre>";

echo "<h2>Formatted Output:</h2>";
$result = format_customizations($test_data);
echo "<div style='border: 1px solid #ccc; padding: 10px;'>" . $result . "</div>";

echo "<h2>Is result empty?</h2>";
echo empty($result) ? "YES - EMPTY" : "NO - HAS CONTENT";

// Also test with empty data
echo "<h2>Testing with empty data:</h2>";
$empty_result = format_customizations('');
echo "Result: '" . $empty_result . "'";
echo empty($empty_result) ? " (EMPTY)" : " (HAS CONTENT)";
?>