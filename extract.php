<?php
/**
 * Vendor Extractor Script
 * Visit this URL after deployment to extract vendor.zip
 * DELETE THIS FILE AFTER USE for security!
 */

// Security key - change this!
$secret_key = 'citropak_extract_2024';

if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die('Unauthorized. Use: ?key=YOUR_SECRET_KEY');
}

$zipFile = __DIR__ . '/vendor.zip';
$extractTo = __DIR__ . '/';

if (!file_exists($zipFile)) {
    die('Error: vendor.zip not found!');
}

$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    echo "Extracting vendor.zip...<br>";
    $zip->extractTo($extractTo);
    $zip->close();
    
    // Delete the zip file after extraction
    unlink($zipFile);
    
    echo "✅ Success! Vendor extracted and zip deleted.<br>";
    echo "<strong>⚠️ IMPORTANT: Delete this extract.php file now for security!</strong>";
} else {
    die('Error: Could not open vendor.zip');
}
