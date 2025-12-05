<?php
// Quick check for upload issues
echo "<h2>Upload Configuration Check</h2>";

echo "<h3>PHP Upload Settings:</h3>";
echo "<ul>";
echo "<li>upload_max_filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "<li>post_max_size: " . ini_get('post_max_size') . "</li>";
echo "<li>max_file_uploads: " . ini_get('max_file_uploads') . "</li>";
echo "<li>file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "</li>";
echo "</ul>";

echo "<h3>Directory Check:</h3>";
$bg_dir = __DIR__ . '/assets/images/backgrounds/';
echo "<p>Path: " . htmlspecialchars($bg_dir) . "</p>";
if (is_dir($bg_dir)) {
    echo "<p style='color: green;'>✓ Directory exists</p>";
    if (is_writable($bg_dir)) {
        echo "<p style='color: green;'>✓ Directory is writable</p>";
    } else {
        echo "<p style='color: red;'>✗ Directory is NOT writable</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Directory does NOT exist</p>";
}

echo "<h3>Test Form:</h3>";
?>
<form method="post" enctype="multipart/form-data" action="check_upload.php">
    <input type="file" name="test_file" accept="image/*">
    <button type="submit" name="test_upload">Test Upload</button>
</form>

<?php
if (isset($_POST['test_upload'])) {
    echo "<h3>Upload Test Results:</h3>";
    echo "<pre>";
    echo "POST data:\n";
    print_r($_POST);
    echo "\nFILES data:\n";
    print_r($_FILES);
    echo "</pre>";
    
    if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        echo "<p style='color: green;'>✓ File upload detected successfully!</p>";
    } elseif (isset($_FILES['test_file'])) {
        echo "<p style='color: red;'>✗ Upload error: " . $_FILES['test_file']['error'] . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No file data received</p>";
    }
}
?>

