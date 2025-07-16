<?php
// check_menu_items.php - Script to check what's in the database
require_once 'includes/db.php';

$conn = db_connect();

if (!$conn) {
    die('Database connection failed.');
}

echo "<h2>Current Menu Items in Database:</h2>";

$result = $conn->query("SELECT * FROM menu_items ORDER BY id");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Category</th><th>Image Path</th><th>Available</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "<td>€" . number_format($row['price'], 2) . "</td>";
        echo "<td>" . ($row['category_id'] ? $row['category_id'] : 'None') . "</td>";
        echo "<td>" . ($row['image_path'] ? htmlspecialchars($row['image_path']) : 'No image') . "</td>";
        echo "<td>" . ($row['available'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Total items: " . $result->num_rows . "</strong></p>";
} else {
    echo "<p>No menu items found in database.</p>";
}

// Also check categories
echo "<h2>Current Categories:</h2>";
$cat_result = $conn->query("SELECT * FROM categories ORDER BY id");
if ($cat_result && $cat_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th></tr>";
    while ($cat = $cat_result->fetch_assoc()) {
        echo "<tr><td>" . $cat['id'] . "</td><td>" . htmlspecialchars($cat['name']) . "</td></tr>";
    }
    echo "</table>";
    echo "<p><strong>Total categories: " . $cat_result->num_rows . "</strong></p>";
} else {
    echo "<p>No categories found.</p>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 20px 0; }
th, td { padding: 8px; text-align: left; }
th { background: #f0f0f0; }
</style> 