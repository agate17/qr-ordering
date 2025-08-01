<?php
require_once __DIR__ . '/../includes/db.php';

// generate_qr.php?table=1
$table = isset($_GET['table']) ? intval($_GET['table']) : 0;
if ($table < 1 || $table > get_table_count()) {
    http_response_code(400);
    echo 'Invalid table number.';
    exit;
}
$url = "http://192.168.8.135/qr-ordering/qr-ordering/public/menu.php?table=$table";
// Try to use endroid/qr-code if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    try {
        $qr = new Endroid\QrCode\QrCode($url);
        $qr->setSize(220);
        header('Content-Type: image/png');
        echo $qr->writeString();
        exit;
    } catch (Exception $e) {}
}
// Fallback: QRServer API
header('Content-Type: image/png');
$api = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($url);
readfile($api); 