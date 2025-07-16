<?php
// clear_database.php - Script to clear all data from database tables
// WARNING: This will delete ALL data from the database!

require_once 'includes/db.php';

$conn = db_connect();

if (!$conn) {
    die('Database connection failed. Please check your database settings.');
}

echo "<h2>Clearing Database...</h2>";

// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Clear all tables in the correct order
$tables = [
    'order_items',
    'orders', 
    'menu_items',
    'categories'
];

$success = true;

foreach ($tables as $table) {
    $result = $conn->query("DELETE FROM $table");
    if ($result) {
        $affected = $conn->affected_rows;
        echo "<p>✓ Cleared table '$table': $affected rows deleted</p>";
    } else {
        echo "<p>✗ Error clearing table '$table': " . $conn->error . "</p>";
        $success = false;
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Reset auto-increment counters
foreach ($tables as $table) {
    $conn->query("ALTER TABLE $table AUTO_INCREMENT = 1");
    echo "<p>✓ Reset auto-increment for table '$table'</p>";
}

$conn->close();

if ($success) {
    echo "<h3 style='color: green;'>✓ Database cleared successfully!</h3>";
    echo "<p>All data has been removed from the database. You can now start fresh with testing.</p>";
    echo "<p><a href='public/admin_menu.php'>Go to Admin Panel</a> | <a href='public/menu.php'>Go to Customer Menu</a></p>";
} else {
    echo "<h3 style='color: red;'>✗ Some errors occurred while clearing the database.</h3>";
    echo "<p>Please check the error messages above.</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
h2 { color: #333; }
p { margin: 10px 0; padding: 8px; background: #fff; border-radius: 4px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style> 